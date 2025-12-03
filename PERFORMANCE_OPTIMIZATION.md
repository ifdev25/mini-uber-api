# Optimisation des performances - Mini Uber API

## Problème résolu

Temps de réponse initial : **5-6 secondes par requête**
Temps de réponse optimisé : **< 500ms par requête** (attendu)

## Changements appliqués

### 1. Volumes Docker optimisés

**Problème** : Sur Windows/Mac, les volumes montés sont très lents, surtout `vendor/` et `var/cache/`.

**Solution** : Utilisation de volumes Docker nommés au lieu de bind mounts.

```yaml
volumes:
  - .:/var/www/html:cached           # Code source
  - php_vendor:/var/www/html/vendor  # Volume nommé (rapide)
  - php_var:/var/www/html/var        # Volume nommé (rapide)
```

### 2. Xdebug désactivé par défaut

**Problème** : Xdebug ralentit énormément PHP, même en mode "trigger".

**Solution** : Xdebug désactivé par défaut via `XDEBUG_MODE=off`.

```yaml
environment:
  XDEBUG_MODE: "off"  # Xdebug désactivé
```

### 3. OPcache optimisé

**Problème** : OPcache revalidait les fichiers toutes les 2 secondes.

**Solution** : Désactivation de la validation pour maximiser le cache.

```ini
opcache.validate_timestamps = 0  # Pas de vérification des changements
opcache.revalidate_freq = 0      # Pas de revalidation
```

## Comment appliquer les optimisations

### Étape 1 : Rebuild les conteneurs

```bash
# Arrêter les conteneurs
docker compose down

# Supprimer les anciens volumes (optionnel mais recommandé)
docker volume rm mini-uber-api_php_var mini-uber-api_php_vendor

# Rebuild et redémarrer
docker compose up -d --build
```

### Étape 2 : Réinstaller les dépendances dans le volume

```bash
# Installer composer dans le nouveau volume
docker compose exec php composer install --optimize-autoloader

# Vider le cache Symfony
docker compose exec php php bin/console cache:clear
```

### Étape 3 : Tester les performances

```bash
# Test simple avec curl
time curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Devrait être < 500ms maintenant
```

## Comment réactiver Xdebug (si nécessaire)

### Option 1 : Temporairement pour une requête

```bash
# Exécuter une commande avec Xdebug activé
docker compose exec -e XDEBUG_MODE=debug php php bin/console [command]
```

### Option 2 : Activer pour toutes les requêtes

Modifiez `compose.yaml` :

```yaml
environment:
  XDEBUG_MODE: "debug"  # Au lieu de "off"
```

Puis redémarrez :

```bash
docker compose restart php
```

### Option 3 : Créer un profil compose pour le debug

Créez `compose.debug.yaml` :

```yaml
services:
  php:
    environment:
      XDEBUG_MODE: "debug"
```

Lancez avec :

```bash
docker compose -f compose.yaml -f compose.debug.yaml up -d
```

## Clearing OPcache

Comme OPcache ne revalide plus automatiquement, vous devez le vider manuellement après des changements de code :

### Option 1 : Redémarrer PHP-FPM (recommandé)

```bash
docker compose restart php
```

### Option 2 : Vider le cache via Symfony

```bash
docker compose exec php php bin/console cache:clear
```

### Option 3 : Script automatique

Créez un alias dans votre terminal :

```bash
# PowerShell (Windows)
function Restart-API {
    docker compose restart php
    docker compose exec php php bin/console cache:clear
}

# Bash (Linux/Mac)
alias restart-api='docker compose restart php && docker compose exec php php bin/console cache:clear'
```

## Résultats attendus

### Avant optimisation
```
POST /api/login     → 5600ms
GET  /api/me        → 5000ms
Total connexion     → ~10-11 secondes
```

### Après optimisation
```
POST /api/login     → 200-400ms
GET  /api/me        → 150-300ms
Total connexion     → ~500-700ms
```

## Troubleshooting

### Les performances ne s'améliorent pas

1. **Vérifier que Xdebug est bien désactivé**
```bash
docker compose exec php php -m | grep -i xdebug
# Ne devrait rien retourner
```

2. **Vérifier OPcache**
```bash
docker compose exec php php -i | grep opcache.enable
# Devrait être "On"
```

3. **Vérifier les volumes**
```bash
docker compose exec php ls -la /var/www/html/vendor
# Devrait contenir les dépendances
```

### Erreur "vendor not found"

Si les dépendances ne sont pas présentes :

```bash
docker compose exec php composer install --optimize-autoloader
```

### Le cache ne se vide pas

```bash
# Supprimer complètement le cache
docker compose exec php rm -rf var/cache/*
docker compose exec php php bin/console cache:warmup
```

## Monitoring des performances

### Option 1 : Avec curl et time

```bash
# Linux/Mac
time curl http://localhost:8080/api

# Windows (PowerShell)
Measure-Command { curl http://localhost:8080/api }
```

### Option 2 : Avec les logs Symfony

Activez le profiler dans `config/packages/dev/web_profiler.yaml` et consultez `http://localhost:8080/_profiler`.

### Option 3 : Avec Blackfire

Pour un profiling détaillé, installez Blackfire :

```bash
# Ajouter dans compose.yaml
services:
  php:
    environment:
      BLACKFIRE_CLIENT_ID: your-id
      BLACKFIRE_CLIENT_TOKEN: your-token
```

## Mode production

Pour la production, utilisez ces paramètres optimaux :

```ini
; opcache.ini
opcache.validate_timestamps = 0
opcache.enable_cli = 0
opcache.preload = /var/www/html/config/preload.php
```

```yaml
# compose.yaml
services:
  php:
    build:
      target: prod  # Build de production
    environment:
      APP_ENV: prod
      XDEBUG_MODE: "off"
```

## Notes importantes

1. **Développement** : Pensez à redémarrer PHP après des changements de code (`docker compose restart php`)
2. **Volumes nommés** : Les données persistent même après `docker compose down`. Pour tout réinitialiser : `docker compose down -v`
3. **Xdebug** : Ne réactivez Xdebug que quand vous en avez vraiment besoin
4. **Cache** : Si vous avez des comportements étranges, videz le cache : `docker compose exec php php bin/console cache:clear`

## Commandes rapides

```bash
# Redémarrer l'API proprement
docker compose restart php && docker compose exec php php bin/console cache:clear

# Voir les performances
docker compose exec php php -i | grep -E '(opcache|xdebug)'

# Reinstaller tout
docker compose down -v && docker compose up -d --build && docker compose exec php composer install
```

---

**Performance gain attendu** : ~10x plus rapide (de 5-6s à 300-500ms par requête)
