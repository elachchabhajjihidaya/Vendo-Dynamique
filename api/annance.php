<?php
require_once __DIR__ . '/../config/helpers.php';
cors();
$pdo = getDatabase();

$base = "SELECT a.*, v.nom AS ville_nom, c.nom AS categorie_nom
         FROM annonces a
         LEFT JOIN villes v ON v.id = a.ville_id
         LEFT JOIN categories c ON c.id = a.categorie_id
         WHERE a.statut = 'PUBLIEE'";

// Détail: annonces.php?id=5
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare($base . ' AND a.id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $a = $stmt->fetch();
    if (!$a) jsonOut(['message' => 'Introuvable'], 404);
    jsonOut($a);
}

// Listes pour les filtres: annonces.php?what=villes / categories
if (($_GET['what'] ?? '') === 'villes') {
    jsonOut($pdo->query('SELECT * FROM villes ORDER BY nom')->fetchAll());
}
if (($_GET['what'] ?? '') === 'categories') {
    jsonOut($pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll());
}

// Liste + filtres: ?ville_id=3&categorie_id=2&prix_min=100&prix_max=500&q=iphone
$sql = $base;
$params = [];
if (!empty($_GET['ville_id']))     { $sql .= ' AND a.ville_id = ?';     $params[] = (int) $_GET['ville_id']; }
if (!empty($_GET['categorie_id'])) { $sql .= ' AND a.categorie_id = ?'; $params[] = (int) $_GET['categorie_id']; }
if (is_numeric($_GET['prix_min'] ?? null)) { $sql .= ' AND a.prix >= ?'; $params[] = $_GET['prix_min']; }
if (is_numeric($_GET['prix_max'] ?? null)) { $sql .= ' AND a.prix <= ?'; $params[] = $_GET['prix_max']; }
if (!empty($_GET['q'])) { $sql .= ' AND a.titre LIKE ?'; $params[] = '%' . $_GET['q'] . '%'; }

$stmt = $pdo->prepare($sql . ' ORDER BY a.created_at DESC');
$stmt->execute($params);
jsonOut($stmt->fetchAll());