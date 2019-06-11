<?php

use DEM\Dem;

use PHPUnit\Framework\TestCase;

class DemMatcherTest extends TestCase
{
    public function testMatchProviderSimple()
    {
        error_reporting(E_ERROR | E_PARSE);
        $dem = new Dem("a", "b");
        $this->assertEquals("id", $dem->matchProvider($dem::PATTERN_ATTRIBUTE_NAME, "@DEM_name=id"));
        $this->assertEquals("name", $dem->matchProvider($dem::PATTERN_ATTRIBUTE_NAME, "@DEM_name=name"));
        $this->assertEquals("test", $dem->matchProvider($dem::PATTERN_TABLE, "@DEM_table=test"));
        $this->assertEquals("testRepo", $dem->matchProvider($dem::PATTERN_REPO, "@DEM_repo=testRepo"));
        $this->assertEquals("string", $dem->matchProvider($dem::PATTERN_VAR, "@var string", " "));
    }

    public function testMatchProviderWithSpace()
    {
        error_reporting(E_ERROR | E_PARSE);
        $dem = new Dem("a", "b");
        $this->assertEquals("id", $dem->matchProvider($dem::PATTERN_ATTRIBUTE_NAME, "@DEM_name=  id"));
        $this->assertEquals("string", $dem->matchProvider($dem::PATTERN_VAR, "@var   string", " "));
    }

    public function testMatchProviderWithSpace1()
    {
        error_reporting(E_ERROR | E_PARSE);
        $dem = new Dem("a", "b");
        $this->assertEquals("test", $dem->matchProvider($dem::PATTERN_TABLE, "@DEM_table =  test  "));
    }

    public function testMatchProviderWithSpaces()
    {
        error_reporting(E_ERROR | E_PARSE);
        $dem = new Dem("a", "b");
        $this->assertEquals("id", $dem->matchProvider($dem::PATTERN_ATTRIBUTE_NAME, "@DEM_name   =    id"));
    }


    /*
    public function testDataBaseReading()
    {
        error_reporting(E_ERROR | E_PARSE);
        //$dem = new Dem("a", "b");
        //$dbExpected = array("host" => "localhost", "port" => "3306", "name" => "test", "user" => "root", "password" => "root");
        //$this->assertEquals($dbExpected, $dem->readDataBaseConfig());
    }
    */
}
