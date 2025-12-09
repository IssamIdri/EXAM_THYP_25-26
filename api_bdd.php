<?php
// Désactiver l'affichage des erreurs pour éviter de polluer le JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// API pour récupérer les données directement depuis MySQL
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration de la base de données MySQL
$DB_HOST = 'localhost';
$DB_NAME = 'master_eval';
$DB_USER = 'root'; // À adapter selon votre configuration
$DB_PASS = ''; // À adapter selon votre configuration

// Configuration Omeka S (gardée pour référence)
$OMEKA_S_URL = 'http://localhost/omk_thyp_25-26_clone';
$KEY_IDENTITY = 'EI6g2wROVTOiIBHmTf0roMIL647UenRu';
$KEY_CREDENTIAL = 'JaOh8wKwDo2hTiR66uDjiMGaYoNsCiOj';

// Récupérer le type de requête
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'cours';

try {
    // Connexion à la base de données
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Traiter selon l'endpoint
    switch ($endpoint) {
        case 'cours':
            // Récupérer tous les cours avec leurs étudiants inscrits
            $stmt = $pdo->query("
                SELECT 
                    c.id_cours,
                    c.code_cours,
                    c.titre,
                    c.description,
                    c.id_master,
                    m.titre as master_titre,
                    GROUP_CONCAT(
                        CONCAT(e.nom, ' ', e.prenom) 
                        ORDER BY e.nom, e.prenom
                        SEPARATOR '||'
                    ) as etudiants
                FROM cours c
                LEFT JOIN master m ON c.id_master = m.id_master
                LEFT JOIN inscription i ON c.id_cours = i.id_cours
                LEFT JOIN etudiant e ON i.id_etudiant = e.id_etudiant
                GROUP BY c.id_cours, c.code_cours, c.titre, c.description, c.id_master, m.titre
                ORDER BY c.code_cours
            ");
            
            $cours = [];
            while ($row = $stmt->fetch()) {
                $cours[] = [
                    'id' => $row['id_cours'],
                    'code' => $row['code_cours'],
                    'titre' => $row['titre'],
                    'description' => $row['description'],
                    'master' => $row['master_titre'],
                    'etudiants' => $row['etudiants'] ? explode('||', $row['etudiants']) : []
                ];
            }
            
            echo json_encode($cours, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        case 'evals':
        case 'evaluations':
            // Récupérer toutes les évaluations avec leurs détails
            $stmt = $pdo->query("
                SELECT 
                    ev.id_evaluation,
                    ev.id_cours,
                    ev.id_etudiant,
                    ev.type_evaluation,
                    ev.date_evaluation,
                    ev.coefficient,
                    c.code_cours,
                    c.titre as cours_titre,
                    e.nom as etudiant_nom,
                    e.prenom as etudiant_prenom,
                    n.valeur as note_valeur,
                    n.commentaire as note_commentaire
                FROM evaluation ev
                LEFT JOIN cours c ON ev.id_cours = c.id_cours
                LEFT JOIN etudiant e ON ev.id_etudiant = e.id_etudiant
                LEFT JOIN note n ON ev.id_evaluation = n.id_evaluation
                ORDER BY ev.date_evaluation DESC, ev.id_evaluation
            ");
            
            $evals = [];
            while ($row = $stmt->fetch()) {
                $evals[] = [
                    'id' => $row['id_evaluation'],
                    'cours_id' => $row['id_cours'],
                    'cours_code' => $row['code_cours'],
                    'cours' => $row['cours_titre'],
                    'etudiant_id' => $row['id_etudiant'],
                    'etudiant' => trim($row['etudiant_nom'] . ' ' . $row['etudiant_prenom']),
                    'type' => $row['type_evaluation'],
                    'date' => $row['date_evaluation'],
                    'coefficient' => floatval($row['coefficient']),
                    'note' => $row['note_valeur'] !== null ? floatval($row['note_valeur']) : null,
                    'commentaire' => $row['note_commentaire']
                ];
            }
            
            echo json_encode($evals, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Endpoint non reconnu',
                'endpoints_disponibles' => ['cours', 'evals', 'evaluations']
            ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    http_response_code(500);
    $errorResponse = [
        'error' => 'Erreur de base de données',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];
    // Ne pas exposer les détails en production
    if (ini_get('display_errors')) {
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    $errorResponse = [
        'error' => 'Erreur',
        'message' => $e->getMessage()
    ];
    if (ini_get('display_errors')) {
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    exit;
}