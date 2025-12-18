# Optimisations de Performance API Platform - 2025

**Date**: 17 décembre 2025
**Statut**: ✅ **IMPLÉMENTÉ**

---

## 📊 Résumé des optimisations

Ce document décrit toutes les optimisations de performance implémentées selon les recommandations officielles d'API Platform 2025.

---

## 🚀 Optimisations implémentées

### 1. Configuration API Platform (config/packages/api_platform.yaml)

#### ✅ Pagination partielle
```yaml
pagination_partial: true
```
**Impact**: Évite les requêtes `COUNT()` coûteuses sur les grandes collections. Amélioration estimée: **30-50% sur les listes volumineuses**.

#### ✅ Eager Loading optimisé
```yaml
eager_loading:
    enabled: true
    fetch_partial: true  # Charge uniquement les champs nécessaires
    max_joins: 30
    force_eager: true
```
**Impact**:
- `fetch_partial: true` réduit la mémoire et le temps de requête en chargeant uniquement les colonnes dans les serialization groups
- Prévient le problème N+1 avec eager loading forcé

#### ✅ Cache HTTP optimisé
```yaml
cache_headers:
    max_age: 60          # Cache navigateur: 60 secondes
    shared_max_age: 3600 # Cache proxy (Varnish/CDN): 1 heure
```
**Impact**: Réduit drastiquement les requêtes répétées. Les réponses identiques sont servies depuis le cache.

---

### 2. Optimisation des entités

#### ✅ Serialization groups stricts (User.php)

**Avant**:
```php
#[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'driver')]
#[Groups(['user:read'])]  // ⚠️ Chargé à chaque requête user
#[MaxDepth(2)]            // ⚠️ Trop profond
private Collection $ridesAsDriver;

#[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'rater')]
// ❌ Pas de serialization group = lazy loading risqué
private Collection $ratingsGiven;
```

**Après**:
```php
#[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'driver')]
#[Groups(['user:rides:read'])]  // ✅ Chargé uniquement si explicitement demandé
#[MaxDepth(1)]                   // ✅ Limite la profondeur
private Collection $ridesAsDriver;

#[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'rater')]
#[Groups(['user:ratings:read'])] // ✅ Contrôle explicite du chargement
private Collection $ratingsGiven;
```

**Impact**:
- Élimine le lazy loading non contrôlé
- Réduit les requêtes N+1
- Les collections ne sont chargées que lorsque explicitement demandées

#### ✅ MaxDepth réduit sur les relations (Ride.php)

**Avant**:
```php
#[ORM\ManyToOne(inversedBy: 'ridesAsPassenger')]
#[Groups(['ride:read', 'ride:write'])]
// ❌ Pas de MaxDepth = peut charger toute la hiérarchie
private ?User $passenger = null;
```

**Après**:
```php
#[ORM\ManyToOne(inversedBy: 'ridesAsPassenger')]
#[Groups(['ride:read', 'ride:write'])]
#[MaxDepth(1)]  // ✅ Limite la profondeur de sérialisation
private ?User $passenger = null;
```

**Impact**: Empêche la sérialisation récursive excessive.

---

### 3. Index de base de données (migrations/Version20251217100615.php)

#### ✅ Index sur colonnes fréquemment filtrées

**User table**:
```sql
CREATE INDEX idx_user_usertype ON "user" (usertype);
CREATE INDEX idx_user_rating ON "user" (rating);
CREATE INDEX idx_user_createdat ON "user" (createdat);
```

**Ride table**:
```sql
CREATE INDEX idx_ride_status ON ride (status);
CREATE INDEX idx_ride_vehicletype ON ride (vehicle_type);
CREATE INDEX idx_ride_createdat ON ride (created_at);
CREATE INDEX idx_ride_passenger ON ride (passenger_id);
CREATE INDEX idx_ride_driver ON ride (driver_id);
CREATE INDEX idx_ride_completedat ON ride (completed_at);
```

**Driver table**:
```sql
CREATE INDEX idx_driver_isavailable ON driver (isavailable);
CREATE INDEX idx_driver_isverified ON driver (isverified);
CREATE INDEX idx_driver_vehicletype ON driver (vehicletype);
```

**Rating table**:
```sql
CREATE INDEX idx_rating_score ON rating (score);
CREATE INDEX idx_rating_ride ON rating (ride_id);
```

**Impact**:
- Accélère les requêtes avec filtres: **10x à 100x plus rapide** sur grandes tables
- Améliore les performances des `SearchFilter`, `OrderFilter`, et `RangeFilter`

---

### 4. Configuration Production (config/packages/prod/)

#### ✅ Cache APCu activé (framework.yaml)
```yaml
framework:
    cache:
        app: cache.adapter.apcu
        system: cache.adapter.apcu
```

**Impact**: Cache en mémoire ultra-rapide pour les métadonnées API Platform.

#### ✅ Cache Doctrine optimisé (doctrine.yaml)
```yaml
doctrine:
    orm:
        metadata_cache_driver:
            type: apcu
        query_cache_driver:
            type: apcu
        result_cache_driver:
            type: apcu
```

**Impact**: Réduit le temps de parsing des métadonnées et des requêtes DQL.

---

## 📈 Gains de performance estimés

| Optimisation | Gain estimé | Impact |
|-------------|------------|---------|
| **Pagination partielle** | 30-50% | Sur collections volumineuses |
| **Fetch partial** | 20-40% | Réduit mémoire et temps de sérialisation |
| **Serialization groups stricts** | 40-60% | Élimine lazy loading N+1 |
| **Index de base de données** | 10x-100x | Requêtes avec filtres |
| **Cache APCu** | 2-3x | Temps de réponse API |
| **HTTP Cache** | 10x-1000x | Requêtes répétées identiques |

**Gain global estimé**: **3x à 10x** sur les endpoints les plus utilisés.

---

## ✅ Recommandations supplémentaires

### 1. FrankenPHP Worker Mode (déjà actif)
Vous utilisez déjà FrankenPHP qui offre des performances supérieures à NGINX + PHP-FPM.

### 2. Installer symfony/json-streamer (optionnel)
```bash
composer require symfony/json-streamer
```
Puis activer dans `api_platform.yaml`:
```yaml
enable_json_streamer: true
```
**Impact**: **10x meilleures performances** sur la sérialisation JSON de grandes collections.

### 3. Configurer un reverse proxy (Varnish/Caddy)
Pour profiter pleinement du cache HTTP avec invalidation automatique.

### 4. Monitoring avec Blackfire.io
Pour identifier les bottlenecks en production.

---

## 🔧 Configuration OPcache recommandée

Ajouter dans `docker/frankenphp/conf.d/app.prod.ini`:
```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.enable_cli=0
```

---

## 📝 Notes importantes

1. **Environnement dev**: Les optimisations de cache sont minimales pour faciliter le développement
2. **Environnement prod**: Toutes les optimisations de cache sont actives
3. **Migration**: La migration des index doit être exécutée en production pour bénéficier des gains

---

## 🧪 Tests de performance

Pour mesurer l'impact, comparer les temps de réponse avant/après:

```bash
# Test endpoint rides
time curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/rides

# Test endpoint users
time curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/users

# Test avec pagination
time curl -H "Authorization: Bearer TOKEN" "http://localhost:8080/api/rides?page=1&itemsPerPage=30"
```

---

## 📚 Sources

- [API Platform Performance Documentation](https://api-platform.com/docs/core/performance/)
- [2025: Performance Milestone for the Symfony Ecosystem](https://soyuka.me/2025-performance-milestone-for-the-symfony-ecosystem/)
- [Symfony Performance Best Practices](https://symfony.com/doc/current/performance.html)
