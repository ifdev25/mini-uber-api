# 🚀 Quick Start - Installation en 5 minutes

Guide ultra-rapide pour démarrer le projet.

---

## ⚡ Installation Express

```bash
# 1. Cloner le projet
git clone <votre-repo>
cd mini-uber-api

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local si nécessaire

# 4. Démarrer Docker (PostgreSQL + Mercure)
docker compose up -d

# 5. Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Générer les clés JWT
php bin/console lexik:jwt:generate-keypair

# 7. Charger les données de test (optionnel)
php bin/console doctrine:fixtures:load --no-interaction

# 8. Démarrer le serveur
symfony server:start
# ou
php -S localhost:8000 -t public/
```

---

## ✅ Vérification

### 1. API fonctionne
```bash
curl http://localhost:8000/api
```

### 2. Se connecter avec un compte de test
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john.doe@email.com","password":"password123"}'
```

**Réponse :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**⚠️ Copier le token pour l'utiliser dans les requêtes suivantes !**

### 3. Tester un endpoint protégé

Utiliser le token dans le header `Authorization: Bearer <token>` :

```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### 4. Accéder à la documentation
Ouvrir : http://localhost:8000/api

---

## 📋 Comptes de test (après fixtures)

| Email | Password | Type |
|-------|----------|------|
| admin@miniuber.com | admin123 | Admin |
| john.doe@email.com | password123 | Passager |
| marie.martin@driver.com | driver123 | Driver (Tesla) |
| pierre.dubois@driver.com | driver123 | Driver (Peugeot) |

---

## 🛠️ Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Voir les routes
php bin/console debug:router

# Tester l'API
php bin/phpunit

# Recharger les fixtures
php bin/console doctrine:fixtures:load

# Logs Docker
docker compose logs -f
```

---

## 📚 Documentation complète

- README complet : [README.md](README.md)
- Endpoints détaillés : [API_ENDPOINTS.md](API_ENDPOINTS.md)
- Fixtures : [FIXTURES.md](FIXTURES.md)
- Suggestions : [SUGGESTIONS.md](SUGGESTIONS.md)
- Doc API : http://localhost:8000/api

---

## 🔧 Troubleshooting rapide

**Erreur : Can't connect to database**
```bash
docker compose up -d database
php bin/console doctrine:database:create
```

**Erreur : JWT keys not found**
```bash
php bin/console lexik:jwt:generate-keypair
```

**Erreur : Port 8000 already in use**
```bash
php -S localhost:8080 -t public/  # Utiliser un autre port
```

---

**Prêt à développer ! 🚀**
