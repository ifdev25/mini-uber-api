# ❌ ERREUR : Références circulaires dans l'API

## 🔴 Problème identifié par les tests

```
Error 500: The total number of joined relations has exceeded the specified maximum
```

**Cause :** En ajoutant `ride:read` partout, vous avez créé une boucle infinie :
```
Ride → Driver → User → Driver → Rides → Driver → User → ...
```

L'API Platform essaie de charger TOUTES les relations récursivement = **BOOM** 💥

---

## ✅ VRAIE SOLUTION : Limiter la profondeur de sérialisation

### Option 1 : `enable_max_depth` (RECOMMANDÉ)

Cette option est la plus simple et la plus propre.

#### Étape 1 : Activer `enable_max_depth` globalement

Dans `config/packages/framework.yaml` :

```yaml
framework:
    serializer:
        enable_max_depth: true  # ✅ Activer la profondeur max
```

#### Étape 2 : Limiter la profondeur dans les entités

Dans `src/Entity/Ride.php` :

```php
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\ManyToOne(targetEntity: Driver::class)]
#[ORM\JoinColumn(nullable: true)]
#[Groups(['ride:read', 'ride:write'])]
#[MaxDepth(1)]  // ✅ IMPORTANT : Limite à 1 niveau
private ?Driver $driver = null;
```

Dans `src/Entity/Driver.php` :

```php
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'drivers')]
#[ORM\JoinColumn(nullable: false)]
#[Groups(['driver:read', 'ride:read'])]
#[MaxDepth(1)]  // ✅ IMPORTANT : Limite à 1 niveau
private ?User $user = null;
```

Dans `src/Entity/User.php` :

```php
#[ORM\OneToMany(targetEntity: Driver::class, mappedBy: 'user')]
#[Groups(['user:read'])]  // ❌ NE PAS ajouter 'ride:read' ici !
#[MaxDepth(1)]  // ✅ Limite à 1 niveau
private Collection $drivers;
```

#### Résultat attendu

Avec `MaxDepth(1)`, la sérialisation s'arrête après 1 niveau :
- ✅ `Ride` → `Driver` (OK, niveau 0 → 1)
- ✅ `Driver` → `User` (OK, niveau 1 → 2, mais MaxDepth=1 donc on s'arrête)
- ❌ `User` → `Driver` → `Rides` (STOP, MaxDepth atteint)

Pas de boucle infinie !

---

### Option 2 : Groupes séparés (Plus complexe mais plus de contrôle)

Créez des groupes différents pour éviter les boucles.

#### Dans `src/Entity/Ride.php` :

```php
#[ORM\ManyToOne(targetEntity: Driver::class)]
#[ORM\JoinColumn(nullable: true)]
#[Groups(['ride:read', 'ride:write'])]
private ?Driver $driver = null;
```

#### Dans `src/Entity/Driver.php` :

```php
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
#[Groups(['driver:read', 'ride:driver:read'])]  // ✅ Groupe spécifique
private ?int $id = null;

#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: false)]
#[Groups(['driver:read', 'ride:driver:read'])]  // ✅ Groupe spécifique
private ?User $user = null;

#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
#[Groups(['driver:read', 'ride:driver:read'])]  // ✅ Pour la carte
private ?string $currentLatitude = null;

#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
#[Groups(['driver:read', 'ride:driver:read'])]  // ✅ Pour la carte
private ?string $currentLongitude = null;

// ... autres champs avec 'ride:driver:read'
```

#### Dans `src/Entity/User.php` :

```php
#[ORM\Id]
#[Groups(['user:read', 'ride:driver:user:read'])]  // ✅ Groupe spécifique
private ?int $id = null;

#[ORM\Column(length: 100)]
#[Groups(['user:read', 'ride:driver:user:read'])]  // ✅ Groupe spécifique
private ?string $firstName = null;

#[ORM\Column(length: 100)]
#[Groups(['user:read', 'ride:driver:user:read'])]  // ✅ Groupe spécifique
private ?string $lastName = null;

#[ORM\OneToMany(targetEntity: Driver::class, mappedBy: 'user')]
#[Groups(['user:read'])]  // ❌ PAS de 'ride:...' ici
private Collection $drivers;
```

#### Modifier la ressource Ride :

```php
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: [
                'groups' => ['ride:read', 'ride:driver:read', 'ride:driver:user:read']
            ]
        ),
        new GetCollection(
            normalizationContext: [
                'groups' => ['ride:read', 'ride:driver:read', 'ride:driver:user:read']
            ]
        ),
        // ...
    ]
)]
class Ride
{
    // ...
}
```

---

### Option 3 : Désactiver l'eager loading (Temporaire)

**⚠️ Utilisez UNIQUEMENT si les options 1 et 2 ne fonctionnent pas**

Dans `config/packages/api_platform.yaml` :

```yaml
api_platform:
    # ...
    eager_loading:
        enabled: true
        max_joins: 50  # ⚠️ Augmentez la limite (défaut: 30)
        # OU
        # enabled: false  # Désactiver complètement (non recommandé)
```

---

## 🧪 TESTER LA SOLUTION

### 1. Appliquer Option 1 (enable_max_depth) ✅ RECOMMANDÉ

```bash
# Dans votre projet backend :

# 1. Modifier framework.yaml
# 2. Ajouter #[MaxDepth(1)] sur Ride::$driver et Driver::$user
# 3. Vider le cache
php bin/console cache:clear
```

### 2. Tester avec curl

```bash
curl -X GET "http://localhost:8000/api/rides/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/ld+json"
```

**Réponse attendue (SANS erreur 500) :**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "driver": {
    "@id": "/api/drivers/1",
    "id": 1,
    "currentLatitude": "48.8566",
    "currentLongitude": "2.3522",
    "vehicleModel": "Toyota Prius",
    "user": {
      "@id": "/api/users/2",
      "id": 2,
      "firstName": "Békira",
      "lastName": "Dupont"
    }
  },
  "status": "accepted",
  "pickupAddress": "...",
  "dropoffAddress": "..."
}
```

✅ **Le driver est un objet complet avec ses coordonnées GPS !**

---

## 📋 CHECKLIST DE CORRECTION

### À faire dans le backend :

- [ ] Activer `enable_max_depth: true` dans `config/packages/framework.yaml`
- [ ] Ajouter `#[MaxDepth(1)]` sur `Ride::$driver`
- [ ] Ajouter `#[MaxDepth(1)]` sur `Driver::$user`
- [ ] Ajouter `#[MaxDepth(1)]` sur `User::$drivers` (si existant)
- [ ] Vider le cache : `php bin/console cache:clear`
- [ ] Tester `/api/rides/1` : doit retourner le driver en objet complet
- [ ] Tester `/api/rides` : ne doit plus retourner d'erreur 500

### Vérifier dans le frontend :

- [ ] Recharger la page de suivi de course
- [ ] Vérifier les logs console : `driver` doit être un object, pas une string
- [ ] Le chauffeur doit apparaître sur la carte (point bleu)

---

## 🐛 SI ÇA NE FONCTIONNE TOUJOURS PAS

### Vérifier les logs backend

```bash
tail -f var/log/dev.log
```

### Désactiver temporairement MaxDepth pour déboguer

Commentez `#[MaxDepth(1)]` et augmentez max_joins :

```yaml
# config/packages/api_platform.yaml
api_platform:
    eager_loading:
        max_joins: 100  # Temporaire pour déboguer
```

### Vérifier que les coordonnées GPS existent

```sql
SELECT id, currentLatitude, currentLongitude FROM driver WHERE id = 1;
```

Si `currentLatitude` ou `currentLongitude` sont NULL → le chauffeur doit mettre à jour sa position !

---

## 💡 RÉSUMÉ

**Problème :** Boucles circulaires `Ride → Driver → User → Driver → ...`

**Solution :** `enable_max_depth: true` + `#[MaxDepth(1)]`

**Résultat :** API retourne le driver complet SANS boucle infinie

**Effet frontend :** Le chauffeur apparaît enfin sur la carte ! 🎉

---

## 📞 SI BESOIN D'AIDE

Envoyez-moi :
1. Le contenu exact de votre `Ride.php` (les annotations)
2. Le contenu exact de votre `Driver.php` (les annotations)
3. La réponse de `curl -X GET http://localhost:8000/api/rides/1`
4. Les logs de `var/log/dev.log`
