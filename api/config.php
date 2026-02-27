<?php
/**
 * Configuration MySQL - Base universite (universite.sql)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$config = [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'universite',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
];

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $config['pdo'] = $pdo;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Connexion MySQL: ' . $e->getMessage()]);
    exit;
}

function getPdo() {
    global $config;
    return $config['pdo'];
}

// connexion MongoDB (optionnelle, utilisée par le worker/monitor et pour les lectures
// de l'API si la base a déjà été synchronisée). Il faut :
//   * l'extension PHP mongodb activée
//   * la bibliothèque officielle installée (via composer require mongodb/mongodb)
// La base Mongo porte le même nom que la base MySQL pour simplifier la configuration.
// Si la connexion échoue on continue en mode MySQL seul.

// D'abord, charger l'autoloader Composer s'il existe
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

if (extension_loaded('mongodb') && class_exists('MongoDB\\Client')) {
    try {
        $mongoClient = new MongoDB\Client('mongodb://127.0.0.1:27017');
        // stocker l'objet dans $config pour pouvoir le réutiliser ailleurs
        $config['mongo'] = $mongoClient->{$config['database']};
        // indicateur utile pour savoir si on doit lire/écrire dans Mongo
        $config['use_mongo'] = true;
    } catch (Exception $e) {
        // on ne s'arrête pas, certaines pages n'auront pas besoin de Mongo
        error_log("Erreur connexion MongoDB : " . $e->getMessage());
        $config['use_mongo'] = false;
    }
} else {
    $config['use_mongo'] = false;
}

