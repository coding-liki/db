<?php

namespace CodingLiki\Db;
use CodingLiki\PhpMvc\Configs\Config;
class DbFactory{
    private static $db_objects = [];
    private static $known_db_classes = [
        'postgresql' => PostgresqlDb::class
    ];

    public static function getDbObject($config_name){

        $db_settings = Config::config($config_name);
        // print_r($db_settings);
        $db_type = $db_settings['type'];
        if(!array_key_exists($db_type, self::$known_db_classes)){
            return false;
        }

        $db_class = self::$known_db_classes[$db_type];

        $db_config_array = explode(".", $config_name);
        if(isset($db_config_array[1])){
            $db_config_name = $db_config_array[1];
        } else {
            return false;
        }
        if(!isset(self::$db_objects[$db_config_name])){
            $db_object =new $db_class(['config' => $config_name]);
            self::$db_objects[$db_config_name] = $db_object;   
        }

        return self::$db_objects[$db_config_name];
    }
}
