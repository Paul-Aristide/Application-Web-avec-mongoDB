<?php
// daemon de synchronisation MySQL -> MongoDB
// (nécessaire uniquement si vous ne souhaitez pas que l’API réplique
// automatiquement les modifications vers Mongo).
// usage en console : php api/sync_worker.php
// (ou en prod : nohup php api/sync_worker.php &)

// en CLI $_SERVER['REQUEST_METHOD'] peut être absent — éviter les warnings
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// on a besoin de la connexion MySQL (via config.php) et d'un client Mongo
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// récupérer l'objet PDO pour les requêtes SQL
$pdo = getPdo();

// créer la connexion Mongo via l'extension (classe MongoDB\Client existe si
// l'extension mongodb + la bibliothèque "mongodb/mongodb" sont installées).
// Sinon adapter selon ton environnement.

try {
    // on récupère le nom de la base MySQL/target Mongo dans la même variable
    $mongoClient = new MongoDB\Client('mongodb://127.0.0.1:27017');
    $mongo   = $mongoClient->{$config['database']};
} catch (Exception $e) {
    error_log("Impossible de joindre MongoDB : " . $e->getMessage());
    exit(1);
}

while (true) {
    $stmt = $pdo->query(
        "SELECT * FROM sync_log 
         WHERE synced = FALSE 
         ORDER BY id ASC 
         LIMIT 100"
    );

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$logs) {
        // rien à traiter, attendre un peu
        sleep(1);
        continue;
    }

    foreach ($logs as $log) {
        try {
            $collection = $mongo->{$log['table_name']};

            if ($log['operation'] !== 'DELETE') {
                $data = json_decode($log['data'], true);
                $data['mysql_id'] = $log['record_id'];
            }

            switch ($log['operation']) {
                case 'INSERT':
                    $collection->insertOne($data);
                    break;
                case 'UPDATE':
                    $collection->updateOne(
                        ['mysql_id' => $log['record_id']],
                        ['$set' => $data]
                    );
                    break;
                case 'DELETE':
                    $collection->deleteOne(['mysql_id' => $log['record_id']]);
                    break;
            }

            // marquer comme synchronisé
            $pdo->exec("UPDATE sync_log SET synced = TRUE WHERE id = " . (int)$log['id']);

        } catch (Exception $e) {
            error_log("Sync error (id={$log['id']}): " . $e->getMessage());
            // on ne crash pas, l'enregistrement restera unsynced pour une reprise
        }
    }

    // petit pause pour éviter de monopoliser le CPU
    usleep(500000); // 0.5s
}
