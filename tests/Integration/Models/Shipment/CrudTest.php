<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Shipment;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Shipment;
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

    private Shipment $model;
    private string $senderUuid;

    protected function setUp(): void
    {
        $this->model = new Shipment($this->getConnection());
    }

    /**
     * @return void
     * @throws UkrposhtaApiException
     */
    protected function tearDown(): void
    {
        if (isset($this->senderUuid) && filter_var(getenv('CLEAR_SHIPMENTS'), FILTER_VALIDATE_BOOL)) {
            foreach ($this->model->getBySender($this->senderUuid) as $shipment) {
                $this->model->delete($shipment['uuid']);
            }
        }
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testCrudShipment(): void
    {
        $connection = $this->getConnection();
        $sender = $this->createSender($connection);
        $recipient = $this->createRecipient($connection);
        $this->senderUuid = $sender['uuid'];

        $params = $this->buildShipmentParams($sender['uuid'], $recipient['uuid']);
        $shipment = $this->model->save($params);
        $this->assertShipment($shipment, $sender['uuid'], $recipient['uuid']);
        $this->assertSame(1200, $shipment['weight']);

        $actualResult = $this->model->get($shipment['uuid']);
        $this->assertSame($shipment['uuid'], $actualResult['uuid']);

        $actualResult = $this->model->getByBarcode($shipment['barcode']);
        $this->assertSame($shipment['uuid'], $actualResult['uuid']);

        $actualResult = $this->model->getBySender($sender['uuid']);
        $this->assertContains($shipment['uuid'], array_column($actualResult, 'uuid'));

        $this->gulp();

        $params['parcels'][0]['weight'] = 2000;
        $params['parcels'][0]['uuid'] = $shipment['parcels'][0]['uuid'];
        $actualResult = $this->model->update($shipment['uuid'], $params);
        $this->assertSame(2000, $actualResult['weight']);

        $params['parcels'][0]['weight'] = 1500;
        $actualResult = $this->model->updateByBarcode($shipment['barcode'], $params);
        $this->assertSame(1500, $actualResult['weight']);

        $params['uuid'] = $shipment['uuid'];
        $params['description'] = self::SHIPMENT_DESCRIPTION . ' (updated)';
        $actualResult = $this->model->updateMultiple([$params]);
        $this->assertNotEmpty($actualResult);

        $actualResult = $this->model->addParcels($shipment['uuid'], [
            ['weight' => 500, 'length' => 20, 'width' => 10, 'height' => 10, 'declaredPrice' => 100],
        ]);
        $this->assertCount(2, $actualResult['parcels']);

        $actualResult = $this->model->getLifecycle($shipment['uuid']);
        $this->assertEntityHasProperties($actualResult, ['status', 'statusDate']);

        $this->gulp();

        $this->model->delete($shipment['uuid']);
        $this->assertShipmentDeleted($shipment['uuid']);
    }

    /**
     * @param string $uuid
     * @return void
     * @throws UkrposhtaApiException
     */
    private function assertShipmentDeleted(string $uuid): void
    {
        try {
            $shipment = $this->model->get($uuid);
        } catch (UkrposhtaApiException $e) {
            $this->assertSame(404, $e->getCode());
            return;
        }
        $this->assertTrue($shipment['deleted'] ?? false, 'Failed to delete shipment.');
    }

    /**
     * @param array $shipment
     * @param string $senderUuid
     * @param string $recipientUuid
     * @return void
     */
    private function assertShipment(array $shipment, string $senderUuid, string $recipientUuid): void
    {
        $this->assertEntityHasProperties($shipment, [
            'uuid',
            'barcode',
            'type',
            'sender',
            'recipient',
            'deliveryType',
            'weight',
            'length',
            'deliveryPrice',
            'parcels',
            'lifecycle',
        ]);
        $this->assertSame($senderUuid, $shipment['sender']['uuid']);
        $this->assertSame($recipientUuid, $shipment['recipient']['uuid']);
        $this->assertSame(Shipment::DELIVERY_TYPE_W2D, $shipment['deliveryType']);
        $this->assertSame(Shipment::TYPE_EXPRESS, $shipment['type']);
        $this->assertTrue($shipment['paidByRecipient']);
        $this->assertCount(1, $shipment['parcels']);
    }
}
