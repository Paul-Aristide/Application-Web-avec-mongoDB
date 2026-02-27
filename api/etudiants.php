<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $doc = dbFindOne('etudiants', ['_id' => objectId($id)]);
        if (!$doc) {
            jsonResponse(['error' => 'Non trouvé'], 404);
        }
        jsonResponse($doc);
    }
    $list = dbFind('etudiants');
    jsonResponse(['data' => $list]);
}

if ($method === 'POST') {
    $input = getInput();
    if (empty($input['NUM_CARTE'])) {
        jsonResponse(['error' => 'NUM_CARTE requis'], 400);
    }
    $list = dbFind('etudiants');
    $maxId = 0;
    foreach ($list as $r) {
        if (isset($r['ID_ETUDIANTS']) && $r['ID_ETUDIANTS'] > $maxId) {
            $maxId = (int) $r['ID_ETUDIANTS'];
        }
    }
    $idEtud = isset($input['ID_ETUDIANTS']) ? (int) $input['ID_ETUDIANTS'] : ($maxId + 1);
    $doc = [
        'ID_ETUDIANTS' => $idEtud,
        'NUM_CARTE' => $input['NUM_CARTE'],
        'NOM' => $input['NOM'] ?? null,
        'PRENOM' => $input['PRENOM'] ?? null,
        'EMAIL' => $input['EMAIL'] ?? null,
        'TELEPHONE' => isset($input['TELEPHONE']) ? substr((string) $input['TELEPHONE'], 0, 20) : null,
        'FILIERE' => $input['FILIERE'] ?? null,
        'ANNEE_ENTREE' => isset($input['ANNEE_ENTREE']) ? (int) $input['ANNEE_ENTREE'] : null,
        'DATE_NAISSANCE' => !empty($input['DATE_NAISSANCE']) ? $input['DATE_NAISSANCE'] : null,
    ];
    $oid = dbInsert('etudiants', $doc);
    jsonResponse(['_id' => $oid, 'ID_ETUDIANTS' => $idEtud, 'message' => 'Créé'], 201);
}

if ($method === 'PUT' && $id) {
    $input = getInput();
    $doc = dbFindOne('etudiants', ['_id' => objectId($id)]);
    if (!$doc) {
        jsonResponse(['error' => 'Non trouvé'], 404);
    }
    $update = [
        'NUM_CARTE' => $input['NUM_CARTE'] ?? $doc['NUM_CARTE'],
        'NOM' => $input['NOM'] ?? $doc['NOM'],
        'PRENOM' => $input['PRENOM'] ?? $doc['PRENOM'],
        'EMAIL' => $input['EMAIL'] ?? $doc['EMAIL'],
        'TELEPHONE' => isset($input['TELEPHONE']) ? substr((string) $input['TELEPHONE'], 0, 20) : $doc['TELEPHONE'],
        'FILIERE' => $input['FILIERE'] ?? $doc['FILIERE'],
        'ANNEE_ENTREE' => isset($input['ANNEE_ENTREE']) ? (int) $input['ANNEE_ENTREE'] : $doc['ANNEE_ENTREE'],
    ];
    if (isset($input['ID_ETUDIANTS'])) {
        $update['ID_ETUDIANTS'] = (int) $input['ID_ETUDIANTS'];
    }
    if (array_key_exists('DATE_NAISSANCE', $input)) {
        $update['DATE_NAISSANCE'] = empty($input['DATE_NAISSANCE']) ? null : $input['DATE_NAISSANCE'];
    }
    dbUpdate('etudiants', $id, $update);
    jsonResponse(['message' => 'Modifié']);
}

if ($method === 'DELETE' && $id) {
    $doc = dbFindOne('etudiants', ['_id' => objectId($id)]);
    if (!$doc) {
        jsonResponse(['error' => 'Non trouvé'], 404);
    }
    $inscription = dbFindOne('s_inscrire', [
        'ID_ETUDIANTS' => (int) ($doc['ID_ETUDIANTS'] ?? 0),
        'NUM_CARTE' => $doc['NUM_CARTE'] ?? '',
    ]);
    if ($inscription) {
        jsonResponse(['error' => 'Impossible de supprimer : cet étudiant a des inscriptions (contrainte RESTRICT).'], 409);
    }
    dbDelete('etudiants', $id);
    jsonResponse(['message' => 'Supprimé']);
}

jsonResponse(['error' => 'Méthode non supportée'], 405);
