<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Unit\Models\Classifier;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Connection;
use SergiyNezbritskiy\Ukrposhta\Models\Classifier;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class ClassifierTest extends TestCase
{
    private const KYIV = ['REGION_ID' => '286', 'REGION_UA' => 'Київ', 'REGION_EN' => 'Kyiv'];
    private const KYIVSKA = ['REGION_ID' => '270', 'REGION_UA' => 'Київська', 'REGION_EN' => 'Kyivska'];
    private const POST_OFFICE = ['ID' => '266', 'PO_SHORT' => 'Київ 26', 'POSTINDEX' => '03026', 'LOCK_CODE' => '0'];

    private Classifier $model;
    /** mock of the Connection */
    private MockObject $connection;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->connection = $connection;
        $this->model = new Classifier($connection);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetRegionsByUkrainianName(): void
    {
        $this->connection->expects($this->once())
            ->method('getClassifier')
            ->with('get_regions_by_region_ua', ['region_name' => 'Київ'])
            ->willReturn(['Entries' => ['Entry' => [self::KYIV, self::KYIVSKA]]]);

        $this->assertSame([self::KYIV, self::KYIVSKA], $this->model->getRegions(['region_name' => 'Київ']));
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetRegionsByEnglishName(): void
    {
        $this->connection->expects($this->once())
            ->method('getClassifier')
            ->with('get_regions_by_region_ua', ['region_name_en' => 'Kyiv'])
            ->willReturn(['Entries' => ['Entry' => [self::KYIV]]]);

        $this->assertSame([self::KYIV], $this->model->getRegions(['region_name_en' => 'Kyiv']));
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetRegionsRequiresFilter(): void
    {
        $this->connection->expects($this->never())->method('getClassifier');
        $this->expectException(UkrposhtaApiException::class);
        $this->expectExceptionMessage('Either region_name or region_name_en filter is required');

        $this->model->getRegions(['region_name' => '']);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetPostOffices(): void
    {
        $filters = ['region_id' => 286, 'district_id' => '412', 'city_id' => '29713', 'postindex' => '03026'];
        $this->connection->expects($this->once())
            ->method('getClassifier')
            ->with('get_postoffices_by_city_id', $filters)
            ->willReturn(['Entries' => ['Entry' => [self::POST_OFFICE]]]);

        $this->assertSame([self::POST_OFFICE], $this->model->getPostOffices($filters));
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetPostOfficesRequiresRegion(): void
    {
        $this->connection->expects($this->never())->method('getClassifier');
        $this->expectException(UkrposhtaApiException::class);
        $this->expectExceptionMessage('The region_id filter is required');

        $this->model->getPostOffices(['city_id' => '29713']);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetNearestPostOffices(): void
    {
        $entry = ['ID' => '2700', 'POSTFILIALNAME' => 'Київ 1', 'DISTANCE' => '0'];
        $this->connection->expects($this->once())
            ->method('getClassifier')
            ->with('get_postoffices_by_geolocation', ['lat' => 50.450166, 'long' => 30.523307, 'maxdistance' => 5])
            ->willReturn(['Entries' => ['Entry' => [$entry]]]);

        $this->assertSame([$entry], $this->model->getNearestPostOffices(50.450166, 30.523307, 5));
    }

    /**
     * @throws Exception
     * @throws UkrposhtaApiException
     */
    public function testUnwrap(): void
    {
        $cases = [
            'list' => [['Entries' => ['Entry' => [self::POST_OFFICE, self::KYIV]]], [self::POST_OFFICE, self::KYIV]],
            'single entry as object' => [['Entries' => ['Entry' => self::POST_OFFICE]], [self::POST_OFFICE]],
            'empty list' => [['Entries' => ['Entry' => []]], []],
            'null entry' => [['Entries' => ['Entry' => null]], []],
            'scalar entry' => [['Entries' => ['Entry' => 'oops']], []],
            'no entries' => [['Entries' => null], []],
            'empty response' => [[], []],
        ];
        foreach ($cases as $case => [$response, $expected]) {
            $connection = $this->createMock(Connection::class);
            $connection->method('getClassifier')->willReturn($response);
            $model = new Classifier($connection);

            $this->assertSame($expected, $model->getPostOffices(['region_id' => '286']), $case);
        }
    }

    public function testLockCodes(): void
    {
        $this->assertSame('0', Classifier::LOCK_CODE_ACTIVE);
        $this->assertSame('65535', Classifier::LOCK_CODE_BLOCKED);
    }
}
