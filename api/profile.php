<?php
require_once __DIR__ . '/../config/helpers.php';
cors();
$user=currentUser('ANNONCEUR');
$pdo=getDatabase();
if ($_SERVER['REQUEST_METHOD']==='GET') {
    $stmt=$pdo->prepare('SELECT id,nom,email,photo_url FROM users WHERE id=?');
    $stmt->execute([(int)$user['id']]);
    $profile=$stmt->fetch();
    if (!$profile) jsonOut(['message'=>'Profil introuvable'],404);
    jsonOut($profile);
}
jsonOut(['message'=>'Methode non autorisee'],405);
