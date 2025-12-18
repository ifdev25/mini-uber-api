# Guide d'intégration - Gestion des erreurs de validation API

## Vue d'ensemble

L'API Mini Uber utilise **Symfony Validator** pour valider toutes les données entrantes. Les erreurs de validation sont retournées dans un format JSON standardisé avec le code HTTP **422 (Unprocessable Entity)**.

---

## Endpoints et formats de réponse

L'API propose deux types d'endpoints avec des formats de validation différents :

### 1. Endpoint `/api/register` (AuthController)
Format personnalisé utilisé pour l'inscription via le controller manuel.

### 2. Endpoints API Platform `/api/users`, `/api/rides`, etc.
Format Hydra/JSON-LD standard d'API Platform.

---

## Format de réponse - Endpoint `/api/register`

### ✅ Succès (HTTP 201)

```json
{
  "message": "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.",
  "user": {
    "id": 21,
    "email": "success@test.com",
    "firstName": "John",
    "lastName": "Doe",
    "userType": "passenger",
    "isVerified": false
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### ❌ Erreur de validation (HTTP 422)

```json
{
  "error": true,
  "message": "Erreur de validation",
  "violations": {
    "firstName": "Your name must be at least 2 characters long",
    "lastName": "This value should not be blank."
  }
}
```

**Format clé** : `violations` est un **objet** où les **clés** sont les noms de champs et les **valeurs** sont les messages d'erreur.

---

## Exemples réels testés

### 1️⃣ Email invalide

**Request:**
```bash
POST /api/register
Content-Type: application/json

{
  "email": "invalid-email",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

**Response (422):**
```json
{
  "error": true,
  "message": "Erreur de validation",
  "violations": {
    "email": "This value is not a valid email address."
  }
}
```

---

### 2️⃣ Prénom trop court (moins de 2 caractères)

**Request:**
```json
{
  "email": "test@test.com",
  "password": "password123",
  "firstName": "i",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

**Response (422):**
```json
{
  "error": true,
  "message": "Erreur de validation",
  "violations": {
    "firstName": "Your name must be at least 2 characters long"
  }
}
```

---

### 3️⃣ Plusieurs erreurs simultanées

**Request:**
```json
{
  "email": "multi@test.com",
  "password": "pass",
  "firstName": "i",
  "lastName": "",
  "phone": "invalid",
  "userType": "passenger"
}
```

**Response (422):**
```json
{
  "error": true,
  "message": "Erreur de validation",
  "violations": {
    "firstName": "Your name must be at least 2 characters long",
    "lastName": "This value should not be blank."
  }
}
```

---

### 4️⃣ Tous les champs vides

**Request:**
```json
{
  "email": "",
  "password": "password123",
  "firstName": "",
  "lastName": "",
  "phone": "",
  "userType": "passenger"
}
```

**Response (422):**
```json
{
  "error": true,
  "message": "Erreur de validation",
  "violations": {
    "email": "This value should not be blank.",
    "firstName": "Your name must be at least 2 characters long",
    "lastName": "This value should not be blank.",
    "phone": "This value should not be blank."
  }
}
```

---

### 5️⃣ Email déjà existant

**Request:**
```json
{
  "email": "test@test.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+33612345678",
  "userType": "passenger"
}
```

**Response (422):**
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

## Règles de validation

### Entity User

| Champ | Contraintes | Messages possibles |
|-------|------------|-------------------|
| `email` | Required, Email format, Unique | - "This value should not be blank."<br>- "This value is not a valid email address."<br>- "Un compte avec cet email existe déjà." |
| `firstName` | Required, Min: 2 chars, Max: 50 chars | - "This value should not be blank."<br>- "Your name must be at least 2 characters long"<br>- "Your name cannot be longer than 50 characters" |
| `lastName` | Required | - "This value should not be blank." |
| `phone` | Required | - "This value should not be blank." |
| `password` | Required | - "This value should not be blank." |
| `userType` | Must be 'passenger' or 'driver' | - "The value you selected is not a valid choice" |

---

## Implémentation Frontend

### 1. React (TypeScript) - Recommandé

```tsx
import { useState, FormEvent, ChangeEvent } from 'react';

interface ValidationErrors {
  [key: string]: string;
}

interface RegisterFormData {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  phone: string;
  userType: 'passenger' | 'driver';
}

interface SuccessResponse {
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

interface ErrorResponse {
  error: boolean;
  message: string;
  violations: ValidationErrors;
}

function RegisterForm() {
  const [formData, setFormData] = useState<RegisterFormData>({
    email: '',
    password: '',
    firstName: '',
    lastName: '',
    phone: '',
    userType: 'passenger',
  });

  const [errors, setErrors] = useState<ValidationErrors>({});
  const [isLoading, setIsLoading] = useState(false);

  const handleChange = (e: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));

    // Effacer l'erreur du champ quand l'utilisateur tape
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors({});

    try {
      const response = await fetch('http://localhost:8080/api/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (!response.ok) {
        // Extraire les violations
        const errorData = data as ErrorResponse;
        setErrors(errorData.violations || {});
        return;
      }

      // Succès
      const successData = data as SuccessResponse;
      localStorage.setItem('token', successData.token);
      console.log('Inscription réussie:', successData);
      // Redirection...

    } catch (error) {
      console.error('Erreur réseau:', error);
      setErrors({ general: 'Erreur de connexion au serveur' });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {errors.general && (
        <div className="alert alert-danger">{errors.general}</div>
      )}

      {/* Email */}
      <div className="form-group">
        <label htmlFor="email">Email *</label>
        <input
          id="email"
          type="email"
          name="email"
          value={formData.email}
          onChange={handleChange}
          className={errors.email ? 'form-control is-invalid' : 'form-control'}
        />
        {errors.email && (
          <div className="invalid-feedback">{errors.email}</div>
        )}
      </div>

      {/* First Name */}
      <div className="form-group">
        <label htmlFor="firstName">Prénom *</label>
        <input
          id="firstName"
          type="text"
          name="firstName"
          value={formData.firstName}
          onChange={handleChange}
          className={errors.firstName ? 'form-control is-invalid' : 'form-control'}
        />
        {errors.firstName && (
          <div className="invalid-feedback">{errors.firstName}</div>
        )}
      </div>

      {/* Last Name */}
      <div className="form-group">
        <label htmlFor="lastName">Nom *</label>
        <input
          id="lastName"
          type="text"
          name="lastName"
          value={formData.lastName}
          onChange={handleChange}
          className={errors.lastName ? 'form-control is-invalid' : 'form-control'}
        />
        {errors.lastName && (
          <div className="invalid-feedback">{errors.lastName}</div>
        )}
      </div>

      {/* Phone */}
      <div className="form-group">
        <label htmlFor="phone">Téléphone *</label>
        <input
          id="phone"
          type="tel"
          name="phone"
          value={formData.phone}
          onChange={handleChange}
          className={errors.phone ? 'form-control is-invalid' : 'form-control'}
        />
        {errors.phone && (
          <div className="invalid-feedback">{errors.phone}</div>
        )}
      </div>

      {/* Password */}
      <div className="form-group">
        <label htmlFor="password">Mot de passe *</label>
        <input
          id="password"
          type="password"
          name="password"
          value={formData.password}
          onChange={handleChange}
          className={errors.password ? 'form-control is-invalid' : 'form-control'}
        />
        {errors.password && (
          <div className="invalid-feedback">{errors.password}</div>
        )}
      </div>

      {/* User Type */}
      <div className="form-group">
        <label htmlFor="userType">Type d'utilisateur *</label>
        <select
          id="userType"
          name="userType"
          value={formData.userType}
          onChange={handleChange}
          className="form-control"
        >
          <option value="passenger">Passager</option>
          <option value="driver">Chauffeur</option>
        </select>
      </div>

      <button type="submit" disabled={isLoading} className="btn btn-primary">
        {isLoading ? 'Inscription...' : "S'inscrire"}
      </button>
    </form>
  );
}

export default RegisterForm;
```

---

### 2. Vue.js 3 (Composition API)

```vue
<template>
  <form @submit.prevent="handleSubmit">
    <div v-if="errors.general" class="alert alert-danger">
      {{ errors.general }}
    </div>

    <!-- Email -->
    <div class="form-group">
      <label for="email">Email *</label>
      <input
        id="email"
        v-model="formData.email"
        type="email"
        :class="['form-control', { 'is-invalid': errors.email }]"
        @input="clearError('email')"
      />
      <div v-if="errors.email" class="invalid-feedback">
        {{ errors.email }}
      </div>
    </div>

    <!-- First Name -->
    <div class="form-group">
      <label for="firstName">Prénom *</label>
      <input
        id="firstName"
        v-model="formData.firstName"
        type="text"
        :class="['form-control', { 'is-invalid': errors.firstName }]"
        @input="clearError('firstName')"
      />
      <div v-if="errors.firstName" class="invalid-feedback">
        {{ errors.firstName }}
      </div>
    </div>

    <!-- Last Name -->
    <div class="form-group">
      <label for="lastName">Nom *</label>
      <input
        id="lastName"
        v-model="formData.lastName"
        type="text"
        :class="['form-control', { 'is-invalid': errors.lastName }]"
        @input="clearError('lastName')"
      />
      <div v-if="errors.lastName" class="invalid-feedback">
        {{ errors.lastName }}
      </div>
    </div>

    <!-- Phone -->
    <div class="form-group">
      <label for="phone">Téléphone *</label>
      <input
        id="phone"
        v-model="formData.phone"
        type="tel"
        :class="['form-control', { 'is-invalid': errors.phone }]"
        @input="clearError('phone')"
      />
      <div v-if="errors.phone" class="invalid-feedback">
        {{ errors.phone }}
      </div>
    </div>

    <!-- Password -->
    <div class="form-group">
      <label for="password">Mot de passe *</label>
      <input
        id="password"
        v-model="formData.password"
        type="password"
        :class="['form-control', { 'is-invalid': errors.password }]"
        @input="clearError('password')"
      />
      <div v-if="errors.password" class="invalid-feedback">
        {{ errors.password }}
      </div>
    </div>

    <!-- User Type -->
    <div class="form-group">
      <label for="userType">Type d'utilisateur *</label>
      <select
        id="userType"
        v-model="formData.userType"
        class="form-control"
      >
        <option value="passenger">Passager</option>
        <option value="driver">Chauffeur</option>
      </select>
    </div>

    <button type="submit" :disabled="isLoading" class="btn btn-primary">
      {{ isLoading ? 'Inscription...' : "S'inscrire" }}
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';

interface ValidationErrors {
  [key: string]: string;
}

const formData = ref({
  email: '',
  password: '',
  firstName: '',
  lastName: '',
  phone: '',
  userType: 'passenger' as 'passenger' | 'driver',
});

const errors = ref<ValidationErrors>({});
const isLoading = ref(false);

const clearError = (field: string) => {
  if (errors.value[field]) {
    delete errors.value[field];
  }
};

const handleSubmit = async () => {
  isLoading.value = true;
  errors.value = {};

  try {
    const response = await fetch('http://localhost:8080/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData.value),
    });

    const data = await response.json();

    if (!response.ok) {
      errors.value = data.violations || {};
      return;
    }

    // Succès
    localStorage.setItem('token', data.token);
    console.log('Inscription réussie:', data);
    // Redirection...

  } catch (error) {
    console.error('Erreur:', error);
    errors.value = { general: 'Erreur de connexion' };
  } finally {
    isLoading.value = false;
  }
};
</script>
```

---

### 3. JavaScript Vanilla

```javascript
document.getElementById('registerForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  // Récupérer les données du formulaire
  const formData = {
    email: document.getElementById('email').value,
    password: document.getElementById('password').value,
    firstName: document.getElementById('firstName').value,
    lastName: document.getElementById('lastName').value,
    phone: document.getElementById('phone').value,
    userType: document.getElementById('userType').value,
  };

  // Réinitialiser les erreurs
  document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
  document.querySelectorAll('.is-invalid').forEach(el => {
    el.classList.remove('is-invalid');
  });

  try {
    const response = await fetch('http://localhost:8080/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData),
    });

    const data = await response.json();

    if (!response.ok) {
      // Afficher les erreurs
      const violations = data.violations || {};

      Object.keys(violations).forEach(fieldName => {
        const input = document.getElementById(fieldName);
        if (input) {
          input.classList.add('is-invalid');

          const errorDiv = document.createElement('div');
          errorDiv.className = 'invalid-feedback';
          errorDiv.textContent = violations[fieldName];
          input.parentNode.appendChild(errorDiv);
        }
      });

      return;
    }

    // Succès
    localStorage.setItem('token', data.token);
    console.log('Inscription réussie:', data);
    // Redirection...

  } catch (error) {
    console.error('Erreur réseau:', error);
    alert('Erreur de connexion au serveur');
  }
});
```

---

## CSS recommandé

```css
/* Champs invalides */
.form-control.is-invalid {
  border-color: #dc3545;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem);
  padding-right: calc(1.5em + 0.75rem);
}

/* Messages d'erreur */
.invalid-feedback {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #dc3545;
}

/* Alert */
.alert {
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}

/* Bouton désactivé */
button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
```

---

## Points clés

1. **Code HTTP** : `422 Unprocessable Entity` pour les erreurs de validation
2. **Format** : `{ error: true, message: string, violations: { field: message } }`
3. **Violations** : Objet simple `{ fieldName: errorMessage }`, pas un tableau
4. **Plusieurs erreurs** : Possibilité d'avoir plusieurs champs en erreur simultanément
5. **Réinitialisation** : Toujours réinitialiser les erreurs avant une nouvelle soumission
6. **UX** : Effacer les erreurs quand l'utilisateur modifie le champ

---

## Support

Pour toute question concernant l'intégration ou les règles de validation, contactez l'équipe backend.
