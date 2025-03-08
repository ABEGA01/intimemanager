# Guide de Mise à Jour - IntimeManager

## Table des Matières
1. [Procédure Standard de Mise à Jour](#procédure-standard-de-mise-à-jour)
2. [Sauvegarde des Données](#sauvegarde-des-données)
3. [Mise à Jour de la Base de Données](#mise-à-jour-de-la-base-de-données)
4. [Résolution des Problèmes](#résolution-des-problèmes)

## Procédure Standard de Mise à Jour

1. **Préparation**
   - Arrêtez temporairement l'accès à l'application
   - Effectuez une sauvegarde complète
   - Notez la version actuelle de l'application

2. **Installation de la Mise à Jour**
   - Téléchargez le package de mise à jour depuis le dépôt officiel
   - Décompressez les fichiers dans un dossier temporaire
   - Vérifiez l'intégrité des fichiers avec la somme de contrôle fournie

3. **Application de la Mise à Jour**
   - Copiez les nouveaux fichiers vers le répertoire de l'application
   - Appliquez les modifications de la base de données si nécessaire
   - Mettez à jour les dépendances si requis

4. **Vérification**
   - Vérifiez que l'application démarre correctement
   - Testez les fonctionnalités principales
   - Vérifiez les logs pour détecter d'éventuelles erreurs

## Sauvegarde des Données

1. **Base de Données**
   ```sql
   mysqldump -u [utilisateur] -p [nom_base] > backup_[date].sql
   ```

2. **Fichiers de l'Application**
   ```bash
   tar -czf backup_files_[date].tar.gz /chemin/vers/application
   ```

## Mise à Jour de la Base de Données

1. **Vérification des Scripts**
   - Consultez le fichier `database/migrations/` pour les nouveaux scripts
   - Vérifiez la version actuelle dans la table `schema_versions`

2. **Application des Migrations**
   - Exécutez les scripts de migration dans l'ordre
   - Vérifiez les logs après chaque migration

## Résolution des Problèmes

1. **Erreurs Courantes**
   - Problèmes de permissions : vérifiez les droits des fichiers
   - Erreurs de base de données : consultez les logs MySQL
   - Problèmes de cache : videz le cache de l'application

2. **Restauration**
   - En cas d'échec, utilisez les sauvegardes pour revenir à l'état précédent
   - Restaurez la base de données : `mysql -u [user] -p [database] < backup.sql`
   - Restaurez les fichiers depuis la sauvegarde

## Notes Importantes
- Effectuez toujours une sauvegarde avant la mise à jour
- Testez la mise à jour dans un environnement de développement
- Documentez toutes les modifications apportées
- Conservez les anciennes versions des fichiers pendant 30 jours 