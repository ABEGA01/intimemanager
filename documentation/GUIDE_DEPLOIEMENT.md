# Guide de Déploiement - IntimeManager

## Déploiement Local

### Configuration Minimale Requise

#### Systèmes d'Exploitation Supportés
- Windows :
  - Windows 7, 8, 8.1, 10, 11 (32 et 64 bits)
  - Windows Server 2012, 2016, 2019, 2022
- macOS :
  - macOS High Sierra (10.13) et versions ultérieures
- Linux :
  - Ubuntu 18.04 LTS et versions ultérieures
  - Debian 9 et versions ultérieures
  - CentOS 7 et versions ultérieures
  - Red Hat Enterprise Linux 7 et versions ultérieures

#### Navigateurs Supportés
- Google Chrome 60+
- Mozilla Firefox 60+
- Microsoft Edge 79+
- Safari 11+
- Opera 47+

#### Configuration Matérielle Minimale
- Processeur : 2 GHz dual-core
- RAM : 4 Go minimum
- Espace disque : 500 Mo pour l'application
- Résolution d'écran : 1280x720 minimum

### Installation sur Windows

1. Installation de XAMPP
   ```bash
   - Télécharger XAMPP depuis https://www.apachefriends.org/
   - Version recommandée : 8.0 ou supérieure
   - Exécuter l'installateur
   - Sélectionner au minimum Apache, MySQL, PHP
   ```

2. Configuration de XAMPP
   ```bash
   - Démarrer le Control Panel
   - Activer Apache et MySQL
   - Vérifier les ports (80 et 443 pour Apache, 3306 pour MySQL)
   ```

3. Installation de l'Application
   ```bash
   - Copier les fichiers dans C:\xampp\htdocs\[nom_dossier]
   - Importer la base de données via phpMyAdmin
   - Configurer config.php avec les paramètres locaux
   ```

### Installation sur macOS

1. Installation de MAMP
   ```bash
   - Télécharger MAMP depuis https://www.mamp.info/
   - Installer MAMP (version gratuite suffisante)
   - Lancer MAMP et démarrer les serveurs
   ```

2. Configuration
   ```bash
   - Définir le document root
   - Vérifier les ports
   - Configurer PHP (version 8.0+)
   ```

3. Déploiement
   ```bash
   - Copier les fichiers dans /Applications/MAMP/htdocs/[nom_dossier]
   - Importer la base de données
   - Adapter config.php
   ```

### Installation sur Linux

1. Installation des Prérequis
   ```bash
   sudo apt update
   sudo apt install apache2 php mysql-server php-mysql
   sudo apt install php-curl php-gd php-mbstring php-xml
   ```

2. Configuration Apache
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. Déploiement
   ```bash
   - Copier les fichiers dans /var/www/html/[nom_dossier]
   - Configurer les permissions
   - Importer la base de données
   ```

## Déploiement sur Serveur Web

### Hébergement Mutualisé

1. Prérequis
   - Hébergeur supportant PHP 8.0+
   - Base de données MySQL 5.7+
   - Extension PHP requises activées
   - SSL/HTTPS disponible

2. Procédure de Déploiement
   ```bash
   - Créer la base de données via le panel d'hébergement
   - Uploader les fichiers via FTP
   - Configurer les DNS si nécessaire
   - Adapter config.php avec les paramètres de production
   ```

3. Configuration Sécurité
   ```apache
   - Activer HTTPS
   - Configurer les en-têtes de sécurité
   - Protéger les fichiers sensibles
   ```

### Serveur VPS/Dédié

1. Préparation du Serveur
   ```bash
   # Mise à jour du système
   sudo apt update && sudo apt upgrade

   # Installation des packages
   sudo apt install apache2 php mysql-server
   sudo apt install php-mysql php-curl php-gd
   ```

2. Configuration Apache
   ```apache
   <VirtualHost *:80>
       ServerName votre-domaine.com
       DocumentRoot /var/www/html/intimemanager
       
       <Directory /var/www/html/intimemanager>
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/error.log
       CustomLog ${APACHE_LOG_DIR}/access.log combined
   </VirtualHost>
   ```

3. Configuration SSL
   ```bash
   # Installation Certbot
   sudo apt install certbot python3-certbot-apache
   
   # Obtention certificat SSL
   sudo certbot --apache -d votre-domaine.com
   ```

### Configuration pour Accès à Distance

1. Sécurité
   ```apache
   # Configuration .htaccess
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{HTTPS} off
       RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   </IfModule>
   ```

2. Optimisation Performance
   ```apache
   # Activation cache
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType image/jpg "access plus 1 year"
       ExpiresByType image/jpeg "access plus 1 year"
       ExpiresByType image/png "access plus 1 year"
       ExpiresByType text/css "access plus 1 month"
       ExpiresByType application/javascript "access plus 1 month"
   </IfModule>
   ```

3. Configuration Base de Données
   ```php
   // config.php en production
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'nom_base_production');
   define('DB_USER', 'utilisateur_production');
   define('DB_PASS', 'mot_de_passe_securise');
   ```

## Maintenance Post-Déploiement

### Sauvegardes
```bash
# Sauvegarde base de données
mysqldump -u [user] -p [database] > backup.sql

# Sauvegarde fichiers
tar -czf backup.tar.gz /var/www/html/intimemanager
```

### Monitoring
- Mise en place de surveillance serveur
- Monitoring des performances PHP
- Surveillance des logs d'erreur
- Alertes en cas de problème

### Mises à Jour
1. Préparation
   - Sauvegarde complète
   - Notification aux utilisateurs
   - Maintenance mode ON

2. Procédure
   ```bash
   - Déploiement des nouveaux fichiers
   - Mise à jour base de données
   - Tests complets
   - Maintenance mode OFF
   ```

## Résolution des Problèmes Courants

### Problèmes de Connexion
1. Vérifier les logs Apache/PHP
2. Tester la connexion base de données
3. Vérifier les permissions fichiers
4. Contrôler la configuration PHP

### Problèmes de Performance
1. Optimisation MySQL
2. Configuration cache
3. Compression des assets
4. Analyse des logs de performance

### Support Technique
- Email : support@intimemanager.com
- Urgence : +XX XX XX XX XX
- Documentation : /documentation
- Wiki technique : [lien] 