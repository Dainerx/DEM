<?php

use PHPUnit\Framework\TestCase;
use DEM\DemGenerator;
use DEM\IO;

class DemGeneratorTest extends TestCase
{
    //add files to test against
    public function testGeneratorEntityManager()
    {
        $g = new DemGenerator();
        $file = $g->generateFile("EM", array("Repo"), $g->generateEntityManager());
        IO::writeFile("em.php", $file);
    }
    public function testGeneratorDatabaseManager()
    {
        $g = new DemGenerator();
        $file = $g->generateFile("Database", [], $g->generateDataBaseManager());
        IO::writeFile("db.php", $file);
    }
    public function testGeneratorRepo()
    {
        $g = new DemGenerator();
        $file = $g->generateFile("Repo", array("Database\DataBaseManager"), $g->generateRepo());
        IO::writeFile("repo.php", $file);
    }
}
