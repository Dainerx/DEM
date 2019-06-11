<?php
namespace Repo;

class RepoProjectMicroBit extends Repo
{
    public function __construct($db_conf, $className, $tableName, $classMetaData, $dependenciesTree)
    {
        parent::__construct($db_conf, $className, $tableName, $classMetaData, $dependenciesTree);
    }

    public function getByLink($link)
    {
        $query = "SELECT * FROM " . $this->tableName . " WHERE link LIKE ?";
        $param = [];
        array_push($param, "%" . $link . "%");
        return $this->prepareObject($this->dbManager->get($query, $param));
    }
}
