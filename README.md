# 🚖 Mini Uber API - Documentation Complète

API REST moderne pour une application de covoiturage type Uber, construite avec **Symfony 7.3**, **API Platform 4.2** et **FrankenPHP**.

---

## 🎯 État actuel du projet

| Composant | Status | Version/Info |
|-----------|--------|--------------|
| **Backend** | ✅ Opérationnel | Symfony 7.3 + API Platform 4.2 |
| **Serveur Web** | ✅ FrankenPHP | HTTP/2, HTTP/3, HTTPS |
| **Base de données** | ✅ PostgreSQL 16 | Avec fixtures de test |
| **CORS** | ✅ Configuré | Prêt pour frontend (localhost) |
| **Authentification** | ✅ JWT | Tokens valides 1h |
| **Documentation** | ✅ Complète | API + Guide Frontend |
| **Données de test** | ✅ Fixtures | 6 comptes + 3 courses |
| **Ports** | ✅ Actifs | 8080 (HTTP), 8443 (HTTPS), 5432 (DB) |

**🚀 Prêt pour la production et le développement frontend !**

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

## 🐳 Démarrage rapide avec Docker (Recommandé)

### Installation en 3 étapes

```bash
# 1. Cloner le projet
git clone https://github.com/ifdev25/mini-uber-api.git
cd mini-uber-api

# 2. Configurer les variables d'environnement
cp .env .env.local
# Éditez .env.local si nécessaire (JWT passphrase, DATABASE_URL, etc.)

# 3. Démarrer tous les services
docker compose up -d --build

# 4. Installer les dépendances et configurer
docker compose exec frankenphp composer install --optimize-autoloader
docker compose exec frankenphp php bin/console doctrine:database:create --if-not-exists
docker compose exec frankenphp php bin/console doctrine:migrations:migrate -n
docker compose exec frankenphp php bin/console lexik:jwt:generate-keypair --skip-if-exists
docker compose exec frankenphp php bin/console doctrine:fixtures:load -n

# 5. Vider les caches
docker compose exec frankenphp php bin/console cache:clear
```

**L'API est maintenant accessible sur :** `http://localhost:8080` ✅

### Services disponibles

| Service | URL | Port | Description |
|---------|-----|------|-------------|
| **FrankenPHP (API Symfony)** | http://localhost:8080 | 8080 | Serveur web moderne avec HTTP/2/3 |
| **HTTPS (FrankenPHP)** | https://localhost:8443 | 8443 | Accès sécurisé avec certificat auto-signé |
| **PostgreSQL** | localhost:5432 | 5432 | Base de données |
| **Mercure Hub** | http://localhost:3000 | 3000 | Notifications temps réel SSE |
| **API Documentation** | http://localhost:8080/api | 8080 | Swagger UI interactive |

### 🧪 Tester l'API

**Connexion avec un compte de test :**
```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john.doe@email.com","password":"password123"}'
```

**Résultat attendu :**
```json
{"token":"eyJ0eXAiOiJKV1QiLCJhbGc..."}
```

**Comptes de test disponibles :**
- 👤 **Passager** : `john.doe@email.com` / `password123`
- 🚗 **Chauffeur** : `marie.martin@driver.com` / `driver123`
- 👨‍💼 **Admin** : `admin@miniuber.com` / `admin123`

### 🔗 Connecter votre Frontend

Pour connecter votre application frontend (React, Next.js, Vue, etc.), consultez le guide complet :

👉 **[FRONTEND_CONNECTION_GUIDE.md](FRONTEND_CONNECTION_GUIDE.md)**

Ce guide contient :
- ✅ Configuration complète Axios / Fetch
- ✅ Exemples de code React, Next.js, Vue
- ✅ Gestion de l'authentification JWT
- ✅ Troubleshooting CORS

**Documentation complète Docker :** Voir [DOCKER.md](DOCKER.md) et [PERFORMANCE_OPTIMIZATION.md](PERFORMANCE_OPTIMIZATION.md)

### Commandes Docker utiles

```bash
# Voir les logs
docker compose logs -f frankenphp # Logs FrankenPHP/Symfony
docker compose logs -f database   # Logs PostgreSQL
docker compose logs -f mercure    # Logs Mercure

# Redémarrer un service
docker compose restart frankenphp

# Arrêter tous les services
docker compose down

# Reconstruire les images
docker compose build --no-cache frankenphp
docker compose up -d
```

---

## 🔧 Prérequis (Installation manuelle)

### Versions requises

| Composant | Version minimale | Version recommandée |
|-----------|-----------------|---------------------|
| **PHP** | 8.2.0 | 8.3.x |
| **Composer** | 2.0 | 2.7.x |
| **Symfony CLI** | - | 5.x (optionnel) |
| **PostgreSQL** | 14 | 16 |
| **Docker Desktop** | 20.10 | Dernière (recommandé) |

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

## 🚀 FrankenPHP - Serveur Web Moderne

Ce projet utilise **FrankenPHP**, un serveur d'application PHP moderne construit sur Caddy, offrant des performances exceptionnelles pour Symfony.

### Avantages de FrankenPHP

| Fonctionnalité | Description |
|----------------|-------------|
| **HTTP/2 et HTTP/3** | Support natif des protocoles modernes pour de meilleures performances |
| **HTTPS automatique** | Certificats auto-signés en développement, Let's Encrypt en production |
| **Compression automatique** | Gzip et Zstandard pour réduire la taille des réponses |
| **Worker mode** | Garde Symfony en mémoire entre les requêtes (optionnel, performances maximales) |
| **Configuration simple** | Un seul conteneur remplace PHP-FPM + Nginx |
| **Intégration Symfony** | Optimisé spécifiquement pour Symfony |

### Architecture

```
┌─────────────────────────────────────────┐
│         FrankenPHP Container            │
│  ┌───────────────────────────────────┐  │
│  │  Caddy Web Server (HTTP/2/3)      │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  PHP 8.3 + Extensions             │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  Symfony Application              │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

**Avant (PHP-FPM + Nginx) :** 2 conteneurs
**Après (FrankenPHP) :** 1 conteneur ✅

### Mode Worker (Optionnel)

Pour activer le mode worker qui garde l'application Symfony en mémoire entre les requêtes :

```bash
# Installer le runtime FrankenPHP pour Symfony
composer require runtime/frankenphp-symfony

# Dans compose.yaml, modifier :
FRANKENPHP_NUM_WORKERS: "2"  # Au lieu de "0"
```

**Performance avec workers :**
- Première requête : ~300ms
- Requêtes suivantes : ~50-100ms (6x plus rapide) 🚀

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

### Fichiers de documentation

| Documentation | Fichier | Description |
|---------------|---------|-------------|
| **🔗 Guide de Connexion Frontend** | [FRONTEND_CONNECTION_GUIDE.md](FRONTEND_CONNECTION_GUIDE.md) | **Guide complet pour connecter votre frontend** (React, Next.js, Vue) |
| **Documentation Frontend API** | [FRONTEND_API_DOCUMENTATION.md](FRONTEND_API_DOCUMENTATION.md) | Guide complet JSON-LD pour frontend avec exemples TypeScript |
| **Documentation générale** | [API_ENDPOINTS.md](API_ENDPOINTS.md) | Liste complète des endpoints et exemples |
| **Résumé refactoring** | [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md) | Détails des optimisations et bonnes pratiques |

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

## ⚡ Performances et Optimisations

### Performance actuelle avec FrankenPHP

L'API utilise FrankenPHP pour des performances optimales sur Docker :

| Métrique | Temps |
|----------|-------|
| **Temps de réponse moyen** | 300-500ms |
| **Première requête (cache froid)** | ~700ms |
| **Requêtes suivantes** | 200-400ms |
| **Avec mode worker** | 50-100ms (6x plus rapide) |

**Amélioration : 15x plus rapide** qu'une configuration standard Docker sur Windows

### Optimisations appliquées

1. **FrankenPHP moderne**
   - Serveur web haute performance basé sur Caddy
   - HTTP/2 et HTTP/3 natifs
   - Compression automatique (gzip, zstd)

2. **Volumes Docker optimisés**
   - `vendor/` et `var/` utilisent des volumes nommés Docker
   - I/O rapides même sur Windows/Mac

3. **Xdebug désactivé par défaut**
   - Mode "off" pour performance maximale
   - Réactivable facilement pour le debugging

4. **OPcache optimisé**
   - Pas de revalidation de fichiers (performance maximale)
   - Nécessite `docker compose restart frankenphp` après modification du code

### Configuration pour le développement

**Important :** Après chaque modification de code, redémarrez FrankenPHP pour vider le cache OPcache :

```bash
docker compose restart frankenphp
```

**Pour réactiver Xdebug** (debugging) :

Modifiez `docker/php/xdebug.ini` :
```ini
xdebug.mode = debug  # Au lieu de "off"
```

Puis redémarrez :
```bash
docker compose restart frankenphp
```

**Pour activer le mode worker** (performances maximales) :
```bash
composer require runtime/frankenphp-symfony
# Puis modifiez FRANKENPHP_NUM_WORKERS: "2" dans compose.yaml
docker compose up -d --build
```

**Documentation complète :** Voir [PERFORMANCE_OPTIMIZATION.md](PERFORMANCE_OPTIMIZATION.md)

---

## 🌐 Configuration Frontend

Pour connecter votre frontend à l'API, consultez le **guide complet de connexion** : [FRONTEND_CONNECTION_GUIDE.md](FRONTEND_CONNECTION_GUIDE.md)

**Ce guide contient :**
- ✅ Configuration complète Axios / Fetch
- ✅ Exemples React, Next.js, Vue
- ✅ Gestion de l'authentification JWT
- ✅ Hooks personnalisés et services
- ✅ Comptes de test prêts à l'emploi
- ✅ Gestion des erreurs CORS
- ✅ Troubleshooting complet

**En résumé :**
- **URL API** : `http://localhost:8080`
- **Headers requis** : `Content-Type: application/json` + `Authorization: Bearer {token}`
- **CORS** : Déjà configuré pour localhost (tous les ports)
- **Comptes de test** : `john.doe@email.com` / `password123` (passager)
- **Comptes de test** : `marie.martin@driver.com` / `driver123` (chauffeur)

---

## 📞 Support et Contact

- **Issues :** [GitHub Issues](https://github.com/ifdev25/mini-uber-api/issues)
- **Email :** ishake.fouhal@gmail.com

---

## 📝 Changelog récent

### 2025-12-12 - Migration FrankenPHP + Setup Frontend Complet

**🚀 Changement majeur d'infrastructure :**
- ✅ **FrankenPHP installé** - Remplace PHP-FPM + Nginx par un serveur moderne
- ✅ **HTTP/2 et HTTP/3** - Support natif des protocoles modernes
- ✅ **HTTPS activé** - Port 8443 avec certificats auto-signés
- ✅ **Compression automatique** - Gzip et Zstandard intégrés
- ✅ **Architecture simplifiée** - 1 conteneur au lieu de 2

**📦 Configuration FrankenPHP :**
- Image : `dunglas/frankenphp:1-php8.3-alpine`
- Ports : 8080 (HTTP), 8443 (HTTPS + HTTP/3)
- Caddyfile personnalisé optimisé
- Mode worker désactivé par défaut (activable avec `runtime/frankenphp-symfony`)

**🔧 Configuration CORS :**
- ✅ CORS configuré et testé pour `localhost:3000`
- ✅ Accepte tous les ports localhost
- ✅ Headers CORS corrects sur tous les endpoints
- ✅ Support des credentials (`withCredentials: true`)
- ✅ Gestion des requêtes OPTIONS (preflight)

**📚 Documentation créée :**
- ✅ **FRONTEND_CONNECTION_GUIDE.md** - Guide complet de connexion frontend/backend
  - Configuration Axios et Fetch
  - Exemples React, Next.js, Vue avec TypeScript
  - Service API complet avec intercepteurs JWT
  - Hooks personnalisés (useAuth, useApi)
  - Gestion des erreurs et troubleshooting
  - 6 comptes de test documentés

**🎭 Fixtures installées :**
- ✅ 1 compte Admin
- ✅ 2 comptes Passager (1 vérifié, 1 non vérifié)
- ✅ 3 comptes Chauffeur (Paris + Algérie)
- ✅ 3 courses d'exemple (terminée, en cours, en attente)
- ✅ Tous les comptes testés et fonctionnels

**🔧 Commandes mises à jour :**
- `docker compose exec php` → `docker compose exec frankenphp`
- Service renommé de `php` à `frankenphp` dans `compose.yaml`
- Toutes les commandes Docker actualisées

**📊 Tests effectués :**
- ✅ Connexion JWT testée (passager + chauffeur)
- ✅ CORS vérifié avec requêtes OPTIONS et POST
- ✅ Tous les services healthy et opérationnels
- ✅ API accessible sur http://localhost:8080

**🎯 Impact :**
- **Architecture simplifiée** - 1 conteneur vs 2 (PHP-FPM + Nginx)
- **CORS fonctionnel** - Frontend peut se connecter sans problème
- **Documentation complète** - Développeurs frontend autonomes
- **Données de test** - 6 comptes prêts à l'emploi
- **Performances maintenues** - 300-500ms de temps de réponse
- **HTTP/3 ready** - Compression moderne et protocoles futurs

---

### 2025-12-11 - Refactoring majeur et optimisation du code

**✨ Nouveautés :**
- ✅ **GeoService créé** - Service centralisé pour les calculs de distance (Haversine)
- ✅ **Documentation complète JSON-LD** - [FRONTEND_API_DOCUMENTATION.md](FRONTEND_API_DOCUMENTATION.md) avec exemples TypeScript
- ✅ **Résumé du refactoring** - [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md) avec statistiques détaillées

**🗑️ Code supprimé :**
- ✅ **RideController.php entier supprimé** - Déprécié, remplacé par State Processors API Platform
- ✅ **Endpoint dupliqué updateLocation()** - Supprimé du DriverController (géré par API Platform)
- ✅ **Méthodes redondantes** - `getIsVerified()` et `getIsAvailable()` dans Driver.php
- ✅ **Fichiers obsolètes** - BACKEND_AVAILABILITY_ENDPOINT_MISSING.md, DRIVER_AVAILABILITY_ENDPOINT.md, nul

**🔧 Optimisations :**
- ✅ **PricingService, DriverMatchingService, DriverController** - Utilisent maintenant GeoService
- ✅ **Correction de bug** - Méthodes dépréciées dans RideProcessor.php corrigées
- ✅ **Architecture cohérente** - Tous les endpoints personnalisés utilisent State Processors

**📊 Impact :**
- **-350 lignes de code** (-10% du total)
- **-93 lignes de code dupliqué** (-100%)
- **-1 controller obsolète**
- **-4 méthodes redondantes**
- **+1 service centralisé** (GeoService)

**📚 Documentation :**
- Guide complet JSON-LD pour frontend avec exemples React/TypeScript
- Client API TypeScript prêt à l'emploi
- Types TypeScript pour toutes les entités
- Workflow complet passager/driver documenté

### 2025-12-07 - Refactoring majeur et nettoyage du code

**Améliorations :**
- ✅ **Correction de bugs critiques** (méthodes dupliquées dans User.php, getDriverProfile inexistant)
- ✅ **Ajout de getFullName()** - nouvelle méthode utilitaire dans User.php
- ✅ **Refactorisation complète** des services pour utiliser getFullName()
- ✅ **Nettoyage de la documentation** - suppression des fichiers .md obsolètes
- ✅ **README mis à jour** avec les commandes Docker complètes

**Bugs corrigés :**
- Méthodes dupliquées supprimées (addRatingsGiven, removeRatingsGiven, etc.)
- Correction de getDriverProfile() → getDriver() dans DriverController
- Formatage cohérent du code

**Performance :**
- Aucun breaking change pour le frontend
- Tous les endpoints restent compatibles
- Code plus maintenable et lisible

**Documentation :**
- `REFACTORING_REPORT.md` - Rapport détaillé des changements
- Fichiers obsolètes supprimés (BACKEND_ISSUES, FIX-*, SUGGESTIONS, etc.)
- README simplifié et orienté Docker

### 2025-12-03 - Optimisation des performances Docker

**Améliorations :**
- ✅ Volumes Docker optimisés (vendor/ et var/ sur volumes nommés)
- ✅ Xdebug désactivé par défaut (gain de performance 3-5x)
- ✅ OPcache optimisé (pas de revalidation)
- ✅ Documentation complète pour le frontend (FRONTEND_CONFIG.md)

**Performance :**
- Avant : 5-6 secondes par requête ❌
- Après : 300-500ms par requête ✅
- **Gain : 15x plus rapide** 🚀

---


## 🎯 Prochaines étapes suggérées

### Fonctionnalités
- [ ] Ajouter un système de paiement (Stripe)
- [ ] Système de chat en temps réel
- [ ] Ajouter la gestion des promotions
- [ ] Admin panel avec EasyAdmin

### Qualité et Tests
- [ ] Tests automatisés (PHPUnit) pour State Processors
- [ ] Tests pour GeoService
- [ ] Validation Symfony dans AuthController

### DevOps
- [ ] CI/CD avec GitHub Actions
- [ ] Rate limiting et throttling
- [ ] Monitoring avec Sentry
- [ ] Logging structuré

### Documentation
- ✅ ~~Documentation JSON-LD complète pour frontend~~ (Terminé)
- ✅ ~~Nettoyage du code et suppression des doublons~~ (Terminé)
- [ ] Guide de contribution (CONTRIBUTING.md)
- [ ] Documentation des tests