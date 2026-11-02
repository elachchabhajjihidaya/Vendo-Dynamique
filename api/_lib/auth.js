import jwt from 'jsonwebtoken';
import {fail} from './http.js';
export function token(user){const secret=process.env.JWT_SECRET;if(!secret)throw new Error('JWT_SECRET non configure.');return jwt.sign({id:Number(user.id),role:user.role},secret,{expiresIn:'7d'})}
export function current(req,role){const value=req.headers.authorization||'';const m=value.match(/^Bearer\s+(.+)$/i);if(!m)fail('Non autorise',401);let data;try{data=jwt.verify(m[1],process.env.JWT_SECRET)}catch{fail('Token invalide',401)}if(role&&data.role!==role)fail('Acces interdit',403);return data}