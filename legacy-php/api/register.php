<?php
require_once __DIR__ . '/../config/helpers.php';
cors();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['message' => 'Methode non autorisee'], 405);
$nom = trim($_POST['nom'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$telephone = trim($_POST['telephone'] ?? '');
$password = $_POST['password'] ?? '';
if (!$nom || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) jsonOut(['message' => 'Nom, email valide et mot de passe (8 caracteres minimum) obligatoires'], 400);
$photo = null;
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) $photo = uploadImage($_FILES['photo']);
$pdo = getDatabase();
try {
    $stmt = $pdo->prepare("INSERT INTO users (nom,email,telephone,password,role,photo_url) VALUES (?,?,?,?,'ANNONCEUR',?)");
    $stmt->execute([$nom,$email,$telephone,password_hash($password,PASSWORD_DEFAULT),$photo]);
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') jsonOut(['message' => 'Cette adresse email est deja utilisee'], 409);
    throw $e;
}
$id = (int)$pdo->lastInsertId();
$user = ['id'=>$id,'nom'=>$nom,'email'=>$email,'role'=>'ANNONCEUR','photo_url'=>$photo];
jsonOut(['token'=>createToken($user),'id'=>$id,'email'=>$email,'telephone'=>$telephone,'role'=>$user['role'],'nom'=>$nom,'photo_url'=>$photo],201);
