import {fail} from './http.js';

// Lightweight identity hints for a demo without JWT or server-side sessions.
// These headers can be forged by clients and are not suitable for production.
export function current(req,role){
  const id=Number(req.headers['x-user-id']||0);
  const userRole=String(req.headers['x-user-role']||'');
  if(!id||!userRole)fail('Connectez-vous pour continuer.',401);
  if(role&&userRole!==role)fail('Acces interdit',403);
  return {id,role:userRole};
}
