<?php
namespace DEM;

class AttributeEntry
{
    public $name;
    public $mappedName;
    public $isMapped;
    public $type;
    public $getter;
    public $setter;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
