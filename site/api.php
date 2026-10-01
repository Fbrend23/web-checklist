<?php
// Proxy vers Directus : le jeton reste ici, le navigateur ne le voit jamais.
// GET            -> liste des noms de projets
// GET ?projet=x  -> { type, checked } (404 si inconnu)
// POST {nom, type, checked} -> enregistre (crée ou met à jour)
// L'accès est protégé par le mot de passe du dossier (Infomaniak), pas ici.
// DIRECTUS_URL et DIRECTUS_TOKEN : variables d'environnement (Manager Infomaniak,
// onglet PHP / Apache). Le préfixe REDIRECT_ couvre les variables posées par .htaccess.
foreach (['DIRECTUS_URL', 'DIRECTUS_TOKEN'] as $k) define($k, getenv($k) ?: getenv("REDIRECT_$k") ?: '');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!DIRECTUS_URL || !DIRECTUS_TOKEN) {
  http_response_code(500);
  exit(json_encode(['error' => 'config manquante']));
}

function directus(string $method, string $path, ?array $body = null): array {
  $ch = curl_init(rtrim(DIRECTUS_URL, '/') . $path);
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . DIRECTUS_TOKEN, 'Content-Type: application/json'],
  ]);
  if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
  $res = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($res === false || $code >= 400) {
    http_response_code(502);
    exit(json_encode(['error' => 'directus', 'status' => $code]));
  }
  return json_decode($res, true) ?? [];
}

const COLLECTION = '/items/chk_projets';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = json_decode(file_get_contents('php://input'), true);
  $nom = trim($in['nom'] ?? '');
  if ($nom === '' || !is_array($in['checked'] ?? null)) {
    http_response_code(400);
    exit(json_encode(['error' => 'nom et checked requis']));
  }
  $row = ['nom' => $nom, 'type' => (string)($in['type'] ?? ''), 'checked' => (object)$in['checked']];
  $found = directus('GET', COLLECTION . '?limit=1&fields=id&filter[nom][_eq]=' . rawurlencode($nom))['data'] ?? [];
  if ($found) directus('PATCH', COLLECTION . '/' . $found[0]['id'], $row);
  else directus('POST', COLLECTION, $row);
  exit(json_encode(['ok' => true]));
}

$projet = $_GET['projet'] ?? null;
if ($projet === null) {
  // ponytail: 200 projets max, pagination si la liste dépasse
  $rows = directus('GET', COLLECTION . '?limit=200&sort=-date_updated&fields=nom')['data'] ?? [];
  exit(json_encode(array_column($rows, 'nom')));
}
$rows = directus('GET', COLLECTION . '?limit=1&fields=type,checked&filter[nom][_eq]=' . rawurlencode($projet))['data'] ?? [];
if (!$rows) {
  http_response_code(404);
  exit(json_encode(['error' => 'inconnu']));
}
// (object) : un {} vide décodé en tableau PHP ressortirait en [], et JS perdrait les cases
$rows[0]['checked'] = (object)($rows[0]['checked'] ?? []);
exit(json_encode($rows[0]));
