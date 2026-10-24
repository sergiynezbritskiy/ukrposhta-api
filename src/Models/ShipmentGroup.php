<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Models;

use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\ModelInterface;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Class ShipmentGroup
 *
 * Shipments can be combined into groups (e.g. for printing form 103). Only shipments of the same
 * type as the group (EXPRESS, STANDARD or DOCUMENT) can be added to it.
 *
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group
 */
class ShipmentGroup implements ModelInterface
{
    public const TYPE_EXPRESS = 'EXPRESS';
    public const TYPE_STANDARD = 'STANDARD';
    public const TYPE_DOCUMENT = 'DOCUMENT';

    private const URI = 'shipment-groups';

    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/CreateShipmentGroupUsingPOST
     * @param array $group
     *    $group = [
     *        'name'       => (string) Required.
     *        'clientUuid' => (string) Sender uuid. Required.
     *        'type'       => (string) see self::TYPE_* constants. Optional, default EXPRESS.
     *    ];
     * @return array
     * @throws UkrposhtaApiException
     */
    public function save(array $group): array
    {
        return $this->connection->post(self::URI, $group);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/UpdateShipmentGroupUsingPUT
     * @param string $uuid
     * @param array $group see self::save() for the structure
     * @return array
     * @throws UkrposhtaApiException
     */
    public function update(string $uuid, array $group): array
    {
        return $this->connection->put(self::URI . '/' . $uuid, $group);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/GetShipmentGroupUsingGET
     * @param string $uuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function get(string $uuid): array
    {
        return $this->connection->get(self::URI . '/' . $uuid);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/Getallshipmentgroupsbyclientusingget1
     * @param string $clientUuid
     * @param array $filters Available filters are: type, from, to (dates), matching, name
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getByClient(string $clientUuid, array $filters = []): array
    {
        $query = array_filter([
            'type' => $filters['type'] ?? null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'matching' => $filters['matching'] ?? null,
            'name' => $filters['name'] ?? null,
        ]);
        return $this->connection->get(self::URI . '/clients/' . $clientUuid, $query);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/GetShipmentsByShipmentGroupUuidUsingGET
     * @param string $uuid
     * @param array $filters Available filters are: status, page, size, sort, order
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getShipments(string $uuid, array $filters = []): array
    {
        $query = array_filter([
            'status' => $filters['status'] ?? null,
            'page' => $filters['page'] ?? null,
            'size' => $filters['size'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'order' => $filters['order'] ?? null,
        ]);
        return $this->connection->get(self::URI . '/' . $uuid . '/shipments', $query);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/GetShipmentsQuantityFromShipmentGroupByUuidUsingGET
     * @param string $uuid
     * @param string|null $status
     * @return int
     * @throws UkrposhtaApiException
     */
    public function getShipmentsCount(string $uuid, ?string $status = null): int
    {
        $result = $this->connection->get(self::URI . '/' . $uuid . '/shipments-count', ['status' => $status]);
        return (int)($result['quantity'] ?? 0);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/AddShipmentToShipmentGroupUsingPOST
     * @param string $groupUuid
     * @param string $shipmentUuid
     * @return void
     * @throws UkrposhtaApiException
     */
    public function addShipment(string $groupUuid, string $shipmentUuid): void
    {
        $this->connection->post(self::URI . '/' . $groupUuid . '/shipments/' . $shipmentUuid);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/AssignMultipleShipmentsToShipmentGroupUsingPUT
     * @param string $groupUuid
     * @param array $shipmentUuids
     * @return array status per shipment
     * @throws UkrposhtaApiException
     */
    public function addShipments(string $groupUuid, array $shipmentUuids): array
    {
        return $this->connection->put(self::URI . '/' . $groupUuid . '/shipments', array_values($shipmentUuids));
    }

    /**
     * Creates the shipment and adds it to the group in one request
     *
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group/CreateShipmentAndAddToShipmentGroupUsingPOST
     * @param string $groupUuid
     * @param array $shipment see Shipment::save() for the structure
     * @param bool $paidByCardOnline
     * @return array
     * @throws UkrposhtaApiException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function saveShipment(string $groupUuid, array $shipment, bool $paidByCardOnline = false): array
    {
        $query = $paidByCardOnline ? ['paidByCardOnline' => 'true'] : [];
        return $this->connection->post(self::URI . '/' . $groupUuid . '/shipments', $shipment, $query);
    }
}
