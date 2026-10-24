<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Forms;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Forms;
use SergiyNezbritskiy\Ukrposhta\Models\Shipment;
use SergiyNezbritskiy\Ukrposhta\Models\ShipmentGroup;
use SergiyNezbritskiy\Ukrposhta\Tests\ConstantsInterface;
use SergiyNezbritskiy\Ukrposhta\Tests\CreatesFixturesTrait;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class StickerTest extends TestCase implements ConstantsInterface
{
    use CreatesFixturesTrait;
    use UsesConnectionTrait;

    private Forms $model;

    protected function setUp(): void
    {
        $this->model = new Forms($this->getConnection());
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testStickers(): void
    {
        $connection = $this->getConnection();
        $sender = $this->createSender($connection);
        $recipient = $this->createRecipient($connection);

        $group = (new ShipmentGroup($connection))->save([
            'name' => 'Test stickers group',
            'clientUuid' => $sender['uuid'],
            'type' => ShipmentGroup::TYPE_EXPRESS,
        ]);
        $params = $this->buildShipmentParams($sender['uuid'], $recipient['uuid']);
        $shipment = (new ShipmentGroup($connection))->saveShipment($group['uuid'], $params);

        $this->assertPdf($this->model->getShipmentSticker($shipment['uuid']));
        $this->assertPdf($this->model->getShipmentSticker($shipment['barcode'], [
            'size' => Forms::SIZE_A6,
            'hideWeight' => true,
        ]));
        $this->assertPdf($this->model->getParcelSticker($shipment['parcels'][0]['barcode']));
        $this->assertPdf($this->model->getShipmentGroupStickers($group['uuid']));
        $this->assertPdf($this->model->getShipmentGroupForm103a($group['uuid'], ['showSenderName' => true]));

        (new Shipment($connection))->delete($shipment['uuid']);
    }

    /**
     * @param string $content
     * @return void
     */
    private function assertPdf(string $content): void
    {
        $this->assertStringStartsWith('%PDF', $content);
    }
}
