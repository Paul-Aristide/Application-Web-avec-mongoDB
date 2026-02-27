<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $doc = dbFindOne('cours', ['_id' => objectId($id)]);
        if (!$doc) {
            jsonResponse(['error' => 'Non trouvé'], 404);
        }
        jsonResponse($doc);
    }
    $list = dbFind('cours');
    jsonResponse(['data' => $list]);
}

if ($method === 'POST') {
    $input = getInput();
    if (!array_key_exists('ID_ENS', $input) || (string) ($input['ID_ENS'] ?? '') === '') {
        jsonResponse(['error' => 'ID_ENS (enseignant) requis'], 400);
    }
    if (empty($input['INTITULE']) || !isset($input['NBRE_CREDITS']) || empty($input['SEMESTRE']) || empty($input['NIVEAU']) || empty($input['DEPARTEMENT'])) {
        jsonResponse(['error' => 'INTITULE, NBRE_CREDITS, SEMESTRE, NIVEAU et DEPARTEMENT requis'], 400);
    }
    $list = dbFind('cours');
    $maxIdCours = 0;
    $maxCode = 0;
    foreach ($list as $r) {
        if (isset($r['ID_COURS']) && $r['ID_COURS'] > $maxIdCours) {
            $maxIdCours = (int) $r['ID_COURS'];
        }
        if (isset($r['CODE_COURS']) && $r['CODE_COURS'] > $maxCode) {
            $maxCode = (int) $r['CODE_COURS'];
        }
    }
    $idCours = isset($input['ID_COURS']) ? (int) $input['ID_COURS'] : ($maxIdCours + 1);
    $codeCours = isset($input['CODE_COURS']) ? (int) $input['CODE_COURS'] : ($maxCode + 1);
    $doc = [
        'ID_COURS' => $idCours,
        'CODE_COURS' => $codeCours,
        'ID_ENS' => (int) $input['ID_ENS'],
        'INTITULE' => $input['INTITULE'],
        'DESCRIPTION_' => $input['DESCRIPTION_'] ?? null,
        'NBRE_CREDITS' => (float) $input['NBRE_CREDITS'],
        'SEMESTRE' => (int) $input['SEMESTRE'],
        'NIVEAU' => $input['NIVEAU'],
        'DEPARTEMENT' => $input['DEPARTEMENT'],
        'PREREQUIS' => $input['PREREQUIS'] ?? null,
    ];
    $oid = dbInsert('cours', $doc);
    jsonResponse(['_id' => $oid, 'ID_COURS' => $idCours, 'CODE_COURS' => $codeCours, 'message' => 'Créé'], 201);
}

if ($method === 'PUT' && $id) {
    $input = getInput();
    $doc = dbFindOne('cours', ['_id' => objectId($id)]);
    if (!$doc) {
        jsonResponse(['error' => 'Non trouvé'], 404);
    }
    $update = [
        'ID_ENS' => isset($input['ID_ENS']) ? (int) $input['ID_ENS'] : $doc['ID_ENS'],
        'INTITULE' => $input['INTITULE'] ?? $doc['INTITULE'],
        'DESCRIPTION_' => $input['DESCRIPTION_'] ?? $doc['DESCRIPTION_'],
        'NBRE_CREDITS' => isset($input['NBRE_CREDITS']) ? (float) $input['NBRE_CREDITS'] : $doc['NBRE_CREDITS'],
        'SEMESTRE' => isset($input['SEMESTRE']) ? (int) $input['SEMESTRE'] : $doc['SEMESTRE'],
        'NIVEAU' => $input['NIVEAU'] ?? $doc['NIVEAU'],
        'DEPARTEMENT' => $input['DEPARTEMENT'] ?? $doc['DEPARTEMENT'],
        'PREREQUIS' => $input['PREREQUIS'] ?? $doc['PREREQUIS'],
    ];
    if (isset($input['ID_COURS'])) {
        $update['ID_COURS'] = (int) $input['ID_COURS'];
    }
    if (isset($input['CODE_COURS'])) {
        $update['CODE_COURS'] = (int) $input['CODE_COURS'];
    }
    dbUpdate('cours', $id, $update);
    jsonResponse(['message' => 'Modifié']);
}

if ($method === 'DELETE' && $id) {
    $doc = dbFindOne('cours', ['_id' => objectId($id)]);
    if (!$doc) {
        jsonResponse(['error' => 'Non trouvé'], 404);
    }
    $inscription = dbFindOne('s_inscrire', [
        'ID_COURS' => (int) ($doc['ID_COURS'] ?? 0),
        'CODE_COURS' => (int) ($doc['CODE_COURS'] ?? 0),
    ]);
    if ($inscription) {
        jsonResponse(['error' => 'Impossible de supprimer : ce cours a des inscriptions (contrainte RESTRICT).'], 409);
    }
    dbDelete('cours', $id);
    jsonResponse(['message' => 'Supprimé']);
}

jsonResponse(['error' => 'Méthode non supportée'], 405);
