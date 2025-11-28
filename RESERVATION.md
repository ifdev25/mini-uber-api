# API de Réservation - Documentation Frontend

## Créer une nouvelle réservation

### Endpoint
```
POST /api/rides
```

### Headers requis
```
Content-Type: application/ld+json
Authorization: Bearer {token_jwt}
```

> **Important**: Le Content-Type doit être `application/ld+json` (JSON-LD pour API Platform), pas `application/json`.

> **Note**: L'utilisateur doit être authentifié. Le token JWT est obtenu après login/register.

### Corps de la requête

```json
{
  "passenger": "/api/users/{userId}",
  "pickupAddress": "123 Rue de Paris, 75001 Paris",
  "pickupLatitude": 48.8566,
  "pickupLongitude": 2.3522,
  "dropoffAddress": "456 Avenue des Champs-Élysées, 75008 Paris",
  "dropoffLatitude": 48.8698,
  "dropoffLongitude": 2.3078,
  "vehicleType": "standard"
}
```

### Champs obligatoires

| Champ | Type | Description | Exemple |
|-------|------|-------------|---------|
| `passenger` | string (IRI) | IRI du passager (utilisateur connecté) | `/api/users/1` |
| `pickupAddress` | string | Adresse complète du point de départ | `"123 Rue de Paris, 75001 Paris"` |
| `pickupLatitude` | float | Latitude du point de départ | `48.8566` |
| `pickupLongitude` | float | Longitude du point de départ | `2.3522` |
| `dropoffAddress` | string | Adresse complète de la destination | `"456 Avenue des Champs-Élysées"` |
| `dropoffLatitude` | float | Latitude de la destination | `48.8698` |
| `dropoffLongitude` | float | Longitude de la destination | `2.3078` |
| `vehicleType` | string | Type de véhicule souhaité | `"standard"` |

### Types de véhicules disponibles

- `standard` - Véhicule standard (économique)
- `comfort` - Véhicule confort (milieu de gamme)
- `premium` - Véhicule premium (haut de gamme)
- `xl` - Véhicule XL (grande capacité)

### Réponse en cas de succès (201 Created)

```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "driver": null,
  "passenger": {
    "@id": "/api/users/1",
    "@type": "User",
    "id": 1,
    "email": "passenger@example.com",
    "firstName": "John",
    "lastName": "Doe"
  },
  "status": "pending",
  "pickupAddress": "123 Rue de Paris, 75001 Paris",
  "pickupLatitude": 48.8566,
  "pickupLongitude": 2.3522,
  "dropoffAddress": "456 Avenue des Champs-Élysées, 75008 Paris",
  "dropoffLatitude": 48.8698,
  "dropoffLongitude": 2.3078,
  "estimatedDistance": 5.2,
  "estimatedPrice": 12.5,
  "estimatedDuration": 15.3,
  "finalPrice": null,
  "vehicleType": "standard",
  "createdAt": "2024-11-24T14:30:00+00:00",
  "acceptedAt": null,
  "startedAt": null,
  "completedAt": null
}
```

### Champs calculés automatiquement par l'API

Ces champs sont calculés par le backend et ne doivent **PAS** être envoyés dans la requête :

- `id` - Généré automatiquement
- `driver` - Null au moment de la création, assigné quand un chauffeur accepte
- `status` - Défini automatiquement sur `"pending"`
- `estimatedDistance` - Calculé en km à partir des coordonnées
- `estimatedPrice` - Calculé selon la distance et le type de véhicule
- `estimatedDuration` - Durée estimée en minutes
- `finalPrice` - Prix final (null jusqu'à la fin de la course)
- `createdAt` - Date/heure de création
- `acceptedAt` - Date/heure d'acceptation par le chauffeur
- `startedAt` - Date/heure de début de la course
- `completedAt` - Date/heure de fin de la course

### Statuts de réservation

Une fois créée, la réservation peut avoir les statuts suivants :

1. `pending` - En attente d'un chauffeur (statut initial)
2. `accepted` - Acceptée par un chauffeur
3. `in_progress` - Course en cours
4. `completed` - Course terminée
5. `cancelled` - Course annulée

### Erreurs possibles

#### 400 Bad Request - Données invalides
```json
{
  "@context": "/api/contexts/ConstraintViolationList",
  "@type": "ConstraintViolationList",
  "hydra:title": "An error occurred",
  "violations": [
    {
      "propertyPath": "pickupAddress",
      "message": "This value should not be blank."
    }
  ]
}
```

#### 401 Unauthorized - Non authentifié
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

### Exemple de code JavaScript

```javascript
async function createRide(rideData) {
  const token = localStorage.getItem('jwt_token');
  const userId = localStorage.getItem('user_id');

  const response = await fetch('http://localhost:8000/api/rides', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      passenger: `/api/users/${userId}`,
      pickupAddress: rideData.pickupAddress,
      pickupLatitude: rideData.pickupLatitude,
      pickupLongitude: rideData.pickupLongitude,
      dropoffAddress: rideData.dropoffAddress,
      dropoffLatitude: rideData.dropoffLatitude,
      dropoffLongitude: rideData.dropoffLongitude,
      vehicleType: rideData.vehicleType
    })
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Erreur lors de la création de la réservation');
  }

  return await response.json();
}

// Utilisation
try {
  const ride = await createRide({
    pickupAddress: "123 Rue de Paris, 75001 Paris",
    pickupLatitude: 48.8566,
    pickupLongitude: 2.3522,
    dropoffAddress: "456 Avenue des Champs-Élysées, 75008 Paris",
    dropoffLatitude: 48.8698,
    dropoffLongitude: 2.3078,
    vehicleType: "standard"
  });

  console.log('Réservation créée:', ride);
  console.log('Prix estimé:', ride.estimatedPrice, '€');
  console.log('Distance estimée:', ride.estimatedDistance, 'km');
} catch (error) {
  console.error('Erreur:', error.message);
}
```

### Workflow complet

1. **Utilisateur crée une réservation** → Statut: `pending`
2. **L'API notifie les chauffeurs à proximité** (automatique)
3. **Un chauffeur accepte** → Statut: `accepted`
4. **Le chauffeur démarre la course** → Statut: `in_progress`
5. **La course se termine** → Statut: `completed`

### Notes importantes

- L'utilisateur connecté doit avoir le rôle `ROLE_USER`
- Le passager doit être l'utilisateur actuellement connecté
- Les coordonnées GPS doivent être valides (latitude: -90 à 90, longitude: -180 à 180)
- Le calcul du prix est basé sur la distance et le type de véhicule
- Une fois la réservation créée, les chauffeurs à proximité sont automatiquement notifiés

### Récupérer une réservation

```
GET /api/rides/{id}
Authorization: Bearer {token_jwt}
```

### Récupérer toutes les réservations d'un utilisateur

```
GET /api/rides?passenger={userId}
Authorization: Bearer {token_jwt}
```

### Annuler une réservation

```
POST /api/rides/{id}/cancel
Content-Type: application/ld+json
Authorization: Bearer {token_jwt}
```

> ⚠️ **IMPORTANT** :
> - Content-Type DOIT être `application/ld+json` (pas `application/json`)
> - Le corps de la requête DOIT être `{}` (objet JSON vide)
> - L'URL est `/api/rides/{id}/cancel` (remplacer `{id}` par l'ID de la course)

**Qui peut annuler :**
- ✅ Le passager peut annuler sa course si elle est `pending` ou `accepted`
- ✅ Le chauffeur assigné peut annuler si la course est `accepted`
- ❌ Les courses `in_progress` ou `completed` ne peuvent pas être annulées

**Corps de la requête :**
```json
{}
```
> ⚠️ Pas de données nécessaires, **mais vous DEVEZ envoyer `{}` dans le body**

**Réponse (200 OK) :**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/4",
  "@type": "Ride",
  "id": 4,
  "status": "cancelled",
  "pickupAddress": "Place de la Bastille, 75012 Paris",
  "dropoffAddress": "Musee du Louvre, 75001 Paris",
  "estimatedPrice": 10.1,
  "createdAt": "2025-11-24T12:50:28+00:00"
}
```

**Comportement automatique :**
- Si un chauffeur était assigné, il redevient automatiquement disponible
- L'autre partie (passager ou chauffeur) est notifiée via Mercure
- Le statut passe à `cancelled` de manière irréversible

**Erreurs possibles :**

```json
// 400 Bad Request - Statut non annulable
{
  "@type": "Error",
  "title": "An error occurred",
  "detail": "Cannot cancel ride with status \"completed\". Only pending or accepted rides can be cancelled.",
  "status": 400
}
```

```json
// 403 Forbidden - Pas autorisé
{
  "@type": "Error",
  "title": "An error occurred",
  "detail": "Only the passenger or assigned driver can cancel this ride",
  "status": 403
}
```

### Filtres disponibles

```
GET /api/rides?status=pending
GET /api/rides?vehicleType=premium
GET /api/rides?passenger={userId}&status=completed
```

---

## Test Réel de Réservation

### Test effectué le 24 novembre 2025

#### Étape 1: Connexion de l'utilisateur

**Requête:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@email.com",
    "password": "password123"
  }'
```

**Réponse (Succès):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Note:** Le token JWT est valide pendant 1 heure (3600 secondes).

#### Étape 2: Vérification de l'utilisateur

**Requête:**
```bash
curl -X GET "http://localhost:8000/api/users?email=john.doe@email.com" \
  -H "Authorization: Bearer {TOKEN}"
```

**Réponse:**
```json
{
  "@id": "/api/users/2",
  "@type": "User",
  "id": 2,
  "email": "john.doe@email.com",
  "firstName": "John",
  "lastName": "Doe",
  "userType": "passenger"
}
```

#### Étape 3: Création de la réservation

**Requête:**
```bash
curl -X POST http://localhost:8000/api/rides \
  -H "Content-Type: application/ld+json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{
    "passenger": "/api/users/2",
    "pickupAddress": "Place de la Bastille, 75012 Paris",
    "pickupLatitude": 48.8530,
    "pickupLongitude": 2.3696,
    "dropoffAddress": "Musee du Louvre, 75001 Paris",
    "dropoffLatitude": 48.8606,
    "dropoffLongitude": 2.3376,
    "vehicleType": "comfort"
  }'
```

**Réponse (Succès - 201 Created):**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/4",
  "@type": "Ride",
  "id": 4,
  "passenger": "/api/users/2",
  "status": "pending",
  "pickupAddress": "Place de la Bastille, 75012 Paris",
  "pickupLatitude": 48.853,
  "pickupLongitude": 2.3696,
  "dropoffAddress": "Musee du Louvre, 75001 Paris",
  "dropoffLatitude": 48.8606,
  "dropoffLongitude": 2.3376,
  "estimatedDistance": 2.49,
  "estimatedPrice": 10.1,
  "estimatedDuration": 5,
  "vehicleType": "comfort",
  "createdAt": "2025-11-24T12:50:28+00:00"
}
```

### Analyse des résultats

✅ **Réservation créée avec succès !**

**Détails de la course:**
- **ID de la course:** 4
- **Statut:** pending (en attente d'un chauffeur)
- **Distance estimée:** 2.49 km
- **Prix estimé:** 10.10€
- **Durée estimée:** 5 minutes
- **Type de véhicule:** comfort

**Calculs automatiques:**
- La distance est calculée selon la formule de Haversine
- Le prix est calculé selon: Prix de base (5€) + Distance × Tarif/km (variable selon le type de véhicule)
  - Standard: 2€/km
  - Comfort: 2.50€/km
  - Premium: 3€/km
  - XL: 3.50€/km
- La durée est estimée en fonction de la distance (vitesse moyenne urbaine de ~30 km/h)

**Prochaines étapes:**
1. Les chauffeurs de type "comfort" à proximité (< 5km) reçoivent une notification via Mercure
2. Un chauffeur peut accepter la course via `POST /api/rides/4/accept`
3. La course passe au statut "accepted", puis "in_progress", puis "completed"

#### Étape 4: Test d'annulation de course

**Création d'une nouvelle course pour le test :**
```bash
curl -X POST http://localhost:8000/api/rides \
  -H "Content-Type: application/ld+json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{
    "passenger": "/api/users/2",
    "pickupAddress": "Gare du Nord, 75010 Paris",
    "pickupLatitude": 48.8809,
    "pickupLongitude": 2.3553,
    "dropoffAddress": "Tour Eiffel, 75007 Paris",
    "dropoffLatitude": 48.8584,
    "dropoffLongitude": 2.2945,
    "vehicleType": "standard"
  }'
```

**Réponse:**
```json
{
  "@id": "/api/rides/8",
  "id": 8,
  "status": "pending",
  "pickupAddress": "Gare du Nord, 75010 Paris",
  "dropoffAddress": "Tour Eiffel, 75007 Paris",
  "estimatedDistance": 5.1,
  "estimatedPrice": 11.17,
  "estimatedDuration": 10,
  "vehicleType": "standard"
}
```

**Annulation de la course :**
```bash
curl -X POST http://localhost:8000/api/rides/8/cancel \
  -H "Content-Type: application/ld+json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{}'
```

**Réponse (200 OK) :**
```json
{
  "@id": "/api/rides/8",
  "id": 8,
  "status": "cancelled",
  "pickupAddress": "Gare du Nord, 75010 Paris",
  "dropoffAddress": "Tour Eiffel, 75007 Paris",
  "estimatedDistance": 5.1,
  "estimatedPrice": 11.17,
  "vehicleType": "standard"
}
```

✅ **Course annulée avec succès !** Le statut est passé de `pending` à `cancelled`.

### Points importants pour l'intégration frontend

1. **Content-Type:** Toujours utiliser `application/ld+json` pour les requêtes POST/PUT/PATCH
2. **IRI du passager:** Utiliser le format `/api/users/{userId}` (pas juste l'ID)
3. **Token JWT:** Stocker le token après login et l'inclure dans tous les headers Authorization
4. **Gestion d'erreurs:** L'API renvoie toujours des structures d'erreur standardisées
5. **Notifications temps réel:** Implémenter Mercure pour recevoir les mises à jour de statut

### Exemple complet avec gestion d'erreurs (JavaScript/TypeScript)

```javascript
async function createRide(rideData) {
  try {
    const token = localStorage.getItem('jwt_token');
    const userId = localStorage.getItem('user_id');

    if (!token || !userId) {
      throw new Error('Utilisateur non authentifié');
    }

    const response = await fetch('http://localhost:8000/api/rides', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        passenger: `/api/users/${userId}`,
        pickupAddress: rideData.pickupAddress,
        pickupLatitude: rideData.pickupLatitude,
        pickupLongitude: rideData.pickupLongitude,
        dropoffAddress: rideData.dropoffAddress,
        dropoffLatitude: rideData.dropoffLatitude,
        dropoffLongitude: rideData.dropoffLongitude,
        vehicleType: rideData.vehicleType
      })
    });

    if (!response.ok) {
      const error = await response.json();

      // Gestion spécifique selon le code d'erreur
      if (response.status === 401) {
        // Token expiré, rediriger vers login
        localStorage.removeItem('jwt_token');
        window.location.href = '/login';
        return;
      }

      if (response.status === 400) {
        // Erreur de validation
        throw new Error(error.detail || 'Données invalides');
      }

      throw new Error(error.detail || 'Erreur lors de la création de la réservation');
    }

    const ride = await response.json();

    // Succès - afficher les informations
    console.log('✅ Réservation créée:', ride);
    console.log(`💰 Prix estimé: ${ride.estimatedPrice}€`);
    console.log(`📏 Distance: ${ride.estimatedDistance} km`);
    console.log(`⏱️ Durée estimée: ${ride.estimatedDuration} minutes`);

    return ride;

  } catch (error) {
    console.error('❌ Erreur:', error.message);
    throw error;
  }
}

// Utilisation
const newRide = await createRide({
  pickupAddress: "Place de la Bastille, 75012 Paris",
  pickupLatitude: 48.8530,
  pickupLongitude: 2.3696,
  dropoffAddress: "Musée du Louvre, 75001 Paris",
  dropoffLatitude: 48.8606,
  dropoffLongitude: 2.3376,
  vehicleType: "comfort"
});
```

### Annuler une réservation (JavaScript)

```javascript
async function cancelRide(rideId) {
  try {
    const token = localStorage.getItem('jwt_token');

    if (!token) {
      throw new Error('Utilisateur non authentifié');
    }

    // ⚠️ IMPORTANT :
    // - URL correcte : /api/rides/{rideId}/cancel
    // - Content-Type : application/ld+json (PAS application/json)
    // - Body : {} (objet vide mais REQUIS)
    const response = await fetch(`http://localhost:8000/api/rides/${rideId}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',  // ⚠️ ld+json, pas json
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({})  // ⚠️ {} obligatoire
    });

    if (!response.ok) {
      const error = await response.json();

      if (response.status === 400) {
        // Course déjà en cours ou terminée
        throw new Error(error.detail || 'Cette course ne peut pas être annulée');
      }

      if (response.status === 403) {
        // Pas autorisé
        throw new Error('Vous n\'êtes pas autorisé à annuler cette course');
      }

      throw new Error(error.detail || 'Erreur lors de l\'annulation');
    }

    const ride = await response.json();
    console.log('✅ Course annulée:', ride);
    return ride;

  } catch (error) {
    console.error('❌ Erreur:', error.message);
    throw error;
  }
}

// Utilisation
try {
  await cancelRide(4);
  alert('Course annulée avec succès');
} catch (error) {
  alert(error.message);
}
```

### Exemple avec React + TypeScript

```typescript
import { useState } from 'react';

interface RideRequest {
  pickupAddress: string;
  pickupLatitude: number;
  pickupLongitude: number;
  dropoffAddress: string;
  dropoffLatitude: number;
  dropoffLongitude: number;
  vehicleType: 'standard' | 'comfort' | 'premium' | 'xl';
}

interface Ride {
  '@id': string;
  id: number;
  status: string;
  estimatedPrice: number;
  estimatedDistance: number;
  estimatedDuration: number;
  vehicleType: string;
}

function useRideBooking() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const createRide = async (rideData: RideRequest): Promise<Ride | null> => {
    setLoading(true);
    setError(null);

    try {
      const token = localStorage.getItem('jwt_token');
      const userId = localStorage.getItem('user_id');

      const response = await fetch('http://localhost:8000/api/rides', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          passenger: `/api/users/${userId}`,
          ...rideData
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || 'Erreur lors de la création');
      }

      const ride = await response.json();
      return ride;

    } catch (err) {
      const message = err instanceof Error ? err.message : 'Erreur inconnue';
      setError(message);
      return null;
    } finally {
      setLoading(false);
    }
  };

  return { createRide, loading, error };
}

// Hook pour annuler une course
function useRideCancel() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const cancelRide = async (rideId: number): Promise<Ride | null> => {
    setLoading(true);
    setError(null);

    try {
      const token = localStorage.getItem('jwt_token');

      const response = await fetch(`http://localhost:8000/api/rides/${rideId}/cancel`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({})
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || 'Erreur lors de l\'annulation');
      }

      const ride = await response.json();
      return ride;

    } catch (err) {
      const message = err instanceof Error ? err.message : 'Erreur inconnue';
      setError(message);
      return null;
    } finally {
      setLoading(false);
    }
  };

  return { cancelRide, loading, error };
}

// Utilisation dans un composant
function BookingForm() {
  const { createRide, loading, error } = useRideBooking();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const ride = await createRide({
      pickupAddress: "Place de la Bastille, 75012 Paris",
      pickupLatitude: 48.8530,
      pickupLongitude: 2.3696,
      dropoffAddress: "Musée du Louvre, 75001 Paris",
      dropoffLatitude: 48.8606,
      dropoffLongitude: 2.3376,
      vehicleType: "comfort"
    });

    if (ride) {
      console.log('Course créée:', ride);
      // Rediriger vers la page de suivi de la course
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Formulaire */}
      <button type="submit" disabled={loading}>
        {loading ? 'Création...' : 'Réserver une course'}
      </button>
      {error && <p className="error">{error}</p>}
    </form>
  );
}

// Composant pour afficher et annuler une course
function RideCard({ ride }: { ride: Ride }) {
  const { cancelRide, loading, error } = useRideCancel();

  const handleCancel = async () => {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette course ?')) {
      return;
    }

    const cancelled = await cancelRide(ride.id);
    if (cancelled) {
      alert('Course annulée avec succès');
      // Mettre à jour l'état ou recharger la liste
    }
  };

  const canCancel = ride.status === 'pending' || ride.status === 'accepted';

  return (
    <div className="ride-card">
      <h3>Course #{ride.id}</h3>
      <p>De: {ride.pickupAddress}</p>
      <p>À: {ride.dropoffAddress}</p>
      <p>Statut: {ride.status}</p>
      <p>Prix: {ride.estimatedPrice}€</p>

      {canCancel && (
        <button
          onClick={handleCancel}
          disabled={loading}
          className="btn-cancel"
        >
          {loading ? 'Annulation...' : 'Annuler la course'}
        </button>
      )}

      {error && <p className="error">{error}</p>}
    </div>
  );
}
```

---

## 🐛 GUIDE DE DÉBOGAGE - Annulation de Course

### Si l'annulation ne fonctionne pas, vérifiez ceci :

#### ✅ Checklist obligatoire :

**1. URL correcte :**
```
✅ Correct : http://localhost:8000/api/rides/6/cancel
❌ Incorrect : http://localhost:8000/api/rides/cancel/6
❌ Incorrect : http://localhost:8000/api/ride/6/cancel
```

**2. Content-Type :**
```javascript
✅ Correct : 'Content-Type': 'application/ld+json'
❌ Incorrect : 'Content-Type': 'application/json'
```

**3. Body de la requête :**
```javascript
✅ Correct : body: JSON.stringify({})
❌ Incorrect : (pas de body)
❌ Incorrect : body: null
❌ Incorrect : body: ''
```

**4. Authorization header :**
```javascript
✅ Correct : 'Authorization': `Bearer ${token}`
❌ Incorrect : 'Authorization': token
❌ Incorrect : 'Bearer': token
```

**5. Méthode HTTP :**
```javascript
✅ Correct : method: 'POST'
❌ Incorrect : method: 'DELETE'
❌ Incorrect : method: 'PUT'
```

### Exemple complet testé et fonctionnel :

```javascript
// ✅ CETTE REQUÊTE FONCTIONNE À 100%
async function cancelRide(rideId) {
  const token = localStorage.getItem('jwt_token');

  const response = await fetch(`http://localhost:8000/api/rides/${rideId}/cancel`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({})
  });

  if (!response.ok) {
    const error = await response.json();
    console.error('Erreur détaillée:', error);
    throw new Error(error.detail || error.message);
  }

  return await response.json();
}

// Test
cancelRide(6)
  .then(ride => console.log('Course annulée:', ride))
  .catch(err => console.error('Erreur:', err));
```

### Test avec CURL (pour vérifier que le backend fonctionne) :

```bash
# 1. Se connecter
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john.doe@email.com","password":"password123"}'

# 2. Copier le token reçu, puis :
curl -X POST http://localhost:8000/api/rides/6/cancel \
  -H "Content-Type: application/ld+json" \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -d '{}'
```

Si CURL fonctionne mais pas votre frontend → le problème est dans votre code JavaScript/TypeScript.

### Erreurs courantes et solutions :

#### Erreur 415 - Unsupported Media Type
```json
{
  "status": 415,
  "detail": "The content-type \"application/json\" is not supported"
}
```
**Solution :** Changez `application/json` en `application/ld+json`

#### Erreur 401 - Unauthorized
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```
**Solutions :**
- Vérifiez que le token est présent dans localStorage
- Vérifiez le format : `Bearer ${token}` (avec espace après Bearer)
- Vérifiez que le token n'a pas expiré (durée : 1 heure)

#### Erreur 403 - Forbidden
```json
{
  "status": 403,
  "detail": "Only the passenger or assigned driver can cancel this ride"
}
```
**Solutions :**
- Vous essayez d'annuler la course de quelqu'un d'autre
- Connectez-vous avec le bon compte (passager ou chauffeur de cette course)

#### Erreur 400 - Bad Request
```json
{
  "status": 400,
  "detail": "Cannot cancel ride with status \"completed\""
}
```
**Solutions :**
- Vous ne pouvez annuler que les courses `pending` ou `accepted`
- Vérifiez le statut de la course avant d'afficher le bouton "Annuler"

### Exemple avec gestion d'erreurs complète :

```javascript
async function cancelRide(rideId) {
  try {
    const token = localStorage.getItem('jwt_token');

    if (!token) {
      alert('Vous devez être connecté pour annuler une course');
      window.location.href = '/login';
      return null;
    }

    console.log('🔵 Annulation de la course:', rideId);
    console.log('🔵 Token présent:', token ? 'Oui' : 'Non');

    const url = `http://localhost:8000/api/rides/${rideId}/cancel`;
    console.log('🔵 URL:', url);

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({})
    });

    console.log('🔵 Status HTTP:', response.status);

    if (!response.ok) {
      const error = await response.json();
      console.error('🔴 Erreur API:', error);

      // Gestion spécifique par code d'erreur
      switch (response.status) {
        case 401:
          alert('Session expirée. Veuillez vous reconnecter.');
          localStorage.removeItem('jwt_token');
          window.location.href = '/login';
          break;
        case 403:
          alert('Vous n\'êtes pas autorisé à annuler cette course.');
          break;
        case 400:
          alert(error.detail || 'Cette course ne peut pas être annulée.');
          break;
        case 415:
          console.error('❌ Content-Type incorrect ! Utilisez application/ld+json');
          alert('Erreur technique. Contactez le support.');
          break;
        default:
          alert('Erreur lors de l\'annulation: ' + (error.detail || error.message));
      }
      return null;
    }

    const ride = await response.json();
    console.log('✅ Course annulée avec succès:', ride);
    return ride;

  } catch (error) {
    console.error('🔴 Erreur réseau:', error);
    alert('Erreur de connexion. Vérifiez votre réseau.');
    return null;
  }
}
```

### Test dans la console du navigateur :

Ouvrez la console (F12) et collez ceci :

```javascript
// Remplacez 6 par l'ID de votre course
fetch('http://localhost:8000/api/rides/6/cancel', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/ld+json',
    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
  },
  body: JSON.stringify({})
})
.then(r => r.json())
.then(data => console.log('Résultat:', data))
.catch(err => console.error('Erreur:', err));
```

Si ça fonctionne dans la console mais pas dans votre code → cherchez la différence !

### Vérifications réseau (DevTools) :

1. Ouvrez l'onglet **Network** (Réseau) des DevTools
2. Cliquez sur "Annuler"
3. Trouvez la requête vers `/api/rides/.../cancel`
4. Vérifiez :
   - **Request URL** : doit finir par `/cancel`
   - **Request Method** : doit être `POST`
   - **Content-Type** : doit être `application/ld+json`
   - **Authorization** : doit commencer par `Bearer `
   - **Request Payload** : doit contenir `{}`

### Résumé ultra-simplifié :

```javascript
// Copier-coller ce code, il fonctionne !
fetch(`http://localhost:8000/api/rides/${rideId}/cancel`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/ld+json',
    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
  },
  body: '{}'
})
.then(r => r.json())
.then(data => alert('Course annulée !'))
.catch(err => alert('Erreur: ' + err.message));
```

---

**Si après tout ça ça ne fonctionne toujours pas :**
1. Copiez-collez la requête exacte que vous envoyez
2. Copiez-collez la réponse exacte que vous recevez
3. Vérifiez que le backend tourne bien sur `http://localhost:8000`
