<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Models;

use RuntimeException;
use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\ModelInterface;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Class Forms
 *
 * Printable documents (stickers, form 103a). Every method returns the raw content of a PDF file.
 *
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Domestic%20Shipment%20Forms
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group%20Forms
 */
class Forms implements ModelInterface
{
    public const SIZE_10X10 = 'SIZE_10X10';
    public const SIZE_A6 = 'SIZE_A6';
    public const SIZE_A5 = 'SIZE_A5';
    public const SIZE_A4 = 'SIZE_A4';

    private const SIZES = [self::SIZE_10X10, self::SIZE_A6, self::SIZE_A5, self::SIZE_A4];

    private const SHIPMENTS_URI = 'shipments';
    private const SHIPMENT_GROUPS_URI = 'shipment-groups';

    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Domestic%20Shipment%20Forms/getShipmentStickerUsingGET_2
     * @param string $uuidOrBarcode
     * @param array $options
     *    $options = [
     *        'size'                    => (string) see self::SIZE_* constants. Optional.
     *        'hideDeliveryPrice'       => (bool) Optional.
     *        'hidePostpayDeliveryPrice'=> (bool) Optional.
     *        'hideWeight'              => (bool) Optional.
     *    ];
     * @return string PDF content
     * @throws UkrposhtaApiException
     */
    public function getShipmentSticker(string $uuidOrBarcode, array $options = []): string
    {
        $uri = self::SHIPMENTS_URI . '/' . $uuidOrBarcode . '/sticker';
        return $this->connection->getFile($uri, $this->buildStickerQuery($options));
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Domestic%20Shipment%20Forms/getStickerForParcelUsingGET
     * @param string $barcode parcel barcode
     * @param array $options see self::getShipmentSticker()
     * @return string PDF content
     * @throws UkrposhtaApiException
     */
    public function getParcelSticker(string $barcode, array $options = []): string
    {
        $uri = self::SHIPMENTS_URI . '/sticker/parcel/' . $barcode;
        return $this->connection->getFile($uri, $this->buildStickerQuery($options));
    }

    /**
     * Stickers for all shipments from the group
     *
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group%20Forms/getShipmentGroupStickerUsingGET_2
     * @param string $groupUuid
     * @param array $options see self::getShipmentSticker()
     * @return string PDF content
     * @throws UkrposhtaApiException
     */
    public function getShipmentGroupStickers(string $groupUuid, array $options = []): string
    {
        $uri = self::SHIPMENT_GROUPS_URI . '/' . $groupUuid . '/sticker';
        return $this->connection->getFile($uri, $this->buildStickerQuery($options));
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Shipment%20Group%20Forms/getShipmentGroupForm103AUsingGET_1
     * @param string $groupUuid
     * @param array $options
     *    $options = [
     *        'size'              => (string) see self::SIZE_* constants. Optional.
     *        'showSenderName'    => (bool) Optional.
     *        'hideDeclaredPrice' => (bool) Optional.
     *    ];
     * @return string PDF content
     * @throws UkrposhtaApiException
     */
    public function getShipmentGroupForm103a(string $groupUuid, array $options = []): string
    {
        $this->assertSize($options['size'] ?? null);
        $query = array_filter([
            'size' => $options['size'] ?? null,
            'showSenderName' => $this->boolToString($options['showSenderName'] ?? null),
            'hideDeclaredPrice' => $this->boolToString($options['hideDeclaredPrice'] ?? null),
        ]);
        return $this->connection->getFile(self::SHIPMENT_GROUPS_URI . '/' . $groupUuid . '/form103a', $query);
    }

    /**
     * @param array $options
     * @return array
     */
    private function buildStickerQuery(array $options): array
    {
        $this->assertSize($options['size'] ?? null);
        return array_filter([
            'size' => $options['size'] ?? null,
            'hideDeliveryPrice' => $this->boolToString($options['hideDeliveryPrice'] ?? null),
            'hidePostpayDeliveryPrice' => $this->boolToString($options['hidePostpayDeliveryPrice'] ?? null),
            'hideWeight' => $this->boolToString($options['hideWeight'] ?? null),
        ]);
    }

    /**
     * @param string|null $size
     * @return void
     */
    private function assertSize(?string $size): void
    {
        if ($size !== null && !in_array($size, self::SIZES, true)) {
            throw new RuntimeException(sprintf('Unknown size "%s", see %s::SIZE_* constants', $size, self::class));
        }
    }

    /**
     * @param bool|null $value
     * @return string|null
     */
    private function boolToString(?bool $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value ? 'true' : 'false';
    }
}
