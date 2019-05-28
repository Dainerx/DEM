<?php
spl_autoload_register(function ($class_name) {
    include __DIR__ . '/' . str_replace('\\', '/', $class_name)  . '.php';
});

//path constants
const METADATA_FILE = __DIR__ . "/metadata.json";
const DBCONFIG_FILE = __DIR__ . "/db.json";


const PATH_ENTITY = __DIR__ . "/Entity/";
const PATH_REPO = __DIR__ . "/Repo/";
const NS_ENTITY = "Entity\\";
const NS_REPO = "Repo\\";

//patterns constants
const PATTERN_TABLE = "/@DEM_table=.*/";
const PATTERN_REPO = "/@DEM_repo=.*/";
const PATTERN_ATTRIBUTE_NAME = "/@DEM_name=.*/";
const PATTERN_VAR = "/@var .*/";
//prefixes constants
const PREFIX_BOOL_GET = "is";
const PREFIX_GENERIC_GET = "get";
const PREFIX_GENERIC_SET = "set";
//output constants
const OUTPUT_PROJECT_CONFIG_START = "Generating project metadata...\nEnities path: " . PATH_ENTITY . "\nRepositories path: " . PATH_REPO . "\n";
const OUTPUT_PROJECT_CONFIG_FAILED = "Project metadata generation has failed:" . "\n";
const OUTPUT_PROJECT_CONFIG_END = "Project metadata generation has been successfully completed.\nMetadata parsed in " . METADATA_FILE . "\n";
const OUTPUT_CLASS_CONFIG_START = "Started generating metadata for ";
const OUTPUT_CLASS_CONFIG_FAILED = "Generating metadata has failed for ";
const OUTPUT_CLASS_CONFIG_FINISHED = "Finished generating metadata for ";
const OUTPUT_NEWLINE = " \n";


function println($message)
{
    print($message . OUTPUT_NEWLINE);
}
function readDataBaseConfig()
{
    $db = [];
    $dataBaseHost = readline("Enter database host (empty for default [localhost]): ");
    $db['host'] = ($dataBaseHost === "") ? "localhost" : $dataBaseHost;
    println("database host: " . $db['host']);

    $dataBasePort = readline("Enter database port (empty for default [3306]): ");
    $db['port'] = ($dataBasePort === "") ? "3306" : $dataBasePort;
    println("database port: " . $db['port']);

    do {
        $dataBaseName = readline("Enter database name: ");
        $db['name'] = $dataBaseName;
    } while ($db['name'] === "");
    println("database name: " . $db['name']);

    $dataBaseUser = readline("Enter database user (empty for default [root]): ");
    $db['user'] = ($dataBaseUser === "") ? "root" : $dataBaseUser;
    println("database user: " . $db['user']);

    $dataBaseUserPassword = readline("Password (empty for default [root]): ");
    $db['password'] = ($dataBaseUserPassword === "") ? "root" : $dataBaseUserPassword;

    println("OK.");
    return $db;
}

function generateClass()
{
    mkdir("DAINER/", 0774);
    $em = '<?php namespace EM;use Repo;class EntityManager{    /**     * @var mixed     */    private $repo;    public function __construct($className)    {        $db_conf = json_decode(file_get_contents("$databaseconfig"));        $data = json_decode(file_get_contents("$metadata"));        $filter = array_filter($data, function ($obj) use ($className) {            return $obj->class == $className;        });        $data = reset($filter);        var_dump($data->repo);        $this->repo = new $data->repo($db_conf, $data->class, $data->table, $data->attributesEntries);    }    /**     * getRepo     *     * @return Repo\Repo     */    public function getRepo()    {        return $this->repo;    }}';
    $plug = array(
        '$databaseconfig' => DBCONFIG_FILE,
        '$metadata' => METADATA_FILE,
    );
    file_put_contents("DAINER/EM.php", strtr($em, $plug));
}

function matchProvider($pattern, $string, $sep = "=")
{
    preg_match($pattern, $string, $matched);
    return rtrim(substr($matched[0], strrpos($matched[0], $sep) + 1, strlen($matched[0])));
}

function write($text, $file, $successMessage, $failedMessage)
{
    try {
        $handle = fopen($file, "w");
        fwrite($handle, $text);
        println($successMessage);
    } catch (Exception $e) {
        println($failedMessage);
        println($e->getMessage());
    }
}

function main()
{
    class Entry
    {
        public $class;
        public $repo;
        public $table;
        public $attributesEntries;
        public function __construct($class)
        {
            $this->class = $class;
            println(OUTPUT_CLASS_CONFIG_START . $this->class);
        }
    }
    class AttributeEntry
    {
        public $name;
        public $mappedName;
        public $type;
        public $getter;
        public $setter;

        public function __construct($name)
        {
            $this->name = $name;
        }
    }

    $conf = [];
    println(OUTPUT_PROJECT_CONFIG_START);
    foreach (glob(PATH_ENTITY . '*.php') as $classFile) {
        try {
            $class = basename($classFile, '.php');
            $reflectionClass = new ReflectionClass(NS_ENTITY . $class);
            $entry = new Entry(NS_ENTITY . $class);
            $entry->repo = NS_REPO . matchProvider(PATTERN_REPO, $reflectionClass->getDocComment());
            $entry->table = matchProvider(PATTERN_TABLE, $reflectionClass->getDocComment());;
            $entry->attributesEntries = [];
            $vars = $reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE);
            //catch some exceptions
            //add tests if that set functio does exisit or no.
            //left space before attribute name
            foreach ($vars as $v) {
                $entryAttribute = new AttributeEntry($v->name);
                $reflectionProperty = new ReflectionProperty($entry->class, $entryAttribute->name);
                $matchAttributeMappedName = matchProvider(PATTERN_ATTRIBUTE_NAME, $reflectionProperty->getDocComment());
                $matchAttributeType = matchProvider(PATTERN_VAR, $reflectionProperty->getDocComment(), " ");
                $entryAttribute->mappedName = $matchAttributeMappedName;
                $entryAttribute->type = $matchAttributeType;
                if ($entryAttribute->type === 'bool') {
                    $entryAttribute->getter = $reflectionClass->getMethod(PREFIX_BOOL_GET . ucfirst($entryAttribute->name))->getName();
                    $entryAttribute->setter = $reflectionClass->getMethod(PREFIX_GENERIC_SET . ucfirst($entryAttribute->name))->getName();
                } else {
                    $entryAttribute->getter = $reflectionClass->getMethod(PREFIX_GENERIC_GET . ucfirst($entryAttribute->name))->getName();
                    $entryAttribute->setter =  $reflectionClass->getMethod(PREFIX_GENERIC_SET . ucfirst($entryAttribute->name))->getName();
                }
                array_push($entry->attributesEntries, $entryAttribute);
            }
            println(OUTPUT_CLASS_CONFIG_FINISHED . $entry->class);
            array_push($conf, $entry);
        } catch (Exception $e) {
            println(OUTPUT_PROJECT_CONFIG_FAILED);
            println(OUTPUT_CLASS_CONFIG_FAILED . $entry->class);
            println($e->getMessage());
        }
    }
    return $conf;
}

//generateClass();
//exec
$db = readDataBaseConfig(); //change messages
write(json_encode($db), DBCONFIG_FILE, "", "");
$conf = main();
if ($conf != [])
    write(json_encode($conf), METADATA_FILE, OUTPUT_PROJECT_CONFIG_END, OUTPUT_PROJECT_CONFIG_FAILED);
