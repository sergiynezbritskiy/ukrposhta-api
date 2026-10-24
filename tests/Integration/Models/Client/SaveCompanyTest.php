<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Client;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Client;
use SergiyNezbritskiy\Ukrposhta\Tests\ConstantsInterface;
use SergiyNezbritskiy\Ukrposhta\Tests\CreatesFixturesTrait;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class SaveCompanyTest extends TestCase implements ConstantsInterface
{
    use CreatesFixturesTrait;
    use UsesConnectionTrait;

    private Client $model;

    protected function setUp(): void
    {
        $this->model = new Client($this->getConnection());
    }

    /**
     * @return void
     * @throws UkrposhtaApiException
     */
    public function testSaveCompany(): void
    {
        //no dots allowed, the API treats them as a file extension in the path
        $externalId = uniqid('company-');
        $company = self::COMPANY;
        $company['type'] = Client::TYPE_COMPANY;
        $company['addressId'] = $this->createAddress($this->getConnection(), self::SENDER_ADDRESS);
        $company['externalId'] = $externalId;

        $actualResult = $this->model->save($company);
        $this->assertNotEmpty($actualResult['uuid']);
        $this->assertSame(Client::TYPE_COMPANY, $actualResult['type']);
        $this->assertSame(self::COMPANY['edrpou'], $actualResult['edrpou']);
        $this->assertStringContainsString(self::COMPANY['name'], $actualResult['name']);

        $actualResult = $this->model->getByExternalId($externalId);
        $this->assertSame($externalId, $actualResult['externalId']);
    }
}
