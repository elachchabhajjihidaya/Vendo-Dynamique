<?php
require_once __DIR__ . '/../config/helpers.php';
cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['message' => 'Méthode non autorisée'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    jsonOut(['message' => 'Email et mot de passe obligatoires'], 400);
}

$pdo = getDatabase();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonOut(['message' => 'Identifiants incorrects'], 400);
}

jsonOut([
    'token' => createToken($user),
    'role'  => $user['role'],
    'nom'   => $user['nom'],
]);