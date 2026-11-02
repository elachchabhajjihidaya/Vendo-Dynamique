import mysql from 'mysql2/promise';
import fs from 'node:fs';
import path from 'node:path';

let pool;
export function db(){
  if(pool)return pool;
  const {DB_HOST,DB_PORT,DB_NAME,DB_USER,DB_PASSWORD}=process.env;
  if(!DB_HOST||!DB_NAME||!DB_USER)throw new Error('Configuration DB_* incomplete.');
  const caPath=path.join(process.cwd(),'certs','ca.pem');
  const ssl=fs.existsSync(caPath)?{ca:fs.readFileSync(caPath,'utf8'),rejectUnauthorized:true}:undefined;
  pool=mysql.createPool({host:DB_HOST,port:Number(DB_PORT||3306),database:DB_NAME,user:DB_USER,password:DB_PASSWORD,charset:'utf8mb4',waitForConnections:true,connectionLimit:5,ssl});
  return pool;
}