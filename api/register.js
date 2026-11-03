import bcrypt from 'bcryptjs';
import {db} from './_lib/db.js';
import {route,method,multipart,fail} from './_lib/http.js';
export const config={api:{bodyParser:false}};
export default route(async(req,res)=>{if(method(req,res,['POST']))return;const {fields}=await multipart(req),nom=String(fields.nom||'').trim(),email=String(fields.email||'').trim().toLowerCase(),telephone=String(fields.telephone||'').trim(),password=String(fields.password||'');if(!nom||!/^\S+@\S+\.\S+$/.test(email)||password.length<8)fail('Nom, email valide et mot de passe (8 caracteres minimum) obligatoires');const hash=await bcrypt.hash(password,12);let result;try{[result]=await db().execute("INSERT INTO users (nom,email,telephone,mot_de_passe,role) VALUES (?,?,?,?,'ANNONCEUR')",[nom,email,telephone,hash])}catch(e){if(e.code==='ER_DUP_ENTRY')fail('Cette adresse email est deja utilisee',409);throw e}const u={id:Number(result.insertId),nom,email,role:'ANNONCEUR'};res.status(201).json({...u,telephone})});
