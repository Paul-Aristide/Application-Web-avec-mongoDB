<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $doc = dbFindOne('enseignants', ['_id' => objectId($id)]);
        if (!$doc) {
            jsonResponse(['error' => 'Non trouvé'], 404);
        }
        jsonResponse($doc);
    }
    $list = dbFind('enseignants');
    jsonResponse(['data' => $list]);
}

if ($method === 'POST') {
    $input = getInput();
    if (empty($input['NOM']) || empty($input['PRENOM']) || empty($input['EMAIL']) || empty($input['SPECIALITE'])) {
        jsonResponse(['error' => 'NOM, PRENOM, EMAIL et SPECIALITE requis'], 400);
    }
    $list = dbFind('enseignants');
    $maxId = 0;
    foreach ($list as $r) {
        if (isset($r['ID_ENS']) && $r['ID_ENS'] > $maxId) {
            $maxId = (int) $r['ID_ENS'];
        }
    }
    $idEns = isset($input['ID_ENS']) ? (int) $input['ID_ENS'] : ($maxId + 1);
    $doc = [
        'ID_ENS' => $idEns,
        'NUM_ENS' => isset($input['NUM_ENS']) ? substr((string) $input['NUM_ENS'], 0, 10) : str_pad((string) $idEns, 10, '0', STR_PAD_LEFT),
        'NOM' => $input['NOM'],
        'PRENOM' => $input['PRENOM'],
        'EMAIL' => $input['EMAIL'],
        'DEPARTEMENT' => $input['DEPARTEMENT'] ?? null,
        'GRADE' => isset($input['GRADE']) ? substr((string) $input['GRADE'], 0, 5) : null,
        'SPECIALITE' => $input['SPECIALITE'],
    ];
    $oid = dbInsert('enseignants', $doc);
    jsonResponse(['_id' => $oid, 'ID_ENS' => $idEns, 'message' => 'Créé'], 201);
}

if ($method === 'PUT' && $id) {
    $input = getInput();
    $doc = dbFindOne('enseignants', ['_id' => objectId($id)]);
    if (!$doc) {
        jsonResponse(['error' => 'Non trouvé'], 404);
    }
    $update = [
        'NUM_ENS' => isset($input['NUM_ENS']) ? substr((string) $input['NUM_ENS'], 0, 10) : $doc['NUM_ENS'],
        'NOM' => $input['NOM'] ?? $doc['NOM'],
        'PRENOM' => $input['PRENOM'] ?? $doc['PRENOM'],
        'EMAIL' => $input['EMAIL'] ?? $doc['EMAIL'],
        'DEPARTEMENT' => $input['DEPARTEMENT'] ?? $doc['DEPARTEMENT'],
        'GRADE' => isset($input['GRADE']) ? substr((string) $input['GRADE'], 0, 5) : $doc['GRADE'],
        'SPECIALITE' => $input['SPECIALITE'] ?? $doc['SPECIALITE'],
    ];
    if (isset($input['ID_ENS'])) {
        $update['ID_ENS'] = (int) $input['ID_ENS'];
    }
    dbUpdate('enseignants', $id, $update);
    jsonResponse(['message' => 'Modifié']);
}

if ($method === 'DELETE' && $id) {
    $doc = dbFindOne('enseignants', ['_id' => objectId($id)]);
    if (!$doc) {
        jsonResponse(['error' => 'Non trouvé'], 404);
    }
    $idEns = (int) ($doc['ID_ENS'] ?? 0);
    $coursLie = dbFindOne('cours', ['ID_ENS' => $idEns]);
    if ($coursLie) {
        jsonResponse(['error' => 'Impossible de supprimer : cet enseignant donne des cours (contrainte RESTRICT).'], 409);
    }
    dbDelete('enseignants', $id);
    jsonResponse(['message' => 'Supprimé']);
}

jsonResponse(['error' => 'Méthode non supportée'], 405);
