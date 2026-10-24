<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\ShipmentGroup;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Shipment;
use SergiyNezbritskiy\Ukrposhta\Models\ShipmentGroup;
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

    private ShipmentGroup $model;
    private Shipment $shipmentModel;

    protected function setUp(): void
    {
        $this->model = new ShipmentGroup($this->getConnection());
        $this->shipmentModel = new Shipment($this->getConnection());
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testCrudShipmentGroup(): void
    {
        $connection = $this->getConnection();
        $sender = $this->createSender($connection);
        $recipient = $this->createRecipient($connection);

        $groupName = 'Test group ' . date('Y-m-d H:i:s');
        $group = $this->model->save([
            'name' => $groupName,
            'clientUuid' => $sender['uuid'],
            'type' => ShipmentGroup::TYPE_EXPRESS,
        ]);
        $this->assertEntityHasProperties($group, ['uuid', 'name', 'clientUuid', 'type', 'closed']);
        $this->assertSame($groupName, $group['name']);
        $this->assertSame(ShipmentGroup::TYPE_EXPRESS, $group['type']);

        $actualResult = $this->model->get($group['uuid']);
        $this->assertSame($group['uuid'], $actualResult['uuid']);

        $updatedName = $groupName . ' (updated)';
        $actualResult = $this->model->update($group['uuid'], ['name' => $updatedName]);
        $this->assertSame($updatedName, $actualResult['name']);

        $actualResult = $this->model->getByClient($sender['uuid'], ['name' => $updatedName]);
        $this->assertContains($group['uuid'], array_column($actualResult, 'uuid'));

        $this->performShipmentsCheck($group['uuid'], $sender['uuid'], $recipient['uuid']);
    }

    /**
     * @param string $groupUuid
     * @param string $senderUuid
     * @param string $recipientUuid
     * @return void
     * @throws UkrposhtaApiException
     */
    private function performShipmentsCheck(string $groupUuid, string $senderUuid, string $recipientUuid): void
    {
        $params = $this->buildShipmentParams($senderUuid, $recipientUuid);
        //note: the sandbox does not fill `shipmentGroupUuid` of the shipment, so membership is asserted via the group
        $shipmentInGroup = $this->model->saveShipment($groupUuid, $params);
        $this->assertNotEmpty($shipmentInGroup['uuid']);

        $shipment = $this->shipmentModel->save($params);
        $this->model->addShipment($groupUuid, $shipment['uuid']);

        $anotherShipment = $this->shipmentModel->save($params);
        $this->model->addShipments($groupUuid, [$anotherShipment['uuid']]);

        $this->gulp();

        $actualResult = $this->model->getShipments($groupUuid);
        $uuids = array_column($actualResult, 'uuid');
        $this->assertContains($shipmentInGroup['uuid'], $uuids);
        $this->assertContains($shipment['uuid'], $uuids);
        $this->assertContains($anotherShipment['uuid'], $uuids);
        $this->assertSame(3, $this->model->getShipmentsCount($groupUuid));

        $actualResult = $this->shipmentModel->removeFromGroup($shipment['uuid']);
        $this->assertSame($shipment['uuid'], $actualResult['uuid']);

        $this->gulp();

        //note: `shipments-count` of the sandbox keeps counting the removed shipment, so only the list is asserted
        $uuids = array_column($this->model->getShipments($groupUuid), 'uuid');
        $this->assertNotContains($shipment['uuid'], $uuids);

        foreach ([$shipmentInGroup, $shipment, $anotherShipment] as $toDelete) {
            $this->shipmentModel->delete($toDelete['uuid']);
        }
    }
}
