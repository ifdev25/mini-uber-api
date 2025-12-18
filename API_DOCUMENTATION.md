# Documentation API - Mini Uber

**Version**: 1.1.0
**Date**: 16 décembre 2025
**Base URL**: `http://localhost:8080/api`

---

## Table des matières

1. [Authentification](#authentification)
2. [Utilisateurs (Users)](#utilisateurs-users)
3. [Chauffeurs (Drivers)](#chauffeurs-drivers)
4. [Courses (Rides)](#courses-rides)
5. [Évaluations (Ratings)](#évaluations-ratings)
6. [Codes d'erreur](#codes-derreur)
7. [Types et énumérations](#types-et-énumérations)

---

## Authentification

Tous les endpoints (sauf `/register`, `/login`, `/verify-email`, `/resend-verification`) nécessitent un token JWT dans le header :

```http
Authorization: Bearer <JWT_TOKEN>
```

### POST /api/register

Inscription d'un nouveau utilisateur.

**Body (JSON)**:
```json
{
  "email": "john.doe@example.com",
  "password": "securePassword123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

**Champs requis**:
| Champ | Type | Contraintes |
|-------|------|-------------|
| `email` | string | Email valide, unique |
| `password` | string | Non vide |
| `firstName` | string | 2-50 caractères |
| `lastName` | string | Non vide |
| `phone` | string | Non vide |
| `userType` | string | `passenger` ou `driver` |

**Réponse (201)**:
```json
{
  "message": "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.",
  "user": {
    "id": 1,
    "email": "john.doe@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "userType": "passenger",
    "isVerified": false
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Erreurs (422)**:
```json
{
  "error": true,
  "message": "Erreur de validation",
  "violations": {
    "email": "Un compte avec cet email existe déjà."
  }
}
```

---

### POST /api/login

Connexion d'un utilisateur existant.

**Body (JSON)**:
```json
{
  "email": "john.doe@example.com",
  "password": "securePassword123"
}
```

**Réponse (200)**:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Erreurs (401)**:
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

---

### GET /api/me

Récupère les informations de l'utilisateur authentifié.

**Headers**:
```http
Authorization: Bearer <JWT_TOKEN>
```

**Réponse (200)**:
```json
{
  "id": 1,
  "email": "marie.martin@driver.com",
  "firstName": "Marie",
  "lastName": "Martin",
  "phone": "+33612345678",
  "userType": "driver",
  "rating": 4.9,
  "totalRides": 234,
  "isVerified": true,
  "createdAt": "2024-01-15T10:30:00+00:00",
  "driverProfile": {
    "id": 1,
    "vehicleModel": "Tesla Model 3",
    "vehicleColor": "Blanc Nacré",
    "vehicleType": "premium",
    "isAvailable": true,
    "currentLatitude": 48.8566,
    "currentLongitude": 2.3522
  }
}
```

**Note**: `driverProfile` est `null` si l'utilisateur n'est pas un chauffeur.

---

### POST /api/verify-email

Vérification de l'email avec le token reçu par email.

**Body (JSON)**:
```json
{
  "token": "abc123def456..."
}
```

**Réponse (200)**:
```json
{
  "message": "Email vérifié avec succès",
  "user": {
    "id": 1,
    "email": "john.doe@example.com",
    "isVerified": true
  }
}
```

**Erreurs (400)**:
```json
{
  "error": "Token invalide"
}
```

---

### POST /api/resend-verification

Renvoie un email de vérification.

**Body (JSON)**:
```json
{
  "email": "john.doe@example.com"
}
```

**Réponse (200)**:
```json
{
  "message": "Email de vérification renvoyé"
}
```

**Erreurs**:
- `404`: Utilisateur non trouvé
- `400`: Email déjà vérifié

---

## Utilisateurs (Users)

API Platform génère automatiquement les endpoints CRUD pour les utilisateurs.

### GET /api/users

Liste tous les utilisateurs (paginée).

**Query Parameters**:
| Paramètre | Type | Description |
|-----------|------|-------------|
| `page` | integer | Numéro de page (défaut: 1) |
| `userType` | string | Filtre par type (`passenger` ou `driver`) |
| `email` | string | Recherche partielle dans l'email |
| `firstName` | string | Recherche partielle dans le prénom |
| `lastName` | string | Recherche partielle dans le nom |
| `rating[gte]` | float | Rating minimum |
| `rating[lte]` | float | Rating maximum |

**Exemple**: `GET /api/users?userType=driver&rating[gte]=4.5`

**Réponse (200)**:
```json
{
  "hydra:member": [
    {
      "id": 1,
      "email": "john.doe@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "phone": "+33612345678",
      "userType": "passenger",
      "rating": 4.8,
      "totalRides": 15,
      "isVerified": true,
      "createdAt": "2024-01-15T10:30:00+00:00"
    }
  ],
  "hydra:totalItems": 1
}
```

---

### GET /api/users/{id}

Récupère un utilisateur spécifique.

**Réponse (200)**: Structure identique à l'objet dans `/api/users`

---

### POST /api/users

Crée un nouvel utilisateur (utiliser `/api/register` de préférence).

---

### PATCH /api/users/{id}

Modifie un utilisateur.

**Sécurité**: L'utilisateur ne peut modifier que son propre profil.

**Body (JSON)** (tous les champs sont optionnels):
```json
{
  "firstName": "John",
  "lastName": "Doe Updated",
  "phone": "+33612345679",
  "profilePicture": "https://example.com/avatar.jpg"
}
```

---

### DELETE /api/users/{id}

Supprime un utilisateur.

**Sécurité**: Administrateur uniquement (`ROLE_ADMIN`).

---

## Chauffeurs (Drivers)

### GET /api/drivers-available

Récupère les chauffeurs disponibles à proximité.

**Query Parameters**:
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `lat` | float | Non | Latitude du point de recherche |
| `lng` | float | Non | Longitude du point de recherche |
| `radius` | float | Non | Rayon de recherche en km (défaut: 5) |

**Exemple**: `GET /api/drivers-available?lat=48.8566&lng=2.3522&radius=10`

**Réponse (200)**:
```json
[
  {
    "id": 1,
    "name": "Marie Martin",
    "rating": 4.9,
    "vehicle": {
      "model": "Tesla Model 3",
      "color": "Blanc Nacré",
      "type": "premium"
    },
    "location": {
      "lat": 48.8566,
      "lng": 2.3522
    },
    "distance": 2.34
  }
]
```

---

### GET /api/driver/available-rides

**🆕 NOUVEAU** - Récupère les courses en attente disponibles pour le chauffeur.

**Authentification**: Requiert un token JWT avec `userType=driver`.

**Query Parameters**:
| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `limit` | integer | 20 | Nombre maximum de résultats |
| `vehicleType` | string | - | Filtre par type de véhicule |
| `maxDistance` | float | - | Distance max en km depuis la position du chauffeur |

**Exemple**: `GET /api/driver/available-rides?maxDistance=5&vehicleType=standard`

**Réponse (200)**:
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

**Erreurs**:
- `401`: Non authentifié
- `403`: Pas un chauffeur
- `404`: Profil chauffeur non trouvé

---

### GET /api/driver/history

Récupère l'historique des courses du chauffeur authentifié.

**Authentification**: Requiert un token JWT avec `userType=driver`.

**Query Parameters**:
| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `status` | string | - | Filtre par statut de course |
| `limit` | integer | 20 | Nombre de résultats |
| `offset` | integer | 0 | Pagination |

**Exemple**: `GET /api/driver/history?status=completed&limit=10`

**Réponse (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 25,
      "status": "completed",
      "passenger": {
        "id": 48,
        "name": "John Doe",
        "phone": "+33612345678",
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
        "estimated": 18.5,
        "final": 18.5
      },
      "distance": 5.2,
      "duration": 15,
      "vehicleType": "premium",
      "dates": {
        "created": "2025-12-15 10:00:00",
        "accepted": "2025-12-15 10:01:00",
        "started": "2025-12-15 10:05:00",
        "completed": "2025-12-15 10:20:00"
      }
    }
  ],
  "pagination": {
    "limit": 20,
    "offset": 0,
    "count": 1
  }
}
```

---

### GET /api/driver/stats

Récupère les statistiques du chauffeur authentifié.

**Authentification**: Requiert un token JWT avec `userType=driver`.

**Réponse (200)**:
```json
{
  "driver": {
    "id": 1,
    "isAvailable": true,
    "isVerified": true,
    "vehicleModel": "Tesla Model 3",
    "vehicleType": "premium",
    "vehicleColor": "Blanc Nacré"
  },
  "stats": {
    "completedRides": 234,
    "canceledRides": 5,
    "totalEarnings": 4523.50,
    "averageRating": 4.9,
    "totalRides": 239
  }
}
```

---

### PATCH /api/driver/availability

Met à jour la disponibilité du chauffeur.

**Authentification**: Requiert un token JWT avec `userType=driver`.

**Body (JSON)**:
```json
{
  "isAvailable": true
}
```

**Champs requis**:
| Champ | Type | Contraintes |
|-------|------|-------------|
| `isAvailable` | boolean | Requis, doit être un booléen |

**Réponse (200)**:
```json
{
  "success": true,
  "message": "Availability updated successfully",
  "data": {
    "id": 1,
    "isAvailable": true,
    "user": {
      "id": 1,
      "email": "marie.martin@driver.com",
      "firstName": "Marie",
      "lastName": "Martin"
    }
  }
}
```

**Erreurs**:
- `400`: Le champ `isAvailable` est manquant ou n'est pas un booléen
- `401`: Non authentifié
- `403`: Pas un chauffeur
- `404`: Profil chauffeur non trouvé

---

### GET /api/drivers

Liste tous les profils chauffeurs (API Platform).

**Query Parameters**:
| Paramètre | Type | Description |
|-----------|------|-------------|
| `isAvailable` | boolean | Filtre par disponibilité |
| `isVerified` | boolean | Filtre par vérification |
| `vehicleType` | string | Filtre par type de véhicule |
| `vehicleModel` | string | Recherche partielle dans le modèle |
| `vehicleColor` | string | Recherche partielle dans la couleur |

**Exemple**: `GET /api/drivers?isAvailable=true&vehicleType=premium`

**Réponse (200)**:
```json
{
  "hydra:member": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "email": "marie.martin@driver.com",
        "firstName": "Marie",
        "lastName": "Martin",
        "rating": 4.9
      },
      "vehicleModel": "Tesla Model 3",
      "vehicleType": "premium",
      "vehicleColor": "Blanc Nacré",
      "currentLatitude": 48.8566,
      "currentLongitude": 2.3522,
      "isVerified": true,
      "isAvailable": true
    }
  ],
  "hydra:totalItems": 1
}
```

---

### GET /api/drivers/{id}

Récupère un profil chauffeur spécifique.

---

### POST /api/drivers

Crée un nouveau profil chauffeur.

**Sécurité**: Utilisateur authentifié uniquement.

**Body (JSON)**:
```json
{
  "user": "/api/users/1",
  "vehicleModel": "Tesla Model 3",
  "vehicleType": "premium",
  "vehicleColor": "Blanc Nacré",
  "licenceNumber": "ABC123456",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522
}
```

**Champs requis**:
| Champ | Type | Contraintes |
|-------|------|-------------|
| `user` | IRI | Référence vers un utilisateur |
| `vehicleModel` | string | Non vide |
| `vehicleType` | string | `standard`, `comfort`, `premium`, `xl` |
| `vehicleColor` | string | Non vide |
| `licenceNumber` | string | Non vide |
| `currentLatitude` | float | - |
| `currentLongitude` | float | - |

---

### PATCH /api/drivers/{id}

Modifie un profil chauffeur.

**Sécurité**: Le chauffeur ne peut modifier que son propre profil.

---

### PATCH /api/drivers/location

Met à jour la position GPS du chauffeur.

**Sécurité**: Chauffeur authentifié uniquement.

**Body (JSON)**:
```json
{
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522
}
```

**Champs requis**:
| Champ | Type | Contraintes |
|-------|------|-------------|
| `currentLatitude` | float | Requis |
| `currentLongitude` | float | Requis |

**Effets de bord automatiques**:
- ✅ Une notification en temps réel est envoyée via Mercure avec la nouvelle position

**Réponse (200)**:
```json
{
  "id": 1,
  "user": {
    "id": 1,
    "email": "marie.martin@driver.com"
  },
  "vehicleModel": "Tesla Model 3",
  "vehicleType": "premium",
  "vehicleColor": "Blanc Nacré",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "isVerified": true,
  "isAvailable": true
}
```

**Erreurs**:
- `403`: Utilisateur n'est pas un chauffeur
- `404`: Profil Driver non trouvé

---

### DELETE /api/drivers/{id}

Supprime un profil chauffeur.

**Sécurité**: Administrateur uniquement.

---

## Courses (Rides)

### GET /api/rides

Liste toutes les courses (paginée).

**Query Parameters**:
| Paramètre | Type | Description |
|-----------|------|-------------|
| `status` | string | Filtre par statut |
| `vehicleType` | string | Filtre par type de véhicule |
| `passenger` | integer | ID du passager |
| `driver` | integer | ID du chauffeur |
| `estimatedPrice[gte]` | float | Prix estimé minimum |
| `estimatedPrice[lte]` | float | Prix estimé maximum |
| `order[createdAt]` | string | Tri par date (`asc` ou `desc`) |

**Exemple**: `GET /api/rides?status=pending&vehicleType=standard`

**Réponse (200)**:
```json
{
  "hydra:member": [
    {
      "id": 28,
      "driver": null,
      "passenger": {
        "id": 48,
        "email": "john.doe@example.com",
        "firstName": "John",
        "lastName": "Doe"
      },
      "status": "pending",
      "pickupAddress": "Opéra Garnier, Paris",
      "pickupLatitude": 48.872,
      "pickupLongitude": 2.3318,
      "dropoffAddress": "Gare de Lyon, Paris",
      "dropoffLatitude": 48.8449,
      "dropoffLongitude": 2.3738,
      "estimatedDistance": 4.5,
      "estimatedPrice": 15.2,
      "estimatedDuration": 14,
      "finalPrice": null,
      "vehicleType": "standard",
      "createdAt": "2025-12-16T11:20:15+00:00",
      "acceptedAt": null,
      "startedAt": null,
      "completedAt": null
    }
  ],
  "hydra:totalItems": 1
}
```

---

### GET /api/rides/{id}

Récupère une course spécifique.

---

### POST /api/rides

Crée une nouvelle course (demande de course par un passager).

**Sécurité**: Utilisateur authentifié uniquement.

**Body (JSON)**:
```json
{
  "passenger": "/api/users/48",
  "pickupAddress": "Opéra Garnier, Paris",
  "pickupLatitude": 48.872,
  "pickupLongitude": 2.3318,
  "dropoffAddress": "Gare de Lyon, Paris",
  "dropoffLatitude": 48.8449,
  "dropoffLongitude": 2.3738,
  "vehicleType": "standard"
}
```

**Champs requis**:
| Champ | Type | Contraintes |
|-------|------|-------------|
| `passenger` | IRI | Référence vers un utilisateur |
| `pickupAddress` | string | Non vide |
| `pickupLatitude` | float | Non vide |
| `pickupLongitude` | float | Non vide |
| `dropoffAddress` | string | Non vide |
| `dropoffLatitude` | float | Non vide |
| `dropoffLongitude` | float | Non vide |
| `vehicleType` | string | `standard`, `comfort`, `premium`, `xl` |

**Effets de bord automatiques**:
- ✅ Le champ `status` est défini à `pending`
- ✅ Le champ `estimatedDistance` est calculé automatiquement (en km)
- ✅ Le champ `estimatedPrice` est calculé automatiquement (en €)
- ✅ Le champ `estimatedDuration` est calculé automatiquement (en minutes)
- ✅ Les chauffeurs à proximité reçoivent une notification

**Réponse (201)**:
```json
{
  "id": 28,
  "driver": null,
  "passenger": {
    "id": 48,
    "email": "john.doe@example.com"
  },
  "status": "pending",
  "pickupAddress": "Opéra Garnier, Paris",
  "pickupLatitude": 48.872,
  "pickupLongitude": 2.3318,
  "dropoffAddress": "Gare de Lyon, Paris",
  "dropoffLatitude": 48.8449,
  "dropoffLongitude": 2.3738,
  "estimatedDistance": 4.5,
  "estimatedPrice": 15.2,
  "estimatedDuration": 14,
  "vehicleType": "standard",
  "createdAt": "2025-12-16T11:20:15+00:00"
}
```

---

### POST /api/rides/{id}/accept

Accepte une course (chauffeur).

**Sécurité**: Utilisateur authentifié uniquement.

**Body (JSON)**: Vide `{}`

**Validations côté serveur**:
- ✅ L'utilisateur doit être un chauffeur (`userType=driver`)
- ✅ Le chauffeur doit avoir un profil Driver créé
- ✅ Le chauffeur doit être vérifié (`isVerified=true`)
- ✅ Le chauffeur doit être disponible (`isAvailable=true`)
- ✅ La course doit avoir le statut `pending`
- ✅ Le type de véhicule du chauffeur doit correspondre EXACTEMENT au type demandé

**Effets de bord automatiques**:
- ✅ Le chauffeur est assigné à la course
- ✅ Le statut passe à `accepted`
- ✅ Le champ `acceptedAt` est défini automatiquement
- ✅ Le chauffeur devient automatiquement **non disponible** (`isAvailable=false`)
- ✅ Le passager reçoit une notification

**Réponse (200)**:
```json
{
  "id": 28,
  "status": "accepted",
  "driver": {
    "id": 1,
    "email": "marie.martin@driver.com",
    "firstName": "Marie",
    "lastName": "Martin"
  },
  "acceptedAt": "2025-12-16T11:25:00+00:00"
}
```

**Erreurs**:
- `400`: Course déjà acceptée (`status != 'pending'`)
- `400`: Chauffeur non disponible (`isAvailable=false`)
- `400`: Type de véhicule incompatible (ex: course demande `premium`, chauffeur a `standard`)
- `403`: Pas un chauffeur (`userType != 'driver'`)
- `403`: Chauffeur non vérifié (`isVerified=false`)
- `404`: Profil Driver non trouvé

---

### PATCH /api/rides/{id}/status

Met à jour le statut d'une course (chauffeur uniquement).

**Sécurité**: Le chauffeur assigné à la course uniquement.

**Body (JSON)**:
```json
{
  "status": "in_progress"
}
```

**Statuts possibles**: `accepted`, `in_progress`, `completed`

**Effets de bord selon le statut**:

#### Statut `in_progress`:
- ✅ Le champ `startedAt` est défini automatiquement
- ✅ Le passager reçoit une notification

#### Statut `completed`:
- ✅ Le champ `completedAt` est défini automatiquement
- ✅ Le champ `finalPrice` est défini automatiquement (= `estimatedPrice`)
- ✅ Le chauffeur redevient **disponible** (`isAvailable=true`)
- ✅ Le `totalRides` du passager est incrémenté de 1
- ✅ Le `totalRides` du chauffeur est incrémenté de 1
- ✅ Le passager reçoit une notification

**Réponse (200)**: Retourne la course mise à jour.

**Erreurs**:
- `403`: Seul le chauffeur assigné peut changer le statut

---

### POST /api/rides/{id}/cancel

Annule une course (passager ou chauffeur).

**Sécurité**: Le passager ou le chauffeur de la course uniquement.

**Body (JSON)**: Vide `{}` (le champ `reason` n'est pas utilisé actuellement)

**Validations côté serveur**:
- ✅ Seules les courses avec statut `pending` ou `accepted` peuvent être annulées
- ✅ Seul le passager ou le chauffeur assigné peut annuler

**Effets de bord automatiques**:
- ✅ Le statut passe à `cancelled`
- ✅ Si un chauffeur était assigné, il redevient **disponible** (`isAvailable=true`)
- ✅ L'autre partie (passager ou chauffeur) reçoit une notification

**Réponse (200)**:
```json
{
  "id": 28,
  "status": "cancelled",
  "passenger": {...},
  "driver": {...}
}
```

**Erreurs**:
- `400`: Impossible d'annuler une course avec statut `in_progress`, `completed` ou `cancelled`
- `403`: Seul le passager ou le chauffeur assigné peut annuler

---

### PATCH /api/rides/{id}

Modifie une course.

**Sécurité**: Le chauffeur ou le passager de la course uniquement.

---

### DELETE /api/rides/{id}

Supprime une course.

**Sécurité**: Administrateur uniquement.

---

## Évaluations (Ratings)

### GET /api/ratings

Liste toutes les évaluations (paginée).

**Réponse (200)**:
```json
{
  "hydra:member": [
    {
      "id": 1,
      "ride": {
        "id": 25
      },
      "rater": {
        "id": 48,
        "email": "john.doe@example.com",
        "firstName": "John",
        "lastName": "Doe"
      },
      "rated": {
        "id": 1,
        "email": "marie.martin@driver.com",
        "firstName": "Marie",
        "lastName": "Martin"
      },
      "score": 5.0,
      "comment": "Excellent chauffeur, très professionnel!"
    }
  ],
  "hydra:totalItems": 1
}
```

---

### GET /api/ratings/{id}

Récupère une évaluation spécifique.

---

### POST /api/ratings

Crée une nouvelle évaluation.

**Sécurité**: Utilisateur authentifié uniquement.

**Body (JSON)**:
```json
{
  "ride": "/api/rides/25",
  "rater": "/api/users/48",
  "rated": "/api/users/1",
  "score": 5.0,
  "comment": "Excellent chauffeur, très professionnel!"
}
```

**Champs requis**:
| Champ | Type | Contraintes |
|-------|------|-------------|
| `ride` | IRI | Référence vers une course |
| `rater` | IRI | Utilisateur qui note |
| `rated` | IRI | Utilisateur noté |
| `score` | float | Entre 1 et 5 |
| `comment` | string | Max 1000 caractères (optionnel) |

---

### PATCH /api/ratings/{id}

Modifie une évaluation.

**Sécurité**: L'auteur de l'évaluation uniquement.

---

### DELETE /api/ratings/{id}

Supprime une évaluation.

**Sécurité**: L'auteur de l'évaluation uniquement.

---

## Codes d'erreur

| Code | Description |
|------|-------------|
| `200` | Succès |
| `201` | Ressource créée |
| `400` | Requête invalide |
| `401` | Non authentifié |
| `403` | Accès refusé |
| `404` | Ressource non trouvée |
| `422` | Erreur de validation |
| `500` | Erreur serveur |

---

## Types et énumérations

### UserType

- `passenger` : Passager
- `driver` : Chauffeur

### VehicleType

- `standard` : Véhicule standard
- `comfort` : Véhicule confort
- `premium` : Véhicule premium
- `xl` : Véhicule XL (grande capacité)

### RideStatus

- `pending` : En attente d'un chauffeur
- `accepted` : Acceptée par un chauffeur
- `in_progress` : En cours
- `completed` : Terminée
- `cancelled` : Annulée

---

## Validations des entités

### User (Utilisateur)

| Champ | Contraintes | Message d'erreur |
|-------|-------------|------------------|
| `email` | Email valide, unique | "Un compte avec cet email existe déjà." |
| `password` | Non vide | "{{ label }} is empty, please enter a value." |
| `firstName` | 2-50 caractères | "Your name must be at least 2 characters long" |
| `lastName` | Non vide | - |
| `phone` | Non vide | - |
| `userType` | `passenger` ou `driver` | - |

### Driver (Chauffeur)

| Champ | Contraintes | Message d'erreur |
|-------|-------------|------------------|
| `vehicleModel` | Non vide | - |
| `vehicleType` | `standard`, `comfort`, `premium`, `xl` | - |
| `vehicleColor` | Non vide | - |
| `licenceNumber` | Non vide | - |
| `currentLatitude` | Requis (float) | - |
| `currentLongitude` | Requis (float) | - |

### Ride (Course)

| Champ | Contraintes | Message d'erreur |
|-------|-------------|------------------|
| `status` | `pending`, `accepted`, `in_progress`, `completed`, `cancelled` | - |
| `pickupAddress` | Non vide | - |
| `pickupLatitude` | Non vide (float) | - |
| `pickupLongitude` | Non vide (float) | - |
| `dropoffAddress` | Non vide | - |
| `dropoffLatitude` | Non vide (float) | - |
| `dropoffLongitude` | Non vide (float) | - |
| `vehicleType` | `standard`, `comfort`, `premium`, `xl` | - |

### Rating (Évaluation)

| Champ | Contraintes | Message d'erreur |
|-------|-------------|------------------|
| `ride` | IRI valide, non vide | - |
| `rater` | IRI valide, non vide | - |
| `rated` | IRI valide, non vide | - |
| `score` | Entre 1 et 5 | - |
| `comment` | Max 1000 caractères (optionnel) | - |

---

## Notes importantes

### Pagination API Platform

Tous les endpoints GET de collection retournent une réponse paginée avec:
- `hydra:member`: Tableau des résultats
- `hydra:totalItems`: Nombre total d'éléments
- `hydra:view`: Liens de navigation (first, last, next, previous)

**Exemple**:
```http
GET /api/rides?page=2
```

### Filtres

Les filtres sont appliqués via query parameters. Consultez chaque endpoint pour les filtres disponibles.

### Sécurité

- Les tokens JWT expirent après 1 heure
- Utilisez HTTPS en production
- Les mots de passe sont hashés avec bcrypt

### Calculs et actions automatiques

#### Création d'une course (`POST /api/rides`)
- `estimatedDistance`: Distance en km entre pickup et dropoff
- `estimatedPrice`: Prix calculé selon la distance et le type de véhicule
- `estimatedDuration`: Durée estimée en minutes
- `status`: Défini à `pending`
- `createdAt`: Timestamp actuel
- Notification envoyée aux chauffeurs à proximité

#### Acceptation d'une course (`POST /api/rides/{id}/accept`)
- `driver`: Assigné au chauffeur qui accepte
- `status`: Changé à `accepted`
- `acceptedAt`: Timestamp actuel
- `driver.isAvailable`: Mis à `false` automatiquement
- Notification envoyée au passager

#### Changement de statut en "in_progress" (`PATCH /api/rides/{id}/status`)
- `startedAt`: Timestamp actuel
- Notification envoyée au passager

#### Changement de statut en "completed" (`PATCH /api/rides/{id}/status`)
- `completedAt`: Timestamp actuel
- `finalPrice`: Copié depuis `estimatedPrice`
- `driver.isAvailable`: Remis à `true`
- `passenger.totalRides`: Incrémenté de 1
- `driver.totalRides`: Incrémenté de 1
- Notification envoyée au passager

#### Annulation d'une course (`POST /api/rides/{id}/cancel`)
- `status`: Changé à `cancelled`
- `driver.isAvailable`: Remis à `true` si un chauffeur était assigné
- Notification envoyée à l'autre partie

#### Mise à jour de position (`PATCH /api/drivers/location`)
- Notification en temps réel envoyée via Mercure

---

**Documentation générée le 16 décembre 2025**
