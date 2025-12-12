# 🚗 Endpoint d'Historique des Courses pour Chauffeurs

## 📋 Vue d'ensemble

L'endpoint `/api/driver/history` permet à un chauffeur authentifié de récupérer l'historique de toutes ses courses dans un format simple et optimisé pour le frontend.

---

## 🔗 Endpoint

```
GET /api/driver/history
```

**Authentification requise** : Oui (JWT Token)
**Rôle requis** : Driver (userType = 'driver')

---

## 📝 Paramètres de requête (Query Parameters)

| Paramètre | Type | Requis | Défaut | Description |
|-----------|------|--------|--------|-------------|
| `status` | string | Non | - | Filtrer par statut : `completed`, `cancelled`, `in_progress`, etc. |
| `limit` | integer | Non | 20 | Nombre maximum de résultats à retourner |
| `offset` | integer | Non | 0 | Position de départ pour la pagination |

---

## 🔐 Headers requis

```http
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

---

## 📤 Exemple de requête

### Récupérer toutes les courses

```bash
curl -X GET "http://localhost:8080/api/driver/history" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json"
```

### Filtrer uniquement les courses terminées

```bash
curl -X GET "http://localhost:8080/api/driver/history?status=completed" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json"
```

### Pagination (20 résultats à partir de la position 40)

```bash
curl -X GET "http://localhost:8080/api/driver/history?limit=20&offset=40" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json"
```

---

## 📥 Exemple de réponse

```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "status": "completed",
      "passenger": {
        "id": 12,
        "name": "John Doe",
        "phone": "+33623456789",
        "rating": 4.8
      },
      "pickup": {
        "address": "Gare du Nord, Paris",
        "latitude": 48.8809,
        "longitude": 2.3553
      },
      "dropoff": {
        "address": "Tour Eiffel, Paris",
        "latitude": 48.8584,
        "longitude": 2.2945
      },
      "price": {
        "estimated": 18.50,
        "final": 18.50
      },
      "distance": 5.2,
      "duration": 15.0,
      "vehicleType": "premium",
      "dates": {
        "created": "2025-12-11 13:51:10",
        "accepted": "2025-12-09 13:51:10",
        "started": "2025-12-09 13:56:10",
        "completed": "2025-12-09 14:11:10"
      }
    },
    {
      "id": 11,
      "status": "completed",
      "passenger": {
        "id": 10,
        "name": "Marie Martin",
        "phone": "+33612345678",
        "rating": 4.9
      },
      "pickup": {
        "address": "Place de la République, Paris",
        "latitude": 48.8676,
        "longitude": 2.3634
      },
      "dropoff": {
        "address": "Montmartre, Paris",
        "latitude": 48.8867,
        "longitude": 2.3431
      },
      "price": {
        "estimated": 12.80,
        "final": 12.80
      },
      "distance": 3.8,
      "duration": 12.0,
      "vehicleType": "comfort",
      "dates": {
        "created": "2025-12-11 10:30:00",
        "accepted": "2025-12-11 10:32:00",
        "started": "2025-12-11 10:35:00",
        "completed": "2025-12-11 10:47:00"
      }
    }
  ],
  "pagination": {
    "limit": 20,
    "offset": 0,
    "count": 2
  }
}
```

---

## 📊 Structure de données

### Objet `Ride` (Course)

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID unique de la course |
| `status` | string | Statut : `pending`, `accepted`, `in_progress`, `completed`, `cancelled` |
| `passenger` | object | Informations sur le passager |
| `pickup` | object | Adresse et coordonnées de départ |
| `dropoff` | object | Adresse et coordonnées d'arrivée |
| `price` | object | Prix estimé et final |
| `distance` | float | Distance en kilomètres |
| `duration` | float | Durée estimée en minutes |
| `vehicleType` | string | Type de véhicule : `standard`, `comfort`, `premium`, `xl` |
| `dates` | object | Dates de création, acceptation, démarrage et fin |

### Objet `Passenger`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID du passager |
| `name` | string | Nom complet du passager |
| `phone` | string | Numéro de téléphone |
| `rating` | float | Note moyenne du passager |

### Objet `Pagination`

| Champ | Type | Description |
|-------|------|-------------|
| `limit` | integer | Nombre maximum de résultats demandés |
| `offset` | integer | Position de départ |
| `count` | integer | Nombre de résultats retournés |

---

## 🚨 Codes d'erreur

| Code | Message | Description |
|------|---------|-------------|
| 401 | `Not authenticated` | Token JWT manquant ou invalide |
| 401 | `Expired JWT Token` | Token JWT expiré |
| 403 | `Not a driver` | L'utilisateur n'est pas un chauffeur |

### Exemple d'erreur

```json
{
  "error": "Not authenticated",
  "code": 401
}
```

---

## 🎨 Intégration Frontend (React/TypeScript)

### Fonction de récupération de l'historique

```typescript
interface DriverHistoryResponse {
  success: boolean;
  data: Ride[];
  pagination: {
    limit: number;
    offset: number;
    count: number;
  };
}

interface Ride {
  id: number;
  status: 'pending' | 'accepted' | 'in_progress' | 'completed' | 'cancelled';
  passenger: {
    id: number;
    name: string;
    phone: string;
    rating: number;
  };
  pickup: {
    address: string;
    latitude: number;
    longitude: number;
  };
  dropoff: {
    address: string;
    latitude: number;
    longitude: number;
  };
  price: {
    estimated: number;
    final: number | null;
  };
  distance: number;
  duration: number;
  vehicleType: 'standard' | 'comfort' | 'premium' | 'xl';
  dates: {
    created: string;
    accepted: string | null;
    started: string | null;
    completed: string | null;
  };
}

async function getDriverHistory(
  token: string,
  options?: {
    status?: string;
    limit?: number;
    offset?: number;
  }
): Promise<DriverHistoryResponse> {
  const params = new URLSearchParams();

  if (options?.status) params.append('status', options.status);
  if (options?.limit) params.append('limit', options.limit.toString());
  if (options?.offset) params.append('offset', options.offset.toString());

  const url = `http://localhost:8080/api/driver/history?${params.toString()}`;

  const response = await fetch(url, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  return await response.json();
}
```

### Exemple d'utilisation avec React

```tsx
import { useState, useEffect } from 'react';

function DriverHistoryPage() {
  const [rides, setRides] = useState<Ride[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const token = localStorage.getItem('jwt_token'); // Récupérer le token

  useEffect(() => {
    async function fetchHistory() {
      try {
        setLoading(true);
        const data = await getDriverHistory(token!, {
          status: 'completed', // Uniquement les courses terminées
          limit: 50,
        });
        setRides(data.data);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Une erreur est survenue');
      } finally {
        setLoading(false);
      }
    }

    if (token) {
      fetchHistory();
    }
  }, [token]);

  if (loading) return <div>Chargement...</div>;
  if (error) return <div>Erreur : {error}</div>;

  return (
    <div className="driver-history">
      <h1>Historique de mes courses</h1>

      <div className="rides-list">
        {rides.map((ride) => (
          <div key={ride.id} className="ride-card">
            <div className="ride-header">
              <span className={`status ${ride.status}`}>{ride.status}</span>
              <span className="date">{new Date(ride.dates.completed || ride.dates.created).toLocaleDateString()}</span>
            </div>

            <div className="passenger">
              <h3>{ride.passenger.name}</h3>
              <p>⭐ {ride.passenger.rating} | 📞 {ride.passenger.phone}</p>
            </div>

            <div className="route">
              <div className="location">
                <span className="icon">📍</span>
                <span>{ride.pickup.address}</span>
              </div>
              <div className="location">
                <span className="icon">🎯</span>
                <span>{ride.dropoff.address}</span>
              </div>
            </div>

            <div className="ride-details">
              <span>{ride.distance} km</span>
              <span>{ride.duration} min</span>
              <span className="price">{ride.price.final || ride.price.estimated}€</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## 🎯 Cas d'usage

### 1. Page d'historique complète
```typescript
const { data } = await getDriverHistory(token, { limit: 50 });
```

### 2. Statistiques des courses terminées
```typescript
const { data: completedRides } = await getDriverHistory(token, {
  status: 'completed',
  limit: 100
});

const totalEarnings = completedRides.reduce((sum, ride) => sum + (ride.price.final || 0), 0);
```

### 3. Pagination infinie
```typescript
const [offset, setOffset] = useState(0);
const limit = 20;

function loadMore() {
  getDriverHistory(token, { limit, offset })
    .then(({ data }) => {
      setRides(prev => [...prev, ...data]);
      setOffset(prev => prev + limit);
    });
}
```

---

## ✅ Avantages de cet endpoint

1. **Format simplifié** : Pas de JSON-LD, pas d'IRIs, juste des données propres
2. **Optimisé pour le frontend** : Structure plate et facile à consommer
3. **Pagination intégrée** : Facile à implémenter avec scroll infini ou pagination classique
4. **Filtrage flexible** : Par statut, avec limite et offset personnalisables
5. **Performances** : Une seule requête pour toutes les données nécessaires
6. **Type-safe** : Structure TypeScript fournie pour une intégration facile

---

## 🔗 Endpoints complémentaires

- **`GET /api/driver/stats`** : Statistiques du chauffeur (gains totaux, nombre de courses, etc.)
- **`GET /api/drivers-available`** : Liste des chauffeurs disponibles à proximité
- **`GET /api/rides/{id}`** : Détails complets d'une course spécifique (format API Platform)

---

## 📞 Support

Pour toute question ou problème, consultez la documentation complète dans `README.md` ou contactez l'équipe de développement.
