<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Client;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Client;
use SergiyNezbritskiy\Ukrposhta\Tests\ConstantsInterface;
use SergiyNezbritskiy\Ukrposhta\Tests\CreatesFixturesTrait;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class GetByPhoneTest extends TestCase implements ConstantsInterface
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
    public function testGetByPhone(): void
    {
        //a unique phone number keeps the lookup fast, searching by a popular number takes ~20 sec on the sandbox
        $phoneNumber = sprintf('050%07d', random_int(0, 9999999));
        $sender = self::SENDER;
        $sender['phoneNumber'] = $phoneNumber;
        $client = $this->createIndividual($this->getConnection(), $sender, self::SENDER_ADDRESS);

        $actualResult = $this->model->getByPhone($phoneNumber);
        $this->assertNotEmpty($actualResult);
        $uuids = array_column($actualResult, 'uuid');
        $this->assertContains($client['uuid'], $uuids);
    }
}
