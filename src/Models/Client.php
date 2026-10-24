<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Models;

use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\ModelInterface;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Class Client
 *
 * Clients are senders and recipients of shipments. Both are created with the same request.
 *
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client
 */
class Client implements ModelInterface
{
    public const TYPE_INDIVIDUAL = 'INDIVIDUAL';
    public const TYPE_COMPANY = 'COMPANY';
    public const TYPE_PRIVATE_ENTREPRENEUR = 'PRIVATE_ENTREPRENEUR';

    public const POSTPAY_PAYMENT_CASH_ONLY = 'POSTPAY_PAYMENT_CASH_ONLY';
    public const POSTPAY_PAYMENT_CASH_AND_CASHLESS = 'POSTPAY_PAYMENT_CASH_AND_CASHLESS';

    private const URI = 'clients';
    private const ADDRESSES_URI = 'client-addresses';
    private const PHONES_URI = 'client-phones';
    private const EMAILS_URI = 'client-emails';

    private Connection $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client/CreateClientsUsingPOST
     * @param array $client
     *    $client = [
     *        'type'                     => (string) see self::TYPE_* constants. Required.
     *        'addressId'                => (int)    Address identifier, see Address::save(). Required.
     *        'phoneNumber'              => (string) Phone number, e.g. '0671231234'. Required.
     *        'firstName'                => (string) Required for individuals.
     *        'middleName'               => (string) Optional.
     *        'lastName'                 => (string) Required for individuals.
     *        'name'                     => (string) Company name. Required for companies.
     *        'edrpou'                   => (string) Required for companies.
     *        'tin'                      => (string) Tax identification number. Optional.
     *        'uniqueRegistrationNumber' => (string) Optional.
     *        'email'                    => (string) Optional.
     *        'bankAccount'              => (string) IBAN. Optional.
     *        'externalId'               => (string) Identifier in your system. Optional.
     *        'resident'                 => (bool)   Optional.
     *        'postPayPaymentType'       => (string) see self::POSTPAY_PAYMENT_* constants. Optional.
     *    ];
     * @return array
     * @throws UkrposhtaApiException
     */
    public function save(array $client): array
    {
        return $this->connection->post(self::URI, $client);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client/UpdateClientUsingPUT
     * @param string $uuid
     * @param array $client see self::save() for the structure
     * @return array
     * @throws UkrposhtaApiException
     */
    public function update(string $uuid, array $client): array
    {
        return $this->connection->put(self::URI . '/' . $uuid, $client);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client/GetClientUsingGET
     * @param string $uuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function get(string $uuid): array
    {
        return $this->connection->get(self::URI . '/' . $uuid);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client/GetClientByExternalIdUsingGET
     * @param string $externalId
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getByExternalId(string $externalId): array
    {
        return $this->connection->get(self::URI . '/external-id/' . $externalId);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client/GetClientsByPhoneUsingGET
     * @param string $phoneNumber
     * @param string $countryIso3166
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getByPhone(string $phoneNumber, string $countryIso3166 = 'UA'): array
    {
        return $this->connection->get(self::URI . '/phone', [
            'phoneNumber' => $phoneNumber,
            'countryISO3166' => $countryIso3166,
        ]);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client's%20Address/GetAllClientAddressesByClientUuidUsingGET
     * @param string $clientUuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getAddresses(string $clientUuid): array
    {
        return $this->connection->get(self::ADDRESSES_URI, ['clientUuid' => $clientUuid]);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client's%20Address/RemoveClientAddressUsingDELETE
     * @param string $uuid uuid of the client address (not the address id)
     * @return void
     * @throws UkrposhtaApiException
     */
    public function deleteAddress(string $uuid): void
    {
        $this->connection->delete(self::ADDRESSES_URI . '/' . $uuid);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client's%20Phone/GetAllClientPhonesByClientUuidUsingGET
     * @param string $clientUuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getPhones(string $clientUuid): array
    {
        return $this->connection->get(self::PHONES_URI, ['clientUuid' => $clientUuid]);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client's%20Phone/RemoveClientPhoneUsingDELETE
     * @param string $uuid uuid of the client phone
     * @return void
     * @throws UkrposhtaApiException
     */
    public function deletePhone(string $uuid): void
    {
        $this->connection->delete(self::PHONES_URI . '/' . $uuid);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client's%20email/GetAllClientEmailsByClientUuidUsingGET
     * @param string $clientUuid
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getEmails(string $clientUuid): array
    {
        return $this->connection->get(self::EMAILS_URI, ['clientUuid' => $clientUuid]);
    }

    /**
     * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0#/Client's%20email/RemoveClientEmailUsingDELETE
     * @param string $uuid uuid of the client email
     * @return void
     * @throws UkrposhtaApiException
     */
    public function deleteEmail(string $uuid): void
    {
        $this->connection->delete(self::EMAILS_URI . '/' . $uuid);
    }
}
