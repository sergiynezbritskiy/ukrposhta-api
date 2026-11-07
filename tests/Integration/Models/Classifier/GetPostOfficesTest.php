<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Classifier;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Classifier;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

/**
 * The sandbox classifier has its own IDs (e.g. Kyiv is 389 there and 286 in production), so Kyiv is resolved first
 */
class GetPostOfficesTest extends TestCase
{
    use UsesConnectionTrait;

    private const KHRESHCHATYK_POSTINDEX = '01001';

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
        $postOffices = $this->model->getPostOffices(['region_id' => $this->getKyivRegionId()]);

        $this->assertNotEmpty($postOffices);
        $this->assertContains(self::KHRESHCHATYK_POSTINDEX, array_column($postOffices, 'POSTINDEX'));
        foreach ($postOffices as $postOffice) {
            $this->assertArrayHasKey('ID', $postOffice);
            $this->assertArrayHasKey('PO_SHORT', $postOffice);
            $this->assertArrayHasKey('CITY_ID', $postOffice);
            $this->assertArrayHasKey('LOCK_CODE', $postOffice);
        }
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetPostOfficesByPostindex(): void
    {
        $filters = ['region_id' => $this->getKyivRegionId(), 'postindex' => self::KHRESHCHATYK_POSTINDEX];
        $postOffices = $this->model->getPostOffices($filters);

        $this->assertNotEmpty($postOffices);
        $this->assertSame(self::KHRESHCHATYK_POSTINDEX, $postOffices[0]['POSTINDEX']);
        $this->assertSame('Київ', $postOffices[0]['CITY_UA']);
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetNearestPostOffices(): void
    {
        $postOffices = $this->model->getNearestPostOffices(50.450166, 30.523307, 2);

        $this->assertNotEmpty($postOffices);
        $this->assertArrayHasKey('DISTANCE', $postOffices[0]);
        $this->assertArrayHasKey('POSTINDEX', $postOffices[0]);
    }

    /**
     * @return string
     * @throws UkrposhtaApiException
     */
    private function getKyivRegionId(): string
    {
        foreach ($this->model->getRegions(['region_name' => 'Київ']) as $region) {
            if ($region['REGION_UA'] === 'Київ') {
                return (string)$region['REGION_ID'];
            }
        }
        $this->fail('Region Київ not found');
    }
}
