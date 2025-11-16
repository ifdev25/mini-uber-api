<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests fonctionnels pour l'API Ride
 *
 * Ces tests vérifient que les endpoints API fonctionnent correctement
 * et retournent les bonnes réponses HTTP.
 */
class RideApiTest extends WebTestCase
{
    public function testGetRidesCollectionReturnsSuccessResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rides');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetRidesCollectionReturnsJsonLdFormat(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rides', [], [], ['CONTENT_TYPE' => 'application/ld+json']);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('hydra:member', $responseData);
        $this->assertArrayHasKey('@context', $responseData);
        $this->assertArrayHasKey('@type', $responseData);
    }

    public function testRideEstimateEndpointReturnsEstimate(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/ride-estimates', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'pickupLat' => 48.8566,
            'pickupLng' => 2.3522,
            'dropoffLat' => 48.8606,
            'dropoffLng' => 2.3376,
            'vehicleType' => 'standard'
        ]));

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('distance', $responseData);
        $this->assertArrayHasKey('duration', $responseData);
        $this->assertArrayHasKey('price', $responseData);
        $this->assertArrayHasKey('vehicleType', $responseData);
    }

    public function testRideEstimateWithInvalidDataReturnsError(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/ride-estimates', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'pickupLat' => 'invalid',
            'pickupLng' => 2.3522,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetDriversCollectionWithFilters(): void
    {
        $client = static::createClient();

        // Test with filters
        $client->request('GET', '/api/drivers?isAvailable=true&isVerified=true');

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('hydra:member', $responseData);
    }

    public function testGetUsersCollectionWithFilters(): void
    {
        $client = static::createClient();

        // Test with usertype filter
        $client->request('GET', '/api/users?usertype=driver');

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('hydra:member', $responseData);
    }

    public function testApiDocumentationIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api');

        $this->assertResponseIsSuccessful();
    }
}
