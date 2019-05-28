<?php
namespace Repo;

class RepoProjectMicroBit extends Repo
{
    public function __construct($db_conf, $className, $tableName, $classMetaData)
    {
        parent::__construct($db_conf, $className, $tableName, $classMetaData);
    }
}
