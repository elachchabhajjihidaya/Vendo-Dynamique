import {db} from './_lib/db.js';
import {route,method,query} from './_lib/http.js';

export default route(async(req,res)=>{
  if(method(req,res,['GET']))return;
  const p=query(req);
  if(p.get('what')==='villes'){
    const [r]=await db().query('SELECT * FROM villes ORDER BY nom');
    return res.status(200).json(r);
  }
  if(p.get('what')==='categories'){
    const [r]=await db().query('SELECT * FROM categories ORDER BY nom');
    return res.status(200).json(r);
  }
  const id=p.get('id');
  let sql="SELECT a.*,a.image_url AS image,v.nom AS ville_nom,c.nom AS categorie_nom,u.nom AS annonceur_nom FROM annonces a LEFT JOIN villes v ON v.id=a.ville_id LEFT JOIN categories c ON c.id=a.categorie_id LEFT JOIN users u ON u.id=a.user_id WHERE a.statut='VALIDEE'";
  const args=[];
  if(id){sql+=' AND a.id=?';args.push(Number(id))}
  if(p.get('ville_id')){sql+=' AND a.ville_id=?';args.push(Number(p.get('ville_id')))}
  if(p.get('categorie_id')){sql+=' AND a.categorie_id=?';args.push(Number(p.get('categorie_id')))}
  if(p.has('prix_min')){sql+=' AND a.prix>=?';args.push(Number(p.get('prix_min')))}
  if(p.has('prix_max')){sql+=' AND a.prix<=?';args.push(Number(p.get('prix_max')))}
  if(p.get('q')){sql+=' AND a.titre LIKE ?';args.push('%'+p.get('q')+'%')}
  sql+=' ORDER BY a.created_at DESC';
  const [rows]=await db().execute(sql,args);
  if(id&&!rows.length)return res.status(404).json({message:'Introuvable'});
  const normalized=rows.map(a=>({...a,statut:a.statut==='VALIDEE'?'PUBLIEE':a.statut}));
  res.status(200).json(id?normalized[0]:normalized);
});
