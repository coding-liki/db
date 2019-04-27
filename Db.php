<?php
namespace CodingLiki\Db;

use CodingLiki\Configs\Config;


/**
 * Родительский Класс для объектов подключения к базам данных
 */
abstract class  Db{
    protected $db_object = null; // Объект для осуществления подключения к базе данных
    protected $main_port = null; // основной порт

    protected $instance = null;  // Синглтон модели 

    protected $config_name = null; // путь до конфига с настройками подключения
    protected static $db_type = ""; // Тип базы данных
    
    
    public function __construct($options = [])
    {
        if(isset($options['config'])){
            $this->initFromConfig($options['config']);
        } else {
            $this->initFromConfig();
        }
    }
    public static function getInstatnce(){
        if(self::$instance == null){
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    protected function initConnection($host, $port, $name, $login, $pass){

    }

    protected function initFromConfig($config = null){

        if($config == null){
            $config = $this->config_name;
        }
        $db = Config::config($config);
        $this->initConnection($db['host'] ?? "localhost", $db['port'] ?? $this->main_port, $db['name'], $db['login'], $db['pass'] );
    }

    protected abstract function prepareValues($query, $values);
    protected abstract function query($query, $values = []);
    public abstract function getLastInsertId($table, $index);

    public function mainQuery($query, $values=[]){
        list($new_query, $new_values) = $this->prepareValues($query, $values);
        // echo "query = `$new_query`\n";
        // print_r($new_values);
        return $this->query($new_query, $new_values);
    }
}
