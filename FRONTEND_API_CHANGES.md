# Modifications API Backend - Mini Uber

**Date**: 16 décembre 2025
**Version**: 1.1.0
**Priorité**: HIGH

---

## Résumé des changements

Ajout d'un nouvel endpoint permettant aux chauffeurs de consulter la liste des courses en attente (pending rides) qu'ils peuvent accepter.

### Problème résolu

Auparavant, l'endpoint `/api/driver/history` ne retournait que les courses où le chauffeur était déjà assigné. Les courses en attente (`status=pending` avec `driver=null`) n'étaient jamais visibles pour les chauffeurs, les empêchant de voir les courses disponibles.

---

## Nouvel Endpoint

### `GET /api/driver/available-rides`

Récupère toutes les courses en attente disponibles pour un chauffeur authentifié.

#### Authentification

Requiert un token JWT valide avec le rôle `ROLE_USER` et `userType=driver`.

```http
Authorization: Bearer <JWT_TOKEN>
```

#### Paramètres de requête (optionnels)

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `limit` | integer | 20 | Nombre maximum de courses à retourner |
| `vehicleType` | string | - | Filtre par type de véhicule (`standard`, `comfort`, `premium`, `xl`) |
| `maxDistance` | float | - | Distance maximale en km entre le chauffeur et le point de départ |

#### Exemple de requête

```bash
# Toutes les courses disponibles
GET /api/driver/available-rides

# Courses dans un rayon de 5km
GET /api/driver/available-rides?maxDistance=5

# Courses pour véhicule standard uniquement
GET /api/driver/available-rides?vehicleType=standard

# Courses dans un rayon de 10km, maximum 10 résultats
GET /api/driver/available-rides?maxDistance=10&limit=10
```

#### Réponse succès (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 28,
      "status": "pending",
      "passenger": {
        "id": 48,
        "name": "John Doe",
        "rating": 4.8
      },
      "pickup": {
        "address": "Opéra Garnier, Paris",
        "latitude": 48.872,
        "longitude": 2.3318
      },
      "dropoff": {
        "address": "Gare de Lyon, Paris",
        "latitude": 48.8449,
        "longitude": 2.3738
      },
      "price": {
        "estimated": 15.2
      },
      "distance": 4.5,
      "duration": 14,
      "vehicleType": "standard",
      "createdAt": "2025-12-16 11:20:15",
      "distanceToPickup": 2.27
    }
  ],
  "count": 1
}
```

#### Réponses d'erreur

**401 - Non authentifié**
```json
{
  "error": "Not authenticated"
}
```

**403 - Pas un chauffeur**
```json
{
  "error": "Not a driver"
}
```

**404 - Profil chauffeur non trouvé**
```json
{
  "error": "Driver profile not found"
}
```

---

## Structure des données

### Objet Ride (course disponible)

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | Identifiant unique de la course |
| `status` | string | Statut de la course (toujours `"pending"` pour cet endpoint) |
| `passenger` | object | Informations du passager |
| `passenger.id` | integer | ID du passager |
| `passenger.name` | string | Nom complet du passager |
| `passenger.rating` | float | Note moyenne du passager |
| `pickup` | object | Point de départ |
| `pickup.address` | string | Adresse complète du point de départ |
| `pickup.latitude` | float | Latitude du point de départ |
| `pickup.longitude` | float | Longitude du point de départ |
| `dropoff` | object | Point d'arrivée |
| `dropoff.address` | string | Adresse complète du point d'arrivée |
| `dropoff.latitude` | float | Latitude du point d'arrivée |
| `dropoff.longitude` | float | Longitude du point d'arrivée |
| `price` | object | Informations de prix |
| `price.estimated` | float | Prix estimé en euros |
| `distance` | float | Distance totale de la course en km |
| `duration` | float | Durée estimée de la course en minutes |
| `vehicleType` | string | Type de véhicule requis |
| `createdAt` | string | Date/heure de création (format: `Y-m-d H:i:s`) |
| `distanceToPickup` | float | Distance entre le chauffeur et le point de départ en km (uniquement si la position du chauffeur est disponible) |

---

## Flux de travail recommandé

### 1. Affichage des courses disponibles

```javascript
// Récupérer les courses disponibles toutes les 30 secondes
const fetchAvailableRides = async () => {
  try {
    const response = await fetch('/api/driver/available-rides?maxDistance=10', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    const data = await response.json();

    if (data.success) {
      // Mettre à jour l'interface avec data.data
      displayRides(data.data);
    }
  } catch (error) {
    console.error('Erreur lors de la récupération des courses:', error);
  }
};

// Polling toutes les 30 secondes
setInterval(fetchAvailableRides, 30000);
```

### 2. Accepter une course

Une fois qu'un chauffeur sélectionne une course, utilisez l'endpoint existant :

```javascript
const acceptRide = async (rideId) => {
  try {
    const response = await fetch(`/api/rides/${rideId}/accept`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type: application/json'
      }
    });

    if (response.ok) {
      // Course acceptée avec succès
      console.log('Course acceptée!');
    }
  } catch (error) {
    console.error('Erreur lors de l\'acceptation:', error);
  }
};
```

---

## Notes importantes

### Tri des résultats

Les courses sont triées par date de création, **les plus anciennes en premier** (FIFO - First In First Out). Cela garantit que les passagers qui attendent le plus longtemps sont servis en priorité.

### Calcul de distance

Le champ `distanceToPickup` n'est présent que si :
- Le chauffeur a une position GPS enregistrée (`currentLatitude` et `currentLongitude` dans son profil)
- Si la position n'est pas disponible, le champ sera absent de la réponse

### Filtrage automatique

Lorsque le paramètre `maxDistance` est utilisé :
- Les courses situées au-delà de cette distance ne seront **pas incluses** dans la réponse
- Le calcul se base sur la distance entre la position actuelle du chauffeur et le point de départ de la course

### Performance

- Limite par défaut : 20 courses maximum
- Recommandation : implémenter un système de polling (30-60 secondes) plutôt que des requêtes continues
- Pour une solution temps réel, envisager l'utilisation de Mercure (déjà disponible dans le projet)

---

## Exemples d'intégration Frontend

### React/Vue/Angular

```javascript
// Service API
class DriverService {
  async getAvailableRides(maxDistance = 10, vehicleType = null, limit = 20) {
    const params = new URLSearchParams({
      maxDistance: maxDistance.toString(),
      limit: limit.toString()
    });

    if (vehicleType) {
      params.append('vehicleType', vehicleType);
    }

    const response = await fetch(`/api/driver/available-rides?${params}`, {
      headers: {
        'Authorization': `Bearer ${this.getToken()}`
      }
    });

    if (!response.ok) {
      throw new Error('Failed to fetch available rides');
    }

    return response.json();
  }
}
```

### Affichage dans l'interface

```jsx
// Exemple React
const AvailableRides = () => {
  const [rides, setRides] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchRides = async () => {
      try {
        const data = await driverService.getAvailableRides(10);
        setRides(data.data);
      } catch (error) {
        console.error(error);
      } finally {
        setLoading(false);
      }
    };

    fetchRides();
    const interval = setInterval(fetchRides, 30000);

    return () => clearInterval(interval);
  }, []);

  if (loading) return <div>Chargement...</div>;

  if (rides.length === 0) {
    return <div>Aucune course disponible pour le moment</div>;
  }

  return (
    <div>
      <h2>Courses disponibles ({rides.length})</h2>
      {rides.map(ride => (
        <RideCard key={ride.id} ride={ride} />
      ))}
    </div>
  );
};
```

---

## Migration et Rétrocompatibilité

### Endpoints existants

Tous les endpoints existants continuent de fonctionner normalement :
- `GET /api/driver/history` - Historique des courses du chauffeur
- `GET /api/driver/stats` - Statistiques du chauffeur
- `PATCH /api/driver/availability` - Modifier la disponibilité
- `POST /api/rides/{id}/accept` - Accepter une course

### Aucune modification requise

Si votre application n'utilise pas encore la fonctionnalité de consultation des courses disponibles, aucune modification n'est nécessaire. Le nouvel endpoint est purement additionnel.

---

## Support et Questions

Pour toute question ou problème technique :
- Vérifier que le chauffeur est bien authentifié avec un token JWT valide
- Vérifier que le `userType` de l'utilisateur est bien `"driver"`
- S'assurer que le profil Driver est créé et lié à l'utilisateur

## Changelog

### v1.1.0 - 2025-12-16
- ✅ Ajout de l'endpoint `GET /api/driver/available-rides`
- ✅ Support du filtrage par distance (`maxDistance`)
- ✅ Support du filtrage par type de véhicule (`vehicleType`)
- ✅ Calcul automatique de la distance jusqu'au point de départ
- ✅ Tri FIFO (First In First Out) des courses

---

**Fin du document**
