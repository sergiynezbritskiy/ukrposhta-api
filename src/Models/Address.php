<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Models;

use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\ModelInterface;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Class Address
 *
 * Addresses are shared entities: an address is created once and then attached to clients by its `id`.
 *
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Address
 */
class Address implements ModelInterface
{
    private const URI = 'addresses';
    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Address/CreateAddressUsingPOST
     * @param array $address
     *    $address = [
     *        'postcode'        => (string) Postcode, e.g. '07401'. Required.
     *        'country'         => (string) ISO 3166 country code, e.g. 'UA'. Optional.
     *        'region'          => (string) Region, e.g. 'Київська'. Optional.
     *        'district'        => (string) District, e.g. 'Київський'. Optional.
     *        'city'            => (string) City, e.g. 'Бровари'. Optional.
     *        'street'          => (string) Street. Optional.
     *        'houseNumber'     => (string) House number. Optional.
     *        'apartmentNumber' => (string) Apartment number. Optional.
     *        'description'     => (string) Free text note. Optional.
     *        'countryside'     => (bool) Optional.
     *    ];
     * @return array
     * @throws UkrposhtaApiException
     */
    public function save(array $address): array
    {
        return $this->connection->post(self::URI, $address);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Address/GetAddressUsingGET
     * @param int $addressId
     * @return array
     * @throws UkrposhtaApiException
     */
    public function get(int $addressId): array
    {
        return $this->connection->get(self::URI . '/' . $addressId);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Address/GetAddressWithCountryNameInLatinUsingGET
     * @param int $addressId
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getWithCountryNameInLatin(int $addressId): array
    {
        return $this->connection->get(self::URI . '/' . $addressId . '/countryNameInLatin');
    }
}
