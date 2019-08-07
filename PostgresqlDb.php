<?php
namespace CodingLiki\Db;

// use Db;

class PostgresqlDb extends Db{
    protected $main_port = "5432";
    protected static $db_type = "postgresql";

    protected function initConnection($host, $port, $name, $login, $pass){
        $this->db_object = pg_connect("host=$host port=$port dbname=$name user=$login password=$pass options='--client_encoding=UTF8'");
    }

    protected function prepareValues($query, $values){
        
        $new_values = [];
        
        $val_num = 1;
        $values_keys = array_keys($values);
        for($i = 0; $i < count($values); $i++){
            # code...
            
            $key = $values_keys[$i];
            $value = $values[$key];
            $has_key = strpos($query, '{{'.$key.'}}');
            if($has_key === FALSE){
                continue;
            } else {
                $check_sub_query = is_string($value) && strpos($value, "{{") !== false && strpos($value, "}}") !== false;
                $check_sub_query = $check_sub_query || (is_array($value) && count($value) > 1 && strpos($value[1], "{{") !== false && strpos($value[1], "}}") !== false);
                
                if($check_sub_query){
                    if(is_array($value) && count($value) > 1){
                        $value = $value[1];
                    }
                    $query = str_replace('{{'.$key.'}}', $value, $query);
                    $i = -1;
                    continue;
                }
                $query = str_replace('{{'.$key.'}}', '$'.$val_num, $query);
                $val_num++;
                if(is_array($value) && count($value) > 1){
                    if (is_array($value[1])) {
                        $value[1] = '{'.implode(",",$value[1]).'}';
                    }
                    $new_values[] = $value[1];
                } else if(is_array($value)){
                    $new_values[] = $value[0];
                } else {
                    $new_values[] = $value;
                }
            }
        }

        return [$query, $new_values];
        
    }
    public function getLastInsertId($table, $index){
        $query = "SELECT currval('$table"."_".$index."_seq') AS lastinsertid";
        $result = $this->mainQuery($query)[0];
        return $result['lastinsertid'];
    }
    protected function query($query, $values=[]){
        $result = pg_query_params($this->db_object, $query, $values);
        
        $result_array = pg_fetch_all($result);
        return $result_array;
    }
}
