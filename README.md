# Blogy — Résumé

Blogy est une application de blog développée avec Symfony (micro-kernel). Elle offre un CRUD pour les articles, la gestion des utilisateurs, des commentaires et des likes.

## Points clés
- Authentification utilisateur (entité User implémente UserInterface)
- Articles : création, lecture, modification, suppression
- Commentaires et likes (un like par utilisateur/article grâce à une contrainte d'unicité)
- Entités Doctrine avec timestamps automatiques
- Templates Twig + Stimulus JS pour des interactions front simples

## Stack
- PHP + Symfony 7.4
- Doctrine ORM 3.5
- Twig
- PostgreSQL (Docker — postgres:16-alpine)
- Stimulus, AssetMapper
- Tests : PHPUnit

Langages principaux : PHP, Twig, JavaScript.

## Démarrage rapide (dev)
1. Cloner :
   git clone https://github.com/Ilyass-FIRAR/Blogy.git && cd Blogy
2. Installer dépendances PHP :
   composer install
3. Lancer PostgreSQL :
   docker compose up -d
4. Configurer `.env.local` (DATABASE_URL, APP_SECRET...)
5. Créer la BDD et appliquer les migrations :
   bin/console doctrine:database:create
   bin/console doctrine:migrations:migrate
6. Lancer le serveur Symfony :
   symfony server:start  (ou php -S 127.0.0.1:8000 -t public)

## Tests
Exécuter :
APP_ENV=test bin/phpunit

## Conseils rapides
- Après modification d'entités : `bin/console make:migration` puis `bin/console doctrine:migrations:migrate`
- Vider le cache si besoin : `bin/console cache:clear`
- Activer provider d'entité dans `config/packages/security.yaml` pour utiliser la table User en production

## Contribuer
Fork → branche feature → PR. Inclure migrations et instructions si la DB change.

## Licence
Voir le dépôt pour les détails de licence (si présent).

````