<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * Class ConnectionTest
 * Unit tests for class \SergiyNezbritskiy\Ukrposhta\Connection
 * @see Connection
 */
class ConnectionTest extends TestCase
{
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

    public function testException(): void
    {
        $exceptionMock = new TransferException('Test');
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->method('request')->willThrowException($exceptionMock);
        $this->expectException(UkrposhtaApiException::class);
        $this->expectExceptionMessage('Connection to Ukrposhta API failed: Test');
        $this->object->get('uri');
    }

    /**
     * @throws Exception
     */
    public function testApiError(): void
    {
        $contentJson = json_encode(['code' => 'UPE02001', 'message' => 'Client 123 not found!']);
        $response = $this->createResponse($contentJson, 404);
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->method('request')->willReturn($response);

        try {
            $this->object->get('uri');
            $this->fail('Exception expected');
        } catch (UkrposhtaApiException $e) {
            $this->assertSame('Connection to Ukrposhta API failed: Client 123 not found!', $e->getMessage());
            $this->assertSame(404, $e->getCode());
            $this->assertSame('UPE02001', $e->getApiErrorCode());
        }
    }

    /**
     * @throws Exception
     */
    public function testUnauthorized(): void
    {
        $contentJson = json_encode([
            'error' => 'unauthorized',
            'error_description' => 'Unauthorized - invalid or missing token',
        ]);
        $response = $this->createResponse($contentJson, 401);
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->method('request')->willReturn($response);

        try {
            $this->object->post('uri', ['key' => 'value']);
            $this->fail('Exception expected');
        } catch (UkrposhtaApiException $e) {
            $this->assertSame(
                'Connection to Ukrposhta API failed: Unauthorized - invalid or missing token',
                $e->getMessage()
            );
            $this->assertSame(401, $e->getCode());
            $this->assertNull($e->getApiErrorCode());
        }
    }

    /**
     * @throws Exception
     */
    public function testErrorWithoutBody(): void
    {
        $response = $this->createResponse('', 500, 'Internal Server Error');
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->method('request')->willReturn($response);

        $this->expectException(UkrposhtaApiException::class);
        $this->expectExceptionMessage('Connection to Ukrposhta API failed: Internal Server Error');

        $this->object->put('uri', []);
    }

    /**
     * @throws Exception
     */
    public function testInvalidBody(): void
    {
        $response = $this->createResponse('NotAJson');
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->method('request')->willReturn($response);

        $this->expectException(UkrposhtaApiException::class);
        $this->expectExceptionMessage('Invalid response from Ukrposhta API');

        $this->object->get('uri');
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testEmptyBody(): void
    {
        $response = $this->createResponse('');
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->method('request')->willReturn($response);

        $this->assertSame([], $this->object->delete('uri'));
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testSuccess(): void
    {
        $response = $this->createResponse(json_encode(['uuid' => '123']));
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.ukrposhta.ua/ecom/0.0.1/shipments', $this->anything())
            ->willReturn($response);

        $this->assertSame(['uuid' => '123'], $this->object->post('shipments', ['weight' => 1]));
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testGetFile(): void
    {
        $response = $this->createResponse('%PDF-1.4');
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://www.ukrposhta.ua/forms/ecom/0.0.1/shipments/123/sticker', $this->anything())
            ->willReturn($response);

        $this->assertSame('%PDF-1.4', $this->object->getFile('shipments/123/sticker'));
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testSandbox(): void
    {
        $options = [Connection::OPTION_SANDBOX => true];
        $connection = new Connection(self::BEARER, self::TOKEN, $this->clientMock, $options);
        $response = $this->createResponse('[]');
        /** @scrutinizer ignore-deprecated */
        $this->clientMock->expects($this->exactly(2))
            ->method('request')
            ->with(
                'GET',
                $this->logicalOr(
                    'https://dev.ukrposhta.ua/ecom/0.0.1/banks',
                    'https://dev.ukrposhta.ua/forms/ecom/0.0.1/shipments/123/sticker'
                ),
                $this->anything()
            )
            ->willReturn($response);

        $connection->get('banks');
        $connection->getFile('shipments/123/sticker');
    }

    public function testBuildOptions(): void
    {
        $connection = new Connection(self::BEARER, self::TOKEN, $this->clientMock, [
            RequestOptions::TIMEOUT => 5,
            RequestOptions::HTTP_ERRORS => true,
            Connection::OPTION_SANDBOX => false,
        ]);

        $options = $connection->buildOptions(['page' => 1, 'empty' => null], ['weight' => 1]);

        $this->assertSame(5, $options[RequestOptions::TIMEOUT]);
        $this->assertFalse($options[RequestOptions::HTTP_ERRORS]);
        $this->assertArrayNotHasKey(Connection::OPTION_SANDBOX, $options);
        $this->assertSame(['page' => 1, 'token' => self::TOKEN], $options[RequestOptions::QUERY]);
        $this->assertSame('Bearer ' . self::BEARER, $options[RequestOptions::HEADERS]['Authorization']);
        $this->assertSame('application/json', $options[RequestOptions::HEADERS]['Content-Type']);
        $this->assertSame('{"weight":1}', $options[RequestOptions::BODY]);

        //without body
        $options = $this->object->buildOptions([]);
        $this->assertArrayNotHasKey(RequestOptions::BODY, $options);
        $this->assertArrayNotHasKey('Content-Type', $options[RequestOptions::HEADERS]);
        $this->assertSame(['token' => self::TOKEN], $options[RequestOptions::QUERY]);
    }

    /**
     * @param string $content
     * @param int $statusCode
     * @param string $reasonPhrase
     * @return ResponseInterface|MockObject
     * @throws Exception
     */
    private function createResponse(
        string $content,
        int $statusCode = 200,
        string $reasonPhrase = ''
    ): ResponseInterface|MockObject {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getReasonPhrase')->willReturn($reasonPhrase);
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willReturn($content);
        $response->method('getBody')->willReturn($bodyMock);
        return $response;
    }
}
