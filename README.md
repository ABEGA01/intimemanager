# IntimeManager

## À propos
IntimeManager est une application web de gestion de stock conçue spécifiquement pour les boutiques de lingerie. Elle permet une gestion complète des articles, des ventes, des fournisseurs et des utilisateurs.

## Fonctionnalités

- 👕 **Gestion des Articles**
  - Inventaire complet
  - Catégorisation
  - Alertes de stock
  - Prix et marges

- 💰 **Gestion des Ventes**
  - Interface de vente intuitive
  - Historique des transactions
  - Génération de factures
  - Suivi des paiements

- 🏢 **Gestion des Fournisseurs**
  - Base de données fournisseurs
  - Historique des commandes
  - Coordonnées et contacts

- 📊 **Rapports et Statistiques**
  - Tableaux de bord
  - Analyses des ventes
  - Suivi des performances
  - Rapports personnalisables

## Prérequis

- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx
- Extensions PHP :
  - PDO
  - MySQL
  - GD
  - mbstring

## Installation

1. Cloner le repository
```bash
git clone https://github.com/votre-username/intimemanager.git
```

2. Configurer la base de données
```bash
# Importer le schéma SQL
mysql -u root -p < database/schema.sql
```

3. Configurer l'application
```bash
# Copier le fichier de configuration
cp config.example.php config.php

# Éditer avec vos paramètres
nano config.php
```

4. Configurer les permissions
```bash
chmod 755 -R /chemin/vers/intimemanager
chmod 777 -R /chemin/vers/intimemanager/uploads
```

## Structure du Projet

```
intimemanager/
├── assets/          # Ressources statiques (CSS, JS, images)
├── includes/        # Fichiers d'inclusion PHP
├── documentation/   # Documentation du projet
├── uploads/         # Fichiers uploadés
└── vendor/          # Dépendances
```

## Sécurité

- Authentification requise
- Gestion des rôles (Admin/Employé)
- Protection contre les injections SQL
- Sessions sécurisées
- Validation des entrées

## Documentation

La documentation complète est disponible dans le dossier `documentation/` :
- Guide Utilisateur
- Guide Administrateur
- Guide Technique
- Guide de Déploiement
- Guide de Sécurité
- Guide de Migration

## Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## Licence

Ce projet est sous licence [MIT](LICENSE)

## Support

Pour toute assistance :
- 📧 Email : support@intimemanager.com
- 📞 Téléphone : +XX XX XX XX XX
- 📚 Wiki : [lien]

## Auteurs

- [Votre Nom](https://github.com/votre-username)

## Remerciements

- Tous les contributeurs qui participent à ce projet
- La communauté open source pour ses outils et bibliothèques 