<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests;

use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\Models\Address;
use SergiyNezbritskiy\Ukrposhta\Models\Client;
use SergiyNezbritskiy\Ukrposhta\Models\Shipment;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Trait CreatesFixturesTrait
 * Helpers for creating the entities required by shipment related tests
 */
trait CreatesFixturesTrait
{
    /**
     * @param Connection $connection
     * @param array $address
     * @return int address id
     * @throws UkrposhtaApiException
     */
    private function createAddress(Connection $connection, array $address): int
    {
        $result = (new Address($connection))->save($address);
        return (int)$result['id'];
    }

    /**
     * @param Connection $connection
     * @param array $client
     * @param array $address
     * @return array client
     * @throws UkrposhtaApiException
     */
    private function createIndividual(Connection $connection, array $client, array $address): array
    {
        $client['type'] = Client::TYPE_INDIVIDUAL;
        $client['addressId'] = $this->createAddress($connection, $address);
        return (new Client($connection))->save($client);
    }

    /**
     * @param Connection $connection
     * @return array sender
     * @throws UkrposhtaApiException
     */
    private function createSender(Connection $connection): array
    {
        return $this->createIndividual($connection, ConstantsInterface::SENDER, ConstantsInterface::SENDER_ADDRESS);
    }

    /**
     * @param Connection $connection
     * @return array recipient
     * @throws UkrposhtaApiException
     */
    private function createRecipient(Connection $connection): array
    {
        return $this->createIndividual(
            $connection,
            ConstantsInterface::RECIPIENT,
            ConstantsInterface::RECIPIENT_ADDRESS
        );
    }

    /**
     * @param string $senderUuid
     * @param string $recipientUuid
     * @return array shipment params
     */
    private function buildShipmentParams(string $senderUuid, string $recipientUuid): array
    {
        return [
            'sender' => ['uuid' => $senderUuid],
            'recipient' => ['uuid' => $recipientUuid],
            'deliveryType' => Shipment::DELIVERY_TYPE_W2D,
            'paidByRecipient' => true,
            'description' => ConstantsInterface::SHIPMENT_DESCRIPTION,
            'parcels' => [
                [
                    'weight' => 1200,
                    'length' => 30,
                    'width' => 20,
                    'height' => 10,
                ],
            ],
        ];
    }

    /**
     * @param Connection $connection
     * @param string $senderUuid
     * @param string $recipientUuid
     * @return array shipment
     * @throws UkrposhtaApiException
     */
    private function createShipment(Connection $connection, string $senderUuid, string $recipientUuid): array
    {
        $params = $this->buildShipmentParams($senderUuid, $recipientUuid);
        return (new Shipment($connection))->save($params);
    }
}
