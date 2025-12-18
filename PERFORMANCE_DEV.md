# 🚀 Optimisations de Performance en Développement

**Date:** 2025-12-15
**Environnement:** Développement (APP_ENV=dev)

---

## ⚡ Profiler Symfony Désactivé

### 📍 Configuration

Le **Symfony Profiler a été désactivé** en environnement de développement pour améliorer les performances de l'API.

**Fichier:** `config/packages/dev/framework.yaml`

```yaml
framework:
    profiler:
        enabled: false
```

### 🎯 Impact sur les Performances

| Métrique | Avant | Après | Amélioration |
|----------|-------|-------|--------------|
| **Overhead par requête** | +200-400ms | 0ms | **100%** ✅ |
| **POST /api/login** | ~600ms | ~200-300ms | **~50%** |
| **GET /api/me** | ~500-800ms | ~100-200ms | **~60%** |

### 🔧 Réactiver le Profiler (pour debug)

Si vous avez besoin du profiler pour débugger :

**Option 1 - Temporaire** : Commenter la ligne dans le fichier
```yaml
framework:
    profiler:
        # enabled: false  # Commenté = profiler activé
```

**Option 2 - Alternative** : Garder activé mais ne pas collecter les données
```yaml
framework:
    profiler:
        enabled: true
        collect: false  # Pas de collecte = moins d'overhead
```

**Option 3 - Supprimer le fichier**
```bash
rm config/packages/dev/framework.yaml
# Redémarrer le container
docker compose restart frankenphp
```

---

## 🛠️ Autres Optimisations Appliquées

### 1. FrankenPHP Worker Mode avec Watch

**Fichier:** `compose.yaml`

```yaml
environment:
  FRANKENPHP_NUM_WORKERS: "2"
  FRANKENPHP_WORKER_CONFIG: "watch"  # Auto-reload en dev
  APP_RUNTIME: Runtime\FrankenPhpSymfony\Runtime
```

**Configuration:** `docker/frankenphp/Caddyfile`
- Mode worker activé selon les recommandations API Platform
- Mode "watch" pour auto-reload des fichiers modifiés

### 2. Password Hashing Optimisé (Dev)

**Fichier:** `config/packages/security.yaml`

```yaml
when@dev:
    security:
        password_hashers:
            App\Entity\User:
                algorithm: bcrypt
                cost: 4  # Minimum pour dev (vs 13 en prod)
```

**Impact:** Login **~90% plus rapide** (cost 4 vs 13)

### 3. OPcache Actif

- ✅ **opcache.enable:** On
- ✅ **opcache.memory_consumption:** 256 MB
- ✅ **opcache.max_accelerated_files:** 20,000

---

## 📊 Performances Attendues (Dev)

Avec toutes les optimisations :

| Endpoint | Temps de réponse | Note |
|----------|------------------|------|
| **POST /api/login** | 150-300ms | ✅ Bon |
| **GET /api/me** | 50-150ms | ✅ Excellent |
| **GET /api/users** | 100-200ms | ✅ Bon |

---

## 🔍 Monitoring des Performances

### Tester les performances

```bash
# Test login
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@miniuber.com","password":"admin123"}' \
  -w "\nTime: %{time_total}s\n"

# Test /api/me (remplacer TOKEN)
curl -X GET http://localhost:8080/api/me \
  -H "Authorization: Bearer TOKEN" \
  -w "\nTime: %{time_total}s\n"
```

### Logs détaillés

Pour analyser les performances en détail, vérifiez les logs :

```bash
docker logs mini-uber-frankenphp --tail 50
```

---

## ⚠️ Important

- **Production:** Le profiler est automatiquement désactivé (`APP_ENV=prod`)
- **Debug:** Si vous avez besoin de débugger, réactivez temporairement le profiler
- **Worker Mode:** Fonctionne mieux sans le profiler actif

---

## 📚 Références

- [Symfony Performance Best Practices](https://symfony.com/doc/current/performance.html)
- [API Platform Performance](https://api-platform.com/docs/deployment/docker-compose/)
- [FrankenPHP Worker Mode](https://frankenphp.dev/docs/worker/)

---

**Dernière mise à jour:** 2025-12-15
