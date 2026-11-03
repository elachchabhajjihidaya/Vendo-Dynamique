import {route,method} from './_lib/http.js';

export default route(async(req,res)=>{
  if(method(req,res,['POST']))return;
  return res.status(200).json({message:'Deconnexion reussie'});
});
