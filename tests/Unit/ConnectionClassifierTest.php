<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Address classifier requests of the connection
 */
class ConnectionClassifierTest extends TestCase
{
    use CreatesResponseMockTrait;

    private const BEARER = 'bearer-uuid';
    private const TOKEN = 'token-uuid';

    private Connection $object;
    private Client|MockObject $clientMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->object = new Connection(self::BEARER, self::TOKEN, $this->clientMock);
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testGetClassifier(): void
    {
        $response = $this->createResponse(json_encode(['Entries' => ['Entry' => []]]));
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://www.ukrposhta.ua/address-classifier-ws/get_regions_by_region_ua',
                $this->callback(function (array $options): bool {
                    $this->assertSame(['region_name' => 'Київ'], $options[RequestOptions::QUERY]);
                    $this->assertSame('application/json', $options[RequestOptions::HEADERS]['Accept']);
                    $this->assertSame('Bearer ' . self::BEARER, $options[RequestOptions::HEADERS]['Authorization']);
                    return true;
                })
            )
            ->willReturn($response);

        $actualResult = $this->object->getClassifier('get_regions_by_region_ua', ['region_name' => 'Київ']);
        $this->assertSame(['Entries' => ['Entry' => []]], $actualResult);
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testClassifierSandbox(): void
    {
        $options = [Connection::OPTION_SANDBOX => true];
        $connection = new Connection(self::BEARER, self::TOKEN, $this->clientMock, $options);
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://dev.ukrposhta.ua/address-classifier-ws/get_postoffices_by_city_id',
                $this->anything()
            )
            ->willReturn($this->createResponse('[]'));

        $connection->getClassifier('get_postoffices_by_city_id', ['region_id' => '389']);
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testClassifierUriOverride(): void
    {
        $options = [
            Connection::OPTION_SANDBOX => true,
            Connection::OPTION_CLASSIFIER_URI => 'https://classifier.example.com/',
        ];
        $connection = new Connection(self::BEARER, self::TOKEN, $this->clientMock, $options);
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://classifier.example.com/get_regions_by_region_ua',
                $this->callback(fn(array $options): bool => !isset($options[Connection::OPTION_CLASSIFIER_URI]))
            )
            ->willReturn($this->createResponse('[]'));

        $connection->getClassifier('get_regions_by_region_ua', ['region_name' => 'Київ']);
    }

    public function testBuildClassifierOptions(): void
    {
        $connection = new Connection(self::BEARER, self::TOKEN, $this->clientMock, [RequestOptions::TIMEOUT => 90]);

        $options = $connection->buildClassifierOptions(['region_id' => '286', 'city_id' => null]);

        $this->assertSame(90, $options[RequestOptions::TIMEOUT]);
        $this->assertSame(['region_id' => '286'], $options[RequestOptions::QUERY]);
        $this->assertSame('application/json', $options[RequestOptions::HEADERS]['Accept']);
        $this->assertSame('Bearer ' . self::BEARER, $options[RequestOptions::HEADERS]['Authorization']);
        $this->assertArrayNotHasKey(RequestOptions::BODY, $options);
        $this->assertArrayNotHasKey('Content-Type', $options[RequestOptions::HEADERS]);
    }
}
