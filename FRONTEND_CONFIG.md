# Configuration API pour le Frontend - Mini Uber

## 📡 Informations de connexion

### URL de base de l'API
```
http://localhost:8080
```

### URL Mercure (temps réel)
```
http://localhost:3000/.well-known/mercure
```

## 🔐 Authentification

L'API utilise JWT (JSON Web Tokens) pour l'authentification.

### Endpoints d'authentification

#### Inscription
```http
POST http://localhost:8080/api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "roles": ["ROLE_USER"]
}
```

#### Connexion
```http
POST http://localhost:8080/api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "def50200..."
}
```

### Utilisation du token

Pour toutes les requêtes authentifiées, ajouter le header :
```
Authorization: Bearer {token}
```

## 🛠️ Configuration Frontend

### Option 1 : Avec Axios (Recommandé)

#### Installation
```bash
npm install axios
```

#### Configuration
```javascript
// src/services/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8080',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Intercepteur pour ajouter automatiquement le token JWT
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

// Intercepteur pour gérer les erreurs 401 (token expiré)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expiré, rediriger vers login
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

#### Utilisation
```javascript
import api from './services/api';

// Login
const login = async (email, password) => {
  try {
    const response = await api.post('/api/login', { email, password });
    localStorage.setItem('token', response.data.token);
    return response.data;
  } catch (error) {
    console.error('Login failed:', error);
    throw error;
  }
};

// Register
const register = async (email, password, roles = ['ROLE_USER']) => {
  try {
    const response = await api.post('/api/register', { email, password, roles });
    return response.data;
  } catch (error) {
    console.error('Registration failed:', error);
    throw error;
  }
};

// Get users (requête authentifiée)
const getUsers = async () => {
  try {
    const response = await api.get('/api/users');
    return response.data;
  } catch (error) {
    console.error('Failed to fetch users:', error);
    throw error;
  }
};
```

### Option 2 : Avec Fetch natif

```javascript
// src/services/api.js
const API_BASE_URL = 'http://localhost:8080';

class ApiService {
  async request(endpoint, options = {}) {
    const token = localStorage.getItem('token');

    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(token && { Authorization: `Bearer ${token}` }),
        ...options.headers,
      },
    };

    try {
      const response = await fetch(`${API_BASE_URL}${endpoint}`, config);

      if (response.status === 401) {
        localStorage.removeItem('token');
        window.location.href = '/login';
        throw new Error('Unauthorized');
      }

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  async login(email, password) {
    const data = await this.request('/api/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    if (data.token) {
      localStorage.setItem('token', data.token);
    }

    return data;
  }

  async register(email, password, roles = ['ROLE_USER']) {
    return this.request('/api/register', {
      method: 'POST',
      body: JSON.stringify({ email, password, roles }),
    });
  }

  async getUsers() {
    return this.request('/api/users');
  }

  async getUser(id) {
    return this.request(`/api/users/${id}`);
  }
}

export default new ApiService();
```

## 📚 Endpoints disponibles

### Utilisateurs
```
GET    /api/users          - Liste des utilisateurs
GET    /api/users/{id}     - Détail d'un utilisateur
POST   /api/users          - Créer un utilisateur
PUT    /api/users/{id}     - Modifier un utilisateur
DELETE /api/users/{id}     - Supprimer un utilisateur
```

### Conducteurs
```
GET    /api/drivers        - Liste des conducteurs
GET    /api/drivers/{id}   - Détail d'un conducteur
POST   /api/drivers        - Créer un conducteur
PUT    /api/drivers/{id}   - Modifier un conducteur
DELETE /api/drivers/{id}   - Supprimer un conducteur
```

### Courses (Rides)
```
GET    /api/rides          - Liste des courses
GET    /api/rides/{id}     - Détail d'une course
POST   /api/rides          - Créer une course
PUT    /api/rides/{id}     - Modifier une course
DELETE /api/rides/{id}     - Supprimer une course
```

## 🎯 Exemples de requêtes

### Inscription et connexion

```javascript
// 1. Inscription
const newUser = {
  email: 'john.doe@example.com',
  password: 'SecurePass123!',
  roles: ['ROLE_USER']
};

const registerUser = await api.post('/api/register', newUser);

// 2. Connexion
const credentials = {
  email: 'john.doe@example.com',
  password: 'SecurePass123!'
};

const loginResponse = await api.post('/api/login', credentials);
localStorage.setItem('token', loginResponse.data.token);
```

### Créer une course

```javascript
const newRide = {
  pickupLocation: '123 Main St, Paris',
  dropoffLocation: '456 Oak Ave, Paris',
  pickupLatitude: 48.8566,
  pickupLongitude: 2.3522,
  dropoffLatitude: 48.8606,
  dropoffLongitude: 2.3376,
  estimatedPrice: 15.50,
  status: 'pending'
};

const ride = await api.post('/api/rides', newRide);
```

### Récupérer les courses d'un utilisateur

```javascript
const userRides = await api.get('/api/rides?user=/api/users/1');
```

### Mettre à jour le statut d'une course

```javascript
const updatedRide = await api.put('/api/rides/1', {
  status: 'completed'
});
```

## 🔧 Variables d'environnement Frontend

Créez un fichier `.env` dans votre projet frontend :

```env
# React (.env)
REACT_APP_API_URL=http://localhost:8080
REACT_APP_MERCURE_URL=http://localhost:3000/.well-known/mercure

# Vue (.env)
VITE_API_URL=http://localhost:8080
VITE_MERCURE_URL=http://localhost:3000/.well-known/mercure

# Next.js (.env.local)
NEXT_PUBLIC_API_URL=http://localhost:8080
NEXT_PUBLIC_MERCURE_URL=http://localhost:3000/.well-known/mercure
```

Puis utilisez-les dans votre code :

```javascript
// React
const API_URL = process.env.REACT_APP_API_URL;

// Vue/Vite
const API_URL = import.meta.env.VITE_API_URL;

// Next.js
const API_URL = process.env.NEXT_PUBLIC_API_URL;
```

## ⚠️ CORS - Configuration déjà faite

L'API accepte les requêtes depuis :
- `http://localhost` (tous les ports)
- `http://127.0.0.1` (tous les ports)

**Headers autorisés :**
- `Content-Type`
- `Authorization`

**Méthodes autorisées :**
- `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`

## 🐛 Troubleshooting

### Problème : "Failed to fetch" ou "Network Error"

#### Solution 1 : Vérifier que l'API est démarrée
```bash
# Vérifier les conteneurs Docker
docker compose ps

# Démarrer l'API si elle n'est pas lancée
docker compose up -d
```

#### Solution 2 : Tester l'API manuellement
```bash
# Test simple
curl http://localhost:8080/api

# Test du login
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

#### Solution 3 : Vérifier les logs Docker
```bash
# Logs de l'API
docker compose logs -f php

# Logs Nginx
docker compose logs -f nginx
```

### Problème : "401 Unauthorized"

**Causes possibles :**
1. Token expiré
2. Token invalide
3. Token manquant dans le header

**Solution :**
```javascript
// Vérifier que le token est bien présent
const token = localStorage.getItem('token');
console.log('Token:', token);

// Si pas de token, rediriger vers login
if (!token) {
  window.location.href = '/login';
}
```

### Problème : "CORS policy error"

**Vérifier dans la console du navigateur :**
```
Access to fetch at 'http://localhost:8080/api/users' from origin 'http://localhost:3000'
has been blocked by CORS policy
```

**Solution :** CORS est déjà configuré pour localhost. Si l'erreur persiste :

1. Vérifier que vous utilisez bien `http://localhost` (pas `http://127.0.0.1`)
2. Redémarrer les conteneurs Docker :
```bash
docker compose restart
```

### Problème : Format de réponse inattendu

L'API utilise **API Platform** qui retourne les données au format JSON-LD :

```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users/1",
  "@type": "User",
  "id": 1,
  "email": "user@example.com",
  "roles": ["ROLE_USER"]
}
```

**Pour obtenir un format JSON simple :**
```javascript
// Ajouter le header Accept
const response = await api.get('/api/users', {
  headers: {
    'Accept': 'application/json'
  }
});
```

## 📞 Contact

Si vous rencontrez des problèmes :
1. Vérifier les logs Docker : `docker compose logs -f`
2. Tester l'API avec curl ou Postman
3. Vérifier que tous les conteneurs sont démarrés : `docker compose ps`

## 🚀 Checklist avant de commencer

- [ ] Docker Desktop est installé et lancé
- [ ] L'API est démarrée : `docker compose up -d`
- [ ] L'API répond : `curl http://localhost:8080/api`
- [ ] Variables d'environnement configurées dans le frontend
- [ ] Axios ou Fetch configuré avec l'URL de base
- [ ] Gestion du token JWT implémentée
- [ ] Intercepteur pour ajouter le token automatiquement

---

**Dernière mise à jour :** 2025-12-03
