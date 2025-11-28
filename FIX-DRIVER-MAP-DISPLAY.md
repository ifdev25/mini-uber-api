# Fix : Affichage du chauffeur sur la carte

## 🔍 Problème identifié

Le chauffeur n'apparaît pas sur la carte lors du suivi de course car l'API Platform retourne uniquement l'IRI du driver (`/api/drivers/1`) au lieu de l'objet complet avec les coordonnées GPS.

**Frontend attendu :**
```json
{
  "id": 1,
  "driver": {
    "id": 1,
    "currentLatitude": 48.8566,
    "currentLongitude": 2.3522,
    "user": { ... },
    "vehicleModel": "Toyota Prius"
  }
}
```

**Backend actuel (probablement) :**
```json
{
  "id": 1,
  "driver": "/api/drivers/1"  // ❌ Juste une IRI
}
```

---

## ✅ Solution : Configuration des groupes de sérialisation

### Étape 1 : Configuration de l'entité `Ride`

Dans `src/Entity/Ride.php`, assurez-vous que le champ `driver` a le groupe de normalisation approprié :

```php
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\ManyToOne(targetEntity: Driver::class)]
#[ORM\JoinColumn(nullable: true)]
#[Groups(['ride:read', 'ride:write'])]
private ?Driver $driver = null;
```

### Étape 2 : Configuration de l'entité `Driver`

Dans `src/Entity/Driver.php`, ajoutez les groupes de normalisation pour les champs nécessaires à l'affichage sur la carte :

```php
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
#[Groups(['driver:read', 'ride:read'])]  // ✅ Ajoutez 'ride:read'
private ?int $id = null;

#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'drivers')]
#[ORM\JoinColumn(nullable: false)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ Inclure l'utilisateur
private ?User $user = null;

#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ IMPORTANT : Latitude
private ?string $currentLatitude = null;

#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ IMPORTANT : Longitude
private ?string $currentLongitude = null;

#[ORM\Column(length: 100)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ Modèle du véhicule
private ?string $vehicleModel = null;

#[ORM\Column(length: 50)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ Couleur du véhicule
private ?string $vehicleColor = null;

#[ORM\Column(length: 20)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ Plaque d'immatriculation
private ?string $vehiclePlateNumber = null;

#[ORM\Column(length: 50, enumType: VehicleType::class)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ Type de véhicule
private ?VehicleType $vehicleType = null;

#[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
#[Groups(['driver:read', 'ride:read'])]  // ✅ Note du chauffeur
private ?string $rating = null;
```

### Étape 3 : Configuration de l'entité `User` (pour les infos du chauffeur)

Dans `src/Entity/User.php`, ajoutez le groupe `ride:read` aux champs nécessaires :

```php
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
#[Groups(['user:read', 'driver:read', 'ride:read'])]  // ✅ Ajoutez 'ride:read'
private ?int $id = null;

#[ORM\Column(length: 100)]
#[Groups(['user:read', 'driver:read', 'ride:read'])]  // ✅ Prénom
private ?string $firstName = null;

#[ORM\Column(length: 100)]
#[Groups(['user:read', 'driver:read', 'ride:read'])]  // ✅ Nom
private ?string $lastName = null;

// ⚠️ NE PAS exposer l'email et le téléphone pour la sécurité
// (ou seulement pour les passagers de la course)
```

### Étape 4 : Vérifier la ressource API Platform

Dans `src/Entity/Ride.php`, vérifiez que la ressource API Platform utilise les bons groupes :

```php
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['ride:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['ride:read']]
        ),
        new Post(
            normalizationContext: ['groups' => ['ride:read']],
            denormalizationContext: ['groups' => ['ride:write']]
        ),
        // ... autres opérations
    ]
)]
class Ride
{
    // ...
}
```

---

## 🧪 Test et vérification

### 1. Test avec curl

Après les modifications, testez l'endpoint :

```bash
# Récupérer une course avec un chauffeur assigné
curl -X GET "http://localhost:8000/api/rides/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/ld+json"
```

**Réponse attendue :**
```json
{
  "@context": "/api/contexts/Ride",
  "@id": "/api/rides/1",
  "@type": "Ride",
  "id": 1,
  "status": "accepted",
  "driver": {
    "@id": "/api/drivers/1",
    "@type": "Driver",
    "id": 1,
    "currentLatitude": "48.8566000",
    "currentLongitude": "2.3522000",
    "vehicleModel": "Toyota Prius",
    "vehicleColor": "Blanc",
    "vehiclePlateNumber": "AB-123-CD",
    "vehicleType": "comfort",
    "rating": "4.80",
    "user": {
      "@id": "/api/users/2",
      "id": 2,
      "firstName": "Békira",
      "lastName": "Dupont"
    }
  },
  "pickupAddress": "...",
  "pickupLatitude": 48.8566,
  "pickupLongitude": 2.3522,
  "dropoffAddress": "...",
  "dropoffLatitude": 48.8606,
  "dropoffLongitude": 2.3376
}
```

✅ **Le driver doit être un objet complet, PAS une string IRI !**

### 2. Vérifier dans le frontend

Dans la console du navigateur (page de suivi de course), vous devriez voir :

```javascript
🔍 ride.driver: {id: 1, currentLatitude: 48.8566, ...}
🔍 Type: object  // ✅ PAS "string"
```

---

## 📝 Checklist

- [ ] Ajouté `Groups(['ride:read'])` sur tous les champs nécessaires de `Driver`
- [ ] Ajouté `Groups(['ride:read'])` sur `currentLatitude` et `currentLongitude`
- [ ] Ajouté `Groups(['ride:read'])` sur les champs de `User` (firstName, lastName)
- [ ] Vérifié que `Ride::$driver` a bien le groupe `ride:read`
- [ ] Testé l'endpoint `/api/rides/{id}` et vérifié que `driver` est un objet
- [ ] Rechargé la page de suivi de course et vérifié que le chauffeur apparaît sur la carte

---

## 🔧 Commandes utiles

```bash
# Vider le cache Symfony
php bin/console cache:clear

# Lister les routes API Platform
php bin/console debug:router | grep api

# Vérifier les groupes de sérialisation
php bin/console debug:serializer App\\Entity\\Ride
php bin/console debug:serializer App\\Entity\\Driver
```

---

## ⚠️ Notes importantes

1. **Sécurité** : Ne pas exposer les données sensibles (email, téléphone) dans le groupe `ride:read` pour tous les utilisateurs. Envisagez d'utiliser des voters ou des groupes conditionnels.

2. **Performance** : L'inclusion du driver complet ajoute une jointure SQL. C'est acceptable car vous en avez besoin pour l'affichage.

3. **Alternatives** :
   - Si vous voulez plus de contrôle, utilisez un `DataTransformer` personnalisé
   - Pour des cas complexes, créez un DTO spécifique avec `ApiPlatform\Metadata\ApiProperty`

---

## 📞 Support

Si après ces modifications le chauffeur n'apparaît toujours pas :
1. Vérifiez les logs Symfony : `tail -f var/log/dev.log`
2. Activez le debug SQL : `doctrine.dbal.logging: true` dans `config/packages/dev/doctrine.yaml`
3. Vérifiez que le chauffeur a bien des coordonnées GPS non nulles dans la base de données