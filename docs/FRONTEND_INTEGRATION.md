# Intégration Frontend - Notifications Mercure

## 📡 Notification de fin de course

Quand une course est terminée par le chauffeur, le backend envoie automatiquement une notification Mercure au passager.

### Structure de la notification

```json
{
  "type": "ride_completed",
  "ride": {
    "id": 123,
    "status": "completed",
    "finalPrice": 15.50,
    "completedAt": "2025-12-04 14:30:00"
  },
  "action": {
    "type": "redirect",
    "route": "/rides/history",
    "userType": "passenger"
  }
}
```

### Exemple d'implémentation côté Frontend

#### React / Next.js

```javascript
import { useEffect } from 'react';
import { useRouter } from 'next/router';

function useMercureNotifications(userId) {
  const router = useRouter();

  useEffect(() => {
    // Connexion au hub Mercure
    const url = new URL('http://localhost:3000/.well-known/mercure');
    url.searchParams.append('topic', `http://localhost:3000/users/${userId}`);

    const eventSource = new EventSource(url.toString());

    eventSource.onmessage = (event) => {
      const notification = JSON.parse(event.data);

      // Gérer les différents types de notifications
      switch (notification.type) {
        case 'ride_completed':
          // Afficher un toast/notification
          showNotification({
            title: 'Course terminée',
            message: `Prix final: ${notification.ride.finalPrice}€`,
            type: 'success'
          });

          // Redirection automatique vers l'historique
          if (notification.action?.type === 'redirect') {
            setTimeout(() => {
              router.push(notification.action.route);
            }, 2000); // Délai de 2s pour laisser voir la notification
          }
          break;

        case 'ride_accepted':
          showNotification({
            title: 'Course acceptée',
            message: `${notification.ride.driver.name} arrive !`,
            type: 'info'
          });
          break;

        case 'ride_started':
          showNotification({
            title: 'Course démarrée',
            message: 'Bon voyage !',
            type: 'info'
          });
          break;
      }
    };

    return () => {
      eventSource.close();
    };
  }, [userId, router]);
}
```

#### Vue.js

```javascript
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

export function useMercureNotifications(userId) {
  const router = useRouter();
  let eventSource = null;

  onMounted(() => {
    const url = new URL('http://localhost:3000/.well-known/mercure');
    url.searchParams.append('topic', `http://localhost:3000/users/${userId}`);

    eventSource = new EventSource(url.toString());

    eventSource.onmessage = (event) => {
      const notification = JSON.parse(event.data);

      if (notification.type === 'ride_completed') {
        // Afficher notification
        notify({
          title: 'Course terminée',
          text: `Prix: ${notification.ride.finalPrice}€`,
          type: 'success'
        });

        // Redirection
        if (notification.action?.type === 'redirect') {
          setTimeout(() => {
            router.push(notification.action.route);
          }, 2000);
        }
      }
    };
  });

  onUnmounted(() => {
    if (eventSource) {
      eventSource.close();
    }
  });
}
```

## 🔔 Autres notifications disponibles

| Type | Description | Destinataire |
|------|-------------|--------------|
| `ride_accepted` | Course acceptée par un chauffeur | Passager |
| `ride_started` | Course démarrée | Passager |
| `ride_completed` | Course terminée | Passager |
| `ride_update` | Mise à jour générale | Chauffeur |
| `new_ride` | Nouvelle demande de course | Chauffeurs à proximité |
| `location_update` | Position du chauffeur | Passager (tracking) |

## 🔐 Authentification Mercure

Pour les environnements sécurisés, vous devrez inclure un JWT Mercure dans la connexion :

```javascript
const url = new URL('http://localhost:3000/.well-known/mercure');
url.searchParams.append('topic', `http://localhost:3000/users/${userId}`);

const eventSource = new EventSource(url.toString(), {
  headers: {
    'Authorization': `Bearer ${mercureJwtToken}`
  }
});
```

## 📚 Documentation Mercure

Voir : https://mercure.rocks/docs/getting-started
