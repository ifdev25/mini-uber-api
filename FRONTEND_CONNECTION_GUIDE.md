# 🔗 Guide de Connexion Frontend ↔ Backend

Guide complet pour connecter votre application frontend à l'API Mini Uber.

---

## 📋 Table des matières

1. [Configuration Backend](#-configuration-backend)
2. [URLs et Ports](#-urls-et-ports)
3. [Authentification JWT](#-authentification-jwt)
4. [Configuration Frontend](#-configuration-frontend)
5. [Comptes de Test](#-comptes-de-test)
6. [Exemples de Code](#-exemples-de-code)
7. [Gestion des Erreurs](#-gestion-des-erreurs)
8. [Troubleshooting](#-troubleshooting)

---

## ✅ Configuration Backend

Le backend est configuré et prêt à accepter les requêtes depuis votre frontend.

### Services actifs

| Service | URL | Status |
|---------|-----|--------|
| **API (HTTP)** | http://localhost:8080 | ✅ Actif |
| **API (HTTPS)** | https://localhost:8443 | ✅ Actif |
| **Documentation** | http://localhost:8080/api | ✅ Actif |
| **PostgreSQL** | localhost:5432 | ✅ Actif |
| **Mercure Hub** | http://localhost:3000 | ✅ Actif |

### CORS Configuration

Le backend accepte les requêtes depuis :
- ✅ `http://localhost:*` (tous les ports)
- ✅ `http://127.0.0.1:*` (tous les ports)
- ✅ Headers autorisés : `Content-Type`, `Authorization`, `X-Requested-With`
- ✅ Méthodes autorisées : `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`
- ✅ Credentials autorisés : `true`

---

## 🌐 URLs et Ports

### URL de base

```typescript
// Development
const API_BASE_URL = "http://localhost:8080";

// Avec HTTPS (certificat auto-signé)
const API_BASE_URL_HTTPS = "https://localhost:8443";
```

### Endpoints principaux

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/login` | POST | Connexion utilisateur |
| `/api/register` | POST | Inscription utilisateur |
| `/api/me` | GET | Profil utilisateur connecté |
| `/api/users` | GET | Liste des utilisateurs |
| `/api/rides` | GET/POST | Courses |
| `/api/rides/{id}` | GET/PATCH | Détails d'une course |
| `/api/drivers` | GET | Liste des chauffeurs |
| `/api/drivers/location` | PATCH | Mise à jour position |

---

## 🔐 Authentification JWT

### 1. Connexion

**Endpoint :** `POST /api/login`

**Request :**
```json
{
  "email": "john.doe@email.com",
  "password": "password123"
}
```

**Response (200 OK) :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 2. Utilisation du Token

Une fois connecté, incluez le token dans toutes vos requêtes :

**Header à ajouter :**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### 3. Durée de validité

- **Token JWT** : 1 heure
- Après expiration : reconnexion nécessaire

---

## ⚙️ Configuration Frontend

### Option 1 : Axios (Recommandé)

#### Installation
```bash
npm install axios
```

#### Configuration
```typescript
// src/services/api.ts
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8080',
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Important pour CORS
});

// Intercepteur pour ajouter le token JWT
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs 401
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expiré ou invalide
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

#### Utilisation
```typescript
// Connexion
import api from './services/api';

async function login(email: string, password: string) {
  try {
    const response = await api.post('/api/login', { email, password });
    const { token } = response.data;

    // Stocker le token
    localStorage.setItem('token', token);

    return token;
  } catch (error) {
    console.error('Erreur de connexion:', error);
    throw error;
  }
}

// Récupérer le profil
async function getProfile() {
  try {
    const response = await api.get('/api/me');
    return response.data;
  } catch (error) {
    console.error('Erreur profil:', error);
    throw error;
  }
}
```

### Option 2 : Fetch (Natif)

#### Configuration
```typescript
// src/services/api.ts
const API_BASE_URL = 'http://localhost:8080';

async function apiFetch(endpoint: string, options: RequestInit = {}) {
  const token = localStorage.getItem('token');

  const config: RequestInit = {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    },
    credentials: 'include', // Important pour CORS
  };

  const response = await fetch(`${API_BASE_URL}${endpoint}`, config);

  if (response.status === 401) {
    localStorage.removeItem('token');
    window.location.href = '/login';
    throw new Error('Non autorisé');
  }

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  return response.json();
}

export default apiFetch;
```

#### Utilisation
```typescript
import apiFetch from './services/api';

// Connexion
async function login(email: string, password: string) {
  const data = await apiFetch('/api/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

  localStorage.setItem('token', data.token);
  return data.token;
}

// Récupérer le profil
async function getProfile() {
  return await apiFetch('/api/me');
}
```

---

## 👥 Comptes de Test

### Admin
```
Email    : admin@miniuber.com
Password : admin123
Rôles    : ROLE_ADMIN
```

### Passagers

**Passager vérifié :**
```
Email    : john.doe@email.com
Password : password123
Nom      : John Doe
Rating   : 4.8 ⭐
Courses  : 15 courses historiques
```

**Passager non vérifié :**
```
Email    : unverified@test.com
Password : password123
Nom      : Sarah Unverified
Status   : Email non vérifié (pour tester la vérification)
```

### Chauffeurs

**Chauffeur 1 - Premium (Disponible) :**
```
Email      : marie.martin@driver.com
Password   : driver123
Nom        : Marie Martin
Véhicule   : Tesla Model 3 (Blanc Nacré)
Type       : Premium
Rating     : 4.9 ⭐
Disponible : ✅ Oui
Position   : Paris (Louvre)
```

**Chauffeur 2 - Comfort (En course) :**
```
Email      : pierre.dubois@driver.com
Password   : driver123
Nom        : Pierre Dubois
Véhicule   : Peugeot 508 (Noir Métallisé)
Type       : Comfort
Rating     : 4.7 ⭐
Disponible : ❌ Non (en course)
Position   : Paris (Champs-Élysées)
```

**Chauffeur 3 - Algérie (Disponible) :**
```
Email      : karim.bensaid@driver.com
Password   : driver123
Nom        : Karim Bensaid
Véhicule   : Renault Symbol (Blanc)
Type       : Standard
Rating     : 4.85 ⭐
Disponible : ✅ Oui
Position   : Constantine, Algérie 🇩🇿
```

---

## 💻 Exemples de Code

### React + TypeScript

#### Service API complet
```typescript
// src/services/api.service.ts
import axios, { AxiosInstance } from 'axios';

class ApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: 'http://localhost:8080',
      headers: {
        'Content-Type': 'application/json',
      },
      withCredentials: true,
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request interceptor
    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          this.logout();
        }
        return Promise.reject(error);
      }
    );
  }

  // Auth
  async login(email: string, password: string) {
    const response = await this.api.post('/api/login', { email, password });
    const { token } = response.data;
    localStorage.setItem('token', token);
    return token;
  }

  async register(userData: {
    email: string;
    password: string;
    firstname: string;
    lastname: string;
    phone: string;
    usertype: 'passenger' | 'driver';
  }) {
    const response = await this.api.post('/api/register', userData);
    return response.data;
  }

  logout() {
    localStorage.removeItem('token');
    window.location.href = '/login';
  }

  // User
  async getProfile() {
    const response = await this.api.get('/api/me');
    return response.data;
  }

  // Rides
  async getRides(filters?: {
    status?: string;
    vehicleType?: string;
  }) {
    const response = await this.api.get('/api/rides', { params: filters });
    return response.data;
  }

  async createRide(rideData: {
    pickupAddress: string;
    pickUpLatitude: number;
    pickUpLongitude: number;
    dropoffAddress: string;
    dropoffLatitude: number;
    dropoffLongitude: number;
    vehiculeType: 'standard' | 'comfort' | 'premium';
  }) {
    const response = await this.api.post('/api/rides', rideData);
    return response.data;
  }

  async getRideEstimate(data: {
    pickupLat: number;
    pickupLng: number;
    dropoffLat: number;
    dropoffLng: number;
    vehicleType: 'standard' | 'comfort' | 'premium';
  }) {
    const response = await this.api.post('/api/ride-estimates', data);
    return response.data;
  }

  // Drivers
  async getAvailableDrivers(filters?: {
    isAvailable?: boolean;
    vehicleType?: string;
  }) {
    const response = await this.api.get('/api/drivers', { params: filters });
    return response.data;
  }

  async updateDriverLocation(lat: number, lng: number) {
    const response = await this.api.patch('/api/drivers/location', {
      lat,
      lng,
    });
    return response.data;
  }
}

export default new ApiService();
```

#### Hook personnalisé pour l'authentification
```typescript
// src/hooks/useAuth.ts
import { useState, useEffect } from 'react';
import apiService from '../services/api.service';

interface User {
  id: number;
  email: string;
  firstname: string;
  lastname: string;
  usertype: 'passenger' | 'driver';
  rating: number;
}

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    checkAuth();
  }, []);

  async function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token) {
      setLoading(false);
      return;
    }

    try {
      const profile = await apiService.getProfile();
      setUser(profile);
    } catch (err) {
      setError('Session expirée');
      localStorage.removeItem('token');
    } finally {
      setLoading(false);
    }
  }

  async function login(email: string, password: string) {
    try {
      setError(null);
      await apiService.login(email, password);
      await checkAuth();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur de connexion');
      throw err;
    }
  }

  function logout() {
    apiService.logout();
    setUser(null);
  }

  return {
    user,
    loading,
    error,
    login,
    logout,
    isAuthenticated: !!user,
  };
}
```

#### Composant de connexion
```typescript
// src/components/Login.tsx
import React, { useState } from 'react';
import { useAuth } from '../hooks/useAuth';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const { login, error, loading } = useAuth();

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    try {
      await login(email, password);
      // Redirection après connexion réussie
      window.location.href = '/dashboard';
    } catch (err) {
      console.error('Erreur de connexion');
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <h2>Connexion</h2>

      {error && <div className="error">{error}</div>}

      <div>
        <label>Email:</label>
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
      </div>

      <div>
        <label>Mot de passe:</label>
        <input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
      </div>

      <button type="submit" disabled={loading}>
        {loading ? 'Connexion...' : 'Se connecter'}
      </button>

      <div className="test-accounts">
        <p>Comptes de test:</p>
        <button
          type="button"
          onClick={() => {
            setEmail('john.doe@email.com');
            setPassword('password123');
          }}
        >
          Passager Test
        </button>
        <button
          type="button"
          onClick={() => {
            setEmail('marie.martin@driver.com');
            setPassword('driver123');
          }}
        >
          Chauffeur Test
        </button>
      </div>
    </form>
  );
}
```

### Next.js 14+ (App Router)

#### Configuration API
```typescript
// src/lib/api.ts
'use client';

const API_BASE_URL = 'http://localhost:8080';

export async function apiRequest(
  endpoint: string,
  options: RequestInit = {}
) {
  const token = localStorage.getItem('token');

  const config: RequestInit = {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    },
    credentials: 'include',
  };

  const response = await fetch(`${API_BASE_URL}${endpoint}`, config);

  if (response.status === 401) {
    localStorage.removeItem('token');
    window.location.href = '/login';
    throw new Error('Unauthorized');
  }

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'API Error');
  }

  return response.json();
}

export const api = {
  login: (email: string, password: string) =>
    apiRequest('/api/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    }),

  getProfile: () => apiRequest('/api/me'),

  getRides: () => apiRequest('/api/rides'),

  createRide: (data: any) =>
    apiRequest('/api/rides', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
};
```

### Vue 3 + Composition API

```typescript
// src/composables/useApi.ts
import { ref } from 'vue';
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8080',
  withCredentials: true,
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export function useApi() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function login(email: string, password: string) {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.post('/api/login', { email, password });
      localStorage.setItem('token', response.data.token);
      return response.data.token;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur de connexion';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function getProfile() {
    loading.value = true;
    try {
      const response = await api.get('/api/me');
      return response.data;
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    error,
    login,
    getProfile,
  };
}
```

---

## ⚠️ Gestion des Erreurs

### Codes d'erreur courants

| Code | Description | Action |
|------|-------------|--------|
| **400** | Bad Request - Données invalides | Vérifier le format des données |
| **401** | Unauthorized - Token invalide/expiré | Reconnecter l'utilisateur |
| **403** | Forbidden - Accès refusé | Vérifier les permissions |
| **404** | Not Found - Ressource introuvable | Vérifier l'URL |
| **422** | Unprocessable Entity - Validation échouée | Afficher les erreurs de validation |
| **500** | Internal Server Error | Réessayer ou contacter le support |

### Exemple de gestion d'erreurs

```typescript
async function handleApiCall() {
  try {
    const data = await apiService.getRides();
    return data;
  } catch (error: any) {
    if (error.response) {
      // Erreur de réponse du serveur
      switch (error.response.status) {
        case 400:
          alert('Données invalides');
          break;
        case 401:
          alert('Session expirée. Veuillez vous reconnecter.');
          apiService.logout();
          break;
        case 403:
          alert('Accès refusé');
          break;
        case 404:
          alert('Ressource introuvable');
          break;
        case 422:
          // Afficher les erreurs de validation
          const errors = error.response.data.errors;
          console.error('Erreurs de validation:', errors);
          break;
        case 500:
          alert('Erreur serveur. Veuillez réessayer plus tard.');
          break;
        default:
          alert('Une erreur est survenue');
      }
    } else if (error.request) {
      // Pas de réponse du serveur
      alert('Impossible de contacter le serveur. Vérifiez votre connexion.');
    } else {
      // Autre erreur
      alert('Erreur: ' + error.message);
    }
  }
}
```

---

## 🐛 Troubleshooting

### Problème : Erreur CORS

**Symptôme :**
```
Access to fetch at 'http://localhost:8080/api/login' from origin
'http://localhost:3000' has been blocked by CORS policy
```

**Solution :**
1. Vérifiez que le backend est bien démarré sur le port 8080
2. Vérifiez que vous utilisez `withCredentials: true` (Axios) ou `credentials: 'include'` (Fetch)
3. Redémarrez les services Docker :
   ```bash
   docker compose restart frankenphp
   ```

### Problème : Token JWT non envoyé

**Symptôme :**
```
401 Unauthorized - Access Denied
```

**Solution :**
1. Vérifiez que le token est bien stocké :
   ```javascript
   console.log('Token:', localStorage.getItem('token'));
   ```
2. Vérifiez le header Authorization :
   ```javascript
   console.log('Headers:', config.headers);
   ```
3. Le format doit être : `Bearer <token>`

### Problème : Token expiré

**Symptôme :**
```
401 Unauthorized après un certain temps
```

**Solution :**
Les tokens JWT expirent après 1 heure. Implémentez un système de refresh ou reconnectez l'utilisateur.

### Problème : Connexion refusée

**Symptôme :**
```
ERR_CONNECTION_REFUSED
```

**Solution :**
1. Vérifiez que le backend est démarré :
   ```bash
   docker compose ps
   ```
2. Vérifiez l'URL (doit être `localhost:8080` et non `localhost:8000`)
3. Testez avec curl :
   ```bash
   curl http://localhost:8080/api
   ```

---

## 📞 Support

Si vous rencontrez des problèmes :

1. Vérifiez les logs du backend :
   ```bash
   docker compose logs -f frankenphp
   ```

2. Testez les endpoints avec curl :
   ```bash
   # Test de connexion
   curl -X POST http://localhost:8080/api/login \
     -H "Content-Type: application/json" \
     -d '{"email":"john.doe@email.com","password":"password123"}'
   ```

3. Vérifiez la configuration CORS :
   ```bash
   curl -H "Origin: http://localhost:3000" \
     -H "Access-Control-Request-Method: POST" \
     -X OPTIONS http://localhost:8080/api/login -i
   ```

---

## 📚 Documentation complémentaire

- **API Endpoints détaillés** : [API_ENDPOINTS.md](API_ENDPOINTS.md)
- **Documentation JSON-LD** : [FRONTEND_API_DOCUMENTATION.md](FRONTEND_API_DOCUMENTATION.md)
- **Swagger UI** : http://localhost:8080/api

---

**Mise à jour :** 2025-12-12
**Backend :** Symfony 7.3 + FrankenPHP
**Version :** 1.0.0
