<?php
// Désactiver l'affichage des erreurs pour éviter de polluer le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Proxy PHP pour contourner les restrictions CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration de l'API Omeka S
$OMEKA_S_URL = 'http://localhost/omk_thyp_25-26_clone';
$KEY_IDENTITY = 'EI6g2wROVTOiIBHmTf0roMIL647UenRu';
$KEY_CREDENTIAL = 'JaOh8wKwDo2hTiR66uDjiMGaYoNsCiOj';

// Récupérer le type de requête (items, item spécifique, etc.)
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'items';
$itemId = isset($_GET['item_id']) ? intval($_GET['item_id']) : null;

// Construire l'URL de l'API Omeka S
if ($itemId) {
    $apiUrl = $OMEKA_S_URL . '/api/items/' . $itemId;
} else {
    $apiUrl = $OMEKA_S_URL . '/api/' . $endpoint;
}

// Ajouter les paramètres d'authentification
$apiUrl .= '?key_identity=' . urlencode($KEY_IDENTITY);
$apiUrl .= '&key_credential=' . urlencode($KEY_CREDENTIAL);

// Ajouter d'autres paramètres de requête s'ils existent
if (isset($_GET['resource_class_id'])) {
    $apiUrl .= '&resource_class_id=' . urlencode($_GET['resource_class_id']);
}

// Initialiser cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

// Exécuter la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$curlInfo = curl_getinfo($ch);
curl_close($ch);

// Gérer les erreurs cURL
if ($error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur cURL',
        'message' => $error,
        'url' => $apiUrl
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Vérifier le code HTTP
if ($httpCode >= 400) {
    http_response_code($httpCode);
    // Essayer de parser la réponse comme JSON, sinon retourner une erreur
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode([
            'error' => 'Erreur HTTP ' . $httpCode,
            'data' => $decoded
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'error' => 'Erreur HTTP ' . $httpCode,
            'message' => substr($response, 0, 500), // Limiter la taille
            'url' => $apiUrl
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    exit;
}

// Vérifier que la réponse n'est pas vide
if (empty($response)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Réponse vide',
        'url' => $apiUrl,
        'http_code' => $httpCode
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Vérifier que c'est du JSON valide
$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Réponse non-JSON',
        'json_error' => json_last_error_msg(),
        'response_preview' => substr($response, 0, 200),
        'url' => $apiUrl
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Retourner la réponse JSON valide
http_response_code($httpCode);
echo $response;
?>

