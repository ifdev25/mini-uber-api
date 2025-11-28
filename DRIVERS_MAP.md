# API Chauffeurs - Affichage sur la Carte

## 📍 Obtenir les chauffeurs disponibles avec leurs positions GPS

### Endpoint principal

```
GET /api/drivers?isAvailable=true&isVerified=true
Authorization: Bearer {token_jwt}
```

### Réponse

```json
{
  "@context": "/api/contexts/Driver",
  "@type": "Collection",
  "totalItems": 2,
  "member": [
    {
      "@id": "/api/drivers/1",
      "@type": "Driver",
      "id": 1,
      "user": "/api/users/3",
      "vehicleModel": "Tesla Model 3",
      "vehicleType": "premium",
      "vehicleColor": "Blanc Nacré",
      "currentLatitude": 48.8566,
      "currentLongitude": 2.3522,
      "licenceNumber": "DR123456789",
      "verifiedAt": "2025-05-24T15:06:38+00:00"
    },
    {
      "@id": "/api/drivers/3",
      "@type": "Driver",
      "id": 3,
      "user": "/api/users/5",
      "vehicleModel": "Renault Symbol",
      "vehicleType": "standard",
      "vehicleColor": "Blanc",
      "currentLatitude": 36.4244,
      "currentLongitude": 6.5983,
      "licenceNumber": "DZ123456789",
      "verifiedAt": "2025-07-24T15:06:38+00:00"
    }
  ]
}
```

### Données disponibles pour chaque chauffeur

| Champ | Type | Description | Utilisation carte |
|-------|------|-------------|-------------------|
| `id` | integer | ID du chauffeur | Identifiant unique |
| `user` | string (IRI) | Référence à l'utilisateur | Obtenir nom/rating |
| `vehicleModel` | string | Modèle du véhicule | Afficher info voiture |
| `vehicleType` | string | Type de véhicule | Icône selon type |
| `vehicleColor` | string | Couleur du véhicule | Info détaillée |
| `currentLatitude` | float | Latitude GPS | **Position Y sur carte** |
| `currentLongitude` | float | Longitude GPS | **Position X sur carte** |
| `licenceNumber` | string | Numéro de permis | Info détaillée |
| `verifiedAt` | datetime | Date de vérification | Badge vérifié |

### ⚠️ Note importante sur `isAvailable`

Le champ `isAvailable` n'apparaît **pas** dans la réponse JSON (problème de sérialisation), MAIS le filtre `?isAvailable=true` fonctionne correctement !

**Solution :** Utiliser le filtre pour obtenir seulement les chauffeurs disponibles.

## 🔍 Filtres disponibles

### Chauffeurs disponibles uniquement
```
GET /api/drivers?isAvailable=true
```

### Chauffeurs vérifiés uniquement
```
GET /api/drivers?isVerified=true
```

### Chauffeurs disponibles ET vérifiés (recommandé)
```
GET /api/drivers?isAvailable=true&isVerified=true
```

### Par type de véhicule
```
GET /api/drivers?vehicleType=standard
GET /api/drivers?vehicleType=comfort
GET /api/drivers?vehicleType=premium
GET /api/drivers?vehicleType=xl
```

### Combinaison de filtres
```
GET /api/drivers?isAvailable=true&isVerified=true&vehicleType=standard
```

## 📊 Obtenir les informations complètes du chauffeur (avec nom et rating)

Pour chaque chauffeur, vous avez une référence IRI vers l'utilisateur : `"user": "/api/users/3"`

### Récupérer les infos complètes

```
GET /api/users/3
Authorization: Bearer {token_jwt}
```

**Réponse :**
```json
{
  "@id": "/api/users/3",
  "@type": "User",
  "id": 3,
  "email": "marie.martin@driver.com",
  "firstName": "Marie",
  "lastName": "Martin",
  "phone": "+33634567890",
  "userType": "driver",
  "rating": 4.9,
  "totalRides": 234
}
```

## 💡 Workflow recommandé pour afficher les chauffeurs sur la carte

### Étape 1 : Récupérer les chauffeurs disponibles

```javascript
async function getAvailableDrivers() {
  const token = localStorage.getItem('jwt_token');

  const response = await fetch(
    'http://localhost:8000/api/drivers?isAvailable=true&isVerified=true',
    {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );

  const data = await response.json();
  return data.member; // Tableau de chauffeurs
}
```

### Étape 2 : Afficher sur la carte (exemple avec Google Maps)

```javascript
async function displayDriversOnMap(map) {
  const drivers = await getAvailableDrivers();

  drivers.forEach(driver => {
    // Créer un marqueur pour chaque chauffeur
    const marker = new google.maps.Marker({
      position: {
        lat: driver.currentLatitude,
        lng: driver.currentLongitude
      },
      map: map,
      title: `${driver.vehicleModel} - ${driver.vehicleType}`,
      icon: getIconForVehicleType(driver.vehicleType)
    });

    // Optionnel : Ajouter une info-bulle
    const infoWindow = new google.maps.InfoWindow({
      content: `
        <div>
          <h3>${driver.vehicleModel}</h3>
          <p>Type: ${driver.vehicleType}</p>
          <p>Couleur: ${driver.vehicleColor}</p>
        </div>
      `
    });

    marker.addListener('click', () => {
      infoWindow.open(map, marker);
    });
  });
}

function getIconForVehicleType(type) {
  const icons = {
    'standard': '/icons/car-standard.png',
    'comfort': '/icons/car-comfort.png',
    'premium': '/icons/car-premium.png',
    'xl': '/icons/car-xl.png'
  };
  return icons[type] || icons.standard;
}
```

### Étape 3 : Rafraîchir les positions en temps réel

```javascript
// Mettre à jour les positions toutes les 10 secondes
setInterval(async () => {
  const drivers = await getAvailableDrivers();
  updateMarkers(drivers);
}, 10000);
```

## 🌍 Exemple avec Leaflet (alternative à Google Maps)

```javascript
import L from 'leaflet';

async function displayDriversOnLeafletMap(map) {
  const drivers = await getAvailableDrivers();

  drivers.forEach(driver => {
    const marker = L.marker([driver.currentLatitude, driver.currentLongitude])
      .addTo(map)
      .bindPopup(`
        <b>${driver.vehicleModel}</b><br>
        Type: ${driver.vehicleType}<br>
        Couleur: ${driver.vehicleColor}
      `);
  });
}
```

## 🗺️ Exemple avec Mapbox

```javascript
async function displayDriversOnMapbox(map) {
  const drivers = await getAvailableDrivers();

  // Créer un GeoJSON avec tous les chauffeurs
  const geojson = {
    type: 'FeatureCollection',
    features: drivers.map(driver => ({
      type: 'Feature',
      geometry: {
        type: 'Point',
        coordinates: [driver.currentLongitude, driver.currentLatitude]
      },
      properties: {
        id: driver.id,
        vehicleModel: driver.vehicleModel,
        vehicleType: driver.vehicleType,
        vehicleColor: driver.vehicleColor
      }
    }))
  };

  // Ajouter la couche à la carte
  map.addSource('drivers', {
    type: 'geojson',
    data: geojson
  });

  map.addLayer({
    id: 'driver-markers',
    type: 'symbol',
    source: 'drivers',
    layout: {
      'icon-image': '{vehicleType}-icon',
      'icon-size': 1.5
    }
  });
}
```

## 🔄 Obtenir les infos détaillées avec nom et rating

Si vous voulez afficher le nom et le rating du chauffeur sur la carte :

```javascript
async function getDriverWithUserInfo(driverId) {
  const token = localStorage.getItem('jwt_token');

  // 1. Récupérer le driver
  const driverResponse = await fetch(
    `http://localhost:8000/api/drivers/${driverId}`,
    {
      headers: { 'Authorization': `Bearer ${token}` }
    }
  );
  const driver = await driverResponse.json();

  // 2. Extraire l'ID de l'utilisateur de l'IRI
  const userId = driver.user.split('/').pop();

  // 3. Récupérer les infos de l'utilisateur
  const userResponse = await fetch(
    `http://localhost:8000/api/users/${userId}`,
    {
      headers: { 'Authorization': `Bearer ${token}` }
    }
  );
  const user = await userResponse.json();

  // 4. Retourner les données complètes
  return {
    ...driver,
    firstName: user.firstName,
    lastName: user.lastName,
    rating: user.rating,
    totalRides: user.totalRides
  };
}
```

## 🎯 Hook React pour afficher les chauffeurs

```typescript
import { useState, useEffect } from 'react';

interface Driver {
  id: number;
  vehicleModel: string;
  vehicleType: string;
  vehicleColor: string;
  currentLatitude: number;
  currentLongitude: number;
  user: string;
}

function useAvailableDrivers(refreshInterval = 10000) {
  const [drivers, setDrivers] = useState<Driver[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchDrivers = async () => {
      try {
        const token = localStorage.getItem('jwt_token');
        const response = await fetch(
          'http://localhost:8000/api/drivers?isAvailable=true&isVerified=true',
          {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          }
        );

        if (!response.ok) {
          throw new Error('Failed to fetch drivers');
        }

        const data = await response.json();
        setDrivers(data.member);
        setError(null);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Unknown error');
      } finally {
        setLoading(false);
      }
    };

    fetchDrivers();

    // Rafraîchir les positions
    const interval = setInterval(fetchDrivers, refreshInterval);

    return () => clearInterval(interval);
  }, [refreshInterval]);

  return { drivers, loading, error };
}

// Utilisation
function DriversMap() {
  const { drivers, loading, error } = useAvailableDrivers();

  if (loading) return <div>Chargement des chauffeurs...</div>;
  if (error) return <div>Erreur: {error}</div>;

  return (
    <div>
      {drivers.map(driver => (
        <div key={driver.id}>
          {driver.vehicleModel} à ({driver.currentLatitude}, {driver.currentLongitude})
        </div>
      ))}
    </div>
  );
}
```

## 📍 Filtrer les chauffeurs par zone géographique

Si vous voulez afficher seulement les chauffeurs proches d'un point :

```javascript
function filterDriversByDistance(drivers, centerLat, centerLng, maxDistanceKm) {
  return drivers.filter(driver => {
    const distance = calculateDistance(
      centerLat,
      centerLng,
      driver.currentLatitude,
      driver.currentLongitude
    );
    return distance <= maxDistanceKm;
  });
}

function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371; // Rayon de la Terre en km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

// Utilisation
const nearbyDrivers = filterDriversByDistance(
  drivers,
  48.8566, // Paris
  2.3522,
  10 // 10 km de rayon
);
```

## 🎨 Résumé des données à afficher sur la carte

Pour une expérience utilisateur optimale, affichez :

**Marqueur basique :**
- Position GPS (latitude/longitude)
- Icône selon le type de véhicule

**Info-bulle au clic :**
- Modèle de véhicule
- Type (standard/comfort/premium/xl)
- Couleur
- Nom du chauffeur (nécessite requête supplémentaire)
- Rating (nécessite requête supplémentaire)

**Mise à jour :**
- Rafraîchir les positions toutes les 10-30 secondes
- Animer les transitions de position pour un effet fluide
