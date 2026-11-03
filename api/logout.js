import {route,method} from './_lib/http.js';
import {destroySession} from './_lib/auth.js';

export default route(async(req,res)=>{
  if(method(req,res,['POST']))return;
  await destroySession(req,res);
  return res.status(200).json({message:'Deconnexion reussie'});
});
