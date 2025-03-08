# Guide de Sécurité - IntimeManager

## Sécurité de l'Application

### Authentification

#### Configuration du Système d'Authentification
```php
// Exemple de configuration sécurisée
define('PASSWORD_MIN_LENGTH', 12);
define('PASSWORD_REQUIRES_SPECIAL', true);
define('PASSWORD_REQUIRES_NUMBERS', true);
define('PASSWORD_REQUIRES_MIXED_CASE', true);
define('SESSION_LIFETIME', 3600); // 1 heure
```

#### Bonnes Pratiques
1. Hachage des Mots de Passe
```php
// Utilisation de password_hash
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Vérification
if (password_verify($password, $hashedPassword)) {
    // Authentification réussie
}
```

2. Protection contre la Force Brute
```php
// Exemple de limitation des tentatives
function checkLoginAttempts($username) {
    $attempts = // Récupérer le nombre de tentatives
    if ($attempts > 5) {
        // Bloquer temporairement le compte
        return false;
    }
    return true;
}
```

### Sessions

#### Configuration des Sessions
```php
// Configuration php.ini recommandée
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
session.gc_maxlifetime = 3600
session.use_strict_mode = 1
```

#### Gestion des Sessions
```php
// Régénération de l'ID de session
session_regenerate_id(true);

// Nettoyage à la déconnexion
session_destroy();
session_unset();
```

### Protection contre les Attaques

#### XSS (Cross-Site Scripting)
```php
// Échappement des sorties
function safeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// En-têtes de sécurité
header("Content-Security-Policy: default-src 'self'");
header("X-XSS-Protection: 1; mode=block");
```

#### CSRF (Cross-Site Request Forgery)
```php
// Génération de token CSRF
function generateCSRFToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

// Vérification
function verifyCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}
```

#### SQL Injection
```php
// Utilisation de requêtes préparées
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// Validation des entrées
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}
```

## Sécurité du Serveur

### Configuration Apache

#### Fichier .htaccess Sécurisé
```apache
# Protection des fichiers sensibles
<FilesMatch "^(config\.php|\.htaccess|\.git)">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# En-têtes de sécurité
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set Strict-Transport-Security "max-age=31536000"

# Désactivation de l'affichage du répertoire
Options -Indexes

# Redirection HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Configuration PHP

#### php.ini Sécurisé
```ini
; Désactivation des fonctions dangereuses
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Configuration des erreurs
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; Limites d'upload
upload_max_filesize = 2M
max_file_uploads = 5
```

### Permissions Fichiers

#### Structure des Permissions
```bash
# Répertoires
find /var/www/html/intimemanager -type d -exec chmod 755 {} \;

# Fichiers
find /var/www/html/intimemanager -type f -exec chmod 644 {} \;

# Fichiers spéciaux
chmod 400 config.php
chmod 600 logs/
```

## Sécurité des Données

### Chiffrement

#### Configuration du Chiffrement
```php
// Clés de chiffrement
define('ENCRYPTION_KEY', getenv('APP_ENCRYPTION_KEY'));
define('ENCRYPTION_METHOD', 'aes-256-gcm');

// Fonction de chiffrement
function encryptData($data) {
    $key = base64_decode(ENCRYPTION_KEY);
    $iv = random_bytes(12);
    $tag = '';
    
    $encrypted = openssl_encrypt(
        $data,
        ENCRYPTION_METHOD,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    
    return base64_encode($iv . $tag . $encrypted);
}
```

### Sauvegarde Sécurisée

#### Chiffrement des Sauvegardes
```bash
# Chiffrement des sauvegardes
openssl enc -aes-256-cbc -salt -in backup.sql -out backup.sql.enc

# Déchiffrement
openssl enc -d -aes-256-cbc -in backup.sql.enc -out backup.sql
```

## Audit et Surveillance

### Journalisation

#### Configuration des Logs
```php
// Logger personnalisé
class SecurityLogger {
    public static function log($event, $level = 'INFO') {
        $logEntry = date('Y-m-d H:i:s') . " [$level] $event\n";
        file_put_contents(
            'logs/security.log',
            $logEntry,
            FILE_APPEND
        );
    }
}

// Exemple d'utilisation
SecurityLogger::log("Tentative de connexion échouée: $username", 'WARNING');
```

### Monitoring

#### Scripts de Surveillance
```bash
#!/bin/bash
# Surveillance des fichiers modifiés
find /var/www/html/intimemanager -type f -mtime -1 -ls > /var/log/file_changes.log

# Surveillance des connexions
grep "Login attempt" /var/log/security.log | mail -s "Daily Security Report" admin@example.com
```

## Réponse aux Incidents

### Plan d'Action

1. Détection
```php
// Exemple de détection d'intrusion
function detectIntrusion($request) {
    if (containsSuspiciousPatterns($request)) {
        SecurityLogger::log("Intrusion détectée", 'ALERT');
        notifyAdmin();
        return true;
    }
    return false;
}
```

2. Confinement
```php
// Blocage temporaire d'IP
function blockIP($ip) {
    // Ajout à la liste noire
    file_put_contents('blocked_ips.txt', "$ip\n", FILE_APPEND);
    
    // Mise à jour des règles du pare-feu
    exec("iptables -A INPUT -s $ip -j DROP");
}
```

3. Éradication
- Analyse des logs
- Correction des vulnérabilités
- Mise à jour des systèmes

4. Récupération
- Restauration des sauvegardes propres
- Réinitialisation des mots de passe
- Mise à jour des configurations

### Documentation des Incidents

#### Template de Rapport
```markdown
# Rapport d'Incident de Sécurité

## Informations Générales
- Date et heure de détection :
- Type d'incident :
- Systèmes affectés :

## Analyse
- Description de l'incident :
- Impact :
- Cause probable :

## Actions Prises
1. Détection :
2. Confinement :
3. Éradication :
4. Récupération :

## Recommandations
- Mesures préventives :
- Améliorations suggérées :
```

## Contact Urgence

### Équipe de Sécurité
- Responsable Sécurité : +XX XX XX XX XX
- Email : security@intimemanager.com
- Astreinte 24/7 : +XX XX XX XX XX

### Ressources Externes
- CERT : [contact]
- Support Hébergeur : [contact]
- Autorités compétentes : [contact] 