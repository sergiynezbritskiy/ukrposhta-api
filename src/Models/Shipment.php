<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Models;

use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\ModelInterface;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Class Shipment
 *
 * Domestic shipments. A shipment can be changed or deleted until it is registered at the post office.
 *
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment
 */
class Shipment implements ModelInterface
{
    public const TYPE_EXPRESS = 'EXPRESS';
    public const TYPE_STANDARD = 'STANDARD';
    public const TYPE_DOCUMENT = 'DOCUMENT';

    /** Warehouse to warehouse */
    public const DELIVERY_TYPE_W2W = 'W2W';
    /** Warehouse to door */
    public const DELIVERY_TYPE_W2D = 'W2D';
    /** Door to warehouse */
    public const DELIVERY_TYPE_D2W = 'D2W';
    /** Door to door */
    public const DELIVERY_TYPE_D2D = 'D2D';

    public const ON_FAIL_RECEIVE_TYPE_RETURN = 'RETURN';
    public const ON_FAIL_RECEIVE_TYPE_RETURN_AFTER_7_DAYS = 'RETURN_AFTER_7_DAYS';
    public const ON_FAIL_RECEIVE_TYPE_PROCESS_AS_REFUSAL = 'PROCESS_AS_REFUSAL';

    private const URI = 'shipments';
    private const DELIVERY_PRICE_URI = 'domestic/delivery-price';

    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/CreateShipmentsUsingPOST
     * @see \SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Shipment\CrudTest for more cases
     * @param array $shipment
     *    $shipment = [
     *        'sender'             => ['uuid' => (string) Client uuid]. Required.
     *        'recipient'          => ['uuid' => (string) Client uuid]. Required.
     *        'deliveryType'       => (string) see self::DELIVERY_TYPE_* constants. Required.
     *        'type'               => (string) see self::TYPE_* constants. Optional, default EXPRESS.
     *        'paidByRecipient'    => (bool) Optional.
     *        'senderAddressId'    => (int) Optional, defaults to the main address of the sender.
     *        'recipientAddressId' => (int) Optional, defaults to the main address of the recipient.
     *        'returnAddressId'    => (int) Optional.
     *        'parcels'            => [ Required.
     *            [
     *                'weight'        => (int) grams. Required.
     *                'length'        => (int) cm. Required. The larger side must be <= 120,
     *                'width'         => (int) cm. Optional.  the two smaller sides <= 70.
     *                'height'        => (int) cm. Optional.
     *                'declaredPrice' => (float) UAH. Optional.
     *            ],
     *            ...
     *        ],
     *        'postPay'            => (float) Cash on delivery amount. Optional.
     *        'description'        => (string) Optional.
     *        'externalId'         => (string) Identifier in your system. Optional.
     *        'onFailReceiveType'  => (string) see self::ON_FAIL_RECEIVE_TYPE_* constants. Optional.
     *        'sms'                => (bool) Optional.
     *        'fragile'            => (bool) Optional.
     *        'bulky'              => (bool) Optional.
     *    ];
     * @param bool $paidByCardOnline
     * @return array
     * @throws UkrposhtaApiException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function save(array $shipment, bool $paidByCardOnline = false): array
    {
        $query = $paidByCardOnline ? ['paidByCardOnline' => 'true'] : [];
        return $this->connection->post(self::URI, $shipment, $query);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/UpdateShipmentUsingPUT
     * @param string $uuid
     * @param array $shipment see self::save() for the structure
     * @return array
     * @throws UkrposhtaApiException
     */
    public function update(string $uuid, array $shipment): array
    {
        return $this->connection->put(self::URI . '/' . $uuid, $shipment);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/UpdateShipmentByBarcodeUsingPUT
     * @param string $barcode
     * @param array $shipment see self::save() for the structure
     * @return array
     * @throws UkrposhtaApiException
     */
    public function updateByBarcode(string $barcode, array $shipment): array
    {
        return $this->connection->put(self::URI . '/barcode/' . $barcode, $shipment);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/UpdateMultipleShipmentsUsingPUT
     * @param array $shipments list of shipments, each must contain `uuid`
     * @return array
     * @throws UkrposhtaApiException
     */
    public function updateMultiple(array $shipments): array
    {
        return $this->connection->put(self::URI, $shipments);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/GetShipmentUsingGET
     * @param string $uuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function get(string $uuid): array
    {
        return $this->connection->get(self::URI . '/' . $uuid);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/GetShipmentByBarcodeUsingGET
     * @param string $barcode
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getByBarcode(string $barcode): array
    {
        return $this->connection->get(self::URI . '/barcode/' . $barcode);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/GetShipmentsBySenderUuidUsingGET
     * @param string $senderUuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getBySender(string $senderUuid): array
    {
        return $this->connection->get(self::URI, ['senderUuid' => $senderUuid]);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/RemoveShipmentUsingDELETE
     * @param string $uuid
     * @return void
     * @throws UkrposhtaApiException
     */
    public function delete(string $uuid): void
    {
        $this->connection->delete(self::URI . '/' . $uuid);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/GetShipmentLifecycleMinifiedByIdentifierUsingGET
     * @param string $uuidOrBarcode
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getLifecycle(string $uuidOrBarcode): array
    {
        return $this->connection->get(self::URI . '/' . $uuidOrBarcode . '/lifecycle');
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/CreateNewParcelsInShipmentUsingPOST
     * @param string $uuid
     * @param array $parcels list of parcels, see self::save() for the structure.
     *                       At least one parcel of a multiple parcels shipment must have `declaredPrice`.
     * @return array
     * @throws UkrposhtaApiException
     */
    public function addParcels(string $uuid, array $parcels): array
    {
        return $this->connection->post(self::URI . '/' . $uuid . '/parcels', array_values($parcels));
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment/RemoveShipmentFromShipmentGroupUsingDELETE
     * @param string $uuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function removeFromGroup(string $uuid): array
    {
        return $this->connection->delete(self::URI . '/' . $uuid . '/shipment-group');
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Calculation%20of%20Shipment/Calculatedeliverypriceusingpost2
     * @see \SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Shipment\CalculateDeliveryPriceTest
     * @param array $params
     *    $params = [
     *        'addressFrom'   => ['postcode' => (string)]. Required.
     *        'addressTo'     => ['postcode' => (string)]. Required.
     *        'type'          => (string) see self::TYPE_* constants. Required.
     *        'deliveryType'  => (string) see self::DELIVERY_TYPE_* constants. Required.
     *        'parcels'       => [ Required for EXPRESS.
     *            ['weight' => (int) grams, 'length' => (int) cm, 'width' => (int), 'height' => (int), ...],
     *            ...
     *        ],
     *        'weight'        => (int) grams. Used when `parcels` is not given.
     *        'length'        => (int) cm. Used when `parcels` is not given.
     *        'declaredPrice' => (float) Optional.
     *        'postPay'       => (float) Optional.
     *        'recommended'   => (bool) Optional.
     *        'sms'           => (bool) Optional.
     *    ];
     * @return array
     * @throws UkrposhtaApiException
     */
    public function calculateDeliveryPrice(array $params): array
    {
        return $this->connection->post(self::DELIVERY_PRICE_URI, $params);
    }
}
