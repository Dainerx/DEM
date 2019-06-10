<?php
use DEM\DemGenerator;

class DemGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function testGeneratorSimple()
    {
        $g = new DemGenerator();
        $g->generateEntityManager();
    }
}
