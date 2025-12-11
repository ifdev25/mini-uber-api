# 📚 Documentation API Mini-Uber - Frontend

**Version:** 1.0
**Format:** JSON-LD (API Platform)
**Base URL:** `http://localhost:8000` (dev)

---

## 📌 Table des matières

1. [Format JSON-LD](#format-json-ld)
2. [Authentication](#authentication)
3. [Users](#users)
4. [Drivers](#drivers)
5. [Rides](#rides)
6. [Ratings](#ratings)
7. [Codes d'erreur](#codes-derreur)
8. [Exemples Frontend](#exemples-frontend)

---

## 🔍 Format JSON-LD

### Structure générale

Tous les endpoints API Platform renvoient des données au format **JSON-LD** (Linked Data).

**Collection (liste):**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides",
  "@type": "hydra:Collection",
  "hydra:member": [
    { /* objet 1 */ },
    { /* objet 2 */ }
  ],
  "hydra:totalItems": 2,
  "hydra:view": {
    "@id": "/api/rides?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/rides?page=1",
    "hydra:last": "/api/rides?page=3",
    "hydra:next": "/api/rides?page=2"
  }
}
```

**Objet unique:**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "status": "pending",
  ...
}
```

### ⚠️ IMPORTANT: Objets complets (pas d'IRIs)

**✅ L'API renvoie des objets complets** en lecture (normalization), **pas des IRIs**:

```json
{
  "driver": {
    "id": 1,
    "user": {
      "id": 5,
      "firstName": "Jane",
      "lastName": "Smith",
      "email": "jane@example.com"
    },
    "vehicleModel": "Toyota Prius"
  }
}
```

**❌ PAS des IRIs comme ça:**
```json
{
  "driver": "/api/drivers/1"  // ❌ Non utilisé en output
}
```

**Note:** Les IRIs sont uniquement utilisées en **input** (denormalization) lors de la création/modification:

```json
POST /api/rides
{
  "passenger": "/api/users/1",  // ✅ IRI acceptée en input
  "pickupAddress": "...",
  ...
}
```

---

## 🔐 Authentication

### 1. Inscription

**Endpoint:** `POST /api/register`

**Request:**
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

**Response (201):**
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

---

### 2. Connexion

**Endpoint:** `POST /api/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502..."
}
```

---

### 3. Utilisateur connecté

**Endpoint:** `GET /api/me`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Response (200):**
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

---

### 4. Vérification email

**Endpoint:** `POST /api/verify-email`

**Request:**
```json
{
  "token": "a1b2c3d4e5f6..."
}
```

**Response (200):**
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

---

### 5. Renvoyer email de vérification

**Endpoint:** `POST /api/resend-verification`

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "message": "Email de vérification renvoyé"
}
```

---

## 👤 Users

Base URL: `/api/users`

### 1. Récupérer un utilisateur

**Endpoint:** `GET /api/users/{id}`

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users/1",
  "@type": "User",
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

**Endpoint:** `GET /api/users?userType=driver&rating[gte]=4.5&page=1`

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/users/1",
      "@type": "User",
      "id": 1,
      "email": "driver@example.com",
      "firstName": "Jane",
      "lastName": "Smith",
      "userType": "driver",
      "rating": 4.8
    }
  ],
  "hydra:totalItems": 1,
  "hydra:view": {
    "@id": "/api/users?page=1",
    "@type": "hydra:PartialCollectionView"
  }
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

---

### 3. Créer un utilisateur

**Endpoint:** `POST /api/users`

**Request - JSON-LD:**
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

**Response (201) - JSON-LD:**
```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users/1",
  "@type": "User",
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "userType": "passenger"
}
```

**Note:** Préférer `/api/register` pour l'inscription (gère l'email de vérification).

---

### 4. Modifier un utilisateur

**Endpoint:** `PATCH /api/users/{id}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/merge-patch+json
```

**Request:**
```json
{
  "firstName": "Jean",
  "phone": "+33612345679"
}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users/1",
  "@type": "User",
  "id": 1,
  "firstName": "Jean",
  "phone": "+33612345679"
}
```

**Champs modifiables:**
- `firstName` (string)
- `lastName` (string)
- `phone` (string)
- `profilePicture` (string)

---

## 🚗 Drivers

Base URL: `/api/drivers`

### 1. Créer un profil driver

**Endpoint:** `POST /api/drivers`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Request - JSON-LD (avec IRI):**
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

**Response (201) - JSON-LD (avec objet complet):**
```json
{
  "@context": "/api/contexts/Driver",
  "@id": "/api/drivers/1",
  "@type": "Driver",
  "id": 1,
  "user": {
    "@id": "/api/users/1",
    "@type": "User",
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "rating": 4.8
  },
  "vehicleModel": "Toyota Prius",
  "vehicleType": "comfort",
  "vehicleColor": "Blanc",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "licenceNumber": "123456789",
  "isVerified": false,
  "isAvailable": false,
  "verifiedAt": null
}
```

---

### 2. Lister les drivers

**Endpoint:** `GET /api/drivers?isAvailable=true&vehicleType=comfort`

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Driver",
  "@id": "/api/drivers",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/drivers/1",
      "@type": "Driver",
      "id": 1,
      "user": {
        "id": 1,
        "firstName": "John",
        "lastName": "Doe"
      },
      "vehicleModel": "Toyota Prius",
      "vehicleType": "comfort",
      "isAvailable": true,
      "isVerified": true
    }
  ],
  "hydra:totalItems": 1
}
```

**Query Parameters:**
- `isAvailable` (boolean): Filtrer par disponibilité
- `isVerified` (boolean): Filtrer par vérification
- `vehicleType` (string): Filtrer par type de véhicule
- `vehicleColor` (string): Recherche partielle par couleur
- `vehicleModel` (string): Recherche partielle par modèle

---

### 3. Récupérer un driver

**Endpoint:** `GET /api/drivers/{id}`

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Driver",
  "@id": "/api/drivers/1",
  "@type": "Driver",
  "id": 1,
  "user": {
    "@id": "/api/users/1",
    "@type": "User",
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
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

### 4. Mettre à jour la localisation

**Endpoint:** `PATCH /api/drivers/location`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/merge-patch+json
```

**Request:**
```json
{
  "currentLatitude": 48.8606,
  "currentLongitude": 2.3376
}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Driver",
  "@id": "/api/drivers/1",
  "@type": "Driver",
  "id": 1,
  "currentLatitude": 48.8606,
  "currentLongitude": 2.3376,
  "isAvailable": true
}
```

---

### 5. Basculer la disponibilité

**Endpoint:** `PATCH /api/drivers/availability`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/merge-patch+json
```

**Request:**
```json
{
  "isAvailable": true
}
```

**Response (200) - JSON-LD (objet complet):**
```json
{
  "@context": "/api/contexts/Driver",
  "@id": "/api/drivers/1",
  "@type": "Driver",
  "id": 1,
  "user": {
    "@id": "/api/users/5",
    "@type": "User",
    "id": 5,
    "firstName": "Jane",
    "lastName": "Smith",
    "email": "jane@example.com",
    "rating": 4.8
  },
  "vehicleModel": "Toyota Prius",
  "vehicleType": "comfort",
  "vehicleColor": "Blanc",
  "licenceNumber": "123456789",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "isVerified": true,
  "isAvailable": true,
  "verifiedAt": "2024-01-10T10:00:00+00:00"
}
```

---

### 6. Drivers disponibles à proximité

**Endpoint:** `GET /api/drivers/available?lat=48.8566&lng=2.3522&radius=5`

**Response (200) - JSON:**
```json
[
  {
    "id": 1,
    "name": "Jane Smith",
    "rating": 4.8,
    "vehicle": {
      "model": "Toyota Prius",
      "color": "Blanc",
      "type": "comfort"
    },
    "location": {
      "lat": 48.8566,
      "lng": 2.3522
    },
    "distance": 1.23
  }
]
```

**Query Parameters:**
- `lat` (float, required): Latitude de recherche
- `lng` (float, required): Longitude de recherche
- `radius` (float, optional): Rayon de recherche en km (default: 5)

---

### 7. Statistiques driver

**Endpoint:** `GET /api/drivers/stats`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Response (200) - JSON:**
```json
{
  "driver": {
    "id": 1,
    "isAvailable": true,
    "isVerified": true,
    "vehicleModel": "Toyota Prius",
    "vehicleType": "comfort",
    "vehicleColor": "Blanc"
  },
  "stats": {
    "completedRides": 120,
    "canceledRides": 5,
    "totalEarnings": 1234.56,
    "averageRating": 4.8,
    "totalRides": 125
  }
}
```

---

## 🚕 Rides

Base URL: `/api/rides`

**⚠️ IMPORTANT:** Tous les endpoints renvoient `driver` et `passenger` comme **objets complets**, pas comme IRIs.

---

### 1. Demander une course

**Endpoint:** `POST /api/rides`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Request - JSON-LD (pas besoin de spécifier passenger, détecté automatiquement):**
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

**Response (201) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "passenger": {
    "@id": "/api/users/1",
    "@type": "User",
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "rating": 4.5
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

---

### 2. Lister les courses

**Endpoint:** `GET /api/rides?status=pending&vehicleType=comfort`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/rides/1",
      "@type": "Ride",
      "id": 1,
      "passenger": {
        "id": 1,
        "firstName": "John",
        "lastName": "Doe"
      },
      "driver": null,
      "status": "pending",
      "vehicleType": "comfort",
      "estimatedPrice": 12.50
    }
  ],
  "hydra:totalItems": 1
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

---

### 3. Récupérer une course

**Endpoint:** `GET /api/rides/{id}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Response (200) - JSON-LD (avec objets complets):**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "passenger": {
    "@id": "/api/users/1",
    "@type": "User",
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "rating": 4.5
  },
  "driver": {
    "@id": "/api/drivers/2",
    "@type": "Driver",
    "id": 2,
    "user": {
      "@id": "/api/users/5",
      "@type": "User",
      "id": 5,
      "firstName": "Jane",
      "lastName": "Smith",
      "email": "jane@example.com",
      "rating": 4.8
    },
    "vehicleModel": "Toyota Prius",
    "vehicleType": "comfort",
    "vehicleColor": "Blanc",
    "licenceNumber": "DRV123456",
    "currentLatitude": 48.8566,
    "currentLongitude": 2.3522,
    "isAvailable": false,
    "isVerified": true
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

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Request (body vide):**
```json
{}
```

**Response (200) - JSON-LD (avec objets complets):**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "passenger": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "rating": 4.5
  },
  "driver": {
    "id": 2,
    "user": {
      "id": 5,
      "firstName": "Jane",
      "lastName": "Smith",
      "email": "jane@example.com",
      "rating": 4.8
    },
    "vehicleModel": "Toyota Prius",
    "vehicleType": "comfort",
    "vehicleColor": "Blanc",
    "licenceNumber": "DRV123456",
    "currentLatitude": 48.8566,
    "currentLongitude": 2.3522,
    "isAvailable": false
  },
  "status": "accepted",
  "estimatedPrice": 12.50,
  "acceptedAt": "2024-01-15T14:32:00+00:00"
}
```

**Vérifications automatiques:**
- L'utilisateur est un driver
- Le driver a un profil complet
- Le driver est vérifié (`isVerified = true`)
- Le driver est disponible (`isAvailable = true`)
- La course est en statut `pending`
- Le type de véhicule correspond

**Erreurs possibles:**
- `403` - Only drivers can accept rides
- `404` - Driver profile not found
- `403` - Driver account not verified
- `400` - Driver is not available
- `400` - Ride already accepted
- `400` - Vehicle type mismatch

---

### 5. Mettre à jour le statut (DRIVER)

**Endpoint:** `PATCH /api/rides/{id}/status`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/merge-patch+json
```

**Request:**
```json
{
  "status": "in_progress"
}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "status": "in_progress",
  "startedAt": "2024-01-15T14:35:00+00:00"
}
```

**Statuts valides:**
- `in_progress` - Course en cours (met à jour `startedAt`)
- `completed` - Course terminée (met à jour `completedAt`, `finalPrice`, rend le driver disponible)

---

### 6. Annuler une course

**Endpoint:** `POST /api/rides/{id}/cancel`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Request (body vide):**
```json
{}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "status": "cancelled"
}
```

**Conditions:**
- La course doit être en statut `pending` ou `accepted`
- Si un driver est assigné, il redevient disponible automatiquement

**Erreurs possibles:**
- `403` - Only the passenger or assigned driver can cancel this ride
- `400` - Cannot cancel ride with status "in_progress"

---

## ⭐ Ratings

Base URL: `/api/ratings`

### 1. Créer une note

**Endpoint:** `POST /api/ratings`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Request - JSON-LD (avec IRIs en input):**
```json
{
  "ride": "/api/rides/1",
  "rater": "/api/users/1",
  "rated": "/api/users/2",
  "score": 4.5,
  "comment": "Excellent driver, very professional!"
}
```

**Response (201) - JSON-LD (avec objets complets en output):**
```json
{
  "@context": "/api/contexts/Rating",
  "@id": "/api/ratings/1",
  "@type": "Rating",
  "id": 1,
  "ride": {
    "@id": "/api/rides/1",
    "@type": "Ride",
    "id": 1,
    "status": "completed",
    "vehicleType": "comfort",
    "estimatedPrice": 12.50
  },
  "rater": {
    "@id": "/api/users/1",
    "@type": "User",
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "rating": 4.5,
    "userType": "passenger",
    "totalRides": 26,
    "phone": "+33612345678",
    "profilePicture": null
  },
  "rated": {
    "@id": "/api/users/2",
    "@type": "User",
    "id": 2,
    "firstName": "Jane",
    "lastName": "Smith",
    "email": "jane@example.com",
    "rating": 4.8,
    "userType": "driver",
    "totalRides": 120,
    "phone": "+33612345679",
    "profilePicture": null
  },
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

---

### 2. Lister les notes

**Endpoint:** `GET /api/ratings`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Rating",
  "@id": "/api/ratings",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/ratings/1",
      "@type": "Rating",
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
```
Authorization: Bearer {JWT_TOKEN}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Rating",
  "@id": "/api/ratings/1",
  "@type": "Rating",
  "id": 1,
  "ride": {
    "id": 1,
    "status": "completed"
  },
  "rater": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe"
  },
  "rated": {
    "id": 2,
    "firstName": "Jane",
    "lastName": "Smith"
  },
  "score": 4.5,
  "comment": "Excellent driver!"
}
```

---

### 4. Modifier une note

**Endpoint:** `PATCH /api/ratings/{id}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/merge-patch+json
```

**Request:**
```json
{
  "score": 5.0,
  "comment": "Updated comment"
}
```

**Response (200) - JSON-LD:**
```json
{
  "@context": "/api/contexts/Rating",
  "@id": "/api/ratings/1",
  "@type": "Rating",
  "id": 1,
  "score": 5.0,
  "comment": "Updated comment"
}
```

---

### 5. Supprimer une note

**Endpoint:** `DELETE /api/ratings/{id}`

**Headers:**
```
Authorization: Bearer {JWT_TOKEN}
```

**Response (204):** No Content

---

## ⚠️ Codes d'erreur

| Code | Signification | Exemple |
|------|---------------|---------|
| 200 | OK - Requête réussie | GET /api/rides/1 |
| 201 | Created - Ressource créée | POST /api/rides |
| 204 | No Content - Suppression réussie | DELETE /api/ratings/1 |
| 400 | Bad Request - Données invalides | `{"error": "Driver is not available"}` |
| 401 | Unauthorized - Token manquant/invalide | `{"message": "JWT Token not found"}` |
| 403 | Forbidden - Accès refusé | `{"message": "Only drivers can accept rides"}` |
| 404 | Not Found - Ressource non trouvée | `{"message": "Driver profile not found"}` |
| 409 | Conflict - Conflit | `{"message": "Email already exists"}` |
| 422 | Unprocessable Entity - Erreurs de validation | Violations de contraintes |
| 500 | Internal Server Error | Erreur serveur |

**Format d'erreur JSON-LD:**
```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "Driver is not available",
  "code": 400
}
```

**Format d'erreur de validation:**
```json
{
  "@context": "/api/contexts/ConstraintViolationList",
  "@type": "ConstraintViolationList",
  "hydra:title": "An error occurred",
  "hydra:description": "email: This value is already used.",
  "violations": [
    {
      "propertyPath": "email",
      "message": "This value is already used.",
      "code": "23bd9dbf-6b9b-41cd-a99e-4844bcf3077f"
    }
  ]
}
```

---

## 💻 Exemples Frontend

### Configuration TypeScript

```typescript
// types/api.ts

export interface JSONLDContext {
  '@context': string;
  '@id': string;
  '@type': string;
}

export interface HydraCollection<T> extends JSONLDContext {
  'hydra:member': T[];
  'hydra:totalItems': number;
  'hydra:view'?: {
    '@id': string;
    '@type': 'hydra:PartialCollectionView';
    'hydra:first'?: string;
    'hydra:last'?: string;
    'hydra:next'?: string;
    'hydra:previous'?: string;
  };
}

export interface User extends JSONLDContext {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  phone: string;
  userType: 'passenger' | 'driver';
  rating: number | null;
  totalRides: number | null;
  profilePicture: string | null;
  isVerified: boolean;
  createdAt: string;
  driver?: Driver;
}

export interface Driver extends JSONLDContext {
  id: number;
  user: User;
  vehicleModel: string;
  vehicleType: 'standard' | 'comfort' | 'premium' | 'xl';
  vehicleColor: string;
  currentLatitude: number;
  currentLongitude: number;
  licenceNumber: string;
  verifiedAt: string | null;
  isVerified: boolean;
  isAvailable: boolean;
}

export interface Ride extends JSONLDContext {
  id: number;
  passenger: User;
  driver: Driver | null;
  status: 'pending' | 'accepted' | 'in_progress' | 'completed' | 'cancelled';
  pickupAddress: string;
  pickupLatitude: number;
  pickupLongitude: number;
  dropoffAddress: string;
  dropoffLatitude: number;
  dropoffLongitude: number;
  estimatedDistance: number;
  estimatedPrice: number;
  estimatedDuration: number | null;
  finalPrice: number | null;
  vehicleType: 'standard' | 'comfort' | 'premium' | 'xl';
  createdAt: string;
  acceptedAt: string | null;
  startedAt: string | null;
  completedAt: string | null;
}

export interface Rating extends JSONLDContext {
  id: number;
  ride: Ride;
  rater: User;
  rated: User;
  score: number;
  comment: string | null;
}
```

---

### Client API TypeScript

```typescript
// lib/api.ts

export class ApiClient {
  private baseURL = 'http://localhost:8000';
  private token: string | null = null;

  setToken(token: string) {
    this.token = token;
    localStorage.setItem('token', token);
  }

  getToken(): string | null {
    if (!this.token) {
      this.token = localStorage.getItem('token');
    }
    return this.token;
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const token = this.getToken();

    const headers: HeadersInit = {
      ...options.headers,
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    if (options.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      ...options,
      headers,
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || error['hydra:description'] || 'API Error');
    }

    if (response.status === 204) {
      return {} as T;
    }

    return response.json();
  }

  // Authentication
  async register(data: {
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    phone: string;
    userType: 'passenger' | 'driver';
  }) {
    return this.request<{ user: User; token: string }>('/api/register', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async login(email: string, password: string) {
    return this.request<{ token: string }>('/api/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  }

  async getMe() {
    return this.request<User>('/api/me');
  }

  // Rides
  async createRide(data: {
    pickupAddress: string;
    pickupLatitude: number;
    pickupLongitude: number;
    dropoffAddress: string;
    dropoffLatitude: number;
    dropoffLongitude: number;
    vehicleType: string;
  }) {
    return this.request<Ride>('/api/rides', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async getRides(params?: {
    status?: string;
    page?: number;
  }) {
    const query = new URLSearchParams(params as any).toString();
    return this.request<HydraCollection<Ride>>(
      `/api/rides${query ? '?' + query : ''}`
    );
  }

  async getRide(id: number) {
    return this.request<Ride>(`/api/rides/${id}`);
  }

  async acceptRide(id: number) {
    return this.request<Ride>(`/api/rides/${id}/accept`, {
      method: 'POST',
      body: JSON.stringify({}),
    });
  }

  async updateRideStatus(id: number, status: string) {
    return this.request<Ride>(`/api/rides/${id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
      body: JSON.stringify({ status }),
    });
  }

  async cancelRide(id: number) {
    return this.request<Ride>(`/api/rides/${id}/cancel`, {
      method: 'POST',
      body: JSON.stringify({}),
    });
  }

  // Drivers
  async updateDriverAvailability(isAvailable: boolean) {
    return this.request<Driver>('/api/drivers/availability', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
      body: JSON.stringify({ isAvailable }),
    });
  }

  async updateDriverLocation(latitude: number, longitude: number) {
    return this.request<Driver>('/api/drivers/location', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
      body: JSON.stringify({
        currentLatitude: latitude,
        currentLongitude: longitude,
      }),
    });
  }

  async getAvailableDrivers(lat: number, lng: number, radius: number = 5) {
    return this.request<any[]>(
      `/api/drivers/available?lat=${lat}&lng=${lng}&radius=${radius}`
    );
  }

  async getDriverStats() {
    return this.request<any>('/api/drivers/stats');
  }

  // Ratings
  async createRating(data: {
    ride: string;
    rater: string;
    rated: string;
    score: number;
    comment?: string;
  }) {
    return this.request<Rating>('/api/ratings', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async getRatings() {
    return this.request<HydraCollection<Rating>>('/api/ratings');
  }
}

export const api = new ApiClient();
```

---

### Exemple d'utilisation React

```tsx
// app/page.tsx

'use client';

import { useEffect, useState } from 'react';
import { api } from '@/lib/api';
import type { User, HydraCollection, Ride } from '@/types/api';

export default function DashboardPage() {
  const [user, setUser] = useState<User | null>(null);
  const [rides, setRides] = useState<HydraCollection<Ride> | null>(null);

  useEffect(() => {
    async function loadData() {
      try {
        // Charger l'utilisateur connecté
        const userData = await api.getMe();
        setUser(userData);

        // Charger les courses
        const ridesData = await api.getRides({ status: 'pending' });
        setRides(ridesData);
      } catch (error) {
        console.error('Error loading data:', error);
      }
    }

    loadData();
  }, []);

  const handleAcceptRide = async (rideId: number) => {
    try {
      const updatedRide = await api.acceptRide(rideId);
      console.log('Ride accepted:', updatedRide);

      // Recharger la liste
      const ridesData = await api.getRides({ status: 'accepted' });
      setRides(ridesData);
    } catch (error) {
      console.error('Error accepting ride:', error);
    }
  };

  const toggleAvailability = async () => {
    try {
      const newAvailability = !user?.driver?.isAvailable;
      const updatedDriver = await api.updateDriverAvailability(newAvailability);

      // Mettre à jour l'utilisateur local
      setUser({
        ...user!,
        driver: updatedDriver,
      });
    } catch (error) {
      console.error('Error updating availability:', error);
    }
  };

  return (
    <div>
      <h1>Dashboard</h1>

      {user && (
        <div>
          <h2>Bienvenue {user.firstName}!</h2>
          {user.driver && (
            <button onClick={toggleAvailability}>
              {user.driver.isAvailable ? 'Se rendre indisponible' : 'Se rendre disponible'}
            </button>
          )}
        </div>
      )}

      <h2>Courses en attente ({rides?.['hydra:totalItems'] || 0})</h2>

      {rides?.['hydra:member'].map(ride => (
        <div key={ride.id}>
          <h3>Course #{ride.id}</h3>
          <p>De: {ride.pickupAddress}</p>
          <p>À: {ride.dropoffAddress}</p>
          <p>Prix: {ride.estimatedPrice}€</p>
          <button onClick={() => handleAcceptRide(ride.id)}>
            Accepter
          </button>
        </div>
      ))}
    </div>
  );
}
```

---

## 📝 Notes importantes

### 1. Format JSON-LD

- Toutes les réponses API Platform incluent `@context`, `@id` et `@type`
- Les collections utilisent `hydra:member` et `hydra:totalItems`
- La pagination utilise `hydra:view` avec `hydra:first`, `hydra:last`, `hydra:next`, `hydra:previous`

### 2. IRIs vs Objets complets

**En INPUT (création/modification):**
```json
{
  "user": "/api/users/1",  // ✅ IRI acceptée
  "ride": "/api/rides/5"   // ✅ IRI acceptée
}
```

**En OUTPUT (lecture):**
```json
{
  "user": {  // ✅ Objet complet retourné
    "id": 1,
    "firstName": "John",
    "lastName": "Doe"
  }
}
```

### 3. Headers obligatoires

**Pour PATCH avec merge-patch:**
```
Content-Type: application/merge-patch+json
```

**Pour POST/PUT:**
```
Content-Type: application/json
```

**Pour toutes les requêtes authentifiées:**
```
Authorization: Bearer {JWT_TOKEN}
```

### 4. Pagination

Par défaut, les collections retournent 30 items par page. Pour changer:

```
GET /api/rides?page=2&itemsPerPage=50
```

### 5. Filtres

Les filtres sont appliqués via query parameters. Consultez la documentation de chaque endpoint pour les filtres disponibles.

---

## 🚀 Workflow typique

### Passager demande une course

```typescript
// 1. Créer la course
const ride = await api.createRide({
  pickupAddress: "10 Rue de Rivoli, 75001 Paris",
  pickupLatitude: 48.8566,
  pickupLongitude: 2.3522,
  dropoffAddress: "Arc de Triomphe, 75008 Paris",
  dropoffLatitude: 48.8738,
  dropoffLongitude: 2.2950,
  vehicleType: "comfort"
});

// 2. Attendre qu'un driver accepte (polling ou WebSocket)
const checkRideStatus = setInterval(async () => {
  const updatedRide = await api.getRide(ride.id);
  if (updatedRide.status === 'accepted') {
    clearInterval(checkRideStatus);
    console.log('Course acceptée par:', updatedRide.driver?.user.firstName);
  }
}, 3000);
```

### Driver accepte et complète une course

```typescript
// 1. Lister les courses disponibles
const rides = await api.getRides({ status: 'pending' });

// 2. Accepter une course
const acceptedRide = await api.acceptRide(rides['hydra:member'][0].id);

// 3. Démarrer la course
await api.updateRideStatus(acceptedRide.id, 'in_progress');

// 4. Mettre à jour la position régulièrement
setInterval(async () => {
  navigator.geolocation.getCurrentPosition(async (position) => {
    await api.updateDriverLocation(
      position.coords.latitude,
      position.coords.longitude
    );
  });
}, 5000);

// 5. Terminer la course
await api.updateRideStatus(acceptedRide.id, 'completed');

// 6. Passager note le driver
await api.createRating({
  ride: `/api/rides/${acceptedRide.id}`,
  rater: `/api/users/${passengerId}`,
  rated: `/api/users/${driverId}`,
  score: 5,
  comment: "Excellent service!"
});
```

---

**Version:** 1.0
**Dernière mise à jour:** 2025-12-11
**Format API:** JSON-LD (API Platform 3.x)
**Symfony:** 7.x
