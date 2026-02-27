<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap.php';

echo "Config use_mongo: " . (isset($config['use_mongo']) ? ($config['use_mongo'] ? 'true' : 'false') : 'not set') . "\n";
echo "Config mongo: " . (isset($config['mongo']) ? 'set' : 'not set') . "\n";

if (isset($config['mongo'])) {
    $mdb = $config['mongo'];
    echo "Enseignants count (Mongo): " . $mdb->enseignants->countDocuments() . "\n";
    echo "Cours count (Mongo): " . $mdb->cours->countDocuments() . "\n";
    echo "Etudiants count (Mongo): " . $mdb->etudiants->countDocuments() . "\n";
    echo "S_inscrire count (Mongo): " . $mdb->s_inscrire->countDocuments() . "\n";
    
    echo "\nFirst enseignant from Mongo:\n";
    $first = $mdb->enseignants->findOne();
    if ($first) {
        echo json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "No enseignant found\n";
    }
}

echo "\nDone\n";
