<?php
require_once __DIR__ . '/config.php';

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getInput() {
    $input = file_get_contents('php://input');
    return $input ? json_decode($input, true) : [];
}

/**
 * Si Mongo est utilisé on convertit les identifiants hexadécimaux en
 * MongoDB\BSON\ObjectId. En MySQL on les laisse tels quels.
 */
function objectId($id) {
    global $config;
    if (!empty($config['use_mongo']) && 
        is_string($id) && preg_match('/^[0-9a-f]{24}$/i', $id)) {
        try {
            return new MongoDB\BSON\ObjectId($id);
        } catch (Exception $e) {
            // si la chaîne n'est pas un ObjectId valide on renvoie la valeur
        }
    }
    return $id;
}
