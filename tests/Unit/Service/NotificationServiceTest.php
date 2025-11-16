<?php

namespace App\Tests\Unit\Service;

use App\Entity\Ride;
use App\Entity\User;
use App\Entity\Driver;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationServiceTest extends TestCase
{
    private HubInterface $hubMock;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        $this->hubMock = $this->createMock(HubInterface::class);
        $this->notificationService = new NotificationService($this->hubMock);
    }

    public function testNotifyDriversAboutNewRide(): void
    {
        // Create mock user (passenger)
        $passenger = $this->createMock(User::class);
        $passenger->method('getFirstname')->willReturn('John');
        $passenger->method('getLastname')->willReturn('Doe');
        $passenger->method('getRating')->willReturn(4.8);

        // Create mock ride
        $ride = $this->createMock(Ride::class);
        $ride->method('getId')->willReturn(123);
        $ride->method('getPickUpAddress')->willReturn('123 Main St');
        $ride->method('getDropoffAddress')->willReturn('456 Avenue');
        $ride->method('getEstimatedPrice')->willReturn(12.80);
        $ride->method('getEstimatedDistance')->willReturn(3.2);
        $ride->method('getVehiculeType')->willReturn('standard');
        $ride->method('getPassenger')->willReturn($passenger);

        // Create mock drivers
        $driver1 = $this->createMock(User::class);
        $driver1->method('getId')->willReturn(1);

        $driver2 = $this->createMock(User::class);
        $driver2->method('getId')->willReturn(2);

        $nearbyDrivers = [$driver1, $driver2];

        // Expect hub->publish to be called twice (once for each driver)
        $this->hubMock->expects($this->exactly(2))
            ->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $this->notificationService->notifyDriversAboutNewRide($ride, $nearbyDrivers);
    }

    public function testNotifyPassengerRideAccepted(): void
    {
        // Create mock driver profile
        $driverProfile = $this->createMock(Driver::class);
        $driverProfile->method('getVehiculeModel')->willReturn('Tesla Model 3');
        $driverProfile->method('getVehiculeColor')->willReturn('Black');
        $driverProfile->method('getVehiculeType')->willReturn('premium');

        // Create mock driver user
        $driver = $this->createMock(User::class);
        $driver->method('getFirstname')->willReturn('Jane');
        $driver->method('getLastname')->willReturn('Smith');
        $driver->method('getRating')->willReturn(4.9);
        $driver->method('getPhone')->willReturn('+33123456789');
        $driver->method('getDriver')->willReturn($driverProfile);

        // Create mock passenger
        $passenger = $this->createMock(User::class);
        $passenger->method('getId')->willReturn(456);

        // Create mock ride
        $ride = $this->createMock(Ride::class);
        $ride->method('getId')->willReturn(123);
        $ride->method('getStatus')->willReturn('accepted');
        $ride->method('getDriver')->willReturn($driver);
        $ride->method('getPassenger')->willReturn($passenger);

        // Expect hub->publish to be called once
        $this->hubMock->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $this->notificationService->notifyPassengerRideAccepted($ride);
    }

    public function testUpdateDriverLocation(): void
    {
        $driver = $this->createMock(User::class);
        $driver->method('getUsertype')->willReturn('driver');
        $driver->method('getId')->willReturn(789);

        $lat = 48.8566;
        $lng = 2.3522;

        // Expect hub->publish to be called once
        $this->hubMock->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $this->notificationService->updateDriverLocation($driver, $lat, $lng);
    }

    public function testUpdateDriverLocationDoesNothingForNonDriver(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUsertype')->willReturn('passenger');

        // Expect hub->publish to never be called
        $this->hubMock->expects($this->never())
            ->method('publish');

        $this->notificationService->updateDriverLocation($user, 48.8566, 2.3522);
    }
}
