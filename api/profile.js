import {db} from './_lib/db.js';
import {route,method,fail} from './_lib/http.js';
import {current} from './_lib/auth.js';
export default route(async(req,res)=>{if(method(req,res,['GET']))return;const u=await current(req,'ANNONCEUR');const [rows]=await db().execute('SELECT id,nom,email,telephone FROM users WHERE id=?',[u.id]);if(!rows.length)fail('Profil introuvable',404);res.status(200).json(rows[0])});
