# 📚 Index de la documentation

Bienvenue ! Voici tous les documents disponibles pour ce projet.

---

## 🎯 Par où commencer ?

### Nouveau sur le projet ?
1. **[QUICK_START.md](QUICK_START.md)** - Installation en 5 minutes ⚡
2. **[README.md](README.md)** - Documentation complète 📖
3. **[FIXTURES.md](FIXTURES.md)** - Comptes de test disponibles 🎭

### Développeur Frontend (Next.js, React, etc.) ?
1. **[API_ENDPOINTS.md](API_ENDPOINTS.md)** - Liste complète des endpoints 🛣️
2. **README.md** - Section "Intégration Frontend" 🎨
3. **Documentation interactive :** http://localhost:8000/api 🌐

---

## 📖 Documentation disponible

### 🚀 Installation et démarrage

| Fichier | Description | Quand l'utiliser |
|---------|-------------|------------------|
| **[QUICK_START.md](QUICK_START.md)** | Installation express en 5 min | Première installation |
| **[README.md](README.md)** | Documentation complète de A à Z | Référence complète |
| **[.env](.env)** | Variables d'environnement | Configuration |

### 🛣️ API et Endpoints

| Fichier | Description | Quand l'utiliser |
|---------|-------------|------------------|
| **[API_ENDPOINTS.md](API_ENDPOINTS.md)** | Liste détaillée de tous les endpoints | Développement frontend |
| **http://localhost:8000/api** | Documentation interactive (API Platform) | Explorer l'API en live |
| **http://localhost:8000/api/docs** | Swagger UI | Tester l'API |

### 🎭 Données de test

| Fichier | Description | Quand l'utiliser |
|---------|-------------|------------------|
| **[FIXTURES.md](FIXTURES.md)** | Comptes de test et données d'exemple | Tests et développement |
| **src/DataFixtures/AppFixtures.php** | Code source des fixtures | Modifier les données |

### 💡 Améliorations

| Fichier | Description | Quand l'utiliser |
|---------|-------------|------------------|
| **[SUGGESTIONS.md](SUGGESTIONS.md)** | Idées d'améliorations futures | Planification de features |

---

## 🎯 Guides par cas d'usage

### Je veux démarrer le projet
```bash
# Suivre QUICK_START.md
git clone <repo>
composer install
docker compose up -d
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:fixtures:load
symfony server:start
```

### Je développe un frontend Next.js
1. Lire **README.md** → Section "Intégration Frontend"
2. Consulter **API_ENDPOINTS.md** pour tous les endpoints
3. Utiliser http://localhost:8000/api pour tester

### Je veux tester l'API
1. Charger les fixtures : `php bin/console doctrine:fixtures:load`
2. Voir **FIXTURES.md** pour les comptes disponibles
3. Utiliser http://localhost:8000/api/docs (Swagger)

### Je veux ajouter une fonctionnalité
1. Lire **SUGGESTIONS.md** pour des idées
2. Consulter **README.md** → Architecture du projet
3. Suivre les patterns existants dans `src/State/`

### Je rencontre un problème
1. Consulter **README.md** → Section "Troubleshooting"
2. Vérifier que Docker est lancé : `docker compose ps`
3. Vider le cache : `php bin/console cache:clear`

---

## 📁 Structure du projet

```
mini-uber-api/
├── 📄 README.md                    # Documentation principale
├── 📄 QUICK_START.md              # Installation rapide
├── 📄 API_ENDPOINTS.md            # Liste des endpoints
├── 📄 FIXTURES.md                 # Données de test
├── 📄 SUGGESTIONS.md              # Améliorations futures
├── 📄 DOCUMENTATION_INDEX.md      # Ce fichier
│
├── 📁 config/
│   ├── packages/                  # Configuration bundles
│   ├── routes/                    # Routes
│   └── jwt/                       # Clés JWT (gitignored)
│
├── 📁 src/
│   ├── ApiResource/              # Ressources API personnalisées
│   ├── Controller/               # Controllers (dépréciés)
│   ├── DataFixtures/             # Fixtures (données test)
│   ├── Dto/                      # Data Transfer Objects
│   ├── Entity/                   # Entités Doctrine
│   ├── Repository/               # Repositories
│   ├── Service/                  # Services métier
│   └── State/                    # State Processors API Platform
│
├── 📁 tests/
│   ├── Unit/                     # Tests unitaires
│   └── Functional/               # Tests fonctionnels
│
├── 📁 migrations/                # Migrations DB
├── 📄 compose.yaml               # Docker (PostgreSQL, Mercure)
├── 📄 .env                       # Variables env (template)
├── 📄 .env.local                 # Variables env locales (gitignored)
└── 📄 .gitignore                 # Fichiers ignorés par Git
```

---

## 🔗 Liens utiles

### Documentation en ligne
- **API Interactive :** http://localhost:8000/api
- **Swagger UI :** http://localhost:8000/api/docs
- **OpenAPI JSON :** http://localhost:8000/api/docs.json

### Documentation externe
- **Symfony :** https://symfony.com/doc/current/index.html
- **API Platform :** https://api-platform.com/docs/
- **Doctrine :** https://www.doctrine-project.org/projects/orm.html
- **JWT Bundle :** https://github.com/lexik/LexikJWTAuthenticationBundle
- **Mercure :** https://mercure.rocks/docs

---

## ✅ Checklist d'installation complète

- [ ] Cloner le projet
- [ ] `composer install`
- [ ] Copier `.env` vers `.env.local`
- [ ] `docker compose up -d`
- [ ] `php bin/console doctrine:database:create`
- [ ] `php bin/console doctrine:migrations:migrate`
- [ ] `php bin/console lexik:jwt:generate-keypair`
- [ ] `php bin/console doctrine:fixtures:load` (optionnel)
- [ ] `symfony server:start` ou `php -S localhost:8000 -t public/`
- [ ] Tester : http://localhost:8000/api

---

## 🎯 Commandes essentielles

```bash
# Démarrer les services
docker compose up -d                           # Démarrer PostgreSQL + Mercure
symfony server:start                           # Démarrer Symfony

# Base de données
php bin/console doctrine:database:create       # Créer la DB
php bin/console doctrine:migrations:migrate    # Exécuter migrations
php bin/console doctrine:fixtures:load         # Charger données test

# Authentification
php bin/console lexik:jwt:generate-keypair     # Générer clés JWT

# Développement
php bin/console cache:clear                    # Vider cache
php bin/console debug:router                   # Voir routes
php bin/phpunit                                # Lancer tests

# Docker
docker compose ps                              # Status services
docker compose logs -f                         # Logs en temps réel
docker compose restart mercure                 # Redémarrer Mercure
```

---

## 📊 Versions et technologies

| Technologie | Version |
|-------------|---------|
| PHP | 8.2+ (8.3 recommandé) |
| Symfony | 7.3.* |
| API Platform | 4.2 |
| Doctrine ORM | 3.5 |
| PostgreSQL | 16 |
| Mercure | 0.3.9 |
| JWT Bundle | Dernière |

---

## 🆘 Support

- **Documentation locale :** Consultez les fichiers .md de ce projet
- **API interactive :** http://localhost:8000/api
- **Issues GitHub :** [Créer une issue](votre-repo/issues)
- **Email :** support@miniuber.com

---

**Bonne lecture et bon développement ! 🚀**

*Dernière mise à jour : $(date)*
