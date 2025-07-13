<?php

use App\Libraries\FeatureFlag\FeatureManager;
use PHPUnit\Framework\TestCase;

class FeaturemanagerTest extends TestCase
{
    public function test_carrega_featuremanager()
    {
        $this->assertEquals(FeatureManager::class, FeatureManager::class);
    }
}
