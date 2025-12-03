# API d'inscription - Spécifications pour le Frontend

## ⚠️ Points critiques pour le Frontend

### Gestion du doublon d'email (IMPORTANT)

Lorsqu'un utilisateur s'inscrit avec un email déjà existant :

```
📧 Email déjà utilisé
    ↓
🔴 HTTP 409 Conflict
    ↓
📦 { "error": "Un compte avec cet email existe déjà." }
    ↓
💡 Frontend affiche l'erreur + lien vers connexion
```

**Code à implémenter obligatoirement :**

```typescript
if (response.status === 409) {
  const data = await response.json();
  showError(data.error); // "Un compte avec cet email existe déjà."
  showLoginLink(); // Lien vers /login
  return;
}
```

---

## Endpoints disponibles

### Endpoint recommandé (JSON simple)

```
POST /api/register
```

**Base URL:** `http://localhost:8000`

**Format de réponse:** JSON simple (pas de JSON-LD)

### Endpoint alternatif (JSON-LD)

```
POST /api/users
```

**Format de réponse:** JSON-LD avec contexte Hydra

> **Note:** `/api/register` est recommandé pour l'inscription car il retourne du JSON simple et envoie automatiquement l'email de vérification. `/api/users` est un endpoint API Platform qui retourne du JSON-LD.

---

## Champs requis

### Obligatoires

| Champ | Type | Contraintes | Description |
|-------|------|------------|-------------|
| `email` | string | Format email valide | Adresse email unique |
| `password` | string | Minimum 6 caractères | Mot de passe (sera hashé côté backend) |
| `firstName` | string | Non vide | Prénom de l'utilisateur |
| `lastName` | string | Non vide | Nom de famille |
| `phone` | string | Non vide | Numéro de téléphone (format: +33612345678) |

### Optionnels

| Champ | Type | Valeur par défaut | Valeurs acceptées |
|-------|------|------------------|------------------|
| `userType` | string | `"passenger"` | `"passenger"` \| `"driver"` |

---

## Exemple de requête

### Inscription passager

```json
POST http://localhost:8000/api/register
Content-Type: application/json

{
  "email": "john.doe@example.com",
  "password": "motdepasse123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

### Inscription chauffeur

```json
POST http://localhost:8000/api/register
Content-Type: application/json

{
  "email": "marie.martin@example.com",
  "password": "driver123",
  "firstName": "Marie",
  "lastName": "Martin",
  "phone": "+33687654321",
  "userType": "driver"
}
```

---

## Réponses API

### Tableau récapitulatif des codes HTTP

| Code HTTP | Statut | Signification | Action frontend |
|-----------|--------|---------------|-----------------|
| **201** | Created | Inscription réussie | Stocker le token JWT et rediriger |
| **400** | Bad Request | Données invalides (validation) | Afficher les erreurs de validation |
| **409** | Conflict | Email déjà utilisé (doublon) | Afficher l'erreur + lien vers connexion |
| **500** | Server Error | Erreur serveur | Afficher "Erreur serveur, réessayez" |

---

### Succès avec `/api/register` (201 Created - JSON simple)

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
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Important:** Le `token` JWT est retourné immédiatement et permet de s'authentifier avant même la vérification de l'email.

---

### Succès avec `/api/users` (201 Created - JSON-LD)

```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users/1",
  "@type": "User",
  "id": 1,
  "email": "john.doe@example.com",
  "roles": [],
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger",
  "rating": null,
  "totalRides": null,
  "profilePicture": null,
  "createdAt": "2025-12-02T10:30:00+00:00",
  "isVerified": false,
  "driver": null,
  "ridesAsDriver": [],
  "ridesAsPassenger": []
}
```

**Métadonnées JSON-LD:**
- `@context`: URL vers le contexte JSON-LD décrivant la structure
- `@id`: IRI unique de la ressource (utilisable pour référencer l'utilisateur)
- `@type`: Type de la ressource (ici "User")

**Note:** Avec `/api/users`, vous devez hasher le mot de passe vous-même ou utiliser un State Processor. `/api/register` le fait automatiquement.

### Erreurs possibles

#### 409 Conflict - Email déjà utilisé (DOUBLON)

**Statut HTTP:** `409 Conflict`

**Quand ?** Lorsqu'un utilisateur tente de s'inscrire avec un email déjà présent en base de données.

**Format de la réponse :**

```json
{
  "error": "Un compte avec cet email existe déjà."
}
```

**Headers de réponse :**
- `Status: 409 Conflict`
- `Content-Type: application/json`

**Ce que le frontend doit faire :**
1. Détecter le code HTTP `409`
2. Afficher le message d'erreur à l'utilisateur
3. Proposer d'aller sur la page de connexion
4. Ou proposer de réinitialiser le mot de passe si oublié

**Exemple de gestion côté frontend :**

```typescript
try {
  const response = await fetch('/api/register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
  });

  if (response.status === 409) {
    const data = await response.json();
    // Afficher : "Cette adresse email est déjà utilisée."
    showError(data.error);
    // Proposer : "Vous avez déjà un compte ? Se connecter"
    showLoginLink();
    return;
  }

  if (!response.ok) {
    throw new Error('Erreur lors de l\'inscription');
  }

  const result = await response.json();
  // Succès
} catch (error) {
  console.error(error);
}
```

#### 400 Bad Request - Validation échouée

```json
{
  "error": "Données invalides",
  "violations": [
    {
      "propertyPath": "email",
      "message": "Cette valeur n'est pas une adresse email valide."
    },
    {
      "propertyPath": "password",
      "message": "Cette chaîne est trop courte. Elle doit avoir au moins 6 caractères."
    }
  ]
}
```

---

## Validation côté Frontend

Avant d'envoyer la requête, assurez-vous de valider :

### Email
- ✅ Format email valide (regex: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`)
- ✅ Champ non vide
- ⚠️ **Contrainte d'unicité** : L'email doit être unique. En cas de doublon, l'API retourne une erreur 409

### Password
- ✅ Minimum 6 caractères
- ✅ Champ non vide
- 💡 Recommandé: au moins 8 caractères avec lettres et chiffres

### FirstName & LastName
- ✅ Champ non vide
- ✅ Minimum 2 caractères recommandé

### Phone
- ✅ Champ non vide
- 💡 Format recommandé: format international (+33...)
- 💡 Regex suggérée: `/^\+?[1-9]\d{1,14}$/`

### UserType
- ✅ Valeur: `"passenger"` ou `"driver"` uniquement
- ✅ Par défaut: `"passenger"` si non spécifié

---

## Flux d'inscription complet

### 1. Inscription (Passager)

```
POST /api/register
→ Reçoit: token JWT + user object
→ Stocker le token dans localStorage/cookie
→ Email de vérification envoyé automatiquement
→ Rediriger vers dashboard ou page de vérification email
```

### 2. Inscription (Chauffeur) - 2 étapes

#### Étape 1: Créer le compte utilisateur

```json
POST /api/register

{
  "email": "driver@example.com",
  "password": "driver123",
  "firstName": "Marie",
  "lastName": "Martin",
  "phone": "+33687654321",
  "userType": "driver"
}
```

**Réponse:** Token JWT + userId

#### Étape 2: Créer le profil chauffeur

**Endpoint:** `POST /api/drivers` (API Platform - retourne JSON-LD)

```json
POST /api/drivers
Authorization: Bearer {token_reçu_étape_1}
Content-Type: application/json

{
  "user": "/api/users/{userId}",
  "vehicleModel": "Tesla Model 3",
  "vehicleType": "premium",
  "vehicleColor": "Black",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "licenceNumber": "ABC123456"
}
```

**Réponse (201 Created - JSON-LD):**

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
    "email": "driver@example.com",
    "firstName": "Marie",
    "lastName": "Martin",
    "phone": "+33687654321",
    "userType": "driver",
    "rating": null
  },
  "vehicleModel": "Tesla Model 3",
  "vehicleType": "premium",
  "vehicleColor": "Black",
  "currentLatitude": 48.8566,
  "currentLongitude": 2.3522,
  "licenceNumber": "ABC123456",
  "verifiedAt": null,
  "isVerified": false,
  "isAvailable": false
}
```

**Champs du profil Driver:**

| Champ | Type | Contrainte | Valeurs acceptées |
|-------|------|-----------|------------------|
| `user` | string (IRI) | Obligatoire | `/api/users/{id}` |
| `vehicleModel` | string | Obligatoire | Ex: "Tesla Model 3" |
| `vehicleType` | string | Obligatoire | `standard` \| `comfort` \| `premium` \| `xl` |
| `vehicleColor` | string | Obligatoire | Ex: "Black", "White", "Blue" |
| `currentLatitude` | float | Obligatoire | Coordonnées GPS (ex: 48.8566) |
| `currentLongitude` | float | Obligatoire | Coordonnées GPS (ex: 2.3522) |
| `licenceNumber` | string | Obligatoire | Numéro de permis unique |

---

## Vérification d'email

### Endpoint de vérification

```
POST /api/verify-email
```

### Requête

```json
{
  "token": "le_token_reçu_par_email"
}
```

### Réponse succès

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

### Renvoyer l'email de vérification

```
POST /api/resend-verification
```

```json
{
  "email": "john.doe@example.com"
}
```

---

## Exemple d'implémentation (TypeScript/React)

### Hook d'inscription

```typescript
interface RegisterData {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  phone: string;
  userType: 'passenger' | 'driver';
}

interface RegisterResponse {
  message: string;
  user: {
    id: number;
    email: string;
    firstName: string;
    lastName: string;
    userType: string;
    isVerified: boolean;
  };
  token: string;
}

async function register(data: RegisterData): Promise<RegisterResponse> {
  const response = await fetch('http://localhost:8000/api/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json();

    // Gestion spécifique de l'erreur de doublon (409)
    if (response.status === 409) {
      throw new Error('Cette adresse email est déjà utilisée.');
    }

    throw new Error(error.error || 'Erreur lors de l\'inscription');
  }

  return response.json();
}

// Usage
try {
  const result = await register({
    email: 'john@example.com',
    password: 'password123',
    firstName: 'John',
    lastName: 'Doe',
    phone: '+33612345678',
    userType: 'passenger',
  });

  // Stocker le token
  localStorage.setItem('authToken', result.token);

  // Rediriger l'utilisateur
  router.push('/dashboard');
} catch (error) {
  console.error('Erreur:', error.message);
}
```

### Validation Zod (recommandé)

```typescript
import { z } from 'zod';

const registerSchema = z.object({
  email: z.string().email('Email invalide'),
  password: z.string().min(6, 'Le mot de passe doit contenir au moins 6 caractères'),
  firstName: z.string().min(2, 'Le prénom doit contenir au moins 2 caractères'),
  lastName: z.string().min(2, 'Le nom doit contenir au moins 2 caractères'),
  phone: z.string().regex(/^\+?[1-9]\d{1,14}$/, 'Numéro de téléphone invalide'),
  userType: z.enum(['passenger', 'driver']).default('passenger'),
});

type RegisterFormData = z.infer<typeof registerSchema>;
```

---

## Messages utilisateur recommandés

### Formulaire d'inscription

```
Champs requis (*):
- Email*: "Votre adresse email"
- Mot de passe*: "Minimum 6 caractères"
- Prénom*: "Votre prénom"
- Nom*: "Votre nom de famille"
- Téléphone*: "Format: +33612345678"
- Type de compte: [Radio] Passager / Chauffeur

[Bouton] S'inscrire
```

### Messages de succès/erreur

```typescript
const messages = {
  success: "Inscription réussie ! Un email de vérification vous a été envoyé.",
  emailExists: "Cette adresse email est déjà utilisée.", // Erreur 409
  invalidEmail: "Veuillez entrer une adresse email valide.",
  passwordTooShort: "Le mot de passe doit contenir au moins 6 caractères.",
  phoneInvalid: "Format de téléphone invalide.",
  serverError: "Une erreur est survenue. Veuillez réessayer.",
};

// Gestion des erreurs
async function handleRegistrationError(error: any) {
  if (error.status === 409) {
    return messages.emailExists;
  } else if (error.status === 400) {
    return messages.invalidEmail; // ou autre erreur de validation
  } else {
    return messages.serverError;
  }
}
```

---

## Format JSON-LD et Collections

### Collections (GET /api/users, GET /api/rides, etc.)

Lorsque vous récupérez une liste de ressources via API Platform, la réponse utilise le vocabulaire **Hydra** :

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
      "email": "john@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "userType": "passenger"
    },
    {
      "@id": "/api/users/2",
      "@type": "User",
      "id": 2,
      "email": "jane@example.com",
      "firstName": "Jane",
      "lastName": "Smith",
      "userType": "driver"
    }
  ],
  "hydra:totalItems": 2,
  "hydra:view": {
    "@id": "/api/users?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/users?page=1",
    "hydra:last": "/api/users?page=1",
    "hydra:next": "/api/users?page=2"
  }
}
```

**Champs Hydra importants:**
- `hydra:member` : Tableau contenant les ressources
- `hydra:totalItems` : Nombre total d'items (pour la pagination)
- `hydra:view` : Informations de pagination
- `hydra:first`, `hydra:last`, `hydra:next`, `hydra:previous` : Liens de navigation

### Gérer JSON-LD côté Frontend

#### Option 1 : Utiliser JSON-LD tel quel

```typescript
interface HydraCollection<T> {
  '@context': string;
  '@id': string;
  '@type': 'hydra:Collection';
  'hydra:member': T[];
  'hydra:totalItems': number;
  'hydra:view'?: {
    'hydra:first'?: string;
    'hydra:last'?: string;
    'hydra:next'?: string;
    'hydra:previous'?: string;
  };
}

// Usage
const response: HydraCollection<User> = await fetch('/api/users').then(r => r.json());
const users = response['hydra:member'];
const total = response['hydra:totalItems'];
```

#### Option 2 : Demander du JSON simple

Ajoutez le header `Accept: application/json` pour obtenir du JSON sans métadonnées :

```typescript
fetch('/api/users', {
  headers: {
    'Accept': 'application/json'
  }
})
```

**Note :** Cette option peut ne pas fonctionner selon la configuration API Platform. Préférez l'option 1 ou utilisez les endpoints custom.

---

## Notes importantes

1. **Token JWT retourné immédiatement** : L'utilisateur peut utiliser l'application avant de vérifier son email
2. **Email de vérification envoyé automatiquement** : Validité 24h (seulement avec `/api/register`)
3. **Compte chauffeur = 2 étapes** : Créer User puis créer Driver
4. **CORS configuré** : L'API accepte les requêtes depuis `localhost` en développement
5. **Format du token** : `Bearer {token}` dans le header `Authorization`
6. **JSON-LD activé** : Les endpoints API Platform retournent du JSON-LD avec Hydra
7. **Endpoint `/api/register` recommandé** : Retourne du JSON simple et envoie l'email automatiquement
8. **⚠️ Email unique requis** : Contrainte d'unicité en base de données. Les doublons retournent une erreur `409 Conflict`

---

## Endpoints connexes

| Endpoint | Méthode | Format | Description |
|----------|---------|--------|-------------|
| `/api/register` | POST | JSON simple | Inscription (recommandé) + email auto |
| `/api/login` | POST | JSON simple | Connexion (retourne un token) |
| `/api/me` | GET | JSON simple | Profil utilisateur connecté |
| `/api/verify-email` | POST | JSON simple | Vérifier l'email avec le token |
| `/api/resend-verification` | POST | JSON simple | Renvoyer l'email de vérification |
| `/api/users` | POST | JSON-LD | Créer un utilisateur (API Platform) |
| `/api/users` | GET | JSON-LD | Liste des utilisateurs |
| `/api/users/{id}` | GET | JSON-LD | Détails d'un utilisateur |
| `/api/drivers` | POST | JSON-LD | Créer le profil chauffeur |
| `/api/drivers` | GET | JSON-LD | Liste des chauffeurs |
| `/api/rides` | GET | JSON-LD | Liste des courses |

---

## Récapitulatif : Quel endpoint utiliser ?

### Pour l'inscription

| Besoin | Endpoint recommandé | Raison |
|--------|---------------------|---------|
| **Inscription simple** | `POST /api/register` | JSON simple + email auto + token JWT |
| **Inscription avec contrôle complet** | `POST /api/users` | JSON-LD + pas d'email auto |
| **Récupérer les utilisateurs** | `GET /api/users` | Collections Hydra avec pagination |

### Pour les chauffeurs

| Besoin | Endpoint | Note |
|--------|----------|------|
| **Créer profil chauffeur** | `POST /api/drivers` | Après création du User |
| **Lister chauffeurs disponibles** | `GET /api/drivers?isAvailable=true` | Filtre sur disponibilité |

---

## Exemples TypeScript complets

### Types TypeScript pour JSON-LD

```typescript
// types/api.ts

// Types de base
export interface JsonLdResource {
  '@context': string;
  '@id': string;
  '@type': string;
}

export interface HydraCollection<T> {
  '@context': string;
  '@id': string;
  '@type': 'hydra:Collection';
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

// User avec JSON-LD
export interface UserJsonLd extends JsonLdResource {
  '@type': 'User';
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  phone: string;
  userType: 'passenger' | 'driver';
  rating: number | null;
  totalRides: number | null;
  isVerified: boolean;
  createdAt: string;
}

// Driver avec JSON-LD
export interface DriverJsonLd extends JsonLdResource {
  '@type': 'Driver';
  id: number;
  user: UserJsonLd;
  vehicleModel: string;
  vehicleType: 'standard' | 'comfort' | 'premium' | 'xl';
  vehicleColor: string;
  currentLatitude: number;
  currentLongitude: number;
  licenceNumber: string;
  isVerified: boolean;
  isAvailable: boolean;
}

// Réponse de /api/register (JSON simple)
export interface RegisterResponse {
  message: string;
  user: {
    id: number;
    email: string;
    firstName: string;
    lastName: string;
    userType: string;
    isVerified: boolean;
  };
  token: string;
}
```

### Service API complet

```typescript
// services/api.ts

const API_BASE_URL = 'http://localhost:8000';

// Helper pour extraire les données d'une collection Hydra
export function extractHydraMembers<T>(collection: HydraCollection<T>): T[] {
  return collection['hydra:member'];
}

// Inscription (JSON simple)
export async function register(data: {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  phone: string;
  userType: 'passenger' | 'driver';
}): Promise<RegisterResponse> {
  const response = await fetch(`${API_BASE_URL}/api/register`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    throw new Error('Registration failed');
  }

  return response.json();
}

// Créer un utilisateur via API Platform (JSON-LD)
export async function createUser(data: {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  phone: string;
  userType: 'passenger' | 'driver';
}): Promise<UserJsonLd> {
  const response = await fetch(`${API_BASE_URL}/api/users`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    throw new Error('User creation failed');
  }

  return response.json();
}

// Récupérer la liste des utilisateurs (JSON-LD)
export async function getUsers(params?: {
  page?: number;
  itemsPerPage?: number;
  userType?: 'passenger' | 'driver';
}): Promise<UserJsonLd[]> {
  const searchParams = new URLSearchParams();
  if (params?.page) searchParams.set('page', params.page.toString());
  if (params?.itemsPerPage) searchParams.set('itemsPerPage', params.itemsPerPage.toString());
  if (params?.userType) searchParams.set('userType', params.userType);

  const url = `${API_BASE_URL}/api/users${searchParams.toString() ? '?' + searchParams : ''}`;

  const response = await fetch(url, {
    headers: {
      'Accept': 'application/ld+json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to fetch users');
  }

  const collection: HydraCollection<UserJsonLd> = await response.json();
  return extractHydraMembers(collection);
}

// Créer un profil chauffeur (JSON-LD)
export async function createDriver(
  token: string,
  userId: number,
  data: {
    vehicleModel: string;
    vehicleType: 'standard' | 'comfort' | 'premium' | 'xl';
    vehicleColor: string;
    currentLatitude: number;
    currentLongitude: number;
    licenceNumber: string;
  }
): Promise<DriverJsonLd> {
  const response = await fetch(`${API_BASE_URL}/api/drivers`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({
      user: `/api/users/${userId}`,
      ...data,
    }),
  });

  if (!response.ok) {
    throw new Error('Driver profile creation failed');
  }

  return response.json();
}

// Récupérer les chauffeurs disponibles
export async function getAvailableDrivers(): Promise<DriverJsonLd[]> {
  const response = await fetch(
    `${API_BASE_URL}/api/drivers?isAvailable=true&isVerified=true`,
    {
      headers: {
        'Accept': 'application/ld+json',
      },
    }
  );

  if (!response.ok) {
    throw new Error('Failed to fetch drivers');
  }

  const collection: HydraCollection<DriverJsonLd> = await response.json();
  return extractHydraMembers(collection);
}
```

### Exemple d'utilisation dans un composant React (avec gestion du doublon)

```typescript
// components/RegisterForm.tsx
import { useState } from 'react';
import { register, createDriver } from '@/services/api';

export function RegisterForm() {
  const [userType, setUserType] = useState<'passenger' | 'driver'>('passenger');
  const [error, setError] = useState<string | null>(null);
  const [showLoginLink, setShowLoginLink] = useState(false);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError(null);
    setShowLoginLink(false);

    const formData = new FormData(e.currentTarget);

    try {
      // Étape 1 : Inscription
      const response = await fetch('http://localhost:8000/api/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: formData.get('email') as string,
          password: formData.get('password') as string,
          firstName: formData.get('firstName') as string,
          lastName: formData.get('lastName') as string,
          phone: formData.get('phone') as string,
          userType: userType,
        }),
      });

      // ⚠️ GESTION SPÉCIFIQUE DU DOUBLON (409)
      if (response.status === 409) {
        const data = await response.json();
        setError(data.error); // "Un compte avec cet email existe déjà."
        setShowLoginLink(true); // Afficher le lien vers la connexion
        return;
      }

      if (!response.ok) {
        throw new Error('Erreur lors de l\'inscription');
      }

      const result = await response.json();
      console.log('Inscription réussie:', result);

      // Stocker le token
      localStorage.setItem('authToken', result.token);

      // Étape 2 (si chauffeur) : Créer le profil Driver
      if (userType === 'driver') {
        const driverProfile = await createDriver(
          result.token,
          result.user.id,
          {
            vehicleModel: formData.get('vehicleModel') as string,
            vehicleType: formData.get('vehicleType') as any,
            vehicleColor: formData.get('vehicleColor') as string,
            currentLatitude: parseFloat(formData.get('lat') as string),
            currentLongitude: parseFloat(formData.get('lng') as string),
            licenceNumber: formData.get('licenceNumber') as string,
          }
        );
        console.log('Profil chauffeur créé:', driverProfile);
      }

      // Redirection
      window.location.href = '/dashboard';

    } catch (error) {
      console.error('Erreur:', error);
      setError('Une erreur est survenue. Veuillez réessayer.');
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Affichage de l'erreur de doublon */}
      {error && (
        <div className="error-message">
          <p>{error}</p>
          {showLoginLink && (
            <a href="/login">Vous avez déjà un compte ? Se connecter</a>
          )}
        </div>
      )}

      {/* Vos champs de formulaire */}
      <input name="email" type="email" required />
      <input name="password" type="password" required minLength={6} />
      <input name="firstName" type="text" required />
      <input name="lastName" type="text" required />
      <input name="phone" type="tel" required />

      <button type="submit">S'inscrire</button>
    </form>
  );
}
```

**Points clés de la gestion du doublon :**
1. ✅ Détection du statut `409` avant de lever une exception
2. ✅ Extraction du message d'erreur : `data.error`
3. ✅ Affichage du message à l'utilisateur
4. ✅ Proposition d'un lien vers la page de connexion
5. ✅ Arrêt du processus d'inscription avec `return`

---

## Tests pratiques pour le Frontend

### Test 1 : Inscription réussie (201)

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nouveau@example.com",
    "password": "test123456",
    "firstName": "Nouveau",
    "lastName": "User",
    "phone": "+33612345678",
    "userType": "passenger"
  }'
```

**Réponse attendue :**
```json
{
  "message": "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.",
  "user": {
    "id": 15,
    "email": "nouveau@example.com",
    "firstName": "Nouveau",
    "lastName": "User",
    "userType": "passenger",
    "isVerified": false
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Test 2 : Email en doublon (409)

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@miniuber.com",
    "password": "test123456",
    "firstName": "Test",
    "lastName": "Doublon",
    "phone": "+33612345678"
  }'
```

**Réponse attendue :**
```json
{
  "error": "Un compte avec cet email existe déjà."
}
```

**Status :** `409 Conflict`

### Test 3 : Validation échouée (400)

```bash
# Email invalide
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "invalid-email",
    "password": "test",
    "firstName": "Test",
    "lastName": "User",
    "phone": "+33612345678"
  }'
```

**Réponse attendue :** Erreur de validation (format varie selon la validation Symfony)

---

## Contact Backend

Pour toute question ou problème:
- Email: ishake.fouhal@gmail.com
- Swagger UI: http://localhost:8000/api/docs
- API Platform: http://localhost:8000/api
- Contextes JSON-LD: http://localhost:8000/api/contexts/User
