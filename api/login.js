import bcrypt from 'bcryptjs';
import {db} from './_lib/db.js';
import {route,method,bodyJson,fail} from './_lib/http.js';

export default route(async(req,res)=>{
  if(method(req,res,['POST']))return;
  const d=await bodyJson(req);
  const identifier=String(d.email||'').trim().toLowerCase();
  const password=String(d.password||'');
  const role=d.role||'ANNONCEUR';
  if(!identifier||!password)fail('Identifiant et mot de passe obligatoires');

  const database=db();
  const [rows]=await database.execute(
    'SELECT id,nom,email,mot_de_passe AS password,role FROM users WHERE email=? OR (LOWER(nom)=? AND role=?) LIMIT 1',
    [identifier,identifier,role]
  );
  let user=rows[0];

  // The requested demo credentials are always admin / admin. Reuse the
  // existing ADMIN account when present, or create it on first use.
  if(!user&&identifier==='admin'&&password==='admin'&&role==='ADMIN'){
    const [admins]=await database.execute("SELECT id FROM users WHERE role='ADMIN' LIMIT 1");
    if(admins.length){
      const [adminRows]=await database.execute(
        "SELECT id,nom,email,mot_de_passe AS password,role FROM users WHERE id=? LIMIT 1",
        [admins[0].id]
      );
      user=adminRows[0];
    }else{
      const hash=await bcrypt.hash('admin',12);
      try{
        const [created]=await database.execute(
          "INSERT INTO users (nom,email,telephone,mot_de_passe,role) VALUES ('admin','admin@vendo.local',NULL,?,'ADMIN')",
          [hash]
        );
        user={id:Number(created.insertId),nom:'admin',email:'admin@vendo.local',password:hash,role:'ADMIN'};
      }catch(e){
        if(e.code!=='ER_DUP_ENTRY')throw e;
      }
    }
  }

  let valid=false;
  if(user){
    const hash=String(user.password||'').replace(/^\$2y\$/,'$2b$');
    valid=(role==='ADMIN'&&identifier==='admin'&&password==='admin')||await bcrypt.compare(password,hash);
  }
  if(!valid)fail('Identifiants incorrects',400);
  if(user.role!==role)fail('Ce compte ne correspond pas à l’espace choisi.',400);
  res.status(200).json({id:Number(user.id),role:user.role,nom:user.nom,email:user.email});
});
