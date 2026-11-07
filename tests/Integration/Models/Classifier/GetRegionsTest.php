<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests\Integration\Models\Classifier;

use PHPUnit\Framework\TestCase;
use SergiyNezbritskiy\Ukrposhta\Models\Classifier;
use SergiyNezbritskiy\Ukrposhta\Tests\UsesConnectionTrait;
use SergiyNezbritskiy\Ukrposhta\UkrposhtaApiException;

class GetRegionsTest extends TestCase
{
    use UsesConnectionTrait;

    private Classifier $model;

    protected function setUp(): void
    {
        $this->model = new Classifier($this->getConnection());
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetRegionsByUkrainianName(): void
    {
        $regions = $this->model->getRegions(['region_name' => 'Київ']);

        $this->assertNotEmpty($regions);
        $names = array_column($regions, 'REGION_UA');
        $this->assertContains('Київ', $names);
        $this->assertContains('Київська', $names, 'the classifier matches by the part of the name');
        foreach ($regions as $region) {
            $this->assertArrayHasKey('REGION_ID', $region);
            $this->assertArrayHasKey('REGION_EN', $region);
            $this->assertNotEmpty($region['REGION_ID']);
        }
    }

    /**
     * @throws UkrposhtaApiException
     */
    public function testGetRegionsByEnglishName(): void
    {
        $regions = $this->model->getRegions(['region_name_en' => 'Kyivska']);

        $this->assertContains('Київська', array_column($regions, 'REGION_UA'));
    }
}
