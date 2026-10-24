<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta;

use GuzzleHttp\Client as HttpClient;
use SergiyNezbritskiy\Ukrposhta\Models\Address;
use SergiyNezbritskiy\Ukrposhta\Models\Client as ClientModel;
use SergiyNezbritskiy\Ukrposhta\Models\Forms;
use SergiyNezbritskiy\Ukrposhta\Models\Shipment;
use SergiyNezbritskiy\Ukrposhta\Models\ShipmentGroup;

/**
 * Class Client
 *
 * Class-connector with Ukrposhta API
 *
 * @see      https://dev.ukrposhta.ua/documentation
 * @see      https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0
 */
readonly class Client
{
    public Address $address;
    public ClientModel $client;
    public Shipment $shipment;
    public ShipmentGroup $shipmentGroup;
    public Forms $forms;

    /**
     * @param string $bearer Bearer eCom, issued by Ukrposhta
     * @param string $token Counterparty token, issued by Ukrposhta
     * @param array $options Guzzle request options, plus `sandbox` => true to work with the sandbox API
     */
    public function __construct(string $bearer, string $token, array $options = [])
    {
        $connection = new Connection($bearer, $token, new HttpClient(), $options);
        $this->address = new Address($connection);
        $this->client = new ClientModel($connection);
        $this->shipment = new Shipment($connection);
        $this->shipmentGroup = new ShipmentGroup($connection);
        $this->forms = new Forms($connection);
    }
}
