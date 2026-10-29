<?php
require_once __DIR__ . '/../config/helpers.php';
cors();

currentUser('ADMIN');
$pdo = getDatabase();

function getInput(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($type, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    return $_POST;
}

/* ============ LECTURE (GET) ============ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $what = $_GET['what'] ?? 'annonces';

    if ($what === 'annonceurs') {
        jsonOut($pdo->query("SELECT id, nom, email, created_at FROM users WHERE role='ANNONCEUR' ORDER BY id DESC")->fetchAll());
    }
    if ($what === 'villes') {
        jsonOut($pdo->query('SELECT * FROM villes ORDER BY nom')->fetchAll());
    }
    if ($what === 'categories') {
        jsonOut($pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll());
    }

    $sql = 'SELECT a.*, u.nom AS annonceur_nom, u.email AS annonceur_email,
                   v.nom AS ville_nom, c.nom AS categorie_nom
            FROM annonces a
            JOIN users u ON u.id = a.user_id
            LEFT JOIN villes v ON v.id = a.ville_id
            LEFT JOIN categories c ON c.id = a.categorie_id';
    $params = [];
    if (!empty($_GET['statut'])) {
        $sql .= ' WHERE a.statut = ?';
        $params[] = $_GET['statut'];
    }
    $stmt = $pdo->prepare($sql . ' ORDER BY a.created_at DESC');
    $stmt->execute($params);
    jsonOut($stmt->fetchAll());
}

/* ============ ACTIONS (POST) ============ */
$data   = getInput();
$action = $data['action'] ?? '';
$id     = (int) ($data['id'] ?? 0);

switch ($action) {

    /* ---------- ANNONCES ---------- */
    case 'publier':
        $pdo->prepare("UPDATE annonces SET statut='PUBLIEE' WHERE id=?")->execute([$id]);
        jsonOut(['message' => 'Annonce publiée']);

    case 'refuser':
        $pdo->prepare("UPDATE annonces SET statut='REFUSEE' WHERE id=?")->execute([$id]);
        jsonOut(['message' => 'Annonce refusée']);

    case 'supprimer_annonce':
        $pdo->prepare('DELETE FROM annonces WHERE id=?')->execute([$id]);
        jsonOut(['message' => 'Annonce supprimée']);

    case 'modifier_annonce':
        $stmt = $pdo->prepare('SELECT * FROM annonces WHERE id=?');
        $stmt->execute([$id]);
        $a = $stmt->fetch();
        if (!$a) jsonOut(['message' => 'Annonce introuvable'], 404);

        $image = $a['image'];
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = uploadImage($_FILES['image']);
        }

        $statut = $data['statut'] ?? $a['statut'];
        if (!in_array($statut, ['EN_ATTENTE', 'PUBLIEE', 'REFUSEE'], true)) {
            $statut = $a['statut'];
        }

        $pdo->prepare('UPDATE annonces
            SET titre=?, description=?, prix=?, ville_id=?, categorie_id=?, image=?, statut=?
            WHERE id=?')
            ->execute([
                trim($data['titre'] ?? $a['titre']),
                trim($data['description'] ?? $a['description']),
                $data['prix'] ?? $a['prix'],
                ($data['ville_id'] ?? $a['ville_id']) ?: null,
                ($data['categorie_id'] ?? $a['categorie_id']) ?: null,
                $image,
                $statut,
                $id,
            ]);
        jsonOut(['message' => 'Annonce modifiée', 'image' => $image]);

    /* ---------- ANNONCEURS ---------- */
    case 'ajouter_annonceur':
        $nom      = trim($data['nom'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        if (!$nom || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            jsonOut(['message' => 'Données invalides (mot de passe min 8 caractères)'], 400);
        }
        try {
            $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, 'ANNONCEUR')")
                ->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $e) {
            jsonOut(['message' => 'Email déjà utilisé'], 400);
        }
        jsonOut(['message' => 'Annonceur ajouté', 'id' => $pdo->lastInsertId()], 201);

    case 'supprimer_annonceur':
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='ANNONCEUR'")->execute([$id]);
        jsonOut(['message' => 'Annonceur supprimé']);

    /* ---------- VILLES & CATEGORIES ---------- */
    case 'ajouter_ville':
    case 'ajouter_categorie':
        $table = $action === 'ajouter_ville' ? 'villes' : 'categories';
        $nom = trim($data['nom'] ?? '');
        if (!$nom) jsonOut(['message' => 'Nom obligatoire'], 400);
        try {
            $pdo->prepare("INSERT INTO $table (nom) VALUES (?)")->execute([$nom]);
        } catch (PDOException $e) {
            jsonOut(['message' => 'Existe déjà'], 400);
        }
        jsonOut(['message' => 'Ajouté', 'id' => $pdo->lastInsertId()], 201);

    case 'modifier_ville':
    case 'modifier_categorie':
        $table = $action === 'modifier_ville' ? 'villes' : 'categories';
        $nom = trim($data['nom'] ?? '');
        if (!$nom) jsonOut(['message' => 'Nom obligatoire'], 400);
        $pdo->prepare("UPDATE $table SET nom=? WHERE id=?")->execute([$nom, $id]);
        jsonOut(['message' => 'Modifié']);

    case 'supprimer_ville':
    case 'supprimer_categorie':
        $table = $action === 'supprimer_ville' ? 'villes' : 'categories';
        $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
        jsonOut(['message' => 'Supprimé']);

    default:
        jsonOut(['message' => 'Action inconnue'], 400);
}