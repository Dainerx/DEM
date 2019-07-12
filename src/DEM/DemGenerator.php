<?php

namespace DEM;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;

class DemGenerator
{
    const NON_DEFAULT_VALUE = "<-- NON DEFAULT -->";

    private function generateMethod(&$class, $name, $visibility, $isStatic = false, $comments = [], $params = [], $body)
    {
        $class->addMethod($name)->setVisibility($visibility);
        $method = $class->getMethod($name);
        if ($isStatic === true)
            $method->setStatic();

        foreach ($comments as $comment) {
            $method->addComment($comment);
        }
        foreach ($params as $param => $defaultValue) {
            if ($defaultValue === self::NON_DEFAULT_VALUE)
                $method->addParameter($param);
            else
                $method->addParameter($param, $defaultValue);
        }
        $method->setBody($body);
    }

    public function generateFile($namespace, $useStatements = [], $class)
    {
        $file = new PhpFile();
        $ns = $file->addNamespace($namespace);
        foreach ($useStatements as $statement) {
            $ns->addUse($statement);
        }
        $ns->add($class);
        return $file;
    }

    public function generateEntityManager()
    {
        $class = new ClassType('EntityManager');
        $class->addProperty('db_conf')->setVisibility('private');
        $class->addProperty('metadata')->setVisibility('private');
        $class->addProperty('repo')->setVisibility('private');

        $this->generateMethod(
            $class,
            '__construct',
            'public',
            false,
            [],
            array("className" => self::NON_DEFAULT_VALUE),
            '$this->db_conf = json_decode(file_get_contents("db.json"));
$this->metadata = json_decode(file_get_contents("metadata.json"));
$filter = array_filter($this->metadata, function ($obj) use ($className) {
    return $obj->class == $className;
});
$data = reset($filter);
$dependencyTree = $this->buildDependenciesTree($this->metadata, [], $data->attributesEntries);
$this->repo = new $data->repo($this->db_conf, $data->class, $data->table, $data->attributesEntries, $dependencyTree);
        '
        );

        $this->generateMethod(
            $class,
            'buildDependenciesTree',
            'private',
            false,
            array(
                "Recursively build the dependencies tree for a root entity", "@param  array \$metadata", "@param  array \$dep", "@param  array \$rootAttributeEntries", "@return array A complete dependencies tree"
            ),
            array(
                "metadata" => self::NON_DEFAULT_VALUE, "dep" => self::NON_DEFAULT_VALUE, "rootAttributeEntries" => self::NON_DEFAULT_VALUE
            ),
            'if (count($rootAttributeEntries) != 0) {
    foreach ($rootAttributeEntries as $singleEntry) {
        if ($singleEntry->isMapped == true) {
            $depClassName = "Entity\\\" . $singleEntry->type;
            $depMetaDataFiltred = array_filter($metadata, function ($obj) use ($depClassName) {
                return $obj->class == $depClassName;
            });
            $depMetaData = reset($depMetaDataFiltred);
            array_push($dep, array($depMetaData, $this->buildDependenciesTree($metadata, [], $depMetaData->attributesEntries)));
        }
    }
}
return $dep;
        '
        );

        $this->generateMethod(
            $class,
            'getRepo',
            'public',
            false,
            array(
                "Get Repo of the className passed as argument, if none is provided the entity manager", "will return the Repo of the class it was constructed with.", "@param string \$className", "@return Repo\Repo"
            ),
            array(
                "className" => ""
            ),
            'if ($className != "") {
    $filter = array_filter($this->metadata, function ($obj) use ($className) {
        return $obj->class == $className;
    });
    $data = reset($filter);
    $dependencyTree = $this->buildDependenciesTree($this->metadata, [], $data->attributesEntries);
    return new $data->repo($this->db_conf, $data->class, $data->table, $data->attributesEntries, $dependencyTree);
}
return $this->repo;    
        '
        );
        return $class;
    }

    public function generateDataBaseManager()
    {
        $class = new ClassType('DataBaseManager');

        $class->addProperty('sharedInstance')->setVisibility('private')->setStatic();
        $class->addProperty('pdo')->setVisibility('private');

        $this->generateMethod(
            $class,
            'getSharedInstance',
            'public',
            true,
            array(
                "Get singelton instance of the DatabaseManager.", "@return DatabaseManager"
            ),
            array(
                "db_conf" => self::NON_DEFAULT_VALUE
            ),
            'if (!isset(self::$sharedInstance)) {
    self::$sharedInstance = new DatabaseManager($db_conf);
}
return self::$sharedInstance;
            '
        );

        $this->generateMethod(
            $class,
            '__construct',
            'private',
            false,
            [],
            array(
                "db_conf" => self::NON_DEFAULT_VALUE
            ),
            'define("VS_DB_HOST", $db_conf->host);
define("VS_DB_PORT", $db_conf->port);
define("VS_DB_NAME", $db_conf->name);
define("VS_DB_USER", $db_conf->user);
define("VS_DB_PWD", $db_conf->password);
$this->pdo = new \PDO(\'mysql:host=\' . VS_DB_HOST . \';dbname=\' . VS_DB_NAME . \';dbport=\' . VS_DB_PORT, VS_DB_USER, VS_DB_PWD);
$this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            '
        );

        $this->generateMethod(
            $class,
            'get',
            'public',
            false,
            array(
                "Get data from database using query and parameters passed as arguments.", "@param string \$query", "@param array \$params", "@return array Data in form of an array"
            ),
            array(
                "query" => self::NON_DEFAULT_VALUE, "params" => []
            ),
            '$statement = $this->pdo->prepare($query);
$statement->execute($params);
return $statement->fetchAll(\PDO::FETCH_ASSOC);
            '
        );

        $this->generateMethod(
            $class,
            'get',
            'public',
            false,
            array(
                "Execute a query.", "@param string \$query", "@param string \$query", "@param array \$params", "@return bool Wether the query has been successfully executed"
            ),
            array(
                "query" => self::NON_DEFAULT_VALUE, "params" => []
            ),
            '$statement = $this->pdo->prepare($sql);
$result =  $statement->execute($params);
return $result;    
            '
        );

        return $class;
    }

    public function generateRepo()
    {
        $class = new ClassType('Repo');

        $class->addProperty('pdo')->setVisibility('protected');
        $class->addProperty('className')->setVisibility('protected');
        $class->addProperty('tableName')->setVisibility('protected');
        $class->addProperty('attributesMetaData')->setVisibility('protected');
        $class->addProperty('dependenciesTree')->setVisibility('private');
        $class->addConstant('SELECT_ALL', "SELECT * FROM");

        $this->generateMethod(
            $class,
            '__construct',
            'public',
            false,
            [],
            array(
                "db_conf" => self::NON_DEFAULT_VALUE, "className" => self::NON_DEFAULT_VALUE, "tableName" => self::NON_DEFAULT_VALUE, "attributesMetaData" => self::NON_DEFAULT_VALUE, "dependenciesTree" => self::NON_DEFAULT_VALUE
            ),
            '$this->dbManager = DatabaseManager::getSharedInstance($db_conf);
$this->className = $className;
$this->tableName = $tableName;
$this->attributesMetaData = $attributesMetaData;
$this->dependenciesTree = $dependenciesTree;
            '
        );

        $this->generateMethod(
            $class,
            'prepareObject',
            'protected',
            false,
            [],
            array(
                "entry" => self::NON_DEFAULT_VALUE
            ),
            '$instance = new $this->className();
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
        $results = $dependencyRepo->getBy(array(\' id \' => $value));
        $instance->$setter(reset($results));
        $treeIndex++;
    }
}
return $instance;    
            '
        );

        $this->generateMethod(
            $class,
            'getAll',
            'public',
            false,
            array(
                "Get an array of all objects", "@return array"
            ),
            [],
            '$query = $this::SELECT_ALL . $this->tableName;
$results = [];
foreach ($this->dbManager->getAll($query) as $entry) {
    array_push($results, $this->prepareObject($entry));
}
return $results;
            '
        );

        $this->generateMethod(
            $class,
            'getBy',
            'public',
            false,
            array(
                "Get an array of objects filtred by options", "@param array \$options", "@param string \$mode to be changed..", "@return array"
            ),
            array(
                "options" => self::NON_DEFAULT_VALUE, "mode" => "AND"
            ),
            '$query = $this::SELECT_ALL . $this->tableName . " WHERE ";
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
            '
        );

        $this->generateMethod(
            $class,
            'persist',
            'public',
            false,
            array(
                "Synchronize an object in the database", "@param mixed \$object An instance of this Repo's class ", "@return mixed|null A synchronized object or null if failed"
            ),
            array(
                "object" => self::NON_DEFAULT_VALUE
            ),
            '$params = [];
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
        $newId = $result[\'LAST_INSERT_ID()\'];
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
        '
        );

        $this->generateMethod(
            $class,
            'delete',
            'public',
            false,
            [],
            array(
                'object' => self::NON_DEFAULT_VALUE
            ),
            '$params = [];
$query = "DELETE FROM " . $this->tableName . " WHERE id=?";
$filterdMetaData = array_filter($this->attributesMetaData, function ($obj) {
    return $obj->name == "id";
});
$getterId = reset($filterdMetaData)->getter;
array_push($params, $object->$getterId());
return $this->dbManager->exec($query, $params);    
            '
        );

        return $class;
    }

    public function generateRepos($repoClassesNames = [])
    {
        $classes = [];
        foreach ($repoClassesNames as $repoClassName) {
            $class = new ClassType($repoClassName);
            $class->setExtends('Repo');
            $this->generateMethod(
                $class,
                '__construct',
                'public',
                false,
                [],
                array(
                    'db_conf' => self::NON_DEFAULT_VALUE, 'className' => self::NON_DEFAULT_VALUE, 'tableName' => self::NON_DEFAULT_VALUE, 'classMetaData' => self::NON_DEFAULT_VALUE, 'dependenciesTree' => self::NON_DEFAULT_VALUE
                ),
                'parent::__construct($db_conf, $className, $tableName, $classMetaData, $dependenciesTree);'
            );
            array_push($classes, $class);
        }
        return $classes;
    }
}
