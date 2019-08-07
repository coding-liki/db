<?php
namespace CodingLiki\Db;

class QueryBuilder{
    protected $query = "";
    public $last_query = "";
    protected $type = "";
    protected $where_fields = [];
    public    $where_values = [];
    public    $additional_values = [];
    protected $where_rules = [];
    protected $limit = 0;
    protected $order_by = [];
    protected $insert_values = [];
    protected $update_fields = [];
    /**
     * Предварительная генерация SELECT запроса
     *
     * @param [type] $table
     * @param string $fields
     * @return void
     */
    public function buildSelect($table, $fields = "*"){
        // echo "table = $table\n";
        $this->query = "SELECT $fields FROM $table";
        $this->type = "select";
    }

    /**
     * Предварительная генерация INSERT запроса
     *
     * @param [type] $table
     * @return void
     */
    public function buildInsert($table){
        $this->query = "INSERT INTO $table";

        $this->type = "insert";
    }

    /**
     * Устанавливаем значения для сортировки
     *
     * @param [type] $fields
     * @return void
     */
    public function orderBy($fields){
        $this->order_by = $fields;
    }
    /**
     * Предварительная генерация UPDATE запроса
     *
     * @param [type] $table
     * @return void
     */
    public function buildUpdate($table){
        $this->query = "UPDATE $table SET";

        $this->type = "update";
    }

    public function buildCount($table, $fields="*"){
        $this->query = "SELECT count($fields) AS count FROM $table";

        $this->type = "count";
    }
    /**
     * Добавление WHERE полей к списку WHERE полей
     *
     * @param array $fields
     * @return void
     */
    public function addWhere($fields){
        // print_r($fields);
        $this->where_fields = $this->where_fields + array_keys($fields);
        $this->where_values = $this->where_values + $fields;
        foreach($fields as $key => $field){
            if(is_array($field) && count($field) > 1){
                $this->where_rules[$key] = $field[0];
            } else{
                $this->where_rules[$key] = "=";
            }
        }
    }

    // public function 

    /**
     * Добавление INSERT полей к списку INSERT полей
     *
     * @param [type] $fields
     * @return void
     */
    public function addInsertFields($fields){
        $this->insert_values = $this->insert_values + $fields;
    }

    public function addUpdateFields($fields){
        $this->update_fields = $this->update_fields + $fields;
    }

    /**
     * Добавление limit
     *
     * @param [type] $limit
     * @return void
     */
    public function addLimit($limit){
        $this->limit = $limit;
    }

    /**
     * Окончательная генерация INSERT запроса
     *
     * @return void
     */
    public function getInsertQuery(){
        $query = $this->query;
        $field_str = implode(",",$this->insert_values);
        $field_str = trim($field_str, ",");

        $values_str = "";
        
        foreach($this->insert_values as $value){
            $values_str .= '{{'.$value.'}},';
        }
        $values_str = trim($values_str, ",");
        $query .= "($field_str) values($values_str)";

        return $query;
    }

    /**
     * Окончательная генерация UPDATE запроса
     *
     * @return void
     */
    public function getUpdateQuery(){
        $query = $this->query;

        foreach ($this->update_fields as $value) {
            # code...
            $query .= " $value=".'{{'.$value.'}},';
        }

        return trim($query, ",");
    }


    /**
     * Окончательная генерация SQL запроса
     *
     * @return void
     */
    public function getQuery($refresh = false){
        // print_r($this->where_rules);
        $query = $this->query;
        $add_where_order_limit = true;
        if($this->type == "insert"){
            $query = $this->getInsertQuery();
            $add_where_order_limit = false;
        } else if($this->type == "update"){
            $query = $this->getUpdateQuery();
        }// } else if($this->type == "count"){
        //     $query = $this->getCountQuery();
        // }
        if($add_where_order_limit){
            if (count($this->where_fields) > 0) {
                $query .= " WHERE ";
                foreach ($this->where_fields as $field) {
                    $rule = trim(strtoupper($this->where_rules[$field]));
                    
                    if($this->where_values[$field] instanceof QueryBuilder){
                        $this->additional_values = $this->additional_values + $this->where_values[$field]->where_values + $this->where_values[$field]->additional_values;
                        $this->additional_values[$field] = $this->where_values[$field]->last_query;
                        $this->where_values[$field] = $this->where_values[$field]->last_query;
                    } else if( is_array($this->where_values[$field]) && $this->where_values[$field][1] instanceof QueryBuilder){
                        $this->additional_values = $this->additional_values + $this->where_values[$field][1]->where_values + $this->where_values[$field][1]->additional_values;
                        $this->additional_values[$field] = $this->where_values[$field][1]->last_query;
                        $this->where_values[$field][1] = $this->where_values[$field][1]->last_query;
                    }
                    $or_and = "AND";
                    if(is_array($rule)){
                        $or_and = $rule[1];
                        $rule = $rule[0];
                    }
                    if($rule == "= ANY" || $rule == "IN" || $rule == "NOT IN"){
                        $query .= " $field ".$rule.' ({{'.$field.'}})'.$or_and;
                    } else{
                        $query .= " $field ".$rule.' {{'.$field.'}} '.$or_and;
                    }
                }
                
                $query = trim($query, 'AND');
                $query = trim($query, 'OR');
            }

            if (count($this->order_by) > 0) {
                $query .= "ORDER BY ";
                foreach ($this->order_by as $key => $order) {
                    $query .= "$key $order,";
                }
                $query = trim($query, ",");
            }
            if ($this->limit > 0) {
                $query .= " LIMIT $this->limit";
            }
        }

        if($refresh){
            $this->refreshBuilder();
        }
        $this->last_query = $query;
        return $this;
    }

    public function refreshBuilder(){
        $this->where_fields = [];
        $this->limit = 0;
        $this->insert_values = [];
        $this->order_by = [];
        $this->update_fields = [];
        $this->type = "";
        $this->query = "";
    }
}
