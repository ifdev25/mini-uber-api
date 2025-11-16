# API Endpoints - Mini Uber API

## État de la migration
Les endpoints ont été migrés vers des State Processors API Platform. Les anciens controllers sont conservés temporairement mais ne doivent plus être utilisés.

## Authentication

### POST /api/login
Se connecter et obtenir un JWT token
- **Authentification:** Non requise
- **Body:**
```json
{
  "email": "john.doe@email.com",
  "password": "password123"
}
```
- **Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Utiliser le token
Tous les endpoints protégés nécessitent un JWT Bearer Token dans le header :
```
Authorization: Bearer <token>
```

---

## Users

### POST /api/users
Créer un nouvel utilisateur (inscription)
- **Authentification:** Non requise
- **Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "firstname": "John",
  "lastname": "Doe",
  "phone": "+33123456789",
  "usertype": "passenger" // ou "driver"
}
```

### GET /api/users
Lister les utilisateurs
- **Filtres disponibles:**
  - `?usertype=driver` - Filtrer par type
  - `?email=john` - Recherche partielle sur email
  - `?rating[gte]=4.5` - Rating minimum
  - `?order[createdAt]=desc` - Trier par date

### GET /api/users/{id}
Récupérer un utilisateur spécifique

### PATCH /api/users/{id}
Modifier un utilisateur
- **Authentification:** Requise (propriétaire uniquement)
- **Sécurité:** `is_granted('ROLE_USER') and object == user`
- **Body:** Champs modifiables (firstname, lastname, phone, etc.)

### DELETE /api/users/{id}
Supprimer un utilisateur
- **Authentification:** Requise (admin uniquement)
- **Sécurité:** `is_granted('ROLE_ADMIN')`

---

## Rides

### POST /api/rides
Créer une nouvelle demande de course
- **Authentification:** Requise (passenger)
- **Body:**
```json
{
  "pickupAddress": "123 Main St",
  "pickUpLatitude": 48.8566,
  "pickUpLongitude": 2.3522,
  "dropoffAddress": "456 Avenue",
  "dropoffLatitude": 48.8606,
  "dropoffLongitude": 2.3376,
  "vehiculeType": "standard"
}
```
- **Note:** Le prix et la distance sont calculés automatiquement via RideProcessor

### POST /api/ride-estimates
Estimer le prix d'une course (sans la créer)
- **Authentification:** Non requise
- **Body:**
```json
{
  "pickupLat": 48.8566,
  "pickupLng": 2.3522,
  "dropoffLat": 48.8606,
  "dropoffLng": 2.3376,
  "vehicleType": "standard"
}
```
- **Response:**
```json
{
  "distance": 3.2,
  "duration": 15.5,
  "price": 12.80,
  "vehicleType": "standard"
}
```

### GET /api/rides
Lister les courses
- **Filtres disponibles:**
  - `?status=pending` - Par statut (pending, accepted, in_progress, completed, cancelled)
  - `?vehiculeType=premium` - Par type de véhicule
  - `?passenger=/api/users/1` - Par passager
  - `?driver=/api/users/2` - Par chauffeur
  - `?estimatedPrice[gte]=10` - Prix minimum
  - `?order[createdAt]=desc` - Trier par date

### GET /api/rides/{id}
Récupérer les détails d'une course

### POST /api/rides/{id}/accept
Accepter une course (chauffeur uniquement)
- **Authentification:** Requise (driver)
- **Body:** Vide ou `{}`
- **Validations:**
  - Le chauffeur doit être vérifié
  - Le chauffeur doit être disponible
  - Le type de véhicule doit correspondre
  - La course doit être en statut "pending"

### PATCH /api/rides/{id}/status
Mettre à jour le statut de la course (chauffeur uniquement)
- **Authentification:** Requise (driver propriétaire de la course)
- **Body:**
```json
{
  "status": "in_progress" // ou "completed", "cancelled"
}
```
- **Comportements:**
  - `in_progress`: Définit startedAt, notifie le passager
  - `completed`: Définit completedAt, rend le chauffeur disponible, incrémente totalRides, notifie le passager

### PATCH /api/rides/{id}
Modifier une course
- **Authentification:** Requise (driver ou passenger propriétaire)
- **Sécurité:** `is_granted('ROLE_USER') and (object.getDriver() == user or object.getPassenger() == user)`
- **Body:** Champs modifiables

### DELETE /api/rides/{id}
Supprimer une course
- **Authentification:** Requise (admin uniquement)
- **Sécurité:** `is_granted('ROLE_ADMIN')`

---

## Drivers

### GET /api/drivers
Lister les chauffeurs
- **Filtres disponibles:**
  - `?isAvailable=true` - Chauffeurs disponibles uniquement
  - `?isVerified=true` - Chauffeurs vérifiés uniquement
  - `?vehiculeType=premium` - Par type de véhicule
  - `?vehiculeModel=Tesla` - Par modèle (recherche partielle)

### GET /api/drivers/{id}
Récupérer un chauffeur spécifique

### POST /api/drivers
Créer un profil chauffeur
- **Authentification:** Requise (user avec usertype=driver)
- **Body:**
```json
{
  "user": "/api/users/1",
  "vehiculeModel": "Tesla Model 3",
  "vehiculeType": "premium",
  "vehiculeColor": "Black",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "licenceNumber": "ABC123456"
}
```

### PATCH /api/drivers/location
Mettre à jour la position du chauffeur
- **Authentification:** Requise (driver)
- **Body:**
```json
{
  "lat": 48.8566,
  "lng": 2.3522
}
```
- **Note:** Envoie une notification temps réel via Mercure

### PATCH /api/drivers/availability
Changer la disponibilité du chauffeur
- **Authentification:** Requise (driver)
- **Body:**
```json
{
  "isAvailable": true
}
```

### PATCH /api/drivers/{id}
Modifier un profil chauffeur
- **Authentification:** Requise (propriétaire uniquement)
- **Sécurité:** `is_granted('ROLE_USER') and object.getUser() == user`
- **Body:** Champs modifiables (vehiculeModel, vehiculeColor, etc.)

### DELETE /api/drivers/{id}
Supprimer un profil chauffeur
- **Authentification:** Requise (admin uniquement)
- **Sécurité:** `is_granted('ROLE_ADMIN')`

---

## Ratings

### GET /api/ratings
Lister les évaluations
- **Authentification:** Requise
- **Filtres disponibles:**
  - `?ride=/api/rides/1` - Par course
  - `?rater=/api/users/1` - Par évaluateur
  - `?rated=/api/users/2` - Par utilisateur évalué

### GET /api/ratings/{id}
Récupérer une évaluation spécifique

### POST /api/ratings
Créer une évaluation après une course
- **Authentification:** Requise
- **Body:**
```json
{
  "ride": "/api/rides/1",
  "rater": "/api/users/1",
  "rated": "/api/users/2",
  "score": 4.5,
  "comment": "Excellent chauffeur, très professionnel"
}
```
- **Validations:**
  - `score`: Entre 1 et 5
  - `comment`: Maximum 1000 caractères (optionnel)

### PATCH /api/ratings/{id}
Modifier une évaluation
- **Authentification:** Requise (créateur uniquement)
- **Sécurité:** `is_granted('ROLE_USER') and object.getRater() == user`
- **Body:** Champs modifiables (score, comment)

### DELETE /api/ratings/{id}
Supprimer une évaluation
- **Authentification:** Requise (créateur uniquement)
- **Sécurité:** `is_granted('ROLE_USER') and object.getRater() == user`

---

## Notifications temps réel (Mercure)

### Topics Mercure

Les clients peuvent s'abonner aux topics suivants via le hub Mercure (`http://localhost:3000/.well-known/mercure`):

#### Pour les chauffeurs :
- `drivers/{driverId}` - Notifications de nouvelles courses à proximité
- `drivers/{driverId}/location` - Mises à jour de position

**Exemple de notification nouvelle course :**
```json
{
  "type": "new_ride",
  "ride": {
    "id": 123,
    "pickupAddress": "123 Main St",
    "dropoffAddress": "456 Avenue",
    "estimatedPrice": 12.80,
    "estimatedDistance": 3.2,
    "vehiculeType": "standard",
    "passenger": {
      "name": "John Doe",
      "rating": 4.8
    }
  }
}
```

#### Pour les passagers :
- `users/{userId}` - Notifications sur l'état de leur course

**Types de notifications :**
- `ride_accepted` - Un chauffeur a accepté la course
- `ride_started` - La course a démarré
- `ride_completed` - La course est terminée

---

## Anciennes routes (DÉPRÉCIÉES - Ne plus utiliser)

Les routes suivantes sont dépréciées et seront supprimées dans une version future :
- ❌ POST `/api/rides/estimate` → Utiliser POST `/api/ride-estimates`
- ❌ POST `/api/rides/request` → Utiliser POST `/api/rides`
- ❌ GET `/api/rides/history` → Utiliser GET `/api/rides?passenger={id}` ou `?driver={id}`
- ❌ GET `/api/drivers/available` → Utiliser GET `/api/drivers?isAvailable=true&isVerified=true`

---

## Codes de statut HTTP

- `200 OK` - Succès
- `201 Created` - Ressource créée
- `400 Bad Request` - Données invalides
- `401 Unauthorized` - Non authentifié
- `403 Forbidden` - Non autorisé
- `404 Not Found` - Ressource introuvable
- `422 Unprocessable Entity` - Erreurs de validation

## Architecture

Le projet utilise maintenant **API Platform avec State Processors** pour toutes les opérations :
- `RideProcessor` - Gestion de la création de rides
- `RideAcceptProcessor` - Acceptation de courses
- `RideStatusProcessor` - Mise à jour de statuts
- `RideEstimateProcessor` - Estimation de prix
- `DriverLocationProcessor` - Mise à jour de position
- `DriverAvailabilityProcessor` - Gestion de disponibilité
- `UserPasswordHashProcessor` - Hashage des mots de passe
