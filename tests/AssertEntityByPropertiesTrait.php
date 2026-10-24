<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta\Tests;

use PHPUnit\Framework\Assert;

/**
 * Trait AssertEntityByPropertiesTrait
 */
trait AssertEntityByPropertiesTrait
{
    /**
     * @param array $entity
     * @param array $expectedProperties
     * @return void
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function assertEntity(array $entity, array $expectedProperties): void
    {
        $actualProperties = array_keys($entity);
        sort($actualProperties);
        sort($expectedProperties);
        Assert::assertSame($expectedProperties, $actualProperties);
    }

    /**
     * @param array $entity
     * @param array $expectedProperties
     * @return void
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function assertEntityHasProperties(array $entity, array $expectedProperties): void
    {
        foreach ($expectedProperties as $property) {
            Assert::assertArrayHasKey($property, $entity);
        }
    }
}
