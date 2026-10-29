<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Cloudinary\Cloudinary;

function cors(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function jsonOut($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function createToken(array $user): string
{
    $payload = [
        'id'   => (int) $user['id'],
        'role' => $user['role'],
        'exp'  => time() + 60 * 60 * 24 * 7,
    ];
    return JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
}

function currentUser(string $role): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '') : '');

    if (!preg_match('/Bearer\s+(.+)/', $header, $m)) {
        jsonOut(['message' => 'Non autorisé'], 401);
    }
    try {
        $data = (array) JWT::decode($m[1], new Key(getenv('JWT_SECRET'), 'HS256'));
    } catch (Throwable $e) {
        jsonOut(['message' => 'Token invalide'], 401);
    }
    if (($data['role'] ?? '') !== $role) {
        jsonOut(['message' => 'Accès interdit'], 403);
    }
    return $data;
}

function uploadImage(array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonOut(['message' => 'Erreur upload image'], 400);
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        jsonOut(['message' => 'Image trop lourde (max 4 Mo)'], 400);
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        jsonOut(['message' => 'Format image invalide'], 400);
    }
    $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
    $result = $cloudinary->uploadApi()->upload($file['tmp_name'], ['folder' => 'vendo']);
    return $result['secure_url'];
}