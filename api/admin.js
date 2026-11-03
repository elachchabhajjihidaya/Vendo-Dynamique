import bcrypt from 'bcryptjs';
import {db} from './_lib/db.js';
import {route,method,bodyJson,query,fail} from './_lib/http.js';
import {current} from './_lib/auth.js';

export default route(async(req,res)=>{
  const admin=current(req,'ADMIN'),pdo=db();
  if(req.method==='GET'){
    const what=query(req).get('what');
    if(what==='villes'||what==='categories'){
      const [rows]=await pdo.query('SELECT * FROM '+what+' ORDER BY nom');
      return res.status(200).json(rows);
    }
    if(what==='annonceurs'){
      const [rows]=await pdo.query("SELECT id,nom,email,telephone,created_at FROM users WHERE role='ANNONCEUR' ORDER BY id DESC");
      return res.status(200).json(rows);
    }
    let sql="SELECT a.*,a.image_url AS image,u.nom AS annonceur_nom,u.email AS annonceur_email,v.nom AS ville_nom,c.nom AS categorie_nom FROM annonces a JOIN users u ON u.id=a.user_id LEFT JOIN villes v ON v.id=a.ville_id LEFT JOIN categories c ON c.id=a.categorie_id";
    const args=[],status=query(req).get('statut');
    if(status){sql+=' WHERE a.statut=?';args.push(status==='PUBLIEE'?'VALIDEE':status)}
    sql+=' ORDER BY a.created_at DESC';
    const [rows]=await pdo.execute(sql,args);
    return res.status(200).json(rows.map(a=>({...a,statut:a.statut==='VALIDEE'?'PUBLIEE':a.statut})));
  }
  if(method(req,res,['POST']))return;
  const d=await bodyJson(req),action=d.action,id=Number(d.id||0);
  switch(action){
    case 'publier':
      await pdo.execute("UPDATE annonces SET statut='VALIDEE',validee_par=?,date_validation=CURRENT_TIMESTAMP WHERE id=?",[admin.id,id]);
      return res.status(200).json({message:'Annonce publiee'});
    case 'refuser':
      await pdo.execute("UPDATE annonces SET statut='REFUSEE' WHERE id=?",[id]);
      return res.status(200).json({message:'Annonce refusee'});
    case 'supprimer_annonce':
      await pdo.execute('DELETE FROM annonces WHERE id=?',[id]);
      return res.status(200).json({message:'Annonce supprimee'});
    case 'modifier_annonce':{
      const [rows]=await pdo.execute('SELECT * FROM annonces WHERE id=?',[id]),a=rows[0];
      if(!a)fail('Annonce introuvable',404);
      let status=['EN_ATTENTE','PUBLIEE','VALIDEE','REFUSEE'].includes(d.statut)?d.statut:a.statut;
      if(status==='PUBLIEE')status='VALIDEE';
      await pdo.execute('UPDATE annonces SET titre=?,description=?,prix=?,ville_id=?,categorie_id=?,statut=? WHERE id=?',[d.titre??a.titre,d.description??a.description,d.prix??a.prix,d.ville_id??a.ville_id,d.categorie_id??a.categorie_id,status,id]);
      return res.status(200).json({message:'Annonce modifiee'});
    }
    case 'ajouter_annonceur':{
      const nom=String(d.nom||'').trim(),email=String(d.email||'').trim().toLowerCase(),telephone=String(d.telephone||''),password=String(d.password||'');
      if(!nom||!/^\S+@\S+\.\S+$/.test(email)||password.length<8)fail('Donnees invalides',400);
      const hash=await bcrypt.hash(password,12);
      const [r]=await pdo.execute("INSERT INTO users (nom,email,telephone,mot_de_passe,role) VALUES (?,?,?,?,'ANNONCEUR')",[nom,email,telephone,hash]);
      return res.status(201).json({message:'Annonceur ajoute',id:r.insertId});
    }
    case 'supprimer_annonceur':{
      const conn=await pdo.getConnection();
      try{
        await conn.beginTransaction();
        await conn.execute('DELETE FROM annonces WHERE user_id=?',[id]);
        await conn.execute("DELETE FROM users WHERE id=? AND role='ANNONCEUR'",[id]);
        await conn.commit();
      }catch(e){await conn.rollback();throw e}
      finally{conn.release()}
      return res.status(200).json({message:'Annonceur et annonces supprimes'});
    }
    case 'ajouter_ville':
    case 'ajouter_categorie':{
      const table=action==='ajouter_ville'?'villes':'categories',nom=String(d.nom||'').trim();
      if(!nom)fail('Nom obligatoire');
      const [r]=await pdo.execute('INSERT INTO '+table+' (nom) VALUES (?)',[nom]);
      return res.status(201).json({message:'Ajoute',id:r.insertId});
    }
    case 'modifier_ville':
    case 'modifier_categorie':{
      const table=action==='modifier_ville'?'villes':'categories',nom=String(d.nom||'').trim();
      if(!nom)fail('Nom obligatoire');
      await pdo.execute('UPDATE '+table+' SET nom=? WHERE id=?',[nom,id]);
      return res.status(200).json({message:'Modifie'});
    }
    case 'supprimer_ville':
    case 'supprimer_categorie':{
      const table=action==='supprimer_ville'?'villes':'categories';
      await pdo.execute('DELETE FROM '+table+' WHERE id=?',[id]);
      return res.status(200).json({message:'Supprime'});
    }
    default:fail('Action inconnue');
  }
});
