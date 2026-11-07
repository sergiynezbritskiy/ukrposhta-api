<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Client;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Client;
use SergiyNezbritskiy\Ukrposhta\Tests\AssertEntityByPropertiesTrait;
use SergiyNezbritskiy\Ukrposhta\Tests\ConstantsInterface;
use SergiyNezbritskiy\Ukrposhta\Tests\CreatesFixturesTrait;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class CrudTest extends TestCase implements ConstantsInterface
{
    use AssertEntityByPropertiesTrait;
    use CreatesFixturesTrait;
    use UsesConnectionTrait;

    private Client $model;

    protected function setUp(): void
    {
        $this->model = new Client($this->getConnection());
    }

    /**
     * @return void
     * @throws UkrposhtaApiException
     */
    public function testClientCrud(): void
    {
        $client = $this->createRecipient($this->getConnection());
        $this->assertClient($client, self::RECIPIENT);

        $actualResult = $this->model->get($client['uuid']);
        $this->assertSame($client['uuid'], $actualResult['uuid']);
        $this->assertClient($actualResult, self::RECIPIENT);

        $this->gulp();

        $updatedPhone = '0501112233';
        $updatedEmail = 'updated@example.com';
        $actualResult = $this->model->update($client['uuid'], [
            'phoneNumber' => $updatedPhone,
            'email' => $updatedEmail,
        ]);
        $this->assertSame($client['uuid'], $actualResult['uuid']);
        $this->assertStringContainsString($updatedPhone, $actualResult['phoneNumber']);
        $this->assertSame($updatedEmail, $actualResult['email']);

        $this->performRelatedEntitiesCheck($client['uuid'], $updatedPhone, $updatedEmail);
    }

    /**
     * @param string $clientUuid
     * @param string $updatedPhone
     * @param string $updatedEmail
     * @return void
     * @throws UkrposhtaApiException
     */
    private function performRelatedEntitiesCheck(string $clientUuid, string $updatedPhone, string $updatedEmail): void
    {
        $addresses = $this->model->getAddresses($clientUuid);
        $this->assertCount(1, $addresses);
        $this->assertSame(self::RECIPIENT_ADDRESS['postcode'], $addresses[0]['address']['postcode']);

        $this->model->update($clientUuid, [
            'addressId' => $this->createAddress($this->getConnection(), self::SENDER_ADDRESS),
        ]);
        $addresses = $this->model->getAddresses($clientUuid);
        $secondaryAddress = $this->findEntity(
            $addresses,
            fn(array $address) => !$address['main'],
            'Secondary address'
        );
        $this->assertCount(2, $addresses);
        $this->model->deleteAddress($secondaryAddress['uuid']);
        $addresses = $this->model->getAddresses($clientUuid);
        $this->assertCount(1, $addresses);
        $this->assertTrue((bool)$addresses[0]['main'], 'The main address can not be removed');

        $phones = $this->model->getPhones($clientUuid);
        $phoneToDelete = $this->findEntity(
            $phones,
            fn(array $phone) => !str_contains((string)$phone['phoneNumber'], $updatedPhone),
            'Secondary phone'
        );
        $this->assertCount(2, $phones);
        $this->model->deletePhone($phoneToDelete['uuid']);
        $this->assertCount(1, $this->model->getPhones($clientUuid));

        $emails = $this->model->getEmails($clientUuid);
        $emailToDelete = $this->findEntity(
            $emails,
            fn(array $email) => $email['email'] !== $updatedEmail,
            'Secondary email'
        );
        $this->assertCount(2, $emails);
        $this->model->deleteEmail($emailToDelete['uuid']);
        $this->assertCount(1, $this->model->getEmails($clientUuid));
    }

    /**
     * Finds the first entity matching the condition, fails the test if there is none.
     * Call it before assertCount(): Scrutinizer narrows the asserted variable to Countable|iterable
     *
     * @param iterable<array> $entities
     * @param callable $matches
     * @param string $description
     * @return array
     */
    private function findEntity(iterable $entities, callable $matches, string $description): array
    {
        foreach ($entities as $entity) {
            if ($matches($entity)) {
                return $entity;
            }
        }
        throw new AssertionFailedError($description . ' not found');
    }

    /**
     * @param array $client
     * @param array $expected
     * @return void
     */
    private function assertClient(array $client, array $expected): void
    {
        $expectedProperties = ['uuid', 'name', 'firstName', 'lastName', 'addressId', 'phoneNumber'];
        $this->assertEntityHasProperties($client, $expectedProperties);
        $this->assertSame(Client::TYPE_INDIVIDUAL, $client['type']);
        $this->assertSame($expected['firstName'], $client['firstName']);
        $this->assertSame($expected['lastName'], $client['lastName']);
        $this->assertStringContainsString($expected['phoneNumber'], $client['phoneNumber']);
    }
}
