<?php
namespace Repo;

use Database\DataBaseManager;

class Repo
{
    protected $dbManager;
    protected $className;
    protected $tableName;
    protected $attributesMetaData;
    private $dependenciesTree;

    const SELECT_ALL = "SELECT * FROM ";

    public function __construct($db_conf, $className, $tableName, $attributesMetaData, $dependenciesTree)
    {
        $this->dbManager = DatabaseManager::getSharedInstance($db_conf);
        $this->className = $className;
        $this->tableName = $tableName;
        $this->attributesMetaData = $attributesMetaData;
        $this->dependenciesTree = $dependenciesTree;
    }

    protected function prepareObject($entry)
    {
        spl_autoload_register(function ($class_name) {
            include __DIR__ . '/' . str_replace('\\', '/', $class_name)  . '.php';
        });
        $instance = new $this->className();
        $treeIndex = 0;
        foreach ($entry as $key => $value) {
            $filterdMetaData = array_filter($this->attributesMetaData, function ($obj) use ($key) {
                return $obj->mappedName == $key;
            });
            if (reset($filterdMetaData)->isMapped == false) {
                $setter = reset($filterdMetaData)->setter;
                $instance->$setter($value);
            } else {
                $setter = reset($filterdMetaData)->setter;
                $dependencyMetaData = $this->dependenciesTree[$treeIndex][0];
                $dependencyRepo = new Repo($this->dbManager, $dependencyMetaData->class, $dependencyMetaData->table, $dependencyMetaData->attributesEntries, $this->dependenciesTree[$treeIndex][1]); //fix this
                $results = $dependencyRepo->getBy(array('id' => $value));
                $instance->$setter(reset($results));
                $treeIndex++;
            }
        }
        return $instance;
    }

    /**
     * Get an array of all objects
     *
     * @return array
     */
    public function getAll()
    {
        $query = $this::SELECT_ALL . $this->tableName;
        $results = [];
        foreach ($this->dbManager->getAll($query) as $entry) {
            array_push($results, $this->prepareObject($entry));
        }
        return $results;
    }

    /**
     * Get an array of objects filtred by options
     *
     * @param array $options
     * @param string $mode to be changed...
     * @return array
     */
    public function getBy($options, $mode = "AND")
    {
        $query = $this::SELECT_ALL . $this->tableName . " WHERE ";
        $optionsLength = count($options);
        $i = 0;
        foreach (array_keys($options) as $key) {
            $filterdMetaData = array_filter($this->attributesMetaData, function ($obj) use ($key) {
                return $obj->name == $key;
            });
            if (reset($filterdMetaData)->isMapped == true)
                $options[$key] = $options[$key]->getId();
            $result = reset($filterdMetaData)->mappedName;
            if (++$i != $optionsLength)
                $query .= $result . " = ? " . $mode . " ";
            else
                $query .= $result . " = ? ";
        }
        $results = [];
        foreach ($this->dbManager->getAll($query, array_values($options)) as $entry) {
            array_push($results, $this->prepareObject($entry));
        }
        return $results;
    }

    /**
     * Synchronize an object in the database
     *
     * @param mixed $object An instance of this Repo's class 
     * @return mixed|null A synchronized object or null if failed
     */
    public function persist($object)
    {
        $params = [];
        $query = "";

        $filterdMetaData = array_filter($this->attributesMetaData, function ($obj) {
            return $obj->name == "id";
        });
        $getterId = reset($filterdMetaData)->getter;
        $setterId = reset($filterdMetaData)->setter;
        if (count($this->getBy(array("id" => $object->$getterId()))) == 0) {
            $query = "INSERT INTO " . $this->tableName . " (";
            $metaDataLength =  count($this->attributesMetaData);
            $i = 0;
            foreach ($this->attributesMetaData as $metaDataEntry) {
                if (++$i !=  $metaDataLength)
                    $query .= $metaDataEntry->mappedName . ",";
                else
                    $query .= $metaDataEntry->mappedName . ")";
            }
            $query .= " VALUES (";
            $i = 0;
            foreach ($this->attributesMetaData as $metaDataEntry) {
                if (++$i !=  $metaDataLength)
                    $query .= "?" . ",";
                else
                    $query .= "?" . ")";
            }

            foreach ($this->attributesMetaData as $metaDataEntry) { //add condition if he is mapped put id instead
                $getter = $metaDataEntry->getter;
                if ($metaDataEntry->isMapped == true)
                    array_push($params, ($object->$getter()===null) ? null : $object->$getter()->getId());
                else
                    array_push($params, $object->$getter());
            }
            $status = $this->dbManager->exec($query, $params);
            if ($status === true) {
                $result = $this->dbManager->get("SELECT LAST_INSERT_ID()");
                $newId = $result['LAST_INSERT_ID()'];
                $object->$setterId($newId);
                return $object;
            } else
                return null;
        } else {
            $query = "UPDATE " . $this->tableName . " SET ";
            $metaDataLength =  count($this->attributesMetaData);
            $i = 0;
            foreach ($this->attributesMetaData as $metaDataEntry) {
                if ($metaDataEntry->mappedName == "id") {
                    $i++;
                    continue;
                }
                if (++$i !=  $metaDataLength)
                    $query .= $metaDataEntry->mappedName . "=?,";
                else
                    $query .= $metaDataEntry->mappedName . "=? ";
            }
            $query .= " WHERE id=?";

            $params = [];
            foreach ($this->attributesMetaData as $metaDataEntry) {
                if ($metaDataEntry->mappedName == "id")
                    continue;
                else {
                    $getter = $metaDataEntry->getter;
                    if ($metaDataEntry->isMapped == true)
                        array_push($params, $object->$getter()->getId());
                    else
                        array_push($params, $object->$getter());
                }
            }
            $parsedObject =  array_values($this->getBy(array("id" => $object->$getterId())))[0];
            $id = $parsedObject->getId();
            array_push($params, $id);
            return ($this->dbManager->exec($query, $params) == true) ? $object : null;
        }
    }

    public function delete($object)
    {
        $params = [];
        $query = "DELETE FROM " . $this->tableName . " WHERE id=?";
        $filterdMetaData = array_filter($this->attributesMetaData, function ($obj) {
            return $obj->name == "id";
        });
        $getterId = reset($filterdMetaData)->getter;
        array_push($params, $object->$getterId());
        return $this->dbManager->exec($query, $params);
    }
}
