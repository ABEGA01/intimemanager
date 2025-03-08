# Documentation IntimeManager

## Présentation du Projet

IntimeManager est une application web de gestion de stock conçue spécifiquement pour les boutiques de lingerie. Elle permet une gestion complète des articles, des ventes, des fournisseurs et des utilisateurs, avec une interface moderne et intuitive.

## Fonctionnalités Principales

### 1. Gestion des Articles
- Liste complète des articles avec filtres et recherche
- Ajout, modification et suppression d'articles
- Gestion des catégories (réservée aux administrateurs)
- Suivi des stocks avec système d'alerte
- Prix d'achat visible uniquement par les administrateurs

### 2. Gestion des Ventes
- Interface de vente intuitive
- Historique des ventes
- Génération de factures
- Suivi des paiements

### 3. Gestion des Fournisseurs
- Base de données des fournisseurs
- Historique des commandes
- Coordonnées et informations de contact

### 4. Rapports et Statistiques
- Tableaux de bord
- Statistiques de vente
- Analyse des performances
- Rapports personnalisables
- Bénéfices visibles uniquement par les administrateurs

### 5. Gestion des Utilisateurs
- Deux niveaux d'accès : Administrateur et Employé
- Gestion des permissions
- Suivi des actions utilisateurs

## Architecture Technique

### Technologies Utilisées
- Frontend : HTML5, CSS3, JavaScript, Bootstrap 5
- Backend : PHP 7.4+
- Base de données : MySQL 5.7+
- Bibliothèques : 
  - TCPDF pour la génération de PDF
  - Font Awesome pour les icônes
  - Chart.js pour les graphiques

### Structure de la Base de Données
- Tables principales :
  - utilisateur
  - article
  - categorie
  - fournisseur
  - vente
  - detail_vente
  - mouvement_stock
  - journal_action

## Sécurité

### Contrôle d'Accès
- Authentification requise pour toutes les pages
- Sessions sécurisées
- Protection contre la force brute
- Restrictions d'accès basées sur les rôles

### Protection des Données
- Validation des entrées
- Protection contre les injections SQL
- Échappement des sorties HTML
- Gestion sécurisée des mots de passe

## Maintenance

### Sauvegarde
- Sauvegarde quotidienne de la base de données recommandée
- Export régulier des données critiques
- Conservation des logs système

### Mises à Jour
- Vérification régulière des dépendances
- Application des correctifs de sécurité
- Mise à jour des bibliothèques

## Guide de Déploiement

### Prérequis Serveur
- Serveur Web Apache/Nginx
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Extensions PHP requises :
  - PDO
  - PDO_MySQL
  - GD
  - mbstring
  - session

### Installation
1. Configuration du serveur web
2. Création de la base de données
3. Import du schéma SQL
4. Configuration des paramètres de connexion
5. Test des fonctionnalités principales

## Support et Contact

Pour toute assistance technique ou question :
- Email : support@intimemanager.com
- Téléphone : +XX XX XX XX XX
- Horaires : 9h-18h du lundi au vendredi 