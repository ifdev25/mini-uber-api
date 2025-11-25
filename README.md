# 🚖 Mini Uber API - Documentation Complète

API REST moderne pour une application de covoiturage type Uber, construite avec **Symfony 7.3** et **API Platform 4.2**.

---

## 📋 Table des matières

1. [Prérequis](#-prérequis)
2. [Installation complète](#-installation-complète)
3. [Configuration](#-configuration)
4. [Base de données et migrations](#-base-de-données-et-migrations)
5. [Authentification JWT](#-authentification-jwt)
6. [Vérification d'Email](#️-vérification-demail)
7. [Notifications temps réel (Mercure)](#-notifications-temps-réel-mercure)
8. [Fixtures (données de test)](#-fixtures-données-de-test)
9. [Tests](#-tests)
10. [Documentation API](#-documentation-api)
11. [Endpoints disponibles](#-endpoints-disponibles)
12. [Déploiement](#-déploiement)
13. [Troubleshooting](#-troubleshooting)

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

## ✉️ Vérification d'Email

### Fonctionnalité

Le système de vérification d'email permet de :
- Confirmer l'adresse email lors de l'inscription
- Sécuriser les comptes utilisateurs
- Envoyer un lien de vérification valable 24h

### Configuration

```env
###> Email Verification ###
# URL de votre application frontend (pour les liens de vérification)
FRONTEND_URL=http://localhost:3000
###< Email Verification ###
```

### Endpoints

#### 1. Inscription (génère automatiquement un token)

```bash
POST /api/register
```

**Body :**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

**Réponse :**
```json
{
  "message": "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "userType": "passenger",
    "isVerified": false
  },
  "token": "eyJ0eXAiOiJKV1Qi..."
}
```

#### 2. Vérifier l'email

```bash
POST /api/verify-email
```

**Body :**
```json
{
  "token": "abc123def456..."
}
```

**Réponse :**
```json
{
  "message": "Email vérifié avec succès",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "isVerified": true
  }
}
```

#### 3. Renvoyer l'email de vérification

```bash
POST /api/resend-verification
```

**Body :**
```json
{
  "email": "user@example.com"
}
```

**Réponse :**
```json
{
  "message": "Email de vérification renvoyé"
}
```

### Configuration de l'envoi d'emails

Le système d'envoi d'emails utilise **Symfony Mailer**. La configuration se fait via la variable d'environnement `MAILER_DSN` dans le fichier `.env.local`.

#### Étape 1 : Configurer MAILER_DSN dans `.env.local`

Plusieurs options sont disponibles selon vos besoins :

##### Option 1 : Gmail (Recommandé pour débuter)

```env
# Dans .env.local
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default
MAILER_FROM_EMAIL=your-email@gmail.com
MAILER_FROM_NAME="Mini Uber"
```

**Configuration Gmail :**
1. Activer la validation en deux étapes sur votre compte Google
2. Créer un mot de passe d'application :
   - Accéder à https://myaccount.google.com/apppasswords
   - Créer un nouveau mot de passe d'application
   - Utiliser ce mot de passe (16 caractères) dans `MAILER_DSN`

**Exemple complet :**
```env
MAILER_DSN=gmail+smtp://john.doe@gmail.com:abcd1234efgh5678@default
MAILER_FROM_EMAIL=john.doe@gmail.com
MAILER_FROM_NAME="Mini Uber"
```

##### Option 2 : Mailtrap (Recommandé pour développement/tests)

[Mailtrap](https://mailtrap.io/) est un service gratuit qui capture les emails sans les envoyer réellement.

```env
# Dans .env.local
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
MAILER_FROM_EMAIL=noreply@mini-uber.com
MAILER_FROM_NAME="Mini Uber"
```

**Configuration Mailtrap :**
1. Créer un compte gratuit sur https://mailtrap.io
2. Créer une inbox
3. Copier les identifiants SMTP (username et password)
4. Les utiliser dans `MAILER_DSN`

##### Option 3 : SMTP Générique

Pour tout autre fournisseur SMTP (SendGrid, Mailgun, Amazon SES, etc.) :

```env
MAILER_DSN=smtp://username:password@smtp.example.com:587
MAILER_FROM_EMAIL=noreply@mini-uber.com
MAILER_FROM_NAME="Mini Uber"
```

**Formats de DSN courants :**
- **Port 587** (TLS) : `smtp://user:pass@smtp.example.com:587`
- **Port 465** (SSL) : `smtps://user:pass@smtp.example.com:465`
- **Port 25** (non sécurisé) : `smtp://user:pass@smtp.example.com:25`

##### Option 4 : Mode développement sans envoi (Null)

Pour tester sans envoyer d'emails réels, utilisez le transport `null` :

```env
MAILER_DSN=null://null
```

Les emails ne seront pas envoyés mais seront loggés dans les logs Symfony pour débogage.

#### Étape 2 : Tester l'envoi d'emails

Après configuration, testez l'envoi d'emails :

```bash
# 1. S'inscrire
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstName": "Test",
    "lastName": "User",
    "phone": "+33612345678",
    "userType": "passenger"
  }'

# 2. Vérifier votre boîte email (Gmail/Mailtrap/etc.)
# Vous devriez recevoir un email avec le lien de vérification
```

#### Étape 3 : Vérifier les logs (si problème)

En cas de problème, vérifiez les logs Symfony :

```bash
# Voir les logs du serveur Symfony
symfony server:log

# Ou vérifier le cache
tail -f var/cache/dev/App_KernelDevDebugContainerCompiler.log
```

#### Configuration avancée

**Personnaliser l'expéditeur par email :**

Vous pouvez modifier `src/Service/EmailService.php:36-37` pour personnaliser l'expéditeur :

```php
$fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@mini-uber.com';
$fromName = $_ENV['MAILER_FROM_NAME'] ?? 'Mini Uber';
```

**Ajouter un nom d'affichage :**

```php
$email = (new Email())
    ->from(new Address($fromEmail, $fromName))
    ->to($to)
    ->subject($subject)
    ->html($body);
```

#### Providers SMTP populaires

| Provider | DSN | Documentation |
|----------|-----|---------------|
| **Gmail** | `gmail+smtp://user:app-password@default` | [Guide Gmail](https://support.google.com/accounts/answer/185833) |
| **Mailtrap** | `smtp://user:pass@smtp.mailtrap.io:2525` | [Mailtrap Docs](https://mailtrap.io/docs/) |
| **SendGrid** | `smtp://apikey:YOUR_API_KEY@smtp.sendgrid.net:587` | [SendGrid SMTP](https://docs.sendgrid.com/for-developers/sending-email/integrating-with-the-smtp-api) |
| **Mailgun** | `smtp://postmaster@domain:password@smtp.mailgun.org:587` | [Mailgun SMTP](https://documentation.mailgun.com/en/latest/user_manual.html#sending-via-smtp) |
| **Amazon SES** | `smtp://username:password@email-smtp.region.amazonaws.com:587` | [AWS SES SMTP](https://docs.aws.amazon.com/ses/latest/dg/smtp-credentials.html) |

#### Dépannage

**Problème : "Connection refused"**
- Vérifiez que le port SMTP est correct (587, 465, ou 25)
- Vérifiez que votre pare-feu autorise les connexions sortantes

**Problème : "Authentication failed"**
- Vérifiez vos identifiants SMTP
- Pour Gmail : utilisez un mot de passe d'application, pas votre mot de passe normal

**Problème : "Email not sent"**
- Vérifiez les logs Symfony : `symfony server:log`
- Vérifiez que `MAILER_DSN` est bien configuré dans `.env.local`
- Testez avec `MAILER_DSN=null://null` pour voir si le problème vient de la configuration SMTP

### Intégration Frontend (Next.js)

**Page de vérification :**
```typescript
// app/verify-email/page.tsx
'use client';

import { useSearchParams } from 'next/navigation';
import { useEffect, useState } from 'react';

export default function VerifyEmail() {
  const searchParams = useSearchParams();
  const token = searchParams.get('token');
  const [status, setStatus] = useState('verifying');

  useEffect(() => {
    if (token) {
      fetch('http://localhost:8000/api/verify-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token })
      })
      .then(res => res.json())
      .then(data => {
        setStatus(data.message ? 'success' : 'error');
      })
      .catch(() => setStatus('error'));
    }
  }, [token]);

  return (
    <div>
      {status === 'verifying' && <p>Vérification en cours...</p>}
      {status === 'success' && <p>✅ Email vérifié avec succès!</p>}
      {status === 'error' && <p>❌ Token invalide ou expiré</p>}
    </div>
  );
}
```

### Champs de l'entité User

| Champ | Type | Description |
|-------|------|-------------|
| `isVerified` | boolean | Statut de vérification (false par défaut) |
| `verificationToken` | string | Token unique de vérification |
| `verificationTokenExpiresAt` | datetime | Date d'expiration du token (24h) |

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

## 📝 Changelog récent

### 2025-01-25 - Corrections API Driver

**Problèmes corrigés :**
- ✅ Exposition du champ `isAvailable` dans le contexte `ride:read` (Driver.php:110)
- ✅ Exposition du champ `rating` dans les contextes `driver:read` et `ride:read` (User.php:93)

**Impact :**
- Les réponses API incluent maintenant la disponibilité des chauffeurs dans toutes les requêtes
- Le rating des chauffeurs est visible lors de la récupération des courses et des profils drivers

**Fichiers modifiés :**
- `src/Entity/Driver.php` - Ajout du groupe `ride:read` à `isAvailable`
- `src/Entity/User.php` - Ajout des groupes `driver:read` et `ride:read` à `rating`

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