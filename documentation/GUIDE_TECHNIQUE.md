# Guide Technique - IntimeManager

## Architecture du Projet

### Structure des Répertoires
```
nouveau_projet/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
│   ├── config.php
│   ├── functions.php
│   └── header.php
├── documentation/
└── [fichiers PHP principaux]
```

### Technologies Utilisées
- Frontend : HTML5, CSS3, JavaScript
- Backend : PHP
- Base de données : MySQL
- Serveur : Apache (XAMPP)

## Configuration du Développement

### Prérequis
1. XAMPP (version 8.0 ou supérieure)
2. PHP 8.0+
3. MySQL 5.7+
4. Éditeur de code (VSCode recommandé)

### Installation Environnement Local
1. Cloner le projet dans htdocs
2. Importer la base de données
3. Configurer config.php
4. Démarrer Apache et MySQL

## Base de Données

### Structure
```sql
-- Structure principale des tables
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50),
    password VARCHAR(255),
    role VARCHAR(20)
);

-- [autres structures de tables]
```

### Relations Clés
- Articles -> Catégories (1:N)
- Ventes -> Articles (N:N)
- Articles -> Fournisseurs (N:1)

## Sécurité

### Authentification
- Hachage des mots de passe (password_hash)
- Sessions PHP sécurisées
- Protection contre les injections SQL

### Autorisations
```php
// Exemple de vérification des droits
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN';
}
```

## API et Points d'Entrée

### Endpoints Principaux
- `/login.php` : Authentification
- `/ListeArticles.php` : Gestion des articles
- `/GestionCategories.php` : Administration des catégories
- `/Rapports.php` : Génération des rapports

### Format des Réponses
```json
{
    "status": "success|error",
    "message": "Description",
    "data": {}
}
```

## Gestion des Erreurs

### Logging
```php
// Exemple d'utilisation du système de log
error_log("Erreur critique : " . $error_message);
```

### Codes d'Erreur
- 1xx : Erreurs d'authentification
- 2xx : Erreurs de données
- 3xx : Erreurs système

## Tests

### Tests Unitaires
- Framework de test recommandé : PHPUnit
- Localisation : `/tests`
- Commande : `phpunit tests/`

### Tests d'Intégration
- Scénarios principaux à tester
- Procédures de validation

## Déploiement

### Procédure
1. Sauvegarde de la base de données
2. Upload des fichiers via FTP
3. Mise à jour de la configuration
4. Tests post-déploiement

### Environnements
- Développement : localhost
- Production : serveur dédié
- Configuration spécifique par environnement

## Maintenance

### Tâches Régulières
- Optimisation de la base de données
- Nettoyage des logs
- Mise à jour des dépendances
- Sauvegarde des données

### Monitoring
- Surveillance des performances
- Logs d'erreurs
- Utilisation des ressources

## Bonnes Pratiques

### Conventions de Code
- PSR-12 pour PHP
- Commentaires en français
- Documentation des fonctions
- Nommage explicite des variables

### Gestion de Version
- Branches : main, develop, feature/*
- Commits descriptifs
- Pull requests documentées

## Dépendances

### Bibliothèques Frontend
- Bootstrap 5.x
- jQuery 3.x
- DataTables
- Chart.js

### Bibliothèques Backend
- PHPMailer
- FPDF
- PHP-JWT

## Contribution

### Processus
1. Fork du projet
2. Création de branche
3. Développement
4. Tests
5. Pull request

### Guidelines
- Tests requis
- Documentation à jour
- Code review obligatoire
- Respect des conventions

## Support

### Ressources
- Documentation : `/documentation`
- Wiki interne
- Base de connaissances

### Contact
- Email développement : dev@intimemanager.com
- Système de tickets
- Chat développeurs 