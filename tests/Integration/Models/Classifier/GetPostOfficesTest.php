<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Classifier;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Classifier;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class GetPostOfficesTest extends TestCase
{
    use UsesConnectionTrait;

    private const KYIV_REGION_ID = '286';
    private const KYIV_CITY_ID = '29713';
    private const KHRESHCHATYK_POST_OFFICE_ID = '2700';

    private Classifier $model;

    protected function setUp(): void
    {
        $this->model = new Classifier($this->getConnection());
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetPostOfficesOfRegion(): void
    {
        $postOffices = $this->model->getPostOffices(['region_id' => self::KYIV_REGION_ID]);

        $this->assertNotEmpty($postOffices);
        $this->assertContains(self::KHRESHCHATYK_POST_OFFICE_ID, array_column($postOffices, 'ID'));
        foreach ($postOffices as $postOffice) {
            $this->assertSame(self::KYIV_REGION_ID, $postOffice['REGION_ID']);
            $this->assertArrayHasKey('PO_SHORT', $postOffice);
            $this->assertArrayHasKey('POSTINDEX', $postOffice);
            $this->assertArrayHasKey('CITY_ID', $postOffice);
            $this->assertArrayHasKey('LOCK_CODE', $postOffice);
        }
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetPostOfficesOfCityByPostindex(): void
    {
        $filters = ['region_id' => self::KYIV_REGION_ID, 'city_id' => self::KYIV_CITY_ID, 'postindex' => '01001'];
        $postOffices = $this->model->getPostOffices($filters);

        $this->assertCount(1, $postOffices);
        $this->assertSame(self::KHRESHCHATYK_POST_OFFICE_ID, $postOffices[0]['ID']);
        $this->assertSame('Київ 1', $postOffices[0]['PO_SHORT']);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetNearestPostOffices(): void
    {
        $postOffices = $this->model->getNearestPostOffices(50.450166, 30.523307, 1);

        $this->assertNotEmpty($postOffices);
        $this->assertContains(self::KHRESHCHATYK_POST_OFFICE_ID, array_column($postOffices, 'ID'));
        $this->assertArrayHasKey('DISTANCE', $postOffices[0]);
    }
}
