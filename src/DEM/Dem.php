<?php

namespace DEM;

class Dem
{
    //file constants
    const METADATA_FILE = "metadata.json";
    const DBCONFIG_FILE = "db.json";
    //path constants
    const NS_ENTITY = "Entity\\";
    const NS_REPO = "Repo\\";
    //patterns constants
    const PATTERN_TABLE = "/@DEM_table=\w+|([ ]{1,}\w+)/";
    const PATTERN_REPO = "/@DEM_repo=\w+|([ ]{1,}\w+)/";
    const PATTERN_ATTRIBUTE_NAME = "/@DEM_name=\w+|([ ]{1,}\w+)/";
    const PATTERN_ATTRIBUTE_MAPPED = "/@DEM_mapped/";
    const PATTERN_VAR = "/@var .*/";
    //prefixes constants
    const PREFIX_BOOL_GET = "is";
    const PREFIX_GENERIC_GET = "get";
    const PREFIX_GENERIC_SET = "set";

    private $entityPath;
    private $repoPath;

    private $db = [];

    public function __construct($entityPath, $repoPath)
    {
        $this->entityPath = $entityPath;
        $this->repoPath = $repoPath;
    }

    public function matchProvider($pattern, $string, $sep = "=")
    {
        preg_match($pattern, $string, $matchedArray);
        $matched = reset($matchedArray);
        $result = trim(substr($matched, strrpos($matched, $sep) + 1, strlen($matched)));
        return $result;
    }

    public function readDataBaseConfig()
    {
        if (IO::isFile(self::DBCONFIG_FILE) == true) {
            IO::println("Found an exisiting database configuration.");
            IO::println("Listing your current configuration...");
            $this->db = json_decode(IO::readFile(self::DBCONFIG_FILE), true);
            IO::println("database host: " . $this->db['host']);
            IO::println("database port: " . $this->db['port']);
            IO::println("database name: " . $this->db['name']);
            IO::println("database user: " . $this->db['user']);
            IO::println("database password: " . $this->db['password']);
            $redoConfig = readLine("Would you like to change this current configuration\n? (Y/n): ");
            if ($redoConfig == "Y" || $redoConfig == "y") {
                IO::removeFile(self::DBCONFIG_FILE);
                $this->readDataBaseConfig();
            }
        } else {
            $dataBaseHost = readline("Enter database host (empty for default [localhost]): ");
            $this->db['host'] = ($dataBaseHost === "") ? "localhost" : $dataBaseHost;
            IO::println("database host: " . $this->db['host']);

            $dataBasePort = readline("Enter database port (empty for default [3306]): ");
            $this->db['port'] = ($dataBasePort === "") ? "3306" : $dataBasePort;
            IO::println("database port: " . $this->db['port']);

            do {
                $dataBaseName = readline("Enter database name: ");
                $this->db['name'] = $dataBaseName;
            } while ($this->db['name'] === "");
            IO::println("database name: " . $this->db['name']);

            $dataBaseUser = readline("Enter database user (empty for default [root]): ");
            $this->db['user'] = ($dataBaseUser === "") ? "root" : $dataBaseUser;
            IO::println("database user: " . $this->db['user']);

            $dataBaseUserPassword = readline("Password (empty for default []): ");
            $this->db['password'] = ($dataBaseUserPassword === "") ? "" : $dataBaseUserPassword;
        }
        IO::println(IO::OUTPUT_OK, IO::SUCCESS);
        return $this->db;
    }

    public function generateMetaData()
    {
        IO::println(IO::OUTPUT_RUNNING, IO::INFO);
        $this->db = $this->readDataBaseConfig();
        IO::writeFile(self::DBCONFIG_FILE, json_encode($this->db), "", "");
        $conf = [];
        IO::println(IO::OUTPUT_PROJECT_CONFIG_START, IO::INFO);
        $classesCount = count(glob($this->entityPath . '*.php'));
        IO::println("Found " . $classesCount . " classes, generating metadata for classes...", IO::INFO);
        foreach (glob($this->entityPath . '*.php') as $classFile) {
            try {
                $class = basename($classFile, '.php');
                $reflectionClass = new \ReflectionClass(self::NS_ENTITY . $class);
                $entry = new Entry(self::NS_ENTITY . $class);
                $entry->repo = self::NS_REPO . $this->matchProvider(self::PATTERN_REPO, $reflectionClass->getDocComment());
                $entry->table = $this->matchProvider(self::PATTERN_TABLE, $reflectionClass->getDocComment());;

                $entry->attributesEntries = [];
                $vars = $reflectionClass->getProperties(\ReflectionProperty::IS_PRIVATE);
                foreach ($vars as $v) {
                    $entryAttribute = new AttributeEntry($v->name);
                    $reflectionProperty = new \ReflectionProperty($entry->class, $entryAttribute->name);
                    $matchAttributeMappedName = $this->matchProvider(self::PATTERN_ATTRIBUTE_NAME, $reflectionProperty->getDocComment());
                    $matchAttributeIsMapped = $this->matchProvider(self::PATTERN_ATTRIBUTE_MAPPED, $reflectionProperty->getDocComment(), "");
                    $matchAttributeType = $this->matchProvider(self::PATTERN_VAR, $reflectionProperty->getDocComment(), " ");
                    $entryAttribute->mappedName = $matchAttributeMappedName;
                    $entryAttribute->isMapped = ($matchAttributeIsMapped === "") ? false : true;
                    $entryAttribute->type = $matchAttributeType;
                    if ($entryAttribute->type === 'bool') {
                        $entryAttribute->getter = $reflectionClass->getMethod(self::PREFIX_BOOL_GET . ucfirst($entryAttribute->name))->getName();
                        $entryAttribute->setter = $reflectionClass->getMethod(self::PREFIX_GENERIC_SET . ucfirst($entryAttribute->name))->getName();
                    } else {
                        $entryAttribute->getter = $reflectionClass->getMethod(self::PREFIX_GENERIC_GET . ucfirst($entryAttribute->name))->getName();
                        $entryAttribute->setter =  $reflectionClass->getMethod(self::PREFIX_GENERIC_SET . ucfirst($entryAttribute->name))->getName();
                    }
                    array_push($entry->attributesEntries, $entryAttribute);
                }

                IO::println(IO::OUTPUT_OK, IO::SUCCESS);
                array_push($conf, $entry);
                IO::println();
                IO::writeFile(self::METADATA_FILE, json_encode($conf), IO::OUTPUT_PROJECT_CONFIG_END, IO::OUTPUT_PROJECT_CONFIG_FAILED);
            } catch (Exception $e) {
                IO::println(IO::OUTPUT_PROJECT_CONFIG_FAILED, IO::ERROR);
                IO::println(IO::OUTPUT_CLASS_CONFIG_FAILED . $entry->class, IO::ERROR);
                IO::println($e->getMessage(), IO::ERROR);
                return [];
            }
        }
    }
}
