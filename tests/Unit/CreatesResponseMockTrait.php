<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Unit;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

trait CreatesResponseMockTrait
{
    /**
     * Provided by \PHPUnit\Framework\TestCase
     *
     * @param string $originalClassName
     * @return MockObject
     */
    abstract protected function createMock(string $originalClassName): MockObject;

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
