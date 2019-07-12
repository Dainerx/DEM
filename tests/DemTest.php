<?php

use PHPUnit\Framework\TestCase;
use DEM\Dem;

class DemTest extends TestCase
{

    public function testMetaData()
    {
        $dem = new Dem("Entity/", "Repo/");
        $dem->generateMetaData();
    }
}
