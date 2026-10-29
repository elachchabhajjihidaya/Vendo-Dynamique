<?php
require_once __DIR__ . '/../config/helpers.php';
cors();

$user = currentUser('ANNONCEUR');
$pdo = getDatabase();

// LIRE mes annonces
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT a.*, v.nom AS ville_nom, c.nom AS categorie_nom
                           FROM annonces a
                           LEFT JOIN villes v ON v.id = a.ville_id
                           LEFT JOIN categories c ON c.id = a.categorie_id
                           WHERE a.user_id = ? ORDER BY a.created_at DESC');
    $stmt->execute([$user['id']]);
    jsonOut($stmt->fetchAll());
}

$action = $_POST['action'] ?? 'creer';

// CRÉER
if ($action === 'creer') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = $_POST['prix'] ?? '';
    $ville_id = (int) ($_POST['ville_id'] ?? 0);
    $categorie_id = (int) ($_POST['categorie_id'] ?? 0);

    if (!$titre || !$description || !is_numeric($prix) || !$ville_id || !$categorie_id || empty($_FILES['image'])) {
        jsonOut(['message' => 'Champs obligatoires manquants'], 400);
    }
    $url = uploadImage($_FILES['image']);

    $pdo->prepare('INSERT INTO annonces (titre, description, prix, ville_id, categorie_id, image, user_id)
                   VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$titre, $description, $prix, $ville_id, $categorie_id, $url, $user['id']]);
    jsonOut(['message' => 'Annonce envoyée, en attente de validation', 'id' => $pdo->lastInsertId()], 201);
}

// Modifier / supprimer: l'annonce khassha tkon dyalo
$id = (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM annonces WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$a = $stmt->fetch();
if (!$a) jsonOut(['message' => 'Annonce introuvable ou interdite'], 403);

if ($action === 'modifier') {
    $image = $a['image'];
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = uploadImage($_FILES['image']);
    }
    $pdo->prepare("UPDATE annonces
        SET titre=?, description=?, prix=?, ville_id=?, categorie_id=?, image=?, statut='EN_ATTENTE'
        WHERE id=?")
        ->execute([
            trim($_POST['titre'] ?? $a['titre']),
            trim($_POST['description'] ?? $a['description']),
            $_POST['prix'] ?? $a['prix'],
            ((int) ($_POST['ville_id'] ?? 0)) ?: $a['ville_id'],
            ((int) ($_POST['categorie_id'] ?? 0)) ?: $a['categorie_id'],
            $image,
            $id,
        ]);
    jsonOut(['message' => 'Annonce modifiée, en attente de validation']);
}

if ($action === 'supprimer') {
    $pdo->prepare('DELETE FROM annonces WHERE id = ?')->execute([$id]);
    jsonOut(['message' => 'Annonce supprimée']);
}

jsonOut(['message' => 'Action inconnue'], 400);