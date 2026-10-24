<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Shipment;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Shipment;
use SergiyNezbritskiy\Ukrposhta\Tests\AssertEntityByPropertiesTrait;
use SergiyNezbritskiy\Ukrposhta\Tests\ConstantsInterface;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class CalculateDeliveryPriceTest extends TestCase implements ConstantsInterface
{
    use AssertEntityByPropertiesTrait;
    use UsesConnectionTrait;

    private Shipment $model;

    protected function setUp(): void
    {
        $this->model = new Shipment($this->getConnection());
    }

    /**
     * @return void
     * @throws UkrposhtaApiException
     */
    public function testCalculateDeliveryPrice(): void
    {
        $actualResult = $this->model->calculateDeliveryPrice([
            'addressFrom' => ['postcode' => self::SENDER_ADDRESS['postcode']],
            'addressTo' => ['postcode' => self::RECIPIENT_ADDRESS['postcode']],
            'type' => Shipment::TYPE_EXPRESS,
            'deliveryType' => Shipment::DELIVERY_TYPE_W2W,
            'parcels' => [
                [
                    'weight' => 1200,
                    'length' => 30,
                    'width' => 20,
                    'height' => 10,
                    'declaredPrice' => 500,
                ],
            ],
        ]);

        $expectedProperties = ['deliveryPrice', 'rawDeliveryPrice', 'calculationDescription'];
        $this->assertEntityHasProperties($actualResult, $expectedProperties);
        $this->assertGreaterThan(0, $actualResult['deliveryPrice']);
    }
}
