<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// renvoie quelques stats basiques sur la table sync_log
$pending = $pdo->query("SELECT COUNT(*) as c FROM sync_log WHERE synced = FALSE")->fetchColumn();
$last = $pdo->query("SELECT * FROM sync_log ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$response = [
    'pending' => (int)$pending,
    'last'    => $last,
];

// si Mongo est configuré on peut également lister quelques compteurs
if (!empty($config['use_mongo']) && isset($config['mongo'])) {
    try {
        $mdb = $config['mongo'];
        $response['mongo_counts'] = [
            'enseignants' => $mdb->enseignants->countDocuments(),
            'etudiants' => $mdb->etudiants->countDocuments(),
            'cours' => $mdb->cours->countDocuments(),
            's_inscrire' => $mdb->s_inscrire->countDocuments(),
        ];
    } catch (Exception $e) {
        // ignore
    }
}

echo json_encode($response);

