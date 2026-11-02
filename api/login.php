<?php
require_once __DIR__ . '/../config/helpers.php';
cors();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['message' => 'Methode non autorisee'], 405);
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$email = strtolower(trim($data['email'] ?? ''));
$password = $data['password'] ?? '';
if (!$email || !$password) jsonOut(['message' => 'Email et mot de passe obligatoires'], 400);
$pdo = getDatabase();
$stmt = $pdo->prepare('SELECT id, nom, email, password, role, photo_url FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password'])) jsonOut(['message' => 'Identifiants incorrects'], 400);
jsonOut(['token'=>createToken($user),'id'=>(int)$user['id'],'role'=>$user['role'],'nom'=>$user['nom'],'email'=>$user['email'],'photo_url'=>$user['photo_url']]);
