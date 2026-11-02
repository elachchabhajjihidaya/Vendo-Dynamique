<?php
require_once __DIR__ . '/config/db.php';
$pdo = getDatabase();
echo "Connexion OK ✅<br>";
print_r($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));