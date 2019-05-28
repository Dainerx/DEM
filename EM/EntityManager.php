<?php
namespace EM;

use Repo;

class EntityManager
{
    /**
     * @var mixed
     */
    private $repo;

    public function __construct($className)
    {
        $db_conf = json_decode(file_get_contents("db.json"));
        $data = json_decode(file_get_contents("metadata.json"));
        $filter = array_filter($data, function ($obj) use ($className) {
            return $obj->class == $className;
        });
        $data = reset($filter);
        var_dump($data->repo);
        $this->repo = new $data->repo($db_conf, $data->class, $data->table, $data->attributesEntries);
    }

    /**
     * getRepo
     *
     * @return Repo\Repo
     */
    public function getRepo()
    {
        return $this->repo;
    }
}
