<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Address;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Address;
use SergiyNezbritskiy\Ukrposhta\Tests\AssertEntityByPropertiesTrait;
use SergiyNezbritskiy\Ukrposhta\Tests\ConstantsInterface;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class CrudTest extends TestCase implements ConstantsInterface
{
    use AssertEntityByPropertiesTrait;
    use UsesConnectionTrait;

    private Address $model;

    protected function setUp(): void
    {
        $this->model = new Address($this->getConnection());
    }

    /**
     * @return void
     * @throws UkrposhtaApiException
     */
    public function testAddressCrud(): void
    {
        $address = $this->model->save(self::SENDER_ADDRESS);
        $this->assertAddress($address);

        $actualResult = $this->model->get((int)$address['id']);
        $this->assertSame($address['id'], $actualResult['id']);
        $this->assertAddress($actualResult);

        $actualResult = $this->model->getWithCountryNameInLatin((int)$address['id']);
        $this->assertSame($address['id'], $actualResult['id']);
    }

    /**
     * @param array $address
     * @return void
     */
    private function assertAddress(array $address): void
    {
        $this->assertEntityHasProperties($address, ['id', 'postcode', 'region', 'city', 'street', 'detailedInfo']);
        $this->assertSame(self::SENDER_ADDRESS['postcode'], $address['postcode']);
        $this->assertSame(self::SENDER_ADDRESS['city'], $address['city']);
        $this->assertStringContainsString(self::SENDER_ADDRESS['street'], $address['detailedInfo']);
    }
}
