# 🚖 Mini Uber API - Documentation Complète

API REST moderne pour une application de covoiturage type Uber, construite avec **Symfony 7.3** et **API Platform 4.2**.

---

## 📋 Table des matières

1. [Prérequis](#-prérequis)
2. [Installation complète](#-installation-complète)
3. [Configuration](#-configuration)
4. [Base de données et migrations](#-base-de-données-et-migrations)
5. [Authentification JWT](#-authentification-jwt)
6. [Notifications temps réel (Mercure)](#-notifications-temps-réel-mercure)
7. [Fixtures (données de test)](#-fixtures-données-de-test)
8. [Tests](#-tests)
9. [Documentation API](#-documentation-api)
10. [Endpoints disponibles](#-endpoints-disponibles)
11. [Déploiement](#-déploiement)
12. [Troubleshooting](#-troubleshooting)

---

## 🔧 Prérequis

### Versions requises

| Composant | Version minimale | Version recommandée |
|-----------|-----------------|---------------------|
| **PHP** | 8.2.0 | 8.3.x |
| **Composer** | 2.0 | 2.7.x |
| **Symfony CLI** | - | 5.x (optionnel) |
| **PostgreSQL** | 14 | 16 |
| **Docker Desktop** | 20.10 | Dernière |

### Extensions PHP requises

```bash
# Vérifier les extensions installées
php -m

# Extensions nécessaires :
- ctype
- iconv
- pdo_pgsql
- intl
- mbstring
- xml
- curl
- openssl
- tokenizer
- json
```

### Installation des prérequis

#### Windows

```powershell
# Télécharger et installer :
# - PHP 8.3 : https://windows.php.net/download/
# - Composer : https://getcomposer.org/download/
# - Docker Desktop : https://www.docker.com/products/docker-desktop/
# - Symfony CLI (optionnel) : https://symfony.com/download
```

#### macOS

```bash
# Avec Homebrew
brew install php@8.3
brew install composer
brew install --cask docker
brew install symfony-cli/tap/symfony-cli
```

#### Linux (Ubuntu/Debian)

```bash
# PHP 8.3
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.3 php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-intl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

---

## 📦 Installation complète

### 1. Cloner le projet

```bash
git clone https://github.com/ifdev25/mini-uber-api.git
cd mini-uber-api
```

### 2. Installer les dépendances PHP

```bash
# Installer toutes les dépendances
composer install

# Si problème de certificat SSL (Windows avec Avast) :
composer config -g -- disable-tls false
composer config -g -- secure-http false
composer install
```

**Dépendances principales installées :**

| Package | Version | Description |
|---------|---------|-------------|
| `symfony/framework-bundle` | 7.3.* | Framework Symfony |
| `api-platform/symfony` | ^4.2 | API Platform |
| `doctrine/orm` | ^3.5 | ORM pour base de données |
| `doctrine/doctrine-migrations-bundle` | ^3.6 | Migrations DB |
| `lexik/jwt-authentication-bundle` | * | Authentification JWT |
| `symfony/mercure-bundle` | ^0.3.9 | Notifications temps réel |
| `nelmio/cors-bundle` | ^2.6 | Gestion CORS |

**Dépendances de développement :**

| Package | Version | Description |
|---------|---------|-------------|
| `symfony/maker-bundle` | ^1.64 | Générateurs de code |
| `doctrine/doctrine-fixtures-bundle` | ^4.3 | Fixtures (données de test) |
| `symfony/phpunit-bridge` | * | Tests unitaires |

---

## ⚙️ Configuration

### 1. Configurer les variables d'environnement

```bash
# Copier le fichier .env
cp .env .env.local

# Éditer .env.local
nano .env.local
```

### 2. Configuration de base (.env.local)

```env
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=votre-secret-aleatoire-32-caracteres
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
# Format : postgresql://user:password@host:port/database?serverVersion=version&charset=utf8
# Le port 65300 correspond au mapping Docker (voir compose.yaml)
DATABASE_URL="postgresql://app:!ChangeMe!@localhost:65300/app?serverVersion=16&charset=utf8"
###< doctrine/doctrine-bundle ###

###> nelmio/cors-bundle ###
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
###< nelmio/cors-bundle ###

###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre-passphrase-jwt-securisee
###< lexik/jwt-authentication-bundle ###

###> symfony/mercure-bundle ###
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"
###< symfony/mercure-bundle ###
```

---

## 🗄️ Base de données et migrations

### 1. Démarrer PostgreSQL avec Docker

```bash
# Démarrer les services Docker (PostgreSQL + Mercure)
docker compose up -d

# Vérifier que les services sont lancés
docker compose ps

# Logs PostgreSQL
docker compose logs database

# Logs Mercure
docker compose logs mercure
```

**Services Docker disponibles :**
- PostgreSQL : `localhost:5432`
- Mercure Hub : `localhost:3000`

### 2. Créer la base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Vérifier la connexion
php bin/console doctrine:database:create --if-not-exists
```

### 3. Exécuter les migrations

```bash
# Voir le statut des migrations
php bin/console doctrine:migrations:status

# Exécuter toutes les migrations
php bin/console doctrine:migrations:migrate

# Ou sans confirmation
php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Créer une nouvelle migration (si nécessaire)

```bash
# Générer une migration automatiquement
php bin/console make:migration

# Exécuter la nouvelle migration
php bin/console doctrine:migrations:migrate
```

---

## 🔐 Authentification JWT

### 1. Générer les clés JWT

```bash
# Créer le dossier config/jwt s'il n'existe pas
mkdir -p config/jwt

# Générer la paire de clés
php bin/console lexik:jwt:generate-keypair

# Si vous devez régénérer les clés
php bin/console lexik:jwt:generate-keypair --overwrite
```

**Structure des clés :**
```
config/jwt/
├── private.pem  (clé privée - à ne JAMAIS committer)
└── public.pem   (clé publique)
```

### 2. Tester l'authentification

```bash
# S'inscrire
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstname": "John",
    "lastname": "Doe",
    "phone": "+33612345678",
    "usertype": "passenger"
  }'

# Se connecter
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# Réponse attendue :
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 3. Utiliser le token JWT

```bash
# Utiliser le token dans les requêtes protégées
curl http://localhost:8000/api/rides \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

## 📡 Notifications temps réel (Mercure)

### 1. Vérifier que Mercure est lancé

```bash
# Vérifier le statut
docker compose ps mercure

# Si arrêté, démarrer Mercure
docker compose up -d mercure

# Logs en temps réel
docker compose logs -f mercure
```

### 2. Configuration Mercure

Le hub Mercure est accessible à : `http://localhost:3000/.well-known/mercure`

**Topics disponibles :**
- `drivers/{driverId}` - Notifications pour un chauffeur
- `users/{userId}` - Notifications pour un passager
- `drivers/{driverId}/location` - Mises à jour de position

### 3. Tester Mercure (exemple JavaScript)

```javascript
// Frontend - S'abonner aux notifications
const eventSource = new EventSource(
  'http://localhost:3000/.well-known/mercure?topic=users/1'
);

eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log('Notification reçue:', data);

  // Exemples de types :
  // - ride_accepted : Un chauffeur a accepté
  // - ride_started : La course a démarré
  // - ride_completed : La course est terminée
  // - new_ride : Nouvelle course (pour drivers)
};
```

---

## 🎭 Fixtures (données de test)

### 1. Charger les fixtures

```bash
# Charger les fixtures (écrase les données existantes)
php bin/console doctrine:fixtures:load

# Sans confirmation
php bin/console doctrine:fixtures:load --no-interaction

# Avec purge via TRUNCATE (plus rapide)
php bin/console doctrine:fixtures:load --purge-with-truncate
```

### 2. Comptes créés par les fixtures

| Type | Email | Mot de passe | Détails |
|------|-------|--------------|---------|
| **Admin** | admin@miniuber.com | admin123 | Accès administrateur |
| **Passager** | john.doe@email.com | password123 | Passager avec 15 courses |
| **Driver 1** | marie.martin@driver.com | driver123 | Tesla Model 3, Disponible ✅ |
| **Driver 2** | pierre.dubois@driver.com | driver123 | Peugeot 508, En course ⏳ |

**3 courses d'exemple :**
- ✅ Terminée : Gare du Nord → Tour Eiffel
- 🚗 En cours : République → Montmartre
- ⏳ En attente : Opéra → Gare de Lyon

Voir [FIXTURES.md](FIXTURES.md) pour plus de détails.

---

## 🧪 Tests

### 1. Installation PHPUnit

```bash
# PHPUnit est déjà inclus via symfony/phpunit-bridge
# Première exécution : télécharge PHPUnit
php bin/phpunit
```

### 2. Exécuter les tests

```bash
# Tous les tests
php bin/phpunit

# Tests unitaires uniquement
php bin/phpunit tests/Unit

# Tests fonctionnels uniquement
php bin/phpunit tests/Functional

# Avec coverage (nécessite Xdebug)
php bin/phpunit --coverage-html coverage/

# Un fichier spécifique
php bin/phpunit tests/Unit/Service/PricingServiceTest.php
```

### 3. Tests disponibles

**Tests unitaires :**
- `PricingServiceTest` : Calcul de prix et distances
- `NotificationServiceTest` : Notifications Mercure

**Tests fonctionnels :**
- `RideApiTest` : Endpoints de l'API

---

## 📚 Documentation API

### URLs de la documentation

| Documentation | URL | Description |
|---------------|-----|-------------|
| **API Platform UI** | http://localhost:8000/api | Interface interactive |
| **Swagger UI** | http://localhost:8000/api/docs | Documentation Swagger |
| **OpenAPI JSON** | http://localhost:8000/api/docs.json | Spec OpenAPI 3.0 |
| **JSON-LD Context** | http://localhost:8000/api/contexts/* | Contextes JSON-LD |

### Accéder à la documentation

```bash
# Démarrer le serveur
symfony server:start
# ou
php -S localhost:8000 -t public/

# Ouvrir le navigateur
open http://localhost:8000/api
```

### Tester l'API avec Swagger

L'API utilise l'authentification JWT. Pour tester les endpoints protégés dans Swagger :

1. **Obtenir un token JWT** :
   - Dans Swagger UI, utilisez l'endpoint `POST /api/login`
   - Body : `{"email": "john.doe@email.com", "password": "password123"}`
   - Copiez le token de la réponse

2. **S'authentifier dans Swagger** :
   - Cliquez sur le bouton **"Authorize" 🔓** en haut à droite
   - Dans le champ "Value", entrez : `Bearer VOTRE_TOKEN`
   - Cliquez sur "Authorize" puis "Close"

3. **Tester les endpoints** :
   - Les cadenas 🔒 sont maintenant fermés
   - Tous les endpoints protégés sont accessibles

**Comptes de test disponibles** (après avoir chargé les fixtures) :
- **Admin** : `admin@miniuber.com` / `admin123`
- **Passager** : `john.doe@email.com` / `password123`
- **Chauffeur 1** : `marie.martin@driver.com` / `driver123`
- **Chauffeur 2** : `pierre.dubois@driver.com` / `driver123`

---

## 🛣️ Endpoints disponibles

### Authentication

#### POST /api/users (Inscription)
```json
{
  "email": "user@example.com",
  "password": "password123",
  "firstname": "John",
  "lastname": "Doe",
  "phone": "+33612345678",
  "usertype": "passenger"  // ou "driver"
}
```

#### POST /api/login (Connexion)
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```
**Réponse :** `{ "token": "eyJ0eXAiOiJKV1Qi..." }`

#### GET /api/me (Profil utilisateur)
**Headers :** `Authorization: Bearer <token>`

---

### Rides (Courses)

#### GET /api/rides
Liste toutes les courses (avec filtres)

**Filtres disponibles :**
- `?status=pending` - Statut (pending, accepted, in_progress, completed, cancelled)
- `?vehiculeType=premium` - Type de véhicule
- `?passenger=/api/users/1` - Par passager
- `?driver=/api/users/2` - Par chauffeur
- `?estimatedPrice[gte]=10` - Prix minimum
- `?order[createdAt]=desc` - Tri par date

**Exemple :**
```bash
GET /api/rides?status=pending&vehiculeType=standard&order[createdAt]=desc
```

#### GET /api/rides/{id}
Détails d'une course spécifique

#### POST /api/ride-estimates (Estimer une course)
```json
{
  "pickupLat": 48.8566,
  "pickupLng": 2.3522,
  "dropoffLat": 48.8606,
  "dropoffLng": 2.3376,
  "vehicleType": "standard"
}
```

**Réponse :**
```json
{
  "distance": 3.2,
  "duration": 15.5,
  "price": 12.80,
  "vehicleType": "standard"
}
```

#### POST /api/rides (Créer une course)
**Headers :** `Authorization: Bearer <token>`

```json
{
  "pickupAddress": "123 Main St",
  "pickUpLatitude": 48.8566,
  "pickUpLongitude": 2.3522,
  "dropoffAddress": "456 Avenue",
  "dropoffLatitude": 48.8606,
  "dropoffLongitude": 2.3376,
  "vehiculeType": "standard"
}
```

#### POST /api/rides/{id}/accept (Accepter une course - Driver)
**Headers :** `Authorization: Bearer <driver-token>`

**Body :** `{}` (vide)

**Validations :**
- Chauffeur vérifié ✅
- Chauffeur disponible ✅
- Type de véhicule correspondant ✅
- Course en statut "pending" ✅

#### PATCH /api/rides/{id}/status (Mettre à jour le statut - Driver)
**Headers :** `Authorization: Bearer <driver-token>`

```json
{
  "status": "in_progress"  // ou "completed", "cancelled"
}
```

---

### Drivers (Chauffeurs)

#### GET /api/drivers
Liste les chauffeurs (avec filtres)

**Filtres disponibles :**
- `?isAvailable=true` - Disponibles uniquement
- `?isVerified=true` - Vérifiés uniquement
- `?vehiculeType=premium` - Par type de véhicule
- `?vehiculeModel=Tesla` - Par modèle

**Exemple :**
```bash
GET /api/drivers?isAvailable=true&isVerified=true&vehiculeType=premium
```

#### GET /api/drivers/{id}
Détails d'un chauffeur

#### POST /api/drivers (Créer un profil driver)
**Headers :** `Authorization: Bearer <token>`

```json
{
  "user": "/api/users/1",
  "vehiculeModel": "Tesla Model 3",
  "vehiculeType": "premium",
  "vehiculeColor": "Black",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "licenceNumber": "ABC123456"
}
```

#### PATCH /api/drivers/location (Mettre à jour la position)
**Headers :** `Authorization: Bearer <driver-token>`

```json
{
  "lat": 48.8566,
  "lng": 2.3522
}
```

#### PATCH /api/drivers/availability (Changer la disponibilité)
**Headers :** `Authorization: Bearer <driver-token>`

```json
{
  "isAvailable": true
}
```

---

### Users (Utilisateurs)

#### GET /api/users
Liste les utilisateurs (avec filtres)

**Filtres disponibles :**
- `?usertype=driver` - Par type
- `?email=john` - Recherche partielle
- `?rating[gte]=4.5` - Rating minimum
- `?order[createdAt]=desc` - Tri

#### GET /api/users/{id}
Détails d'un utilisateur

#### PATCH /api/users/{id}
Mettre à jour un utilisateur

---

## 🚀 Déploiement

### Configuration pour production

```bash
# 1. Définir l'environnement
APP_ENV=prod

# 2. Optimiser l'autoloader
composer install --no-dev --optimize-autoloader

# 3. Vider et réchauffer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# 4. Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Installer les assets
php bin/console assets:install public
```

### Checklist de sécurité

- [ ] Changer `APP_SECRET` et tous les secrets
- [ ] Configurer HTTPS
- [ ] Sécuriser la base de données
- [ ] Configurer CORS correctement
- [ ] Activer le rate limiting
- [ ] Désactiver le mode debug (`APP_ENV=prod`)
- [ ] Ne pas committer `.env.local` et les clés JWT

---

## 🔧 Troubleshooting

### Problème : Erreur 500 au démarrage

```bash
# Vider le cache
php bin/console cache:clear

# Vérifier les permissions
chmod -R 777 var/
```

### Problème : JWT ne fonctionne pas

```bash
# Régénérer les clés
php bin/console lexik:jwt:generate-keypair --overwrite

# Vérifier les permissions
chmod 644 config/jwt/private.pem
chmod 644 config/jwt/public.pem
```

### Problème : Doctrine ne trouve pas la DB

```bash
# Vérifier que Docker est lancé
docker compose ps

# Redémarrer PostgreSQL
docker compose restart database

# Vérifier la connexion
php bin/console doctrine:database:create --if-not-exists
```

### Problème : CORS

```bash
# Éditer config/packages/nelmio_cors.yaml
# Ajouter l'origine de votre frontend dans allow_origin
```

### Problème : Mercure ne fonctionne pas

```bash
# Vérifier que Mercure est lancé
docker compose ps mercure

# Redémarrer Mercure
docker compose restart mercure

# Logs
docker compose logs -f mercure
```

---

## 📞 Support et Contact

- **Issues :** [GitHub Issues](https://github.com/ifdev25/mini-uber-api/issues)
- **Email :** ishake.fouhal@gmail.com

---


## 🎯 Prochaines étapes suggérées

- [ ] Ajouter un système de paiement (Stripe)
- [ ] Implémenter les évaluations et commentaires
- [ ] Ajouter la gestion des promotions
- [ ] Système de chat en temps réel
- [ ] Admin panel avec EasyAdmin
- [ ] CI/CD avec GitHub Actions
- [ ] Dockerisation complète de l'application
- [ ] Rate limiting et throttling
- [ ] Monitoring avec Sentry