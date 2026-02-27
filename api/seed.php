<?php
/**
 * Données de démonstration : ENSEIGNANTS, ETUDIANTS, COURS.
 * Ouvrir dans le navigateur : api/seed.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

// si Mongo est demandé, supprimer les collections existantes pour repartir à zéro
if (!empty($config['use_mongo']) && isset($config['mongo'])) {
    $tables = ['enseignants','etudiants','cours','s_inscrire'];
    foreach ($tables as $t) {
        try {
            $config['mongo']->{$t}->drop();
        } catch (Exception $e) {
            // ignore
        }
    }
}


// Fonction pour générer des noms aléatoires
function generateRandomName() {
    $firstNames = ['Mohamed', 'Aminata', 'Ibrahim', 'Fatoumata', 'Mamadou', 'Aïcha', 'Oumar', 'Mariam', 'Abdou', 'Awa', 
                   'Baba', 'Rokiatou', 'Lassina', 'Kadiatou', 'Souleymane', 'Aminata', 'Yacouba', 'Assetou', 'Drissa', 'Sitan',
                   'Karim', 'Bintou', 'Moussa', 'Halimatou', 'Cheick', 'Aïssatou', 'Boubacar', 'Mariam', 'Ismail', 'Rahmatou',
                   'Souleymane', 'Hadja', 'Lamine', 'Kadija', 'Mamadou', 'Aminata', 'Ibrahim', 'Fatou', 'Ousmane', 'Mariame'];
    $lastNames = ['Konaté', 'Touré', 'Coulibaly', 'Traoré', 'Sangaré', 'Bamba', 'Diarra', 'Keïta', 'Doucouré', 'Sissoko',
                 'Kanté', 'Dembélé', 'Fofana', 'Bakayoko', 'Kouyaté', 'Cissé', 'Berthé', 'Diarra', 'Sangaré', 'Komara',
                 'Diallo', 'Bah', 'Sow', 'Barry', 'Camara', 'Sylla', 'Bangoura', 'Kaba', 'Condé', 'Fofana'];
    
    return [
        'NOM' => $lastNames[array_rand($lastNames)],
        'PRENOM' => $firstNames[array_rand($firstNames)]
    ];
}

// Générer 103 enseignants
$enseignants = [];
$grades = ['PR', 'MCF', 'DR', 'AGR', 'PAST'];
$specialitesMaths = ['Algèbre', 'Analyse', 'Probabilités', 'Statistiques', 'Géométrie', 'Topologie', 'Calcul différentiel', 'Équations différentielles'];
$specialitesInfo = ['Algorithmique', 'Base de données', 'Réseaux', 'Intelligence artificielle', ' Programmation', 'Sécurité', 'Calcul scientifique', 'Théorie des graphes'];

for ($i = 1; $i <= 103; $i++) {
    $name = generateRandomName();
    $isMaths = $i <= 52; // Environ moitié maths, moitié info
    $specialite = $isMaths ? $specialitesMaths[array_rand($specialitesMaths)] : $specialitesInfo[array_rand($specialitesInfo)];
    
    $enseignants[] = [
        'ID_ENS' => $i,
        'NUM_ENS' => 'ENS' . str_pad($i, 7, '0', STR_PAD_LEFT),
        'NOM' => $name['NOM'],
        'PRENOM' => $name['PRENOM'],
        'EMAIL' => strtolower($name['PRENOM']) . '.' . strtolower($name['NOM']) . '@univ.fr',
        'DEPARTEMENT' => $isMaths ? 'Mathématiques' : 'Informatique',
        'GRADE' => $grades[array_rand($grades)],
        'SPECIALITE' => $specialite
    ];
}

foreach ($enseignants as $e) {
    try {
        dbInsert('enseignants', $e);
    } catch (Exception $ex) {
        // ignorer si déjà existant
    }
}

// Générer 3214 étudiants
$etudiants = [];
$filieres = ['Licence Mathématiques', 'Licence Informatique', 'Master Mathématiques', 'Master Informatique', 
             'L1 Mathématiques', 'L2 Mathématiques', 'L3 Mathématiques', 'L1 Informatique', 'L2 Informatique', 'L3 Informatique',
             'M1 Mathématiques Fondamentales', 'M2 Mathématiques Appliquées', 'M1 Informatique', 'M2 Informatique'];

for ($i = 1; $i <= 3214; $i++) {
    $name = generateRandomName();
    $anneeEntree = rand(2020, 2024);
    
    $etudiants[] = [
        'ID_ETUDIANTS' => $i,
        'NUM_CARTE' => 'ETU' . $anneeEntree . str_pad($i, 4, '0', STR_PAD_LEFT),
        'NOM' => $name['NOM'],
        'PRENOM' => $name['PRENOM'],
        'EMAIL' => strtolower($name['PRENOM']) . '.' . strtolower($name['NOM']) . '@etu.univ.fr',
        'TELEPHONE' => '07' . rand(10000000, 99999999),
        'FILIERE' => $filieres[array_rand($filieres)],
        'ANNEE_ENTREE' => $anneeEntree,
        'DATE_NAISSANCE' => date('Y-m-d', strtotime(rand(1995, 2005) . '-' . rand(1, 12) . '-' . rand(1, 28)))
    ];
}

foreach ($etudiants as $e) {
    try {
        dbInsert('etudiants', $e);
    } catch (Exception $ex) {
        // ignorer
    }
}

// Générer des cours pour l'UFR Math-Info
$coursList = [];
$coursId = 1;

// Cours de Mathématiques
$mathsCourses = [
    ['Analyse L1', 'Introduction à l\'analyse réelle', 6, 1, 'L1'],
    ['Algèbre L1', 'Structures algébriques fondamentales', 6, 1, 'L1'],
    ['Calcul Différentiel L2', 'Fonctions de plusieurs variables', 6, 2, 'L2'],
    ['Probabilités L2', 'Théorie des probabilités', 5, 2, 'L2'],
    ['Analyse Complexe L3', 'Fonctions analytiques', 6, 3, 'L3'],
    ['Statistiques L3', 'Statistiques inférentielles', 5, 3, 'L3'],
    ['Algèbre Linéaire Appliquée M1', 'Applications en ingénierie', 8, 4, 'M1'],
    ['Équations Différentielles M1', 'EDO et EDP', 8, 4, 'M1'],
    ['Optimisation M2', 'Théorie et algorithmes', 8, 5, 'M2'],
    ['Analyse Numérique M2', 'Méthodes numériques avancées', 8, 5, 'M2'],
];

foreach ($mathsCourses as $course) {
    $enseignantId = rand(1, 52); // Enseignants de maths
    $coursList[] = [
        'ID_COURS' => $coursId,
        'CODE_COURS' => 100 + $coursId,
        'ID_ENS' => $enseignantId,
        'INTITULE' => $course[0],
        'DESCRIPTION_' => $course[1],
        'NBRE_CREDITS' => $course[2],
        'SEMESTRE' => $course[3],
        'NIVEAU' => $course[4],
        'DEPARTEMENT' => 'Mathématiques',
        'PREREQUIS' => 'Connaissances de base en mathématiques'
    ];
    $coursId++;
}

// Cours d'Informatique
$infoCourses = [
    ['Algorithmique L1', 'Introduction aux algorithmes', 6, 1, 'L1'],
    ['Programmation C L1', 'Bases de la programmation', 6, 1, 'L1'],
    ['Structures de Données L2', 'Listes, piles, files, arbres', 6, 2, 'L2'],
    ['Bases de Données L2', 'SQL et conception', 6, 2, 'L2'],
    ['Réseaux L3', 'Protocoles et architecture', 6, 3, 'L3'],
    ['Programmation Web L3', 'HTML, CSS, JavaScript', 5, 3, 'L3'],
    ['Intelligence Artificielle M1', 'Machine Learning', 8, 4, 'M1'],
    ['Sécurité Informatique M1', 'Cryptographie et sécurité', 8, 4, 'M1'],
    ['Big Data M2', 'Traitement des données massives', 8, 5, 'M2'],
    ['Cloud Computing M2', 'Architecture distribuée', 8, 5, 'M2'],
];

foreach ($infoCourses as $course) {
    $enseignantId = rand(53, 103); // Enseignants d'informatique
    $coursList[] = [
        'ID_COURS' => $coursId,
        'CODE_COURS' => 100 + $coursId,
        'ID_ENS' => $enseignantId,
        'INTITULE' => $course[0],
        'DESCRIPTION_' => $course[1],
        'NBRE_CREDITS' => $course[2],
        'SEMESTRE' => $course[3],
        'NIVEAU' => $course[4],
        'DEPARTEMENT' => 'Informatique',
        'PREREQUIS' => 'Connaissances de base en informatique'
    ];
    $coursId++;
}

foreach ($coursList as $c) {
    try {
        dbInsert('cours', $c);
    } catch (Exception $ex) {
        // ignorer
    }
}

// Générer des inscriptions aléatoires
$inscriptions = [];
$maxInscriptionsPerStudent = 5;

foreach ($etudiants as $etudiant) {
    $nbCours = rand(2, $maxInscriptionsPerStudent);
    $selectedCourses = array_rand($coursList, min($nbCours, count($coursList)));
    
    if (!is_array($selectedCourses)) {
        $selectedCourses = [$selectedCourses];
    }
    
    foreach ($selectedCourses as $courseIndex) {
        $course = $coursList[$courseIndex];
        $inscriptions[] = [
            $etudiant['ID_ETUDIANTS'],
            $etudiant['NUM_CARTE'],
            $course['ID_COURS'],
            $course['CODE_COURS']
        ];
    }
}

foreach ($inscriptions as $i) {
    try {
        dbInsert('s_inscrire', [
            'ID_ETUDIANTS' => $i[0],
            'NUM_CARTE' => $i[1],
            'ID_COURS' => $i[2],
            'CODE_COURS' => $i[3],
        ]);
    } catch (Exception $ex) {
        // ignorer si doublon
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'message' => 'Données générées avec succès pour l\'UFR Math-Info',
    'enseignants' => count($enseignants),
    'etudiants' => count($etudiants),
    'cours' => count($coursList),
    'inscriptions' => count($inscriptions),
    'details' => [
        'Enseignants Mathématiques' => 52,
        'Enseignants Informatique' => 51,
        'Cours Mathématiques' => 10,
        'Cours Informatique' => 10
    ]
], JSON_UNESCAPED_UNICODE);
