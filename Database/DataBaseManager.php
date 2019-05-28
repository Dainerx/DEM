<?php
namespace Database;

class DataBaseManager
{

    private static $sharedInstance;
    private $pdo;

    /**
     * Get singelton instance of the DatabaseManager.
     * @return DatabaseManager
     */
    public static function getSharedInstance($db_conf)
    {
        if (!isset(self::$sharedInstance)) {
            self::$sharedInstance = new DatabaseManager($db_conf);
        }
        return self::$sharedInstance;
    }

    private function __construct($db_conf)
    {
        define("VS_DB_HOST", $db_conf->host); // l'hôte de la base de données.
        define("VS_DB_PORT", $db_conf->port); // le port pour se connecter.
        define("VS_DB_NAME", $db_conf->name); // le nom de la base de données sur le SGBD.
        define("VS_DB_USER", $db_conf->user); // l'utilisateur.
        define("VS_DB_PWD", $db_conf->password); // le mot de passe.
        $this->pdo = new \PDO('mysql:host=' . VS_DB_HOST . ';dbname=' . VS_DB_NAME . ';dbport=' . VS_DB_PORT, VS_DB_USER, VS_DB_PWD);
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Get data from database using query and parameters passed as arguments.
     * 
     * @param string $query
     * @param array $params
     * @return array Data in form of an array
     */
    public function get($query, $params = [])
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Execute a query.
     *
     * @param string $query
     * @param array $params
     * @return bool Wether the query has been successfully executed
     */
    public function exec($sql, $params = [])
    {
        $statement = $this->pdo->prepare($sql);
        $result =  $statement->execute($params);
        return $result;
    }
}
