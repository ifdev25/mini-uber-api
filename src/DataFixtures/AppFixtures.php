<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Driver;
use App\Entity\Ride;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ====================================
        // 1. Créer un Admin
        // ====================================
        $admin = new User();
        $admin->setEmail('admin@miniuber.com');
        $admin->setFirstname('Alice');
        $admin->setLastname('Admin');
        $admin->setPhone('+33612345678');
        $admin->setUsertype('passenger');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setRating(5.0);
        $admin->setTotalRides(0);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));

        $manager->persist($admin);

        // ====================================
        // 2. Créer un User Passager
        // ====================================
        $passenger = new User();
        $passenger->setEmail('john.doe@email.com');
        $passenger->setFirstname('John');
        $passenger->setLastname('Doe');
        $passenger->setPhone('+33623456789');
        $passenger->setUsertype('passenger');
        $passenger->setRoles(['ROLE_USER']);
        $passenger->setRating(4.8);
        $passenger->setTotalRides(15);
        $passenger->setPassword($this->passwordHasher->hashPassword($passenger, 'password123'));

        $manager->persist($passenger);

        // ====================================
        // 3. Créer Driver 1 - Marie Martin
        // ====================================
        $driver1User = new User();
        $driver1User->setEmail('marie.martin@driver.com');
        $driver1User->setFirstname('Marie');
        $driver1User->setLastname('Martin');
        $driver1User->setPhone('+33634567890');
        $driver1User->setUsertype('driver');
        $driver1User->setRoles(['ROLE_USER']);
        $driver1User->setRating(4.9);
        $driver1User->setTotalRides(234);
        $driver1User->setPassword($this->passwordHasher->hashPassword($driver1User, 'driver123'));

        $manager->persist($driver1User);

        // Profil Driver pour Marie
        $driver1Profile = new Driver();
        $driver1Profile->setUser($driver1User);
        $driver1Profile->setVehiculeModel('Tesla Model 3');
        $driver1Profile->setVehiculeType('premium');
        $driver1Profile->setVehiculeColor('Blanc Nacré');
        $driver1Profile->setCurrentLatitude(48.8566);  // Paris - Louvre
        $driver1Profile->setCurrentLongitude(2.3522);
        $driver1Profile->setLicenceNumber('DR123456789');
        $driver1Profile->setIsVerified(true);
        $driver1Profile->setVerifiedAt(new \DateTimeImmutable('-6 months'));
        $driver1Profile->setIsAvailable(true);

        $manager->persist($driver1Profile);

        // ====================================
        // 4. Créer Driver 2 - Pierre Dubois
        // ====================================
        $driver2User = new User();
        $driver2User->setEmail('pierre.dubois@driver.com');
        $driver2User->setFirstname('Pierre');
        $driver2User->setLastname('Dubois');
        $driver2User->setPhone('+33645678901');
        $driver2User->setUsertype('driver');
        $driver2User->setRoles(['ROLE_USER']);
        $driver2User->setRating(4.7);
        $driver2User->setTotalRides(189);
        $driver2User->setPassword($this->passwordHasher->hashPassword($driver2User, 'driver123'));

        $manager->persist($driver2User);

        // Profil Driver pour Pierre
        $driver2Profile = new Driver();
        $driver2Profile->setUser($driver2User);
        $driver2Profile->setVehiculeModel('Peugeot 508');
        $driver2Profile->setVehiculeType('comfort');
        $driver2Profile->setVehiculeColor('Noir Métallisé');
        $driver2Profile->setCurrentLatitude(48.8606);  // Paris - Champs-Élysées
        $driver2Profile->setCurrentLongitude(2.3376);
        $driver2Profile->setLicenceNumber('DR987654321');
        $driver2Profile->setIsVerified(true);
        $driver2Profile->setVerifiedAt(new \DateTimeImmutable('-3 months'));
        $driver2Profile->setIsAvailable(false); // En course

        $manager->persist($driver2Profile);

        // ====================================
        // 5. Créer quelques courses d'exemple
        // ====================================

        // Course 1 - Terminée
        $ride1 = new Ride();
        $ride1->setPassenger($passenger);
        $ride1->setDriver($driver1User);
        $ride1->setStatus('completed');
        $ride1->setPickUpAddress('Gare du Nord, Paris');
        $ride1->setPickUpLatitude(48.8809);
        $ride1->setPickUpLongitude(2.3553);
        $ride1->setDropoffAddress('Tour Eiffel, Paris');
        $ride1->setDropoffLatitude(48.8584);
        $ride1->setDropoffLongitude(2.2945);
        $ride1->setEstimatedDistance(5.2);
        $ride1->setEstimatedPrice(18.50);
        $ride1->setEstimatedDuration(15.0);
        $ride1->setFinalPrice(18.50);
        $ride1->setVehiculeType('premium');
        $ride1->setAcceptedAt(new \DateTimeImmutable('-2 days'));
        $ride1->setStartedAt(new \DateTimeImmutable('-2 days +5 minutes'));
        $ride1->setCompletedAt(new \DateTimeImmutable('-2 days +20 minutes'));

        $manager->persist($ride1);

        // Course 2 - En cours
        $ride2 = new Ride();
        $ride2->setPassenger($passenger);
        $ride2->setDriver($driver2User);
        $ride2->setStatus('in_progress');
        $ride2->setPickUpAddress('Place de la République, Paris');
        $ride2->setPickUpLatitude(48.8676);
        $ride2->setPickUpLongitude(2.3634);
        $ride2->setDropoffAddress('Montmartre, Paris');
        $ride2->setDropoffLatitude(48.8867);
        $ride2->setDropoffLongitude(2.3431);
        $ride2->setEstimatedDistance(3.8);
        $ride2->setEstimatedPrice(12.80);
        $ride2->setEstimatedDuration(12.0);
        $ride2->setVehiculeType('comfort');
        $ride2->setAcceptedAt(new \DateTimeImmutable('-10 minutes'));
        $ride2->setStartedAt(new \DateTimeImmutable('-5 minutes'));

        $manager->persist($ride2);

        // Course 3 - En attente
        $ride3 = new Ride();
        $ride3->setPassenger($passenger);
        $ride3->setStatus('pending');
        $ride3->setPickUpAddress('Opéra Garnier, Paris');
        $ride3->setPickUpLatitude(48.8720);
        $ride3->setPickUpLongitude(2.3318);
        $ride3->setDropoffAddress('Gare de Lyon, Paris');
        $ride3->setDropoffLatitude(48.8449);
        $ride3->setDropoffLongitude(2.3738);
        $ride3->setEstimatedDistance(4.5);
        $ride3->setEstimatedPrice(15.20);
        $ride3->setEstimatedDuration(14.0);
        $ride3->setVehiculeType('standard');

        $manager->persist($ride3);

        $manager->flush();

        // Afficher un résumé dans la console
        echo "\n✅ Fixtures chargées avec succès !\n\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 UTILISATEURS CRÉÉS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "👤 ADMIN\n";
        echo "   Email    : admin@miniuber.com\n";
        echo "   Password : admin123\n";
        echo "   Rôles    : ROLE_USER, ROLE_ADMIN\n\n";

        echo "👤 PASSAGER\n";
        echo "   Email    : john.doe@email.com\n";
        echo "   Password : password123\n";
        echo "   Nom      : John Doe\n";
        echo "   Rating   : 4.8 ⭐\n";
        echo "   Courses  : 15\n\n";

        echo "🚗 DRIVER 1\n";
        echo "   Email      : marie.martin@driver.com\n";
        echo "   Password   : driver123\n";
        echo "   Nom        : Marie Martin\n";
        echo "   Véhicule   : Tesla Model 3 (Blanc Nacré)\n";
        echo "   Type       : Premium\n";
        echo "   Rating     : 4.9 ⭐\n";
        echo "   Courses    : 234\n";
        echo "   Vérifié    : ✅ Oui\n";
        echo "   Disponible : ✅ Oui\n";
        echo "   Position   : 48.8566, 2.3522 (Louvre)\n\n";

        echo "🚗 DRIVER 2\n";
        echo "   Email      : pierre.dubois@driver.com\n";
        echo "   Password   : driver123\n";
        echo "   Nom        : Pierre Dubois\n";
        echo "   Véhicule   : Peugeot 508 (Noir Métallisé)\n";
        echo "   Type       : Comfort\n";
        echo "   Rating     : 4.7 ⭐\n";
        echo "   Courses    : 189\n";
        echo "   Vérifié    : ✅ Oui\n";
        echo "   Disponible : ❌ Non (en course)\n";
        echo "   Position   : 48.8606, 2.3376 (Champs-Élysées)\n\n";

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🚕 COURSES D'EXEMPLE\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "✅ Course 1 : TERMINÉE\n";
        echo "   Gare du Nord → Tour Eiffel\n";
        echo "   Chauffeur : Marie Martin\n";
        echo "   Prix : 18.50€\n\n";

        echo "🚗 Course 2 : EN COURS\n";
        echo "   Place de la République → Montmartre\n";
        echo "   Chauffeur : Pierre Dubois\n";
        echo "   Prix estimé : 12.80€\n\n";

        echo "⏳ Course 3 : EN ATTENTE\n";
        echo "   Opéra Garnier → Gare de Lyon\n";
        echo "   Prix estimé : 15.20€\n\n";

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
}
