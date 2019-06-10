<?php
namespace DEM;

class Entry
{
    public $class;
    public $repo;
    public $table;
    public $attributesEntries;
    public function __construct($class)
    {
        $this->class = $class;
        IO::println(IO::OUTPUT_CLASS_CONFIG_START . $this->class);
    }
}
