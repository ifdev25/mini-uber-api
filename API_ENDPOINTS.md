# Documentation API Mini Uber

Documentation complète de tous les endpoints disponibles dans l'API Mini Uber.

**Base URL:** `http://localhost:8000` (dev) ou votre URL de production

---

## Table des matières

1. [Authentication](#authentication)
2. [Users](#users)
3. [Drivers](#drivers)
4. [Rides](#rides)
5. [Ratings](#ratings)
6. [Codes d'erreur](#codes-derreur)

---

## Authentication

### 1. Inscription (Register)

**Endpoint:** `POST /api/register`

**Headers:**
```json
{
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

**Champs:**
- `email` (string, required): Email de l'utilisateur
- `password` (string, required, min: 6): Mot de passe
- `firstName` (string, required): Prénom
- `lastName` (string, required): Nom
- `phone` (string, required): Numéro de téléphone
- `userType` (string, optional, default: "passenger"): Type d'utilisateur (`passenger` ou `driver`)

**Réponse succès (201):**
```json
{
  "message": "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "userType": "passenger",
    "isVerified": false
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Erreurs possibles:**
- `400` - Données invalides
- `409` - Email déjà utilisé

---

### 2. Connexion (Login)

**Endpoint:** `POST /api/login`

**Headers:**
```json
{
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse succès (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502..."
}
```

**Erreurs possibles:**
- `401` - Email ou mot de passe incorrect

---

### 3. Informations utilisateur connecté

**Endpoint:** `GET /api/me`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Body:** Aucun

**Réponse succès (200):**
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "driver",
  "rating": 4.8,
  "totalRides": 120,
  "isVerified": true,
  "createdAt": "2024-01-15T10:30:00+00:00",
  "driverProfile": {
    "id": 1,
    "vehicleModel": "Toyota Prius",
    "vehicleColor": "Blanc",
    "vehicleType": "comfort",
    "isAvailable": true,
    "currentLatitude": 48.8566,
    "currentLongitude": 2.3522
  }
}
```

**Erreurs possibles:**
- `401` - Non authentifié

---

### 4. Vérification email

**Endpoint:** `POST /api/verify-email`

**Headers:**
```json
{
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "token": "a1b2c3d4e5f6..."
}
```

**Réponse succès (200):**
```json
{
  "message": "Email vérifié avec succès",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "isVerified": true
  }
}
```

**Erreurs possibles:**
- `400` - Token invalide ou expiré

---

### 5. Renvoyer email de vérification

**Endpoint:** `POST /api/resend-verification`

**Headers:**
```json
{
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "email": "user@example.com"
}
```

**Réponse succès (200):**
```json
{
  "message": "Email de vérification renvoyé"
}
```

**Erreurs possibles:**
- `400` - Email manquant ou déjà vérifié
- `404` - Utilisateur non trouvé

---

## Users

Base URL: `/api/users`

### 1. Récupérer un utilisateur

**Endpoint:** `GET /api/users/{id}`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Réponse succès (200):**
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger",
  "rating": 4.5,
  "totalRides": 25,
  "profilePicture": null,
  "isVerified": true,
  "createdAt": "2024-01-15T10:30:00+00:00"
}
```

---

### 2. Lister les utilisateurs

**Endpoint:** `GET /api/users`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Query Parameters:**
- `userType` (string): Filtrer par type (`passenger` ou `driver`)
- `email` (string): Recherche partielle par email
- `firstName` (string): Recherche partielle par prénom
- `lastName` (string): Recherche partielle par nom
- `rating[gte]` (float): Rating minimum
- `rating[lte]` (float): Rating maximum
- `page` (int): Numéro de page (default: 1)
- `itemsPerPage` (int): Nombre d'items par page (default: 30)

**Exemple:**
```
GET /api/users?userType=driver&rating[gte]=4.5
```

**Réponse succès (200):**
```json
{
  "hydra:member": [
    {
      "id": 1,
      "email": "driver@example.com",
      "firstName": "Jane",
      "lastName": "Smith",
      "userType": "driver",
      "rating": 4.8
    }
  ],
  "hydra:totalItems": 1
}
```

---

### 3. Créer un utilisateur (via API Platform)

**Endpoint:** `POST /api/users`

**Headers:**
```json
{
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

**Note:** Préférer utiliser `/api/register` pour l'inscription car il gère l'envoi d'email de vérification.

---

### 4. Modifier un utilisateur

**Endpoint:** `PATCH /api/users/{id}`

**Security:** Seul l'utilisateur lui-même peut se modifier

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/merge-patch+json"
}
```

**Body (partiel):**
```json
{
  "firstName": "Jean",
  "phone": "+33612345679"
}
```

**Champs modifiables:**
- `firstName` (string)
- `lastName` (string)
- `phone` (string)
- `profilePicture` (string)

**Réponse succès (200):** Utilisateur mis à jour

---

## Drivers

Base URL: `/api/drivers`

### 1. Créer un profil driver

**Endpoint:** `POST /api/drivers`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "user": "/api/users/1",
  "vehicleModel": "Toyota Prius",
  "vehicleType": "comfort",
  "vehicleColor": "Blanc",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "licenceNumber": "123456789"
}
```

**Champs:**
- `user` (string, required): IRI de l'utilisateur (`/api/users/{id}`)
- `vehicleModel` (string, required): Modèle du véhicule
- `vehicleType` (string, required): Type de véhicule (`standard`, `comfort`, `premium`, `xl`)
- `vehicleColor` (string, required): Couleur du véhicule
- `currentLatitude` (float, required): Latitude actuelle
- `currentLongitude` (float, required): Longitude actuelle
- `licenceNumber` (string, required): Numéro de permis

**Réponse succès (201):**
```json
{
  "id": 1,
  "user": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe"
  },
  "vehicleModel": "Toyota Prius",
  "vehicleType": "comfort",
  "vehicleColor": "Blanc",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "isVerified": false,
  "isAvailable": false
}
```

---

### 2. Lister les drivers

**Endpoint:** `GET /api/drivers`

**Query Parameters:**
- `isAvailable` (boolean): Filtrer par disponibilité
- `isVerified` (boolean): Filtrer par vérification
- `vehicleType` (string): Filtrer par type de véhicule
- `vehicleColor` (string): Recherche partielle par couleur
- `vehicleModel` (string): Recherche partielle par modèle

**Exemple:**
```
GET /api/drivers?isAvailable=true&vehicleType=comfort
```

---

### 3. Récupérer un driver

**Endpoint:** `GET /api/drivers/{id}`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Réponse succès (200):**
```json
{
  "id": 1,
  "user": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "rating": 4.8
  },
  "vehicleModel": "Toyota Prius",
  "vehicleType": "comfort",
  "vehicleColor": "Blanc",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "licenceNumber": "123456789",
  "isVerified": true,
  "isAvailable": true,
  "verifiedAt": "2024-01-10T10:00:00+00:00"
}
```

---

### 4. Mettre à jour la localisation du driver

**Endpoint:** `PATCH /api/drivers/location`

**Security:** Seul un driver authentifié peut mettre à jour sa position

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/merge-patch+json"
}
```

**Body:**
```json
{
  "currentLatitude": 48.8606,
  "currentLongitude": 2.3376
}
```

**Champs:**
- `currentLatitude` (float, required): Nouvelle latitude
- `currentLongitude` (float, required): Nouvelle longitude

**Réponse succès (200):**
```json
{
  "id": 1,
  "currentLatitude": 48.8606,
  "currentLongitude": 2.3376,
  "isAvailable": true
}
```

**Erreurs possibles:**
- `403` - L'utilisateur n'est pas un driver
- `404` - Profil driver non trouvé

---

### 5. Basculer la disponibilité du driver

**Endpoint:** `PATCH /api/drivers/availability`

**Security:** Seul un driver authentifié peut modifier sa disponibilité

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/merge-patch+json"
}
```

**Body:**
```json
{
  "isAvailable": true
}
```

**Champs:**
- `isAvailable` (boolean, required): Nouvelle disponibilité

**Réponse succès (200):**
```json
{
  "id": 1,
  "isAvailable": true,
  "vehicleType": "comfort"
}
```

**Erreurs possibles:**
- `403` - L'utilisateur n'est pas un driver
- `404` - Profil driver non trouvé

---

### 6. Modifier un driver

**Endpoint:** `PATCH /api/drivers/{id}`

**Security:** Seul le driver lui-même peut se modifier

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/merge-patch+json"
}
```

**Body:**
```json
{
  "vehicleModel": "Tesla Model 3",
  "vehicleColor": "Noir"
}
```

---

## Rides

Base URL: `/api/rides`

### 1. Demander une course

**Endpoint:** `POST /api/rides`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "pickupAddress": "10 Rue de Rivoli, 75001 Paris",
  "pickupLatitude": 48.8566,
  "pickupLongitude": 2.3522,
  "dropoffAddress": "Arc de Triomphe, 75008 Paris",
  "dropoffLatitude": 48.8738,
  "dropoffLongitude": 2.2950,
  "vehicleType": "comfort"
}
```

**Champs:**
- `pickupAddress` (string, required): Adresse de départ
- `pickupLatitude` (float, required): Latitude de départ
- `pickupLongitude` (float, required): Longitude de départ
- `dropoffAddress` (string, required): Adresse d'arrivée
- `dropoffLatitude` (float, required): Latitude d'arrivée
- `dropoffLongitude` (float, required): Longitude d'arrivée
- `vehicleType` (string, required): Type de véhicule (`standard`, `comfort`, `premium`, `xl`)

**Réponse succès (201):**
```json
{
  "id": 1,
  "passenger": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe"
  },
  "driver": null,
  "status": "pending",
  "pickupAddress": "10 Rue de Rivoli, 75001 Paris",
  "pickupLatitude": 48.8566,
  "pickupLongitude": 2.3522,
  "dropoffAddress": "Arc de Triomphe, 75008 Paris",
  "dropoffLatitude": 48.8738,
  "dropoffLongitude": 2.2950,
  "estimatedDistance": 3.5,
  "estimatedPrice": 12.50,
  "estimatedDuration": 15,
  "vehicleType": "comfort",
  "createdAt": "2024-01-15T14:30:00+00:00"
}
```

**Notes:**
- Le backend calcule automatiquement `estimatedDistance`, `estimatedPrice` et `estimatedDuration`
- Le statut est automatiquement défini à `pending`
- Les drivers à proximité sont notifiés automatiquement

---

### 2. Lister les courses

**Endpoint:** `GET /api/rides`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Query Parameters:**
- `status` (string): Filtrer par statut (`pending`, `accepted`, `in_progress`, `completed`, `cancelled`)
- `vehicleType` (string): Filtrer par type de véhicule
- `passenger` (int): Filtrer par ID passager
- `driver` (int): Filtrer par ID driver
- `estimatedPrice[gte]` (float): Prix minimum
- `estimatedPrice[lte]` (float): Prix maximum
- `order[createdAt]` (string): Tri par date (`asc` ou `desc`)

**Exemple:**
```
GET /api/rides?status=pending&vehicleType=comfort
```

**Réponse succès (200):**
```json
{
  "hydra:member": [
    {
      "id": 1,
      "status": "pending",
      "vehicleType": "comfort",
      "estimatedPrice": 12.50
    }
  ],
  "hydra:totalItems": 1
}
```

---

### 3. Récupérer une course

**Endpoint:** `GET /api/rides/{id}`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Réponse succès (200):**
```json
{
  "id": 1,
  "passenger": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "rating": 4.5
  },
  "driver": {
    "id": 2,
    "firstName": "Jane",
    "lastName": "Smith",
    "rating": 4.8
  },
  "status": "in_progress",
  "pickupAddress": "10 Rue de Rivoli, 75001 Paris",
  "pickupLatitude": 48.8566,
  "pickupLongitude": 2.3522,
  "dropoffAddress": "Arc de Triomphe, 75008 Paris",
  "dropoffLatitude": 48.8738,
  "dropoffLongitude": 2.2950,
  "estimatedDistance": 3.5,
  "estimatedPrice": 12.50,
  "estimatedDuration": 15,
  "finalPrice": null,
  "vehicleType": "comfort",
  "createdAt": "2024-01-15T14:30:00+00:00",
  "acceptedAt": "2024-01-15T14:32:00+00:00",
  "startedAt": "2024-01-15T14:35:00+00:00",
  "completedAt": null
}
```

---

### 4. Accepter une course (DRIVER)

**Endpoint:** `POST /api/rides/{id}/accept`

**Security:** Seul un driver authentifié peut accepter

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/json"
}
```

**Body:**
```json
{}
```

**⚠️ IMPORTANT:** Le body doit être VIDE ou un objet JSON vide `{}`

**Le backend vérifie automatiquement:**
1. L'utilisateur est un driver (`userType = "driver"`)
2. Le driver a un profil driver
3. Le driver est vérifié (`isVerified = true`)
4. Le driver est disponible (`isAvailable = true`)
5. La course est en statut `pending`
6. Le type de véhicule du driver correspond au type demandé

**Réponse succès (200):**
```json
{
  "id": 1,
  "driver": {
    "id": 2,
    "firstName": "Jane",
    "lastName": "Smith",
    "rating": 4.8
  },
  "status": "accepted",
  "acceptedAt": "2024-01-15T14:32:00+00:00"
}
```

**Erreurs possibles:**
- `403` - Only drivers can accept rides
- `404` - Driver profile not found
- `403` - Driver account not verified
- `400` - Driver is not available
- `400` - Ride already accepted
- `400` - Vehicle type mismatch. Required: comfort, Driver has: standard

---

### 5. Mettre à jour le statut de la course (DRIVER)

**Endpoint:** `PATCH /api/rides/{id}/status`

**Security:** Seul le driver assigné peut modifier le statut

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/merge-patch+json"
}
```

**Body:**
```json
{
  "status": "in_progress"
}
```

**Champs:**
- `status` (string, required): Nouveau statut (`in_progress` ou `completed`)

**Statuts valides:**
- `in_progress` - Course en cours (met à jour `startedAt`)
- `completed` - Course terminée (met à jour `completedAt`, `finalPrice`, rend le driver disponible)

**Réponse succès (200):**
```json
{
  "id": 1,
  "status": "in_progress",
  "startedAt": "2024-01-15T14:35:00+00:00"
}
```

**Erreurs possibles:**
- `403` - Unauthorized (pas le driver de la course)

---

### 6. Annuler une course (PASSENGER ou DRIVER)

**Endpoint:** `POST /api/rides/{id}/cancel`

**Security:** Seul le passager ou le driver assigné peut annuler

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/json"
}
```

**Body:**
```json
{}
```

**Réponse succès (200):**
```json
{
  "id": 1,
  "status": "cancelled"
}
```

**Conditions:**
- La course doit être en statut `pending` ou `accepted`
- Si un driver est assigné, il redevient disponible automatiquement

**Erreurs possibles:**
- `403` - Only the passenger or assigned driver can cancel this ride
- `400` - Cannot cancel ride with status "in_progress". Only pending or accepted rides can be cancelled.

---

## Ratings

Base URL: `/api/ratings`

### 1. Créer une note

**Endpoint:** `POST /api/ratings`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/json"
}
```

**Body:**
```json
{
  "ride": "/api/rides/1",
  "rater": "/api/users/1",
  "rated": "/api/users/2",
  "score": 4.5,
  "comment": "Excellent driver, very professional!"
}
```

**Champs:**
- `ride` (string, required): IRI de la course (`/api/rides/{id}`)
- `rater` (string, required): IRI de l'utilisateur qui note (`/api/users/{id}`)
- `rated` (string, required): IRI de l'utilisateur noté (`/api/users/{id}`)
- `score` (float, required): Note de 1 à 5
- `comment` (string, optional, max: 1000): Commentaire

**Réponse succès (201):**
```json
{
  "id": 1,
  "ride": "/api/rides/1",
  "rater": "/api/users/1",
  "rated": "/api/users/2",
  "score": 4.5,
  "comment": "Excellent driver, very professional!"
}
```

---

### 2. Lister les notes

**Endpoint:** `GET /api/ratings`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Réponse succès (200):**
```json
{
  "hydra:member": [
    {
      "id": 1,
      "score": 4.5,
      "comment": "Excellent driver!"
    }
  ],
  "hydra:totalItems": 1
}
```

---

### 3. Récupérer une note

**Endpoint:** `GET /api/ratings/{id}`

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

---

### 4. Modifier une note

**Endpoint:** `PATCH /api/ratings/{id}`

**Security:** Seul l'utilisateur qui a donné la note peut la modifier

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}",
  "Content-Type": "application/merge-patch+json"
}
```

**Body:**
```json
{
  "score": 5.0,
  "comment": "Updated comment"
}
```

---

### 5. Supprimer une note

**Endpoint:** `DELETE /api/ratings/{id}`

**Security:** Seul l'utilisateur qui a donné la note peut la supprimer

**Headers:**
```json
{
  "Authorization": "Bearer {JWT_TOKEN}"
}
```

**Réponse succès (204):** No Content

---

## Codes d'erreur

| Code | Signification |
|------|---------------|
| 200 | OK - Requête réussie |
| 201 | Created - Ressource créée avec succès |
| 204 | No Content - Suppression réussie |
| 400 | Bad Request - Données invalides |
| 401 | Unauthorized - Token manquant ou invalide |
| 403 | Forbidden - Accès refusé |
| 404 | Not Found - Ressource non trouvée |
| 409 | Conflict - Conflit (ex: email déjà existant) |
| 422 | Unprocessable Entity - Erreurs de validation |
| 500 | Internal Server Error - Erreur serveur |

---

## Notes importantes pour le Frontend

### Authentication
- Stocker le JWT token reçu lors du login/register
- Envoyer le token dans le header `Authorization: Bearer {token}` pour toutes les requêtes authentifiées
- Gérer le refresh token pour les sessions longues

### API Platform
- Les endpoints API Platform utilisent le format JSON-LD par défaut
- Les relations utilisent des IRIs (ex: `/api/users/1`)
- Pour les opérations PATCH, utiliser le header `Content-Type: application/merge-patch+json`

### Temps réel
- Implémenter des polling ou WebSockets pour les mises à jour en temps réel (position du driver, statut de la course)
- Le backend envoie des notifications via le NotificationService (à implémenter côté frontend)

### Workflow typique d'une course

1. **Passager demande une course**
   - POST `/api/rides` avec les coordonnées
   - Backend calcule le prix et notifie les drivers

2. **Driver accepte la course**
   - POST `/api/rides/{id}/accept`
   - Le driver devient indisponible

3. **Driver démarre la course**
   - PATCH `/api/rides/{id}/status` avec `status: "in_progress"`

4. **Driver met à jour sa position régulièrement**
   - PATCH `/api/drivers/location` toutes les 5-10 secondes

5. **Driver termine la course**
   - PATCH `/api/rides/{id}/status` avec `status: "completed"`
   - Le driver redevient disponible

6. **Passager note le driver**
   - POST `/api/ratings` avec score et commentaire

### Gestion des erreurs
- Toujours vérifier le code HTTP de retour
- Parser les messages d'erreur pour afficher à l'utilisateur
- Gérer les cas 401 pour rediriger vers login

### Types de véhicules disponibles
- `standard` - Véhicule standard
- `comfort` - Véhicule confort
- `premium` - Véhicule premium
- `xl` - Véhicule XL (grand)

### Statuts de course possibles
- `pending` - En attente d'un driver
- `accepted` - Acceptée par un driver
- `in_progress` - En cours
- `completed` - Terminée
- `cancelled` - Annulée

---

## Exemples d'utilisation Frontend

### Inscription d'un passager
```javascript
const response = await fetch('http://localhost:8000/api/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'john@example.com',
    password: 'password123',
    firstName: 'John',
    lastName: 'Doe',
    phone: '+33612345678',
    userType: 'passenger'
  })
});
const data = await response.json();
// Stocker data.token dans localStorage
```

### Connexion
```javascript
const response = await fetch('http://localhost:8000/api/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'john@example.com',
    password: 'password123'
  })
});
const data = await response.json();
// Stocker data.token dans localStorage
```

### Demander une course
```javascript
const token = localStorage.getItem('token');
const response = await fetch('http://localhost:8000/api/rides', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    pickupAddress: '10 Rue de Rivoli, 75001 Paris',
    pickupLatitude: 48.8566,
    pickupLongitude: 2.3522,
    dropoffAddress: 'Arc de Triomphe, 75008 Paris',
    dropoffLatitude: 48.8738,
    dropoffLongitude: 2.2950,
    vehicleType: 'comfort'
  })
});
const ride = await response.json();
```

### Driver accepte une course
```javascript
const token = localStorage.getItem('token');
const rideId = 1;
const response = await fetch(`http://localhost:8000/api/rides/${rideId}/accept`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({})
});
const ride = await response.json();
```

### Mettre à jour la position du driver
```javascript
const token = localStorage.getItem('token');
const response = await fetch('http://localhost:8000/api/drivers/location', {
  method: 'PATCH',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/merge-patch+json'
  },
  body: JSON.stringify({
    currentLatitude: 48.8606,
    currentLongitude: 2.3376
  })
});
```

### Lister les courses en attente
```javascript
const token = localStorage.getItem('token');
const response = await fetch('http://localhost:8000/api/rides?status=pending', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
const data = await response.json();
const rides = data['hydra:member'];
```
