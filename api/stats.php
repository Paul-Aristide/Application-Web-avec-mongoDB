<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    jsonResponse(['error' => 'Méthode non supportée'], 405);
}

if (!empty($config['use_mongo']) && isset($config['mongo'])) {
    $mdb = $config['mongo'];

    $enseignantsCount = $mdb->enseignants->countDocuments();
    $etudiantsCount = $mdb->etudiants->countDocuments();
    $coursCount = $mdb->cours->countDocuments();
    $inscriptionsCount = $mdb->s_inscrire->countDocuments();

    // Cours par enseignant (ID_ENS)
    $coursByEnseignant = iterator_to_array($mdb->cours->aggregate([
        ['$group' => ['_id' => '$ID_ENS', 'count' => ['$sum' => 1], 'total_credits' => ['$sum' => '$NBRE_CREDITS']]],
        ['$sort' => ['count' => -1]],
        ['$limit' => 20]
    ]));
    foreach ($coursByEnseignant as &$r) {
        $r['_id'] = (string) $r['_id'];
    }

    // Cours par département
    $coursByDepartement = iterator_to_array($mdb->cours->aggregate([
        ['$group' => ['_id' => '$DEPARTEMENT', 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]]
    ]));

    // Étudiants par filière
    $etudiantsByFiliere = iterator_to_array($mdb->etudiants->aggregate([
        ['$match' => ['FILIERE' => ['$ne' => null, '$ne' => '']]],
        ['$group' => ['_id' => '$FILIERE', 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]],
        ['$limit' => 15]
    ]));

    // Cours par niveau
    $coursByNiveau = iterator_to_array($mdb->cours->aggregate([
        ['$group' => ['_id' => '$NIVEAU', 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]]
    ]));

    // Enseignants par département
    $enseignantsByDepartement = iterator_to_array($mdb->enseignants->aggregate([
        ['$match' => ['DEPARTEMENT' => ['$ne' => null, '$ne' => '']]],
        ['$group' => ['_id' => '$DEPARTEMENT', 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]]
    ]));

    // Derniers cours
    $recentCours = iterator_to_array($mdb->cours->find([], ['sort' => ['ID_COURS' => -1, 'CODE_COURS' => -1], 'limit' => 5]));
    array_walk($recentCours, function (&$r) {
        $r = _rowToId('cours', json_decode(json_encode($r), true));
    });

    // Inscriptions par cours
    $inscriptionsByCoursNormalized = [];
    $inscCursor = $mdb->s_inscrire->aggregate([
        ['$group' => ['_id' => ['ID_COURS' => '$ID_COURS', 'CODE_COURS' => '$CODE_COURS'], 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]],
        ['$limit' => 15]
    ]);
    foreach ($inscCursor as $r) {
        $inscriptionsByCoursNormalized[] = [
            'ID_COURS' => (int) $r['_id']['ID_COURS'],
            'CODE_COURS' => (int) $r['_id']['CODE_COURS'],
            'count' => (int) $r['count'],
        ];
    }

    // Inscriptions par étudiant
    $inscriptionsByEtudiantNormalized = [];
    $inscCursor = $mdb->s_inscrire->aggregate([
        ['$group' => ['_id' => ['ID_ETUDIANTS' => '$ID_ETUDIANTS', 'NUM_CARTE' => '$NUM_CARTE'], 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]],
        ['$limit' => 15]
    ]);
    foreach ($inscCursor as $r) {
        $inscriptionsByEtudiantNormalized[] = [
            'ID_ETUDIANTS' => (int) $r['_id']['ID_ETUDIANTS'],
            'NUM_CARTE' => (string) $r['_id']['NUM_CARTE'],
            'count' => (int) $r['count'],
        ];
    }

    // Dernières inscriptions (ordre naturel)
    $recentInscriptions = iterator_to_array($mdb->s_inscrire->find([], ['limit' => 5]));
    array_walk($recentInscriptions, function (&$r) {
        $r = _rowToId('s_inscrire', json_decode(json_encode($r), true));
    });
} else {
    $enseignantsCount = count(dbFind('enseignants'));
    $etudiantsCount = count(dbFind('etudiants'));
    $coursCount = count(dbFind('cours'));
    $inscriptionsCount = count(dbFind('s_inscrire'));

    // Cours par enseignant (ID_ENS)
    $coursByEnseignant = dbQuery("SELECT ID_ENS AS _id, COUNT(*) AS count, SUM(NBRE_CREDITS) AS total_credits FROM cours GROUP BY ID_ENS ORDER BY count DESC LIMIT 20");
    foreach ($coursByEnseignant as &$r) {
        $r['_id'] = (string) $r['_id'];
    }

    // Cours par département
    $coursByDepartement = dbQuery("SELECT DEPARTEMENT AS _id, COUNT(*) AS count FROM cours GROUP BY DEPARTEMENT ORDER BY count DESC");

    // Étudiants par filière
    $etudiantsByFiliere = dbQuery("SELECT FILIERE AS _id, COUNT(*) AS count FROM etudiants WHERE FILIERE IS NOT NULL AND FILIERE != '' GROUP BY FILIERE ORDER BY count DESC LIMIT 15");

    // Cours par niveau
    $coursByNiveau = dbQuery("SELECT NIVEAU AS _id, COUNT(*) AS count FROM cours GROUP BY NIVEAU ORDER BY count DESC");

    // Enseignants par département
    $enseignantsByDepartement = dbQuery("SELECT DEPARTEMENT AS _id, COUNT(*) AS count FROM enseignants WHERE DEPARTEMENT IS NOT NULL AND DEPARTEMENT != '' GROUP BY DEPARTEMENT ORDER BY count DESC");

    // Derniers cours (ordre par ID_COURS, CODE_COURS)
    $recentCours = dbFind('cours', [], 'ID_COURS DESC, CODE_COURS DESC', 5);

    // Inscriptions par cours
    $inscriptionsByCoursRaw = dbQuery("SELECT ID_COURS, CODE_COURS, COUNT(*) AS count FROM s_inscrire GROUP BY ID_COURS, CODE_COURS ORDER BY count DESC LIMIT 15");
    $inscriptionsByCoursNormalized = [];
    foreach ($inscriptionsByCoursRaw as $r) {
        $inscriptionsByCoursNormalized[] = [
            'ID_COURS' => (int) $r['ID_COURS'],
            'CODE_COURS' => (int) $r['CODE_COURS'],
            'count' => (int) $r['count'],
        ];
    }

    // Inscriptions par étudiant
    $inscriptionsByEtudiantRaw = dbQuery("SELECT ID_ETUDIANTS, NUM_CARTE, COUNT(*) AS count FROM s_inscrire GROUP BY ID_ETUDIANTS, NUM_CARTE ORDER BY count DESC LIMIT 15");
    $inscriptionsByEtudiantNormalized = [];
    foreach ($inscriptionsByEtudiantRaw as $r) {
        $inscriptionsByEtudiantNormalized[] = [
            'ID_ETUDIANTS' => (int) $r['ID_ETUDIANTS'],
            'NUM_CARTE' => (string) $r['NUM_CARTE'],
            'count' => (int) $r['count'],
        ];
    }

    // Dernières inscriptions (pas de created_at dans s_inscrire, on prend les 5 premières)
    $recentInscriptions = dbFind('s_inscrire', [], 'ID_ETUDIANTS, NUM_CARTE, ID_COURS, CODE_COURS', 5);
}
jsonResponse([
    'source' => _isMongo() ? 'mongo' : 'mysql',
    'summary' => [
        'enseignants' => $enseignantsCount,
        'etudiants' => $etudiantsCount,
        'cours' => $coursCount,
        'inscriptions' => $inscriptionsCount,
    ],
    'cours_by_enseignant' => $coursByEnseignant,
    'cours_by_departement' => $coursByDepartement,
    'etudiants_by_filiere' => $etudiantsByFiliere,
    'cours_by_niveau' => $coursByNiveau,
    'enseignants_by_departement' => $enseignantsByDepartement,
    'inscriptions_by_cours' => $inscriptionsByCoursNormalized,
    'inscriptions_by_etudiant' => $inscriptionsByEtudiantNormalized,
    'recent_cours' => $recentCours,
    'recent_inscriptions' => $recentInscriptions,
]);
