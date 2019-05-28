<?php
namespace Entity;

/**
 * @DEM_table=test
 * @DEM_repo=none
 */
class Test
{

    /**
     * @DEM_name=id 
     * @var integer
     */
    private $id;

    /**
     * @DEM_name=project_name 
     * @var string
     */
    private $name = "Unamed";


    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return integer
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
