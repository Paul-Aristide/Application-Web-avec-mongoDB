<?php
/**
 * S_INSCRIRE : table d'association ETUDIANTS ↔ COURS
 * Clé composite : (ID_ETUDIANTS, NUM_CARTE, ID_COURS, CODE_COURS)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $doc = dbFindOne('s_inscrire', ['_id' => objectId($id)]);
        if (!$doc) {
            jsonResponse(['error' => 'Non trouvé'], 404);
        }
        jsonResponse($doc);
    }
    $list = dbFind('s_inscrire');
    jsonResponse(['data' => $list]);
}

if ($method === 'POST') {
    $input = getInput();
    $idEtu = isset($input['ID_ETUDIANTS']) ? (int) $input['ID_ETUDIANTS'] : null;
    $numCarte = $input['NUM_CARTE'] ?? '';
    $idCours = isset($input['ID_COURS']) ? (int) $input['ID_COURS'] : null;
    $codeCours = isset($input['CODE_COURS']) ? (int) $input['CODE_COURS'] : null;
    if ($idEtu === null || $numCarte === '' || $idCours === null || $codeCours === null) {
        jsonResponse(['error' => 'ID_ETUDIANTS, NUM_CARTE, ID_COURS et CODE_COURS requis'], 400);
    }
    // Vérifier que l'étudiant existe (FK)
    $etu = dbFindOne('etudiants', ['ID_ETUDIANTS' => $idEtu, 'NUM_CARTE' => $numCarte]);
    if (!$etu) {
        jsonResponse(['error' => 'Étudiant (ID_ETUDIANTS, NUM_CARTE) introuvable'], 400);
    }
    // Vérifier que le cours existe (FK)
    $cours = dbFindOne('cours', ['ID_COURS' => $idCours, 'CODE_COURS' => $codeCours]);
    if (!$cours) {
        jsonResponse(['error' => 'Cours (ID_COURS, CODE_COURS) introuvable'], 400);
    }
    // Unicité de l'inscription
    $existant = dbFindOne('s_inscrire', [
        'ID_ETUDIANTS' => $idEtu,
        'NUM_CARTE' => $numCarte,
        'ID_COURS' => $idCours,
        'CODE_COURS' => $codeCours,
    ]);
    if ($existant) {
        jsonResponse(['error' => 'Cet étudiant est déjà inscrit à ce cours'], 409);
    }
    $doc = [
        'ID_ETUDIANTS' => $idEtu,
        'NUM_CARTE' => $numCarte,
        'ID_COURS' => $idCours,
        'CODE_COURS' => $codeCours,
    ];
    $oid = dbInsert('s_inscrire', $doc);
    jsonResponse(['_id' => $oid, 'message' => 'Inscription créée'], 201);
}

if ($method === 'DELETE' && $id) {
    $doc = dbFindOne('s_inscrire', ['_id' => objectId($id)]);
    if (!$doc) {
        jsonResponse(['error' => 'Non trouvé'], 404);
    }
    dbDelete('s_inscrire', $id);
    jsonResponse(['message' => 'Supprimé']);
}

jsonResponse(['error' => 'Méthode non supportée (PUT non autorisé pour une table d\'association)'], 405);
