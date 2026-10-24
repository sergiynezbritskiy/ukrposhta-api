<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Client;
use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\Models\Address;
use SergiyNezbritskiy\Ukrposhta\Models\Client as ClientModel;
use SergiyNezbritskiy\Ukrposhta\Models\Forms;
use SergiyNezbritskiy\Ukrposhta\Models\Shipment;
use SergiyNezbritskiy\Ukrposhta\Models\ShipmentGroup;

/**
 * Class ClientTest
 * Unit test for \SergiyNezbritskiy\Ukrposhta\Client
 * @see Client
 */
class ClientTest extends TestCase
{
    private Client $object;

    protected function setUp(): void
    {
        $this->object = new Client('some-bearer', 'some-token', [Connection::OPTION_SANDBOX => true]);
    }

    public function testModelProperties(): void
    {
        $this->assertInstanceOf(Address::class, $this->object->address);
        $this->assertInstanceOf(ClientModel::class, $this->object->client);
        $this->assertInstanceOf(Shipment::class, $this->object->shipment);
        $this->assertInstanceOf(ShipmentGroup::class, $this->object->shipmentGroup);
        $this->assertInstanceOf(Forms::class, $this->object->forms);
    }
}
