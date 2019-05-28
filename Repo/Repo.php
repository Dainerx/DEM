<?php
namespace Repo;

use Database\DataBaseManager;

class Repo
{
    protected $dbManager;
    protected $className;
    protected $tableName;
    protected $classMetaData;

    const SELECT_ALL = "SELECT * FROM ";

    public function __construct($db_conf, $className, $tableName, $classMetaData)
    {
        $this->dbManager = DatabaseManager::getSharedInstance($db_conf);
        $this->className = $className;
        $this->tableName = $tableName;
        $this->classMetaData = $classMetaData;
    }

    protected function prepareObject($entry)
    {
        spl_autoload_register(function ($class_name) {
            include __DIR__ . '/' . str_replace('\\', '/', $class_name)  . '.php';
        });
        $instance = new $this->className();
        foreach ($entry as $key => $value) {
            $filterdMetaData = array_filter($this->classMetaData, function ($obj) use ($key) {
                return $obj->mappedName == $key;
            });
            $setter = reset($filterdMetaData)->setter;
            $instance->$setter($value);
        }
        return $instance;
    }


    /**
     * Get all objects
     *
     * @return array
     */
    public function getAll()
    {
        $query = $this::SELECT_ALL . $this->tableName;
        $results = [];
        foreach ($this->dbManager->get($query) as $entry) {
            array_push($results, $this->prepareObject($entry));
        }
        return $results;
    }

    /**
     * Get objects filtred by options
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
            $filterdMetaData = array_filter($this->classMetaData, function ($obj) use ($key) {
                return $obj->name == $key;
            });
            $result = reset($filterdMetaData)->mappedName;
            if (++$i != $optionsLength)
                $query .= $result . " = ? " . $mode . " ";
            else
                $query .= $result . " = ? ";
        }
        $results = [];
        foreach ($this->dbManager->get($query, array_values($options)) as $entry) {
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

        $filterdMetaData = array_filter($this->classMetaData, function ($obj) {
            return $obj->name == "id";
        });
        $getterId = reset($filterdMetaData)->getter;
        if (count($this->getBy(array("id" => $object->$getterId()))) == 0) {
            //add OUTPUT Inserted.ID in query
            $query = "INSERT INTO " . $this->tableName . " (";
            $metaDataLength =  count($this->classMetaData);
            $i = 0;
            foreach ($this->classMetaData as $metaDataEntry) {
                if (++$i !=  $metaDataLength)
                    $query .= $metaDataEntry->mappedName . ",";
                else
                    $query .= $metaDataEntry->mappedName . ")";
            }
            $query .= " VALUES (";
            $i = 0;
            foreach ($this->classMetaData as $metaDataEntry) {
                if (++$i !=  $metaDataLength)
                    $query .= "?" . ",";
                else
                    $query .= "?" . ")";
            }

            foreach ($this->classMetaData as $metaDataEntry) {
                $getter = $metaDataEntry->getter;
                array_push($params, $object->$getter());
            }
        } else {
            $query = "UPDATE " . $this->tableName . " SET ";
            $metaDataLength =  count($this->classMetaData);
            $i = 0;
            foreach ($this->classMetaData as $metaDataEntry) {
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
            foreach ($this->classMetaData as $metaDataEntry) {
                if ($metaDataEntry->mappedName != "id") {
                    $getter = $metaDataEntry->getter;
                    array_push($params, $object->$getter());
                }
            }
            $parsedObject =  reset($this->getBy(array("id" => $object->$getterId())));
            $id = $parsedObject->getId();
            array_push($params, $id);
        }
        return ($this->dbManager->exec($query, $params) == true) ? $object : null;
    }
}
