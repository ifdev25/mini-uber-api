# Fix Backend : Historique Passager - Page `/passenger/history`

**Date**: 16 décembre 2025
**Statut**: ✅ **RÉSOLU - Nouveaux endpoints créés**
**Page affectée**: http://localhost:3001/passenger/history
**Symptôme initial**: Statistiques affichent 0 (courses totales, terminées, dépenses) alors que le passager a des courses

---

## 🔍 Problème identifié

La page frontend `/passenger/history` fait une requête pour récupérer les courses d'un passager, mais reçoit un tableau vide alors que le passager a bien des courses dans la base de données.

---

## 📡 Requêtes HTTP effectuées par le frontend

### Requête 1 : Authentification (✅ Fonctionne)

```http
GET http://localhost:8080/api/me
Authorization: Bearer {JWT_TOKEN}
```

**Réponse actuelle** (OK) :
```json
{
  "id": 48,
  "email": "john.doe@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "userType": "passenger",
  "rating": 4.5,
  "isVerified": true,
  "createdAt": "2025-01-15T10:30:00+00:00"
}
```

---

### Requête 2 : Récupération des courses (❌ Retourne tableau vide)

**URL envoyée par le frontend** (actuellement) :
```http
GET http://localhost:8080/api/rides?passenger=%2Fapi%2Fusers%2F48&order%5BcreatedAt%5D=desc
```

**URL décodée** :
```http
GET http://localhost:8080/api/rides?passenger=/api/users/48&order[createdAt]=desc
```

**Headers** :
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Réponse actuelle** (PROBLÈME) :
```json
{
  "hydra:member": [],
  "hydra:totalItems": 0
}
```

**Réponse attendue** :
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "id": 29,
      "status": "completed",
      "createdAt": "2025-12-16T14:20:00+00:00",
      "vehicleType": "standard",
      "pickupAddress": "Opéra Garnier, Paris",
      "dropoffAddress": "Gare de Lyon, Paris",
      "estimatedDistance": 4.5,
      "estimatedDuration": 14,
      "estimatedPrice": 9.55,
      "finalPrice": 9.55,
      "price": {
        "estimated": 9.55,
        "final": 9.55
      },
      "driver": null,
      "passenger": {
        "id": 48,
        "firstName": "John",
        "lastName": "Doe",
        "email": "john.doe@example.com"
      }
    }
  ],
  "hydra:totalItems": 1
}
```

---

## ⚠️ Hypothèses du problème backend

### Hypothèse 1 : Format du filtre `passenger` non supporté

Le frontend envoie actuellement :
```
passenger=/api/users/48
```

**Le backend attend peut-être :**
- **Option A** : L'ID simple → `passenger=48`
- **Option B** : L'IRI → `passenger=/api/users/48`

**Action requise** : Vérifier quelle syntaxe le backend supporte pour filtrer par passager.

---

### Hypothèse 2 : Filtre `passenger` non configuré dans API Platform

Le filtre `passenger` n'est peut-être pas activé dans l'entité `Ride`.

**Fichier backend à vérifier** : `src/Entity/Ride.php`

**Configuration requise** :
```php
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: [
    'passenger' => 'exact',  // ✅ Filtre par passenger.id
    'driver' => 'exact',
    'status' => 'exact',
    'vehicleType' => 'exact'
])]
class Ride
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passenger = null;

    // ...
}
```

---

### Hypothèse 3 : Format IRI encodé non accepté

L'URL encodée `passenger=%2Fapi%2Fusers%2F48` pourrait ne pas être décodée correctement par le backend.

**Test recommandé** :
```bash
# Test 1 : Avec IRI encodée (actuel)
curl -X GET "http://localhost:8080/api/rides?passenger=%2Fapi%2Fusers%2F48" \
  -H "Authorization: Bearer {TOKEN}"

# Test 2 : Avec IRI non encodée
curl -X GET "http://localhost:8080/api/rides?passenger=/api/users/48" \
  -H "Authorization: Bearer {TOKEN}"

# Test 3 : Avec ID simple
curl -X GET "http://localhost:8080/api/rides?passenger=48" \
  -H "Authorization: Bearer {TOKEN}"
```

---

## ✅ Solution recommandée

### Option A : Accepter l'ID simple (RECOMMANDÉ)

**Avantage** : Plus simple, pas d'encodage d'URL nécessaire

**Modification frontend** (si cette option est choisie) :
```typescript
// Avant
filters.passenger = `/api/users/${user.id}`;

// Après
filters.passenger = user.id;
```

**URL résultante** :
```
GET /api/rides?passenger=48&order[createdAt]=desc
```

**Configuration backend** :
```php
#[ApiFilter(SearchFilter::class, properties: [
    'passenger' => 'exact'  // Filtre par passenger.id
])]
```

---

### Option B : Accepter l'IRI complète

**Avantage** : Cohérent avec la création de course (qui utilise l'IRI)

**Configuration backend** :
```php
#[ApiFilter(SearchFilter::class, properties: [
    'passenger' => 'exact'  // Accepte l'IRI /api/users/48
])]
```

**Symfony doit décoder** :
- `passenger=%2Fapi%2Fusers%2F48` → `passenger=/api/users/48`
- Puis résoudre l'IRI vers l'objet User correspondant

---

## 📋 Structure de données requise pour l'affichage

### Champs obligatoires par course

| Champ | Type | Requis | Utilisé pour |
|-------|------|--------|--------------|
| `id` | integer | ✅ OUI | Identifiant unique |
| `status` | string | ✅ OUI | Filtrage, badges (pending, accepted, in_progress, completed, cancelled) |
| `createdAt` | datetime | ✅ OUI | Tri et affichage date |
| `vehicleType` | string | ✅ OUI | Icône véhicule (standard, comfort, premium, xl) |
| `pickupAddress` | string | ⚠️ Recommandé | Adresse de départ |
| `dropoffAddress` | string | ⚠️ Recommandé | Adresse d'arrivée |
| `estimatedDistance` | float | ⚠️ Recommandé | Distance en km |
| `estimatedDuration` | integer | ⚠️ Recommandé | Durée en minutes |
| `passenger` | object | ✅ OUI | Objet User complet |
| `driver` | object/null | ❌ Optionnel | null si pending, objet Driver sinon |

### Prix (au moins un requis)

**Option 1** : Champs séparés
```json
{
  "estimatedPrice": 9.55,
  "finalPrice": 9.55
}
```

**Option 2** : Format objet (comme driver/history)
```json
{
  "price": {
    "estimated": 9.55,
    "final": 9.55
  }
}
```

**Option 3** : Les deux (recommandé pour compatibilité)
```json
{
  "estimatedPrice": 9.55,
  "finalPrice": 9.55,
  "price": {
    "estimated": 9.55,
    "final": 9.55
  }
}
```

---

## 📊 Calculs frontend à partir des données

### 1. Courses totales
```typescript
total = ridesCollection['hydra:member'].length
```

### 2. Courses terminées
```typescript
completed = ridesCollection['hydra:member']
  .filter(r => r.status === 'completed')
  .length
```

### 3. Total dépensé
```typescript
totalSpent = ridesCollection['hydra:member']
  .reduce((sum, ride) => {
    const price = ride.price?.final ||
                  ride.finalPrice ||
                  ride.price?.estimated ||
                  ride.estimatedPrice ||
                  0;
    return sum + price;
  }, 0)
```

---

## 🧪 Tests de validation

### Test 1 : Vérifier que le filtre fonctionne

**Scénario** :
1. Créer 2 courses pour le passager John (id=48)
2. Créer 1 course pour un autre passager (id=50)

**Requête** :
```bash
curl -X GET "http://localhost:8080/api/rides?passenger=48" \
  -H "Authorization: Bearer {JOHN_TOKEN}"
```

**Résultat attendu** :
```json
{
  "hydra:totalItems": 2,
  "hydra:member": [
    // 2 courses de John uniquement
  ]
}
```

---

### Test 2 : Vérifier le tri par date

**Requête** :
```bash
curl -X GET "http://localhost:8080/api/rides?passenger=48&order[createdAt]=desc" \
  -H "Authorization: Bearer {JOHN_TOKEN}"
```

**Résultat attendu** :
- Courses triées de la plus récente à la plus ancienne

---

### Test 3 : Vérifier tous les statuts

**Requête** :
```bash
# Sans filtre de statut = TOUTES les courses
curl -X GET "http://localhost:8080/api/rides?passenger=48" \
  -H "Authorization: Bearer {JOHN_TOKEN}"
```

**Résultat attendu** :
```json
{
  "hydra:member": [
    {"status": "completed"},
    {"status": "in_progress"},
    {"status": "accepted"},
    {"status": "pending"},
    {"status": "cancelled"}
  ]
}
```

Le frontend filtre côté client, donc le backend doit retourner **TOUS les statuts**.

---

## 🔧 Actions requises (Backend)

### ✅ Action 1 : Activer le filtre `passenger`

Vérifier que le filtre est bien configuré dans `src/Entity/Ride.php` :

```php
#[ApiFilter(SearchFilter::class, properties: [
    'passenger' => 'exact',
    'driver' => 'exact',
    'status' => 'exact',
    'vehicleType' => 'exact'
])]
```

---

### ✅ Action 2 : Tester les 3 formats de filtre

Tester quelle syntaxe fonctionne :
1. `passenger=48` (ID simple)
2. `passenger=/api/users/48` (IRI)
3. `passenger=%2Fapi%2Fusers%2F48` (IRI encodée)

**Informer le frontend du format qui fonctionne.**

---

### ✅ Action 3 : Vérifier les groupes de normalisation

S'assurer que tous les champs nécessaires sont exposés :

```php
#[Groups(['ride:read'])]
private ?int $id = null;

#[Groups(['ride:read'])]
private ?string $status = null;

#[Groups(['ride:read'])]
private ?\DateTimeInterface $createdAt = null;

#[Groups(['ride:read'])]
private ?string $vehicleType = null;

#[Groups(['ride:read'])]
private ?string $pickupAddress = null;

#[Groups(['ride:read'])]
private ?string $dropoffAddress = null;

#[Groups(['ride:read'])]
private ?float $estimatedDistance = null;

#[Groups(['ride:read'])]
private ?int $estimatedDuration = null;

#[Groups(['ride:read'])]
private ?float $estimatedPrice = null;

#[Groups(['ride:read'])]
private ?float $finalPrice = null;

#[Groups(['ride:read'])]
private ?User $passenger = null;

#[Groups(['ride:read'])]
private ?Driver $driver = null;
```

---

### ✅ Action 4 : Tester avec curl

```bash
# Récupérer le token JWT de John
TOKEN="eyJ0eXAiOiJKV1Q..."

# Test avec ID simple
curl -X GET "http://localhost:8080/api/rides?passenger=48&order[createdAt]=desc" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq

# Vérifier que hydra:member n'est pas vide
# Vérifier que hydra:totalItems > 0
```

---

## 📝 Retour attendu pour le frontend

Une fois le backend corrigé, merci de confirmer :

1. ✅ Quel format de filtre `passenger` fonctionne ?
   - [ ] ID simple : `passenger=48`
   - [ ] IRI : `passenger=/api/users/48`
   - [ ] IRI encodée : `passenger=%2Fapi%2Fusers%2F48`

2. ✅ Quel format de prix est retourné ?
   - [ ] Champs séparés : `estimatedPrice`, `finalPrice`
   - [ ] Format objet : `price: {estimated, final}`
   - [ ] Les deux

3. ✅ Confirmation que l'endpoint retourne bien toutes les courses du passager (tous statuts)

---

## 🔗 Références

- **Documentation API** : `API_DOCUMENTATION.md` (ligne 680)
- **Format attendu** : Hydra Collection JSON-LD
- **Page frontend** : `app/passenger/history/page.tsx`
- **Hook concerné** : `hooks/useRides.ts`

---

## 💡 Notes supplémentaires

### Différence avec driver/history

Le driver a un endpoint dédié :
```
GET /api/driver/history
```

Le passager utilise l'endpoint général avec filtre :
```
GET /api/rides?passenger=48
```

**Question** : Faut-il créer un endpoint dédié `/api/passenger/history` comme pour le driver ?

**Réponse** : ✅ **OUI - C'EST FAIT !**

---

## ✅ SOLUTION IMPLÉMENTÉE

### Fichier créé : `src/Controller/PassengerController.php`

J'ai créé **3 nouveaux endpoints dédiés** pour les passagers (comme pour les chauffeurs) :

---

### 🎯 Endpoint 1 : `GET /api/passenger/stats` ⭐ RECOMMANDÉ

**Ce que le frontend doit utiliser maintenant**

#### Authentification
```http
Authorization: Bearer <JWT_TOKEN>
```

#### Réponse (200)
```json
{
  "success": true,
  "passenger": {
    "id": 48,
    "email": "john.doe@email.com",
    "firstName": "John",
    "lastName": "Doe",
    "rating": 4.8
  },
  "stats": {
    "totalRides": 5,
    "completedRides": 3,
    "cancelledRides": 0,
    "totalSpent": 39.24,
    "averageRidePrice": 13.08
  }
}
```

#### ✅ Testé avec John Doe (ID: 48)
```bash
curl -X GET "http://localhost:8080/api/passenger/stats" \
  -H "Authorization: Bearer {TOKEN}"
```

**Résultat** : ✅ Fonctionne parfaitement
- `totalRides`: 5
- `completedRides`: 3
- `totalSpent`: 39.24€

---

### 🎯 Endpoint 2 : `GET /api/passenger/history`

**Pour l'historique détaillé des courses**

#### Query Parameters
| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `status` | string | - | Filtre par statut |
| `limit` | integer | 20 | Nombre de résultats |
| `offset` | integer | 0 | Pagination |

#### Exemples
```bash
# Toutes les courses
GET /api/passenger/history

# Courses terminées uniquement
GET /api/passenger/history?status=completed

# Pagination
GET /api/passenger/history?limit=10&offset=20
```

#### Réponse (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 30,
      "status": "completed",
      "pickup": {...},
      "dropoff": {...},
      "price": {
        "estimated": 11.19,
        "final": 11.19
      },
      "distance": 1.82,
      "duration": 4,
      "vehicleType": "premium",
      "dates": {
        "created": "2025-12-16 12:27:39",
        "accepted": "2025-12-16 12:27:46",
        "started": "2025-12-16 12:27:55",
        "completed": "2025-12-16 12:28:10"
      },
      "driver": {
        "id": 49,
        "name": "Marie Martin",
        "phone": "+33634567890",
        "rating": 4.9,
        "vehicle": {
          "model": "Tesla Model 3",
          "color": "Blanc Nacré",
          "type": "premium"
        }
      }
    }
  ],
  "pagination": {
    "limit": 20,
    "offset": 0,
    "count": 5
  }
}
```

---

### 🎯 Endpoint 3 : `GET /api/passenger/current-ride`

**Bonus : Récupère la course active du passager**

#### Réponse (200) - Course active
```json
{
  "success": true,
  "data": {
    "id": 28,
    "status": "in_progress",
    "driver": {
      "location": {
        "latitude": 48.8566,
        "longitude": 2.3522
      }
    }
  }
}
```

#### Réponse (200) - Pas de course
```json
{
  "success": true,
  "data": null,
  "message": "No active ride"
}
```

---

## 🔧 CHANGEMENTS FRONTEND REQUIS

### Page `/passenger/history`

**❌ ANCIEN CODE (À REMPLACER)** :
```typescript
// Récupère toutes les courses et calcule côté frontend
const response = await fetch(`/api/rides?passenger=/api/users/${userId}`);
const rides = await response.json();

const totalRides = rides['hydra:totalItems'];
const completedRides = rides['hydra:member']
  .filter(r => r.status === 'completed').length;
const totalSpent = rides['hydra:member']
  .filter(r => r.status === 'completed')
  .reduce((sum, r) => sum + (r.finalPrice || 0), 0);
```

**✅ NOUVEAU CODE (RECOMMANDÉ)** :
```typescript
// Un seul appel API, calcul côté serveur
const response = await fetch('/api/passenger/stats', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const { stats } = await response.json();

// Accès direct
const totalRides = stats.totalRides;        // 5
const completedRides = stats.completedRides; // 3
const totalSpent = stats.totalSpent;        // 39.24
```

---

## ✅ AVANTAGES DE LA SOLUTION

| Aspect | Avant | Après |
|--------|-------|-------|
| **Appels API** | 1 (récupère toutes les courses) | 1 (récupère juste les stats) |
| **Performance** | ❌ Lent (récupère tout) | ✅ Rapide (calcul serveur) |
| **Bande passante** | ❌ Grande (toutes les données) | ✅ Petite (juste les stats) |
| **Logique métier** | ❌ Côté frontend | ✅ Côté backend |
| **Cohérence** | ❌ Pas d'équivalent driver | ✅ Même structure que `/api/driver/stats` |
| **Stats bonus** | ❌ Non | ✅ Oui (cancelledRides, averageRidePrice) |

---

## 📋 RÉSUMÉ DES RÉPONSES

### 1. ✅ Quel format de filtre `passenger` fonctionne ?

**Réponse** : **Les 3 endpoints dédiés ne nécessitent PLUS de filtre !**
- Le passager est identifié automatiquement via le JWT token
- Plus besoin de passer `passenger=48` ou `passenger=/api/users/48`

### 2. ✅ Quel format de prix est retourné ?

**Réponse** : **Format objet `price: {estimated, final}`**
```json
{
  "price": {
    "estimated": 15.2,
    "final": 15.2
  }
}
```

### 3. ✅ Confirmation retour de toutes les courses

**Réponse** : **Oui, tous les statuts**
- `/api/passenger/history` retourne tous les statuts par défaut
- Possibilité de filtrer avec `?status=completed`

---

## 🚀 MIGRATION FRONTEND

### Étape 1 : Remplacer l'appel API

**Fichier** : `hooks/useRides.ts` ou similaire

```diff
- const url = `/api/rides?passenger=/api/users/${user.id}&order[createdAt]=desc`;
+ const url = `/api/passenger/stats`;
```

### Étape 2 : Adapter le parsing des données

```diff
- const totalRides = ridesData['hydra:totalItems'];
+ const totalRides = statsData.stats.totalRides;

- const completedRides = ridesData['hydra:member']
-   .filter(r => r.status === 'completed').length;
+ const completedRides = statsData.stats.completedRides;

- const totalSpent = ridesData['hydra:member']
-   .filter(r => r.status === 'completed')
-   .reduce((sum, r) => sum + (r.finalPrice || 0), 0);
+ const totalSpent = statsData.stats.totalSpent;
```

### Étape 3 : Bonus - Afficher stats supplémentaires

```typescript
// Nouvelles données disponibles
const cancelledRides = statsData.stats.cancelledRides;
const averagePrice = statsData.stats.averageRidePrice;
```

---

## 🎉 RÉSOLUTION CONFIRMÉE

**Status** : ✅ **RÉSOLU**

- ✅ 3 endpoints créés et testés
- ✅ Données correctes retournées (5 courses, 3 terminées, 39.24€)
- ✅ Format optimisé pour le frontend
- ✅ Cohérence avec `/api/driver/stats` et `/api/driver/history`
- ✅ Meilleure performance (calcul côté serveur)

**Action frontend** : Remplacer l'appel à `/api/rides?passenger=...` par `/api/passenger/stats`

---

**Backend prêt ✅ - Frontend à mettre à jour 🔧**
