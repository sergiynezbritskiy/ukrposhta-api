<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Client;

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
        $this->assertCount(2, $addresses);
        $secondaryAddresses = array_values(array_filter($addresses, fn(array $address) => !$address['main']));
        $this->assertCount(1, $secondaryAddresses, 'The main address can not be removed');
        $this->model->deleteAddress($secondaryAddresses[0]['uuid']);
        $this->assertCount(1, $this->model->getAddresses($clientUuid));

        $phones = $this->model->getPhones($clientUuid);
        $this->assertCount(2, $phones);
        $phoneToDelete = $this->findSecondary($phones, 'phoneNumber', $updatedPhone);
        $this->model->deletePhone($phoneToDelete['uuid']);
        $this->assertCount(1, $this->model->getPhones($clientUuid));

        $emails = $this->model->getEmails($clientUuid);
        $this->assertCount(2, $emails);
        $emailToDelete = $this->findSecondary($emails, 'email', $updatedEmail);
        $this->model->deleteEmail($emailToDelete['uuid']);
        $this->assertCount(1, $this->model->getEmails($clientUuid));
    }

    /**
     * Finds the entity which does not match the current main value
     *
     * @param array $entities
     * @param string $property
     * @param string $mainValue
     * @return array
     */
    private function findSecondary(array $entities, string $property, string $mainValue): array
    {
        foreach ($entities as $entity) {
            if (!str_contains((string)$entity[$property], $mainValue)) {
                return $entity;
            }
        }
        $this->fail('Secondary entity not found');
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
