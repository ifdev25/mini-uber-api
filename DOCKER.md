# Docker Setup - Mini Uber API

Ce guide explique comment utiliser Docker pour exécuter l'application Mini Uber API.

## Architecture Docker

L'application est composée de 4 services :

- **php** : Backend Symfony (PHP 8.2-FPM)
- **nginx** : Serveur web pour servir l'API
- **database** : PostgreSQL 16
- **mercure** : Hub Mercure pour les mises à jour en temps réel

## Prérequis

- Docker Engine 20.10+
- Docker Compose v2.0+

## Installation et démarrage

### 1. Configuration de l'environnement

Assurez-vous que votre fichier `.env.local` contient les bonnes variables :

```env
DATABASE_URL="postgresql://app:!ChangeMe!@database:5432/app?serverVersion=16&charset=utf8"
MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET='!ChangeThisMercureHubJWTSecretKey!'
```

### 2. Build et démarrage des conteneurs

```bash
# Build les images et démarre les conteneurs
docker compose up -d --build

# Ou simplement pour démarrer (sans rebuild)
docker compose up -d
```

### 3. Installation des dépendances

```bash
# Installer les dépendances Composer
docker compose exec php composer install
```

### 4. Configuration de la base de données

```bash
# Créer la base de données
docker compose exec php php bin/console doctrine:database:create --if-not-exists

# Exécuter les migrations
docker compose exec php php bin/console doctrine:migrations:migrate -n

# (Optionnel) Charger les fixtures
docker compose exec php php bin/console doctrine:fixtures:load -n
```

### 5. Générer les clés JWT

```bash
# Générer les clés JWT pour l'authentification
docker compose exec php php bin/console lexik:jwt:generate-keypair
```

## Accès aux services

- **API** : http://localhost:8080
- **Mercure Hub** : http://localhost:3000
- **PostgreSQL** : localhost:5432
  - Database: app
  - Username: app
  - Password: !ChangeMe!

## Commandes utiles

### Gestion des conteneurs

```bash
# Voir les logs de tous les services
docker compose logs -f

# Voir les logs d'un service spécifique
docker compose logs -f php
docker compose logs -f nginx

# Arrêter les conteneurs
docker compose stop

# Arrêter et supprimer les conteneurs
docker compose down

# Arrêter et supprimer les conteneurs + volumes
docker compose down -v
```

### Commandes Symfony

```bash
# Exécuter une commande Symfony
docker compose exec php php bin/console [command]

# Exemples :
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console debug:router
docker compose exec php php bin/console doctrine:schema:update --dump-sql
```

### Accès au shell

```bash
# Shell dans le conteneur PHP
docker compose exec php sh

# Shell dans le conteneur de base de données
docker compose exec database psql -U app -d app
```

### Debug et développement

```bash
# Reconstruire les images sans cache
docker compose build --no-cache

# Voir les processus en cours
docker compose ps

# Inspecter un conteneur
docker inspect mini-uber-php
```

## Configuration Xdebug

Xdebug est activé en mode développement. Configuration dans `docker/php/xdebug.ini` :

- Port : 9003
- IDE Key : PHPSTORM
- Client Host : host.docker.internal

## Optimisation pour la production

Pour la production, modifiez le `compose.yaml` :

```yaml
php:
  build:
    target: prod  # Utilise le stage de production
  environment:
    APP_ENV: prod
```

## Troubleshooting

### Problème de permissions

```bash
# Fixer les permissions du dossier var/
docker compose exec php chown -R www-data:www-data var/
docker compose exec php chmod -R 775 var/
```

### Réinitialiser complètement

```bash
# Tout supprimer et recommencer
docker compose down -v
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

### Les containers ne démarrent pas

```bash
# Vérifier les logs d'erreur
docker compose logs

# Vérifier que les ports ne sont pas déjà utilisés
netstat -ano | findstr :8080
netstat -ano | findstr :5432
netstat -ano | findstr :3000
```

## Structure des fichiers Docker

```
.
├── Dockerfile                    # Image PHP multi-stage (dev/prod)
├── .dockerignore                 # Fichiers à exclure du build
├── compose.yaml                  # Configuration Docker Compose
├── compose.override.yaml         # Overrides pour le développement
└── docker/
    ├── nginx/
    │   └── default.conf         # Configuration Nginx
    └── php/
        ├── php.ini              # Configuration PHP
        ├── opcache.ini          # Configuration OPcache
        └── xdebug.ini           # Configuration Xdebug
```

## Notes importantes

1. **Volumes** : Le code source est monté en volume pour le développement (`./:/var/www/html:cached`)
2. **Réseau** : Tous les services communiquent via le réseau `mini-uber-network`
3. **Base de données** : Les données sont persistées dans un volume Docker `database_data`
4. **Mode dev** : Par défaut, l'environnement est en mode développement avec Xdebug activé
