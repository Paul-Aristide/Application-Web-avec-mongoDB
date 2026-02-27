# Application Web — Enseignants, Étudiants, Cours, S_INSCRIRE (MySQL)

Application web en temps réel pour gérer la base **universite** (tables ENSEIGNANTS, ETUDIANTS, COURS, S_INSCRIRE).  
**Frontend** : HTML, CSS, Vue.js (sans build). **Backend** : PHP. **Base** : **MySQL** (schéma `universite.sql`).

## Base de données

La structure principale est définie dans **`universite.sql`** (export phpMyAdmin).  
Tables : `enseignants`, `etudiants`, `cours`, `s_inscrire` avec clés primaires (simples ou composites) et contraintes **ON DELETE RESTRICT**.

L’application supporte également une base **MongoDB** optionnelle, qui peut être utilisée pour
les lectures et/ou servir de cache/replica de la base MySQL. Si le serveur Apache/PHP a
l’extension `mongodb` activée et que la bibliothèque officielle (`mongodb/mongodb`) est
installée, la connexion est établie automatiquement dans `api/config.php`. Le nom de la
base Mongo est identique à celui de la base MySQL pour simplifier la configuration.

### Connexion MySQL (api/config.php)

Par défaut :
- **Hôte** : 127.0.0.1  
- **Port** : 3306  
- **Base** : `universite`  
- **Utilisateur** : root  
- **Mot de passe** : (vide)

Variables d’environnement optionnelles : `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

### Connexion MongoDB (optionnel)

L’API tente d’ouvrir une connexion à `mongodb://127.0.0.1:27017` si l’extension est là. Aucune
configuration supplémentaire n’est nécessaire tant que le serveur Mongo tourne sur le même
hôte. Lorsque Mongo est disponible :

- Les lectures (`GET`) sont effectuées contre la collection Mongo correspondante.
- Les écritures (`POST`, `PUT`, `DELETE`) sont d’abord appliquées à MySQL puis répliquées
  automatiquement dans Mongo.
- La page de statistiques (`api/stats.php`) utilise des agrégations Mongo pour renvoyer les
  mêmes indicateurs qu’en SQL.

Cela permet à l’interface de « faire interagir » directement avec Mongo tout en conservant
MySQL comme source de vérité ; il n’est donc pas nécessaire de lancer le daemon `sync_worker.php`.

## Installation

1. Créer la base et importer le schéma :
   - Ouvrir phpMyAdmin (ou MySQL en ligne de commande).
   - Créer une base `universite` (ou le nom choisi).
   - Importer le fichier **`universite.sql`** (CREATE TABLE + contraintes FK).
2. Placer l’application dans `C:\wamp64\www\Application-Web-avec-mongoDB` (ou configurer le virtual host).
3. (Optionnel) Données de démo : ouvrir dans le navigateur  
   `http://localhost/Application-Web-avec-mongoDB/api/seed.php`

## Utilisation

Ouvrir : `http://localhost/Application-Web-avec-mongoDB/`

- **Dashboard** : indicateurs et graphiques (rafraîchissement auto toutes les 5 s).
- **Enseignants / Étudiants / Cours** : CRUD complet.
- **S'inscrire (S_INSCRIRE)** : ajout/suppression d’inscriptions (étudiant + cours).

## Fichiers principaux

| Fichier | Rôle |
|---------|------|
| `universite.sql` | Schéma MySQL (tables, clés, contraintes) |
| `index.html` | Page unique Vue.js (dashboard + CRUD + Inscriptions) |
| `css/style.css` | Styles (thème sombre, formulaires, tableaux) |
| `js/app.js` | Application Vue 3 + Chart.js |
| `api/config.php` | Connexion PDO MySQL (universite), CORS |
| `api/db.php` | Helpers MySQL (find, findOne, insert, update, delete, query) |
| `api/enseignants.php` | CRUD Enseignants (DELETE RESTRICT si cours liés) |
| `api/etudiants.php` | CRUD Étudiants (DELETE RESTRICT si inscriptions) |
| `api/cours.php` | CRUD Cours (DELETE RESTRICT si inscriptions) |
| `api/inscriptions.php` | CRUD S_INSCRIRE (POST, DELETE uniquement) |
| `api/stats.php` | Requêtes SQL pour le dashboard |
| `api/seed.php` | Données de démonstration (ENSEIGNANTS, ETUDIANTS, COURS, S_INSCRIRE) |

## Relations (rappel)

- **ENSEIGNANTS (1) — (0,n) COURS** : FK `cours.ID_ENS` → `enseignants.ID_ENS`, ON DELETE RESTRICT.
- **ETUDIANTS (1) — (0,n) S_INSCRIRE** : FK `s_inscrire.(ID_ETUDIANTS, NUM_CARTE)` → `etudiants`, ON DELETE RESTRICT.
- **COURS (1) — (0,n) S_INSCRIRE** : FK `s_inscrire.(ID_COURS, CODE_COURS)` → `cours`, ON DELETE RESTRICT.

L’API vérifie ces contraintes avant suppression et renvoie une erreur 409 avec un message explicite en cas d’impossibilité.
