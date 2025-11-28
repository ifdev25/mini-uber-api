# 📧 Documentation Frontend - Vérification d'Email

## 🎯 Résumé des changements

Le backend a implémenté un système de **vérification d'email** pour sécuriser les inscriptions. Voici ce que le frontend doit gérer.

---

## 📋 Ce qui a changé

### 1. Endpoint d'inscription modifié

**POST /api/register**

#### Nouvelle réponse :

```json
{
  "message": "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "userType": "passenger",
    "isVerified": false  // ⬅️ NOUVEAU CHAMP
  },
  "token": "eyJ0eXAiOiJKV1Qi..."
}
```

**Changements importants :**
- ✅ Nouveau champ `isVerified` dans la réponse (toujours `false` à l'inscription)
- ✅ Nouveau message indiquant qu'un email de vérification a été envoyé
- ✅ L'utilisateur reçoit quand même un JWT token pour se connecter

### 2. Endpoint /api/me modifié

Le champ `isVerified` est maintenant inclus dans toutes les réponses utilisateur :

```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "userType": "passenger",
  "isVerified": false,  // ⬅️ NOUVEAU CHAMP
  "rating": null,
  "totalRides": 0,
  ...
}
```

---

## 🆕 Nouveaux endpoints à implémenter

### 1. Vérifier l'email

**POST /api/verify-email**

**Body :**
```json
{
  "token": "abc123def456..."  // Token reçu par email
}
```

**Réponse succès (200) :**
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

**Réponses d'erreur :**

| Code | Message | Cause |
|------|---------|-------|
| 400 | `"Token manquant"` | Le token n'est pas fourni |
| 400 | `"Token invalide"` | Le token n'existe pas |
| 400 | `"Le token a expiré"` | Le token a plus de 24h |

### 2. Renvoyer l'email de vérification

**POST /api/resend-verification**

**Body :**
```json
{
  "email": "user@example.com"
}
```

**Réponse succès (200) :**
```json
{
  "message": "Email de vérification renvoyé"
}
```

**Réponses d'erreur :**

| Code | Message | Cause |
|------|---------|-------|
| 400 | `"Email manquant"` | Email non fourni |
| 404 | `"Utilisateur non trouvé"` | Email n'existe pas |
| 400 | `"Email déjà vérifié"` | L'email est déjà vérifié |

---

## 🔄 Flux utilisateur à implémenter

### Scénario 1 : Inscription classique

```
1. Utilisateur remplit le formulaire d'inscription
2. Frontend → POST /api/register
3. Backend répond avec isVerified: false
4. Frontend affiche un message :
   "✅ Inscription réussie !
   📧 Un email de vérification a été envoyé à votre adresse.
   Veuillez vérifier votre boîte mail."
5. Utilisateur est redirigé vers le dashboard (peut utiliser l'app)
6. (Optionnel) Afficher un bandeau "Email non vérifié" dans l'app
```

### Scénario 2 : Vérification de l'email

```
1. Utilisateur clique sur le lien dans l'email
   → http://localhost:3000/verify-email?token=abc123...
2. Frontend extrait le token de l'URL
3. Frontend → POST /api/verify-email avec le token
4. Backend répond avec succès
5. Frontend affiche "✅ Email vérifié avec succès !"
6. Frontend redirige vers /login ou /dashboard
```

### Scénario 3 : Renvoyer l'email

```
1. Utilisateur clique sur "Renvoyer l'email de vérification"
2. Frontend → POST /api/resend-verification avec l'email
3. Backend envoie un nouvel email
4. Frontend affiche "📧 Email de vérification renvoyé"
```

---

## 💻 Exemples de code Next.js

### 1. Page de vérification d'email

```typescript
// app/verify-email/page.tsx
'use client';

import { useSearchParams, useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';

export default function VerifyEmailPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const token = searchParams.get('token');

  const [status, setStatus] = useState<'verifying' | 'success' | 'error'>('verifying');
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!token) {
      setStatus('error');
      setMessage('Token manquant');
      return;
    }

    // Vérifier l'email
    fetch('http://localhost:8000/api/verify-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token })
    })
      .then(res => res.json())
      .then(data => {
        if (data.message) {
          setStatus('success');
          setMessage(data.message);

          // Rediriger après 3 secondes
          setTimeout(() => {
            router.push('/dashboard');
          }, 3000);
        } else {
          setStatus('error');
          setMessage(data.error || 'Une erreur est survenue');
        }
      })
      .catch(() => {
        setStatus('error');
        setMessage('Erreur de connexion au serveur');
      });
  }, [token, router]);

  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="max-w-md w-full p-8 bg-white rounded-lg shadow">
        {status === 'verifying' && (
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
            <p className="mt-4 text-gray-600">Vérification en cours...</p>
          </div>
        )}

        {status === 'success' && (
          <div className="text-center">
            <div className="text-green-500 text-5xl mb-4">✅</div>
            <h2 className="text-2xl font-bold mb-2">Email vérifié !</h2>
            <p className="text-gray-600">{message}</p>
            <p className="text-sm text-gray-500 mt-4">Redirection en cours...</p>
          </div>
        )}

        {status === 'error' && (
          <div className="text-center">
            <div className="text-red-500 text-5xl mb-4">❌</div>
            <h2 className="text-2xl font-bold mb-2">Erreur</h2>
            <p className="text-gray-600">{message}</p>
            <button
              onClick={() => router.push('/login')}
              className="mt-6 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            >
              Retour à la connexion
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
```

### 2. Composant de bannière "Email non vérifié"

```typescript
// components/EmailVerificationBanner.tsx
'use client';

import { useState } from 'react';

interface Props {
  userEmail: string;
  isVerified: boolean;
}

export default function EmailVerificationBanner({ userEmail, isVerified }: Props) {
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  if (isVerified) return null;

  const handleResend = async () => {
    setLoading(true);
    setMessage('');

    try {
      const res = await fetch('http://localhost:8000/api/resend-verification', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: userEmail })
      });

      const data = await res.json();

      if (res.ok) {
        setMessage('✅ Email de vérification renvoyé !');
      } else {
        setMessage('❌ ' + (data.error || 'Erreur'));
      }
    } catch (error) {
      setMessage('❌ Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
      <div className="flex items-center justify-between">
        <div className="flex items-center">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <p className="text-sm text-yellow-700">
              Votre email n'est pas encore vérifié. Vérifiez votre boîte mail.
            </p>
            {message && <p className="text-sm mt-1">{message}</p>}
          </div>
        </div>
        <button
          onClick={handleResend}
          disabled={loading}
          className="ml-4 px-3 py-1 text-sm bg-yellow-400 hover:bg-yellow-500 text-yellow-900 rounded disabled:opacity-50"
        >
          {loading ? 'Envoi...' : 'Renvoyer'}
        </button>
      </div>
    </div>
  );
}
```

### 3. Hook personnalisé pour la vérification

```typescript
// hooks/useEmailVerification.ts
import { useState } from 'react';

export function useEmailVerification() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const verifyEmail = async (token: string) => {
    setLoading(true);
    setError(null);

    try {
      const res = await fetch('http://localhost:8000/api/verify-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token })
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || 'Erreur de vérification');
      }

      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Erreur inconnue';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const resendVerification = async (email: string) => {
    setLoading(true);
    setError(null);

    try {
      const res = await fetch('http://localhost:8000/api/resend-verification', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || 'Erreur lors du renvoi');
      }

      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Erreur inconnue';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return {
    verifyEmail,
    resendVerification,
    loading,
    error
  };
}
```

---

## 🔍 Mode Développement (Important !)

### ⚠️ Les emails ne sont PAS envoyés réellement

En mode développement, le backend **logue les emails** au lieu de les envoyer. Pour tester :

1. **Côté Backend** : Après l'inscription, vérifier les logs :
   ```bash
   tail -f var/log/dev.log | grep "Email de vérification"
   ```

2. **Récupérer le lien de vérification** dans les logs :
   ```
   [2024-01-20] app.INFO: Email de vérification généré
   {
     "to": "user@example.com",
     "verification_url": "http://localhost:3000/verify-email?token=abc123...",
     "token": "abc123def456..."
   }
   ```

3. **Copier l'URL** et la tester dans le navigateur

### 🚀 En Production

Quand Symfony Mailer sera installé, les emails seront envoyés automatiquement. Aucun changement côté frontend requis.

---

## ✅ Checklist Frontend

- [ ] Créer la page `/verify-email`
- [ ] Afficher le champ `isVerified` dans le profil utilisateur
- [ ] Ajouter un composant de bannière "Email non vérifié"
- [ ] Implémenter le bouton "Renvoyer l'email"
- [ ] Modifier le message après inscription
- [ ] Tester le flux complet en mode dev (avec les logs backend)
- [ ] Gérer les erreurs (token invalide, expiré, etc.)

---

## 📞 Questions ?

Si quelque chose n'est pas clair ou si vous avez besoin d'ajustements côté backend, n'hésitez pas à demander !

**Endpoints de test :**
- Backend API : `http://localhost:8000/api`
- Documentation interactive : `http://localhost:8000/api/docs`

---

**Date de mise à jour :** 20 janvier 2025
**Version Backend :** Symfony 7.3 + API Platform 4.2
