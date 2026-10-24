<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Unit\Models\Forms;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\Models\Forms;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class SizeValidationTest extends TestCase
{
    private Forms $model;

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getFile')->willReturn('%PDF');
        $this->model = new Forms($connection);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testStickerInvalidSize(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown size "A3"');
        $this->model->getShipmentSticker('123', ['size' => 'A3']);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testForm103aInvalidSize(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown size "A3"');
        $this->model->getShipmentGroupForm103a('123', ['size' => 'A3']);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testValidSize(): void
    {
        $this->assertSame('%PDF', $this->model->getShipmentSticker('123', ['size' => Forms::SIZE_A4]));
        $this->assertSame('%PDF', $this->model->getParcelSticker('123', ['hideWeight' => true]));
        $this->assertSame('%PDF', $this->model->getShipmentGroupStickers('123'));
        $this->assertSame('%PDF', $this->model->getShipmentGroupForm103a('123', ['showSenderName' => false]));
    }
}
