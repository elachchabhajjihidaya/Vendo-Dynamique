import {createHash,randomBytes} from 'node:crypto';
import {db} from './db.js';
import {fail} from './http.js';

const COOKIE='vendo_session';
const SESSION_DAYS=7;
const hash=value=>createHash('sha256').update(value).digest('hex');

function readCookie(req,name){
  const cookies=String(req.headers.cookie||'').split(';');
  for(const item of cookies){
    const [key,...value]=item.trim().split('=');
    if(key===name)return value.join('=');
  }
  return '';
}

function writeCookie(req,res,value,maxAge){
  const secure=req.headers['x-forwarded-proto']==='https'?' Secure':'';
  res.setHeader('Set-Cookie',COOKIE+'='+value+'; Path=/; HttpOnly; SameSite=Lax; Max-Age='+maxAge+secure);
}

export async function createSession(user,req,res){
  const session=randomBytes(32).toString('base64url');
  const expiresAt=new Date(Date.now()+SESSION_DAYS*24*60*60*1000)
    .toISOString().slice(0,19).replace('T',' ');
  await db().execute(
    'INSERT INTO sessions (token_hash,user_id,expires_at) VALUES (?,?,?)',
    [hash(session),Number(user.id),expiresAt]
  );
  writeCookie(req,res,session,SESSION_DAYS*24*60*60);
}

export async function current(req,role){
  const session=readCookie(req,COOKIE);
  if(!session)fail('Session absente. Connectez-vous.',401);
  const [rows]=await db().execute(
    'SELECT s.user_id AS id,u.role FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires_at>UTC_TIMESTAMP() LIMIT 1',
    [hash(session)]
  );
  const user=rows[0];
  if(!user)fail('Session expiree. Connectez-vous a nouveau.',401);
  if(role&&user.role!==role)fail('Acces interdit',403);
  return {id:Number(user.id),role:user.role};
}

export async function destroySession(req,res){
  const session=readCookie(req,COOKIE);
  if(session)await db().execute('DELETE FROM sessions WHERE token_hash=?',[hash(session)]);
  writeCookie(req,res,'',0);
}
