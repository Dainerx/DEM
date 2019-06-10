<?php
namespace EM;

use Repo;

class EntityManager
{
    private $db_conf;
    private $metadata;
    private $repo;


    public function __construct($className)
    {
        $this->db_conf = json_decode(file_get_contents("db.json")); //Fix pathes 
        $this->metadata = json_decode(file_get_contents("metadata.json")); //Fix pathes
        $filter = array_filter($this->metadata, function ($obj) use ($className) {
            return $obj->class == $className;
        });
        $data = reset($filter);
        $dependencyTree = $this->buildDependenciesTree($this->metadata, [], $data->attributesEntries);
        $this->repo = new $data->repo($this->db_conf, $data->class, $data->table, $data->attributesEntries, $dependencyTree);
    }

    /**
     * Recursively build the dependencies tree for a root entity
     *
     * @param  array $metadata
     * @param  array $dep
     * @param  array $rootAttributeEntries
     *
     * @return array A complete dependencies tree
     */
    private function buildDependenciesTree($metadata, $dep, $rootAttributeEntries)
    {
        if (count($rootAttributeEntries) != 0) {
            foreach ($rootAttributeEntries as $singleEntry) {
                if ($singleEntry->isMapped == true) {
                    $depClassName = "Entity\\" . $singleEntry->type;
                    $depMetaDataFiltred = array_filter($metadata, function ($obj) use ($depClassName) {
                        return $obj->class == $depClassName;
                    });
                    $depMetaData = reset($depMetaDataFiltred);
                    array_push($dep, array($depMetaData, $this->buildDependenciesTree($metadata, [], $depMetaData->attributesEntries)));
                }
            }
        }
        return $dep;
    }
    /**
     * Get Repo of the className passed as argument, if none is provided the entity manager
     * will return the Repo of the class it was constructed with.
     *
     * @param string $className
     * @return Repo\Repo
     */
    public function getRepo($className = "")
    {
        if ($className != "") {
            $filter = array_filter($this->metadata, function ($obj) use ($className) {
                return $obj->class == $className;
            });
            $data = reset($filter);
            $dependencyTree = $this->buildDependenciesTree($this->metadata, [], $data->attributesEntries);
            return new $data->repo($this->db_conf, $data->class, $data->table, $data->attributesEntries, $dependencyTree);
        }
        return $this->repo;
    }
}
