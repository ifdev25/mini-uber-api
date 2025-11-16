# Fixtures - Mini Uber API

Ce fichier contient des données de test réalistes pour l'application Mini Uber.

## Chargement des fixtures

Pour charger les fixtures dans la base de données :

```bash
# Réinitialiser la base de données et charger les fixtures
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
```

Ou en une seule commande :
```bash
php bin/console doctrine:fixtures:load --purge-with-truncate
```

## Données disponibles

### 👤 Admin
- **Email:** `admin@miniuber.com`
- **Password:** `admin123`
- **Nom:** Alice Admin
- **Rôles:** ROLE_USER, ROLE_ADMIN
- **Type:** Passager

### 👤 Passager
- **Email:** `john.doe@email.com`
- **Password:** `password123`
- **Nom:** John Doe
- **Rating:** 4.8 ⭐
- **Courses effectuées:** 15
- **Type:** Passager

### 🚗 Driver 1 - Marie Martin
- **Email:** `marie.martin@driver.com`
- **Password:** `driver123`
- **Nom:** Marie Martin
- **Téléphone:** +33634567890
- **Rating:** 4.9 ⭐
- **Courses effectuées:** 234
- **Véhicule:** Tesla Model 3 (Blanc Nacré)
- **Type de véhicule:** Premium
- **Licence:** DR123456789
- **Statut:** ✅ Vérifiée
- **Disponibilité:** ✅ Disponible
- **Position:** 48.8566, 2.3522 (Louvre, Paris)

### 🚗 Driver 2 - Pierre Dubois
- **Email:** `pierre.dubois@driver.com`
- **Password:** `driver123`
- **Nom:** Pierre Dubois
- **Téléphone:** +33645678901
- **Rating:** 4.7 ⭐
- **Courses effectuées:** 189
- **Véhicule:** Peugeot 508 (Noir Métallisé)
- **Type de véhicule:** Comfort
- **Licence:** DR987654321
- **Statut:** ✅ Vérifié
- **Disponibilité:** ❌ Non disponible (en course)
- **Position:** 48.8606, 2.3376 (Champs-Élysées, Paris)

## Courses d'exemple

### ✅ Course 1 - Terminée
- **Passager:** John Doe
- **Chauffeur:** Marie Martin
- **Trajet:** Gare du Nord → Tour Eiffel
- **Distance:** 5.2 km
- **Prix:** 18.50€
- **Type:** Premium
- **Statut:** Terminée il y a 2 jours

### 🚗 Course 2 - En cours
- **Passager:** John Doe
- **Chauffeur:** Pierre Dubois
- **Trajet:** Place de la République → Montmartre
- **Distance:** 3.8 km
- **Prix estimé:** 12.80€
- **Type:** Comfort
- **Statut:** En cours (démarrée il y a 5 minutes)

### ⏳ Course 3 - En attente
- **Passager:** John Doe
- **Trajet:** Opéra Garnier → Gare de Lyon
- **Distance:** 4.5 km
- **Prix estimé:** 15.20€
- **Type:** Standard
- **Statut:** En attente d'acceptation

## Exemples de tests avec ces données

### Se connecter en tant que passager
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@email.com",
    "password": "password123"
  }'
```

### Se connecter en tant que chauffeur
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "marie.martin@driver.com",
    "password": "driver123"
  }'
```

### Se connecter en tant qu'admin
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@miniuber.com",
    "password": "admin123"
  }'
```

### Récupérer les courses en attente
```bash
curl http://localhost:8000/api/rides?status=pending
```

### Récupérer les chauffeurs disponibles
```bash
curl http://localhost:8000/api/drivers?isAvailable=true&isVerified=true
```

## Notes

- Les mots de passe sont hashés avec bcrypt via `UserPasswordHashProcessor`
- Les dates sont relatives pour avoir des données cohérentes
- Les coordonnées GPS sont réelles (Paris)
- Les profils chauffeurs sont automatiquement liés aux users
- Les courses d'exemple permettent de tester tous les statuts possibles

## Développement

Pour modifier les fixtures, éditez le fichier :
```
src/DataFixtures/AppFixtures.php
```

Puis rechargez-les avec :
```bash
php bin/console doctrine:fixtures:load
```
