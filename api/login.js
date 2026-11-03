import bcrypt from 'bcryptjs';
import {db} from './_lib/db.js';
import {route,method,bodyJson,fail} from './_lib/http.js';
import {token} from './_lib/auth.js';
export default route(async(req,res)=>{if(method(req,res,['POST']))return;const d=await bodyJson(req),email=String(d.email||'').trim().toLowerCase(),password=String(d.password||'');if(!email||!password)fail('Email et mot de passe obligatoires');const [rows]=await db().execute('SELECT id,nom,email,mot_de_passe AS password,role FROM users WHERE email=? LIMIT 1',[email]);const u=rows[0];let ok=false;if(u){const hash=String(u.password||'').replace(/^\$2y\$/,'$2b$');ok=await bcrypt.compare(password,hash)}if(!ok)fail('Identifiants incorrects',400);res.status(200).json({token:token(u),id:Number(u.id),role:u.role,nom:u.nom,email:u.email})});
