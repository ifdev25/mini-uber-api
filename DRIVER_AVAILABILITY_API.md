# API - Mise à jour de la disponibilité chauffeur

## Endpoint : Mettre à jour la disponibilité d'un chauffeur

### Description
Permet à un chauffeur de mettre à jour son statut de disponibilité (disponible/indisponible pour accepter des courses).

---

## Détails de l'endpoint

**URL** : `/api/drivers/availability`
**Méthode** : `PATCH`
**Authentification** : Requise (JWT Token)
**Rôle requis** : Driver (chauffeur)

---

## Headers requis

```http
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

---

## Corps de la requête

```json
{
  "isAvailable": true
}
```

### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `isAvailable` | `boolean` | Oui | `true` pour disponible, `false` pour indisponible |

---

## Réponses

### Succès (200 OK)

```json
{
  "message": "Availability updated successfully",
  "driver": {
    "id": 6,
    "isAvailable": true
  }
}
```

### Erreurs possibles

#### 401 Unauthorized - Non authentifié
```json
{
  "error": "Not authenticated"
}
```

#### 403 Forbidden - Pas un chauffeur
```json
{
  "error": "Not a driver"
}
```

#### 404 Not Found - Profil chauffeur introuvable
```json
{
  "error": "Driver profile not found"
}
```

#### 400 Bad Request - Paramètre invalide
```json
{
  "error": "isAvailable field is required and must be a boolean"
}
```

---

## Exemples d'utilisation

### JavaScript (Fetch API)

#### Marquer comme disponible

```javascript
const setDriverAvailable = async (isAvailable) => {
  const token = localStorage.getItem('token'); // ou votre système de stockage de token

  try {
    const response = await fetch('http://localhost:8080/api/drivers/availability', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ isAvailable })
    });

    const data = await response.json();

    if (response.ok) {
      console.log('Disponibilité mise à jour:', data);
      return data;
    } else {
      console.error('Erreur:', data.error);
      throw new Error(data.error);
    }
  } catch (error) {
    console.error('Erreur réseau:', error);
    throw error;
  }
};

// Utilisation
setDriverAvailable(true);  // Marquer comme disponible
setDriverAvailable(false); // Marquer comme indisponible
```

### React Hook Exemple

```javascript
import { useState } from 'react';

const useDriverAvailability = () => {
  const [isAvailable, setIsAvailable] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const toggleAvailability = async (available) => {
    setLoading(true);
    setError(null);

    try {
      const token = localStorage.getItem('token');
      const response = await fetch('http://localhost:8080/api/drivers/availability', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ isAvailable: available })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Erreur lors de la mise à jour');
      }

      setIsAvailable(data.driver.isAvailable);
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { isAvailable, loading, error, toggleAvailability };
};

export default useDriverAvailability;
```

### Composant React Exemple

```javascript
import React from 'react';
import useDriverAvailability from './hooks/useDriverAvailability';

const DriverAvailabilityToggle = () => {
  const { isAvailable, loading, error, toggleAvailability } = useDriverAvailability();

  const handleToggle = async () => {
    try {
      await toggleAvailability(!isAvailable);
    } catch (err) {
      // Gérer l'erreur (afficher un toast, etc.)
      console.error('Erreur:', err);
    }
  };

  return (
    <div className="availability-toggle">
      <label className="switch">
        <input
          type="checkbox"
          checked={isAvailable}
          onChange={handleToggle}
          disabled={loading}
        />
        <span className="slider"></span>
      </label>
      <span className="status">
        {loading ? 'Mise à jour...' : isAvailable ? 'Disponible' : 'Indisponible'}
      </span>
      {error && <div className="error">{error}</div>}
    </div>
  );
};

export default DriverAvailabilityToggle;
```

### Axios Exemple

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8080/api',
  headers: {
    'Content-Type': 'application/json'
  }
});

// Intercepteur pour ajouter le token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Fonction pour mettre à jour la disponibilité
export const updateDriverAvailability = async (isAvailable) => {
  try {
    const response = await api.patch('/driver/availability', { isAvailable });
    return response.data;
  } catch (error) {
    throw error.response?.data || error;
  }
};

// Utilisation
updateDriverAvailability(true)
  .then(data => console.log('Succès:', data))
  .catch(error => console.error('Erreur:', error));
```

---

## Flux de travail recommandé

1. **Au login du chauffeur** : Récupérer le statut actuel via `/api/me`
2. **Toggle de disponibilité** : Utiliser cet endpoint pour mettre à jour
3. **Feedback visuel** : Afficher un indicateur de chargement pendant la requête
4. **Gestion d'erreur** : Afficher un message d'erreur si la requête échoue
5. **Mise à jour de l'état local** : Mettre à jour l'état local seulement après confirmation du serveur

---

## Compte de test

Pour tester l'endpoint, vous pouvez utiliser ce compte chauffeur :

**Email** : `karim.bensaid@driver.com`
**Mot de passe** : `driver123`

### Obtenir un token

```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"karim.bensaid@driver.com","password":"driver123"}'
```

### Tester l'endpoint

```bash
curl -X PATCH http://localhost:8080/api/drivers/availability \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{"isAvailable":true}'
```

---

## Notes importantes

- ✅ Seuls les utilisateurs avec `userType: "driver"` peuvent utiliser cet endpoint
- ✅ Le token JWT doit être valide et non expiré
- ✅ Les changements sont persistés immédiatement en base de données
- ✅ L'endpoint est protégé par CORS (configuré pour accepter toutes les origines en développement)

---

## Questions fréquentes

### Comment récupérer le statut actuel de disponibilité ?

Utilisez l'endpoint `/api/me` qui retourne les informations du chauffeur, incluant `isAvailable`.

```javascript
const response = await fetch('http://localhost:8080/api/me', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const data = await response.json();
console.log('Disponibilité actuelle:', data.driverProfile.isAvailable);
```

### Le changement est-il instantané ?

Oui, une fois la requête réussie, le changement est immédiatement visible dans la base de données et pour les autres services (matching de chauffeurs, etc.).

### Que se passe-t-il si je ne suis pas un chauffeur ?

Vous recevrez une erreur 403 avec le message "Not a driver".

---

## Support

Pour toute question ou problème, consultez la documentation complète de l'API ou contactez l'équipe backend.

**URL de l'API en développement** : `http://localhost:8080`
**Version** : 1.0
**Dernière mise à jour** : 2025-12-12
