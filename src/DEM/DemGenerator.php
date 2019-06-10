<?php

namespace DEM;

use Nette\PhpGenerator\ClassType;

class DemGenerator
{
    const NON_DEFAULT_VALUE = "<-- NON DEFAULT -->";

    private function generateMethod(&$class, $name, $visibility, $comments = [], $params = [], $body)
    {
        $class->addMethod($name)->setVisibility($visibility);
        $method = $class->getMethod($name);
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

    public function generateEntityManager()
    {
        $class = new ClassType('EntityManager');
        $class->addComment("Description of class.\nSecond line\n")->addComment('@property-read Nette\Forms\Form $form');

        $class->addProperty('db_conf')->setVisibility('private');
        $class->addProperty('metadata')->setVisibility('private');
        $class->addProperty('repo')->setVisibility('private');


        $this->generateMethod(
            $class,
            '__construct',
            'public',
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
            array(
                "Recursively build the dependencies tree for a root entity", "@param  array \$metadata", "@param  array \$dep", "@param  array \$rootAttributeEntries", "@return array A complete dependencies tree"
            ),
            array(
                "metadata" => self::NON_DEFAULT_VALUE, "dep" => self::NON_DEFAULT_VALUE, "rootAttributeEntries" => self::NON_DEFAULT_VALUE
            ),
            'if (count($rootAttributeEntries) != 0) {
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
            '
        );

        echo $class;
    }
}
