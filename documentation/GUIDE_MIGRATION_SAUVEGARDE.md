# Guide de Migration et Sauvegarde - IntimeManager

## Sauvegarde des Données

### Sauvegarde de la Base de Données

#### Sauvegarde Manuelle
```bash
# Sauvegarde complète
mysqldump -u [utilisateur] -p [base_de_donnees] > backup_[date].sql

# Sauvegarde avec compression
mysqldump -u [utilisateur] -p [base_de_donnees] | gzip > backup_[date].sql.gz

# Sauvegarde de tables spécifiques
mysqldump -u [utilisateur] -p [base_de_donnees] [table1] [table2] > backup_tables_[date].sql
```

#### Sauvegarde Automatique

1. Script de Sauvegarde (save_db.sh)
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/chemin/vers/backups"
DB_USER="votre_utilisateur"
DB_NAME="votre_base"

# Création du répertoire de sauvegarde
mkdir -p $BACKUP_DIR

# Sauvegarde de la base
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Nettoyage des anciennes sauvegardes (garde 30 jours)
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete
```

2. Configuration Cron
```bash
# Éditer crontab
crontab -e

# Ajouter la ligne pour une sauvegarde quotidienne à 3h du matin
0 3 * * * /chemin/vers/save_db.sh
```

### Sauvegarde des Fichiers

#### Structure des Fichiers à Sauvegarder
```
intimemanager/
├── config/
├── images/
├── uploads/
└── custom/
```

#### Méthodes de Sauvegarde

1. Archive Complète
```bash
# Création d'une archive tar
tar -czf backup_files_[date].tar.gz /chemin/vers/intimemanager/

# Avec exclusion des fichiers temporaires
tar -czf backup_files_[date].tar.gz --exclude='*/tmp/*' /chemin/vers/intimemanager/
```

2. Sauvegarde Incrémentale
```bash
# Utilisation de rsync
rsync -av --delete /source/ /destination/
```

## Migration des Données

### Préparation de la Migration

1. Vérification Préalable
```bash
# Vérification de l'espace disque
df -h

# Vérification de la taille de la base
mysql -u root -p -e "SELECT table_schema, 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' 
    FROM information_schema.tables 
    GROUP BY table_schema;"
```

2. Liste de Contrôle
- [ ] Sauvegarde complète effectuée
- [ ] Espace disque suffisant
- [ ] Versions PHP compatibles
- [ ] Extensions PHP requises
- [ ] Permissions utilisateur

### Migration de la Base de Données

#### Migration Simple
```bash
# Export de l'ancienne base
mysqldump -u [user] -p [old_db] > migration.sql

# Import dans la nouvelle base
mysql -u [user] -p [new_db] < migration.sql
```

#### Migration avec Transformation
```php
<?php
// Script de transformation des données
function transformData($oldData) {
    // Logique de transformation
    $newData = // transformation
    return $newData;
}

// Exemple de migration avec transformation
$query = "SELECT * FROM old_table";
$result = $oldDb->query($query);

while ($row = $result->fetch_assoc()) {
    $transformedData = transformData($row);
    // Insertion dans la nouvelle base
    $newDb->insert('new_table', $transformedData);
}
?>
```

### Migration des Fichiers

#### Transfert Direct
```bash
# Copie avec préservation des attributs
cp -rp /ancien/chemin/* /nouveau/chemin/

# Utilisation de rsync
rsync -avz /ancien/chemin/ /nouveau/chemin/
```

#### Migration Progressive
1. Fichiers Statiques
```bash
# Copie des fichiers statiques
rsync -av --include='*.jpg' --include='*.png' --include='*.pdf' --exclude='*' /source/ /destination/
```

2. Fichiers de Configuration
```bash
# Copie et adaptation des fichiers de configuration
cp /source/config.php /destination/
sed -i 's/ancien_host/nouveau_host/g' /destination/config.php
```

## Restauration des Données

### Restauration de la Base de Données

1. Restauration Complète
```bash
mysql -u [user] -p [database] < backup.sql
```

2. Restauration Sélective
```bash
# Restauration de tables spécifiques
mysql -u [user] -p [database] < backup_tables.sql
```

### Restauration des Fichiers

1. Depuis une Archive
```bash
# Décompression de l'archive
tar -xzf backup_files.tar.gz -C /chemin/destination/

# Restauration des permissions
chown -R www-data:www-data /chemin/destination/
chmod -R 755 /chemin/destination/
```

2. Vérification Post-Restauration
```bash
# Vérification des fichiers
find /chemin/destination/ -type f -exec md5sum {} \; > checksum.txt
diff checksum.txt checksum_original.txt
```

## Maintenance des Sauvegardes

### Rotation des Sauvegardes
```bash
#!/bin/bash
# Script de rotation des sauvegardes

# Garder 7 sauvegardes quotidiennes
find /backups/daily -mtime +7 -delete

# Garder 4 sauvegardes hebdomadaires
find /backups/weekly -mtime +28 -delete

# Garder 12 sauvegardes mensuelles
find /backups/monthly -mtime +365 -delete
```

### Vérification des Sauvegardes
```bash
#!/bin/bash
# Script de vérification

# Vérification de l'intégrité de la base
mysqlcheck -u [user] -p [database]

# Test de restauration dans un environnement de test
mysql -u [user] -p [test_database] < backup.sql

# Vérification des fichiers
find /backups -type f -name "*.sql.gz" -exec gunzip -t {} \;
```

## Procédures d'Urgence

### Récupération Rapide
1. Base de données
```bash
# Restauration de la dernière sauvegarde
gunzip < latest_backup.sql.gz | mysql -u [user] -p [database]
```

2. Fichiers
```bash
# Restauration rapide des fichiers critiques
rsync -av --include='config/*' --include='data/*' --exclude='*' /backup/ /production/
```

### Contact Support
- Urgence 24/7 : +XX XX XX XX XX
- Email : support@intimemanager.com
- Documentation : /documentation/urgence 