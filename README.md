# Ooredoo Gestion Des Equipements

## Overview
Cette application web a été développée dans le cadre de mon stage chez **Ooredoo Tunisie, siège**.  
Elle permet de gérer efficacement les comptes utilisateurs et les équipements, tout en intégrant un module intelligent de prédiction des pannes pour optimiser la maintenance et le suivi du parc matériel.

## Features
- **Gestion des utilisateurs** : création, modification, suppression et consultation des comptes administrateurs et utilisateurs.  
- **Gestion des équipements** : ajout, modification, suppression et suivi de l’état et du statut des équipements.  
- **Prédiction des pannes** : module d’intelligence artificielle pour anticiper les pannes et optimiser la maintenance.  
- **Statistiques** : visualisation claire des performances et de l’état global des équipements.  
- **Paramètres de compte** : gestion des informations personnelles et réinitialisation du mot de passe.

## Tech Stack
### Frontend
- Twig (Symfony templating)
- HTML/CSS/JavaScript

### Backend
- Symfony 6.4 (PHP)
- MySQL

### Other Tools
- GitHub
- Composer
- Visual Studio Code
- phpDesktop
- GitHub

## Directory Structure
/Ooredoo

├── ai/                  # Module d'intelligence artificielle pour la prédiction des pannes

├── assets/              # Fichiers statiques (images, CSS, JS)

├── bin/                 # Scripts exécutables du projet

├── config/              # Configuration du projet Symfony

├── migrations/          # Fichiers de migration de la base de données

├── public/              # Répertoire accessible publiquement (index.php, fichiers web)

├── src/                 # Code source principal (contrôleurs, entités, services)

├── templates/           # Templates Twig pour le rendu des vues

├── tests/               # Tests unitaires et fonctionnels

├── translations/        # Fichiers de traduction

├── var/                 # Fichiers temporaires et cache

├── vendor/              # Dépendances installées via Composer

├── .env                 # Variables d’environnement

├── composer.json        # Définition des dépendances PHP

├── composer.lock        # Version exacte des dépendances installées

├── symfony.lock         # Fichier de verrouillage pour Symfony

├── phpunit.dist.xml     # Configuration PHPUnit pour les tests

├── compose.yaml         # Configuration Docker Compose

├── compose.override.yaml # Overrides Docker Compose

├── importmap.php        # Configuration des assets pour importmap




## Getting Started
1. Cloner le dépôt :  
   `git clone https://github.com/haythem-abdellaoui/Ooredoo-Gestion-Des-Equipements.git`

2. Installer les dépendances :  
   `composer install`

3. Configurer la base de données dans `.env`

4. Lancer le serveur :  
   `symfony server:start`

## Acknowledgments
Ce projet a été réalisé dans le cadre d’un stage chez Ooredoo Tunisie, siège, sous la supervision de l’équipe technique de l’entreprise.

## Topics
symfony, php, python, fastapi, mysql, web-development, smart-maintenance, ooredoo



