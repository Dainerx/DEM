<?php
use PHPUnit\Framework\TestCase;
use DEM\DemGenerator;

class DemGeneratorTest extends TestCase
{
    public function testGeneratorEntityManager()
    {
        $g = new DemGenerator();
        echo $g->generateFile("EM", array("Repo"), $g->generateEntityManager());
    }
    public function testGeneratorDatabaseManager()
    {
        $g = new DemGenerator();
        echo $g->generateFile("Database", [], $g->generateDataBaseManager());
    }
    public function testGeneratorRepo()
    {
        $g = new DemGenerator();
        echo $g->generateFile("Repo", array("Database\DataBaseManager"), $g->generateRepo());
    }
}
