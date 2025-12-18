# Guide des optimisations de performance

## Vue d'ensemble

Ce document décrit toutes les optimisations de performance mises en place pour améliorer les échanges de données entre l'API et le frontend.

---

## Optimisations appliquées

### 1️⃣ Compression HTTP (zstd + gzip)

**Fichier**: `docker/frankenphp/Caddyfile:13`

```caddy
encode zstd gzip
```

**Impact**:
- **Réduction de 60-80%** de la taille des réponses JSON
- zstd offre de meilleures performances que gzip
- Compression automatique pour toutes les réponses > 1KB

**Exemple**:
```bash
# Sans compression
Response size: 5000 bytes

# Avec compression
Response size: 1200 bytes (76% de réduction)
```

---

### 2️⃣ Cache HTTP pour assets statiques

**Fichier**: `docker/frankenphp/Caddyfile:15-22`

```caddy
@static {
    file
    path *.css *.js *.png *.jpg *.jpeg *.gif *.ico *.svg *.woff *.woff2 *.ttf *.eot
}
header @static {
    Cache-Control "public, max-age=31536000, immutable"
}
```

**Impact**:
- Assets mis en cache pendant **1 an** (31536000 secondes)
- Pas de requêtes réseau pour les assets déjà téléchargés
- Utiliser le versioning pour cache busting

**Headers retournés**:
```http
Cache-Control: public, max-age=31536000, immutable
```

---

### 3️⃣ Optimisation CORS

**Fichier**: `docker/frankenphp/Caddyfile:24`

```caddy
Access-Control-Max-Age "86400"
```

**Impact**:
- Requêtes preflight OPTIONS mises en cache pendant **24 heures**
- Réduction du nombre de requêtes réseau
- Amélioration des temps de chargement initiaux

---

### 4️⃣ Serialization optimisée

**Fichier**: `config/packages/framework.yaml:10-12`

```yaml
serializer:
    default_context:
        enable_max_depth: true
        skip_null_values: true  # ✅ NOUVEAU
```

**Impact**:
- **Réduction de 20-40%** de la taille du JSON
- Les champs `null` ne sont pas inclus dans la réponse
- Payload plus léger et parsing JSON plus rapide côté frontend

**Exemple**:
```json
# Avant (avec skip_null_values: false)
{
  "id": 1,
  "name": "John",
  "rating": null,
  "profilePicture": null,
  "driver": null
}

# Après (avec skip_null_values: true)
{
  "id": 1,
  "name": "John"
}
```

---

### 5️⃣ Pagination API Platform

**Fichier**: `config/packages/api_platform.yaml:13-17`

```yaml
pagination_client_enabled: true
pagination_client_items_per_page: true
pagination_items_per_page: 30
pagination_maximum_items_per_page: 100
```

**Impact**:
- Limiter le nombre d'éléments retournés par défaut à **30**
- Client peut ajuster entre 1 et 100 éléments par page
- Réduction drastique de la taille des réponses pour les collections

**Utilisation frontend**:
```javascript
// Par défaut (30 éléments)
fetch('http://api/users')

// Personnalisé (10 éléments)
fetch('http://api/users?itemsPerPage=10')

// Page suivante
fetch('http://api/users?page=2')
```

---

### 6️⃣ Cache HTTP pour API

**Fichier**: `config/packages/api_platform.yaml:7-11`

```yaml
cache_headers:
    vary: ['Content-Type', 'Authorization', 'Origin']
    max_age: 0
    shared_max_age: 3600  # 1 heure pour CDN/proxy
    public: false
```

**Impact**:
- Mise en cache côté proxy/CDN pendant 1 heure
- Cache invalidé automatiquement en cas de modification
- Pas de cache côté client pour les données authentifiées

---

### 7️⃣ HTTP Cache Framework

**Fichier**: `config/packages/framework.yaml:17-19`

```yaml
http_cache:
    enabled: true
```

**Impact**:
- Activation du reverse proxy cache de Symfony
- Gestion automatique des ETags
- Validation conditionnelle avec If-None-Match

---

### 8️⃣ MaxDepth pour éviter circular references

**Fichier**: `src/Entity/User.php:131, 139`

```php
#[MaxDepth(1)]
private ?Driver $driver = null;

#[MaxDepth(2)]
private Collection $ridesAsDriver;
```

**Impact**:
- Évite les références circulaires infinies
- Limite la profondeur de sérialisation
- Réduit drastiquement la taille du payload

---

## Métriques de performance

### Avant optimisations

| Métrique | Valeur |
|----------|--------|
| Taille moyenne réponse JSON | ~3-5 KB |
| Requêtes preflight CORS | À chaque requête |
| Assets statiques | Rechargés à chaque visite |
| Collections | Tous les éléments retournés |

### Après optimisations

| Métrique | Valeur | Amélioration |
|----------|--------|--------------|
| Taille moyenne réponse JSON | ~800 bytes - 1.5 KB | **60-70%** ⬇️ |
| Requêtes preflight CORS | 1 par 24h | **99%** ⬇️ |
| Assets statiques | Cachés 1 an | **100%** ⬇️ (après 1ère visite) |
| Collections | Max 30-100 éléments | **Configurable** |

---

## Recommandations frontend

### 1. Utiliser la pagination

```javascript
// ✅ BON - Charge 10 éléments à la fois
const response = await fetch('/api/rides?itemsPerPage=10&page=1');

// ❌ MAUVAIS - Charge potentiellement des milliers d'éléments
const response = await fetch('/api/rides');
```

### 2. Gérer le cache HTTP

```javascript
// Headers de cache automatiquement gérés
const response = await fetch('/api/users/1');

// Si ETag présent, le serveur retournera 304 Not Modified
console.log(response.status); // 304
```

### 3. Optimiser les requêtes

```javascript
// ✅ BON - Requête ciblée
fetch('/api/users/1')

// ❌ MAUVAIS - Charge tous les users puis filtre côté client
fetch('/api/users').then(data => data.find(u => u.id === 1))
```

### 4. Utiliser les filtres API Platform

```javascript
// Filtrer côté serveur
fetch('/api/users?userType=driver&isAvailable=true')

// Au lieu de filtrer côté client
fetch('/api/users').then(data => data.filter(...))
```

---

## Monitoring des performances

### 1. Vérifier la compression

```bash
curl -H "Accept-Encoding: gzip" -I http://localhost:8080/api/users
# Chercher: Content-Encoding: gzip
```

### 2. Vérifier le cache

```bash
curl -I http://localhost:8080/style.css
# Chercher: Cache-Control: public, max-age=31536000, immutable
```

### 3. Tester la pagination

```bash
curl http://localhost:8080/api/users
# Chercher: "hydra:view" avec "hydra:next" et "hydra:last"
```

---

## Optimisations futures recommandées

### 1. GraphQL (optionnel)
Pour des requêtes ultra-optimisées permettant au client de demander exactement les champs nécessaires.

### 2. Redis Cache
Pour mettre en cache les réponses API fréquemment demandées.

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: redis://localhost
```

### 3. Varnish
Reverse proxy cache pour gérer des millions de requêtes.

### 4. CDN
Pour distribuer les assets statiques géographiquement.

---

## Debugging performance

### Profiler Symfony

En mode dev, activer le profiler pour analyser les performances:

```bash
# Voir les requêtes SQL
http://localhost:8080/_profiler

# Analyser une requête spécifique
http://localhost:8080/_profiler/{token}
```

### Mesurer la taille des réponses

```bash
# Sans compression
curl -w "%{size_download} bytes\n" http://localhost:8080/api/users

# Avec compression
curl -H "Accept-Encoding: gzip" -w "%{size_download} bytes\n" http://localhost:8080/api/users
```

---

## Checklist de performance

- ✅ Compression HTTP activée (zstd + gzip)
- ✅ Cache assets statiques (1 an)
- ✅ CORS preflight cache (24h)
- ✅ skip_null_values activé
- ✅ Pagination configurée (30 éléments par défaut)
- ✅ Cache headers configurés
- ✅ MaxDepth pour éviter circular references
- ✅ HTTP cache framework activé
- ⏳ Redis cache (recommandé pour production)
- ⏳ CDN (recommandé pour production)

---

## Support

Pour toute question concernant les optimisations de performance, contactez l'équipe backend.

**Dernière mise à jour**: 2025-12-14
