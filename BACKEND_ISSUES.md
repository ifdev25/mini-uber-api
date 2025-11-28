# Issues Backend - Mini Uber API

**Date**: 26 Novembre 2025
**Frontend Version**: v0.1.0
**Contexte**: Développement des fonctionnalités chauffeur (driver dashboard)

---

## 🔴 Problèmes critiques identifiés

### 1. Endpoint `/api/drivers/availability` inexistant

**Statut**: ❌ Endpoint non trouvé (404)

**Description**:
L'endpoint `/api/drivers/availability` utilisé pour mettre à jour la disponibilité d'un chauffeur n'existe pas dans l'API.

**Requête attendue**:
```http
PATCH /api/drivers/availability
Authorization: Bearer {token}
Content-Type: application/merge-patch+json

{
  "isAvailable": true
}
```

**Réponse actuelle**:
```json
{
  "@type": "Error",
  "status": 404,
  "detail": "Not Found"
}
```

**Solution attendue**:
Créer un endpoint custom `/api/drivers/availability` qui :
- Identifie automatiquement le chauffeur à partir du token JWT
- Met à jour uniquement le champ `isAvailable` du driver
- Retourne l'objet Driver complet mis à jour

**Workaround temporaire côté frontend**:
Nous récupérons d'abord l'ID du driver via `/api/drivers`, puis nous utilisons `PATCH /api/drivers/{id}` avec `isAvailable` dans le body.

---

### 2. Endpoint `/api/drivers/location` inexistant

**Statut**: ❌ Endpoint non trouvé (404)

**Description**:
L'endpoint `/api/drivers/location` pour mettre à jour la position GPS du chauffeur n'existe pas.

**Requête attendue**:
```http
PATCH /api/drivers/location
Authorization: Bearer {token}
Content-Type: application/merge-patch+json

{
  "lat": 48.8566,
  "lng": 2.3522
}
```

**Solution attendue**:
Créer un endpoint custom `/api/drivers/location` qui :
- Identifie automatiquement le chauffeur à partir du token JWT
- Met à jour les champs `currentLatitude` et `currentLongitude`
- Retourne l'objet Driver complet mis à jour

**Workaround temporaire côté frontend**:
Nous récupérons d'abord l'ID du driver via `/api/drivers`, puis nous utilisons `PATCH /api/drivers/{id}` avec `currentLatitude` et `currentLongitude` dans le body.

---

### 3. Propriété `driverProfile` dans `/api/me` sans ID

**Statut**: ⚠️ Structure de données incomplète

**Description**:
L'endpoint `/api/me` retourne un objet `driverProfile` (pour les utilisateurs de type "driver"), mais cet objet ne contient pas l'ID du driver, ce qui empêche d'identifier facilement le driver.

**Réponse actuelle**:
```json
{
  "id": 5,
  "email": "karim.bensaid@driver.com",
  "userType": "driver",
  "driverProfile": {
    "vehicleModel": "Renault Symbol",
    "vehicleColor": "Blanc",
    "vehicleType": "standard",
    "isAvailable": true
  }
}
```

**Propriété manquante**: `id` dans `driverProfile`

**Réponse attendue**:
```json
{
  "id": 5,
  "email": "karim.bensaid@driver.com",
  "userType": "driver",
  "driverProfile": {
    "id": 3,  // ← ID du driver manquant
    "vehicleModel": "Renault Symbol",
    "vehicleColor": "Blanc",
    "vehicleType": "standard",
    "isAvailable": true
  }
}
```

**Impact**:
Sans l'ID du driver, le frontend doit faire un appel supplémentaire à `/api/drivers` pour trouver le driver correspondant à l'utilisateur connecté, ce qui génère des requêtes inutiles.

**Solution attendue**:
Ajouter la propriété `id` dans l'objet `driverProfile` retourné par `/api/me`.

**Workaround temporaire côté frontend**:
Nous faisons un appel à `/api/drivers` et nous filtrons pour trouver le driver dont `user.id` correspond à l'utilisateur connecté.

---

### 4. Propriété `isAvailable` non retournée dans `/api/drivers/{id}`

**Statut**: ⚠️ Propriété manquante dans la sérialisation

**Description**:
Lorsqu'on fait un `PATCH /api/drivers/{id}` avec `{"isAvailable": false}`, la propriété `isAvailable` n'apparaît pas dans la réponse, même si elle est bien mise à jour en base de données.

**Requête**:
```http
PATCH /api/drivers/3
Content-Type: application/merge-patch+json

{
  "isAvailable": false
}
```

**Réponse actuelle**:
```json
{
  "id": 3,
  "user": {...},
  "vehicleModel": "Renault Symbol",
  "vehicleType": "standard",
  "vehicleColor": "Blanc",
  "currentLatitude": 36.4244,
  "currentLongitude": 6.5983,
  "licenceNumber": "DZ123456789"
  // ← isAvailable manquant
}
```

**Solution attendue**:
Ajouter `isAvailable` dans le groupe de sérialisation de l'entité Driver pour qu'il soit retourné dans les réponses.

**Impact**:
Le frontend ne peut pas savoir si la mise à jour a bien été effectuée sans faire un nouvel appel GET.

---

## 💡 Solutions recommandées

### Option 1: Endpoints custom (Recommandé)

Créer des endpoints custom qui identifient automatiquement le chauffeur via le token JWT :

```php
// Dans DriverController.php

#[Route('/api/drivers/availability', methods: ['PATCH'])]
public function updateAvailability(Request $request): JsonResponse
{
    $driver = $this->getDriverFromToken(); // Récupère le driver du user connecté
    $data = json_decode($request->getContent(), true);

    $driver->setIsAvailable($data['isAvailable']);
    $this->entityManager->flush();

    return $this->json($driver, context: ['groups' => ['driver:read']]);
}

#[Route('/api/drivers/location', methods: ['PATCH'])]
public function updateLocation(Request $request): JsonResponse
{
    $driver = $this->getDriverFromToken();
    $data = json_decode($request->getContent(), true);

    $driver->setCurrentLatitude($data['lat']);
    $driver->setCurrentLongitude($data['lng']);
    $this->entityManager->flush();

    return $this->json($driver, context: ['groups' => ['driver:read']]);
}
```

### Option 2: Améliorer `/api/me`

Enrichir la réponse de `/api/me` pour les drivers :

```php
// Dans UserNormalizer.php ou dans un custom provider

if ($user->getUserType() === 'driver' && $user->getDriver()) {
    $data['driverProfile'] = [
        'id' => $user->getDriver()->getId(),  // ← Ajouter l'ID
        'vehicleModel' => $user->getDriver()->getVehicleModel(),
        'vehicleColor' => $user->getDriver()->getVehicleColor(),
        'vehicleType' => $user->getDriver()->getVehicleType(),
        'isAvailable' => $user->getDriver()->getIsAvailable(),
    ];
}
```

### Option 3: Créer un endpoint `/api/drivers/me`

Créer un endpoint spécifique pour récupérer le driver de l'utilisateur connecté :

```php
#[Route('/api/drivers/me', methods: ['GET'])]
public function getCurrentDriver(): JsonResponse
{
    $driver = $this->getDriverFromToken();
    return $this->json($driver, context: ['groups' => ['driver:read']]);
}
```

---

## 📋 Checklist des modifications backend

- [ ] Créer l'endpoint `PATCH /api/drivers/availability`
- [ ] Créer l'endpoint `PATCH /api/drivers/location`
- [ ] Ajouter `id` dans `driverProfile` de `/api/me`
- [ ] Ajouter `isAvailable` dans le groupe de sérialisation `driver:read`
- [ ] (Optionnel) Créer l'endpoint `GET /api/drivers/me`
- [ ] Tester tous les endpoints avec un token JWT de driver
- [ ] Mettre à jour la documentation API (Swagger/OpenAPI)

---

## 🔧 Informations techniques

**Framework backend détecté**: Symfony + API Platform
**Authentification**: JWT (LexikJWTAuthenticationBundle)
**Format de données**: JSON-LD / Hydra

**Headers requis pour les PATCH**:
```
Content-Type: application/merge-patch+json
Authorization: Bearer {token}
```

---

## 📞 Contact

Pour toute question ou clarification, contactez l'équipe frontend.

**Priorité**: 🔴 Haute - Bloque le développement des fonctionnalités chauffeur

---

## 📝 Notes additionnelles

### Comportement attendu du toggle disponibilité

1. Le chauffeur clique sur le bouton "Disponible/Indisponible"
2. Le frontend envoie `PATCH /api/drivers/availability` avec `{"isAvailable": true/false}`
3. L'API met à jour le driver et retourne l'objet complet avec `isAvailable` à jour
4. Le frontend affiche l'état mis à jour sans recharger la page

### Comportement attendu de la mise à jour de position

1. Le frontend récupère la position GPS du chauffeur toutes les 30 secondes
2. Le frontend envoie `PATCH /api/drivers/location` avec `{"lat": x, "lng": y}`
3. L'API met à jour les coordonnées du driver
4. Les passagers peuvent voir la position à jour du chauffeur sur la carte

---

**Fin du document**
