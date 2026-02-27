# Script d'Insertion de Données Universitaires

Ce projet contient des scripts pour insérer des données de démonstration dans votre base de données universitaire.

## Fichiers disponibles

### 1. `api/comprehensive_seed.php`
Script principal qui insère :
- **103 enseignants** répartis dans 12 départements
- **3720 étudiants** avec des dates d'inscription réalistes
- **120 cours** (10 cours par département)
- **Inscriptions** des étudiants aux cours avec dates entre le 14/09/2025 et 25/02/2026

### 2. `api/update_database_structure.sql`
Script SQL pour mettre à jour la structure de la base de données :
- Ajoute une colonne `DATE_INSCRIPTION` dans la table `s_inscrire`
- Crée un index pour optimiser les performances

### 3. `api/seed.php`
Script original (limité à 2 départements et 3214 étudiants)

## Départements couverts

1. **Mathématiques** - 8-9 enseignants
2. **Informatique** - 8-9 enseignants
3. **Physique** - 8-9 enseignants
4. **Chimie** - 8-9 enseignants
5. **Biologie** - 8-9 enseignants
6. **Géologie** - 8-9 enseignants
7. **Économie** - 8-9 enseignants
8. **Droit** - 8-9 enseignants
9. **Lettres** - 8-9 enseignants
10. **Histoire** - 8-9 enseignants
11. **Sociologie** - 8-9 enseignants
12. **Psychologie** - 8-9 enseignants

## Instructions d'utilisation

### Étape 1 : Mettre à jour la structure de la base de données
1. Ouvrez phpMyAdmin
2. Sélectionnez votre base de données `universite`
3. Importez ou exécutez le fichier `api/update_database_structure.sql`

### Étape 2 : Exécuter le script d'insertion
1. Assurez-vous que votre serveur web est démarré (WAMP, XAMPP, etc.)
2. Ouvrez votre navigateur web
3. Accédez à l'URL : `http://localhost/Application-Web-avec-mongoDB/api/comprehensive_seed.php`

### Étape 3 : Vérifier les résultats
Le script affichera un JSON avec :
- Le nombre total d'enseignants, étudiants, cours et inscriptions
- Les statistiques détaillées par département
- Un message de confirmation

## Caractéristiques des données

### Enseignants
- **Répartition** : 8-9 enseignants par département
- **Grades** : PR, MCF, DR, AGR, PAST, ATER
- **Spécialités** : 96 spécialités différentes couvertes
- **Emails** : Format `prenom.nom@univ.fr`

### Étudiants
- **Total** : 3720 étudiants
- **Filières** : 48 filières différentes (4 par département)
- **Années d'entrée** : 2020-2025
- **Emails** : Format `prenom.nom@etu.univ.fr`
- **Téléphones** : Numéros maliens (format 07xxxxxxxx)

### Cours
- **Total** : 120 cours (10 par département)
- **Niveaux** : L1, L2, L3, M1, M2
- **Crédits** : 5-8 crédits par cours
- **Description** : Description détaillée pour chaque cours

### Inscriptions
- **Période** : 14 septembre 2025 - 25 février 2026
- **Par étudiant** : 3-6 cours en moyenne
- **Dates réalistes** : Distribution aléatoire sur la période spécifiée

## Structure des données générées

### Exemple d'enseignant
```json
{
  "ID_ENS": 1,
  "NUM_ENS": "ENS0000001",
  "NOM": "Konaté",
  "PRENOM": "Mohamed",
  "EMAIL": "mohamed.konate@univ.fr",
  "DEPARTEMENT": "Mathématiques",
  "GRADE": "PR",
  "SPECIALITE": "Algèbre"
}
```

### Exemple d'étudiant
```json
{
  "ID_ETUDIANTS": 1,
  "NUM_CARTE": "ETU20220001",
  "NOM": "Touré",
  "PRENOM": "Aminata",
  "EMAIL": "aminata.toure@etu.univ.fr",
  "TELEPHONE": "0712345678",
  "FILIERE": "Licence Mathématiques",
  "ANNEE_ENTREE": 2022,
  "DATE_NAISSANCE": "2000-05-15"
}
```

### Exemple de cours
```json
{
  "ID_COURS": 1,
  "CODE_COURS": 101,
  "ID_ENS": 1,
  "INTITULE": "Analyse L1",
  "DESCRIPTION_": "Introduction à l'analyse réelle",
  "NBRE_CREDITS": 6,
  "SEMESTRE": 1,
  "NIVEAU": "L1",
  "DEPARTEMENT": "Mathématiques",
  "PREREQUIS": "Connaissances de base en mathématiques"
}
```

## Notes importantes

1. **Sauvegarde** : Avant d'exécuter ces scripts, pensez à faire une sauvegarde de votre base de données existante
2. **Doublons** : Les scripts ignorent automatiquement les doublons grâce aux blocs try-catch
3. **Performance** : L'insertion de 3720 étudiants et leurs inscriptions peut prendre quelques secondes
4. **Compatibilité** : Le script est compatible avec la structure de base de données définie dans `universite.sql`

## Dépannage

### Problèmes courants
- **Erreur de connexion** : Vérifiez vos paramètres dans `api/config.php`
- **Timeout** : Augmentez le temps d'exécution PHP si nécessaire (max_execution_time)
- **Mémoire** : Assurez-vous d'avoir suffisamment de mémoire PHP disponible

### Support
Si vous rencontrez des problèmes, vérifiez :
1. Que votre serveur web fonctionne correctement
2. Que les fichiers de configuration (`config.php`, `db.php`) sont corrects
3. Que la structure de la base de données correspond à `universite.sql`
