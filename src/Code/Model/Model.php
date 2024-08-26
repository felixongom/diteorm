<?php
namespace Dite\Model;

use Dite\DB\Connection;
use Dite\Query\QueryBuilder;
use PDO;
use PDOException;

class Model{
    protected $id_for_one_record;
    private $sql = null;
    private $prepared_values = [];
    private $select = '*';
    private $table_name = null;
    private $where = '';
    private $group_by;
    private $order_by = '';
    private static $limit;
    private static $skip = 0;
    private static $page = 0;
    private static $per_page = 12;
    private $activate_paginating = false;
    private $activate_find = false;
    private $called_class;
    private $first_table_to_join = '';
    private $second_table_to_join = '';
    private $join_result_string = '';
    private $result_by_id;
    private $child_table;
    private $___idname;
    private $activate_hasone;

    private static $passed_table_name;
    // 
    public function __construct($table = null)
    {
        self::$passed_table_name = $table;
    }

    //organising some key variables into on object for static methods
    private static function maker($where_selector = [], $select = '*'){
        $instance = new Connection();
        $builder = new QueryBuilder();
        $genResult = [];
        $table_name = get_called_class();
        // 
        $genResult['instance'] = $instance;
        $genResult['builder'] = $builder;
        $genResult['table_name'] = get_called_class();
        $genResult['where'] = $builder->where($table_name, $where_selector);
        $genResult['prepared_values'] = $builder->getPreparedValues();
        $genResult['star'] = self::selector($select);
        $instance = null;
        return (object) $genResult;
    }
    // ******************************************************************************************
    //checks if there is some result, return booleanall the record
     public static function exist(int|string|array $where_selector=[]):bool{
        $result = null;
        if(is_int($where_selector) || is_string($where_selector)){
            $result = self::findById($where_selector);
        }else{
            $result = self::findOne($where_selector);
        }
        return $result?true:false;
    }
    //dropping the table
    public static function drop():bool{
        try{
            $maker = self::maker();
            $sql = "DROP TABLE $maker->table_name";
            // 
            $stmt = $maker->instance->connect()->prepare($sql);
            $stmt->execute();
            // Close connection
            return true;
        }catch(PDOException $e){
            // print_r($e);
            return false;
        }
    }
    //fetches all the record
     public static function all(array $where_selector = [], array|string $select = '*'){
        $maker = self::maker($where_selector, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $results = $stmt->fetchAll($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return $results??[];
    }
    //fetches one record by id
    public static function findById(int|string $where_selector, array|string $select = '*'){
        $maker = self::maker($where_selector, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return $result;
    }
    //fatches on record that matches the query
    public static function findOne(array $where_selector=[], array|string $select = '*'){
        $maker = self::maker($where_selector, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where LIMIT 1";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return $result;
    }
    //fetches the last record that matches the query
    public static function last(array $where_selector = [], array|string $select = '*'){
        $maker = self::maker($where_selector, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where ORDER BY {$maker->builder->idColName($maker->table_name)} DESC LIMIT 1";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return $result;
    }
    //fetches the first record that matches the query
    public static function first(array $where_selector = [], array|string $select = '*'){
        $maker = self::maker($where_selector, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where ORDER BY {$maker->builder->idColName($maker->table_name)} LIMIT  1";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return $result;
    }
    //delete by id
    private static function doDelete(int|string|array $where_selector){
        $maker = self::maker($where_selector);
        $deleted_record = true;
        // 
        if(is_int($where_selector) || is_string($where_selector)){
            $deleted_record = self::findById($where_selector);
        }
        // 
        if(!$deleted_record){
            return false;
        }
        $sql = "DELETE FROM $maker->table_name $maker->where";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);

        return $result && (is_int($where_selector)||is_string($where_selector))?$deleted_record:false;
    }
    //delete by id
    public static function delete(int|string $where_selector){
        return self::doDelete($where_selector);
    }
    //delete many that matches the query
    public static function deleteMany(array $where_selector):void{
        self::doDelete($where_selector);
     }
    //count records
    public static function countRecords(array $where_selector = [], array|string $column = '*'):int{
        $maker = self::maker($where_selector);
        $sql = "SELECT COUNT($column) AS total FROM $maker->table_name $maker->where";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return $result;
    }

    // ******************************************************************************************
    //creates new record

     public static function create(array $data){
        if (count($data)==0) return;
        $instance = new Connection();
        $many = is_int(array_keys($data)[0]);
        if($many){// checks for indexed array meaning creating multiple records
           foreach($data as $each_data){
               self::addAndPersistsToDb($each_data, $instance);
           }
           $total = count($data);
           $instance->debargPrint(null, null, "$total records created");
          
        }elseif(!$many){// checks for indexed array meaning creating single record
           return self::addAndPersistsToDb($data, $instance); 
        }
    }
    //the actual function that adds to the database
    private static function addAndPersistsToDb(array $data, $instance){
        $table_name = strtolower(get_called_class()); //table name
        $conn = $instance->connect();
        $isSqlite = $instance->env()['DRIVER'] === 'sqlite';
        $time_colmn_exist = $instance->includeTime($table_name, 'created_at'); //boolean
        $timestamp = $time_colmn_exist?'created_at, updated_at':null;
        $createdat_val = $time_colmn_exist?[date("Y-m-d h:i:s"), date("Y-m-d h:i:s")]:[];
        // 
        $max_id = $isSqlite?self::autoincreamentId($table_name, $conn,  $isSqlite):'';
        $values = array_values($data);// get values
        // chang the boolean values
        $qnmarks = null; 
        $columns = null;
        $SQL = null;
        $prepared_values = null;
        // 
        if($isSqlite && $time_colmn_exist){ //is sqlite and timestamp column exist
            $qnmarks = '('. str_repeat('?,', count($data) + 2).'?)'; //get question marks
            $columns = strtolower($table_name.'_id, ').join(", ",array_keys($data)).", $timestamp";
            $SQL = "INSERT INTO $table_name ($columns) VALUES $qnmarks";
            $prepared_values = [$max_id, ...$values, ...$createdat_val];
        }elseif($isSqlite && !$time_colmn_exist){//is sqlite and timestamp column does not exist
            $qnmarks = '('. str_repeat('?,', count($data)).'?)'; //get question marks
            $columns = strtolower($table_name.'_id, ').join(", ",array_keys($data));
            $SQL = "INSERT INTO $table_name ($columns) VALUES $qnmarks";
            $prepared_values = [$max_id, ...$values];
        }else{//is other DRIVER and timestamp column exist
            $qnmarks = '('. str_repeat('?,', count($data) - 1).'?)'; //get question marks
            $columns = join(", ",array_keys($data));
            $SQL = "INSERT INTO $table_name ($columns) VALUES $qnmarks";
            $prepared_values = [...$values];
        }
        //execut the query
        $stmt = $conn->prepare($SQL);
        $stmt->execute([...$prepared_values]);
        $lastId = $conn->lastInsertId();
        // deburg messeges
        $sigle_success_messege = "1 record created";
        $instance->debargPrint($SQL, join(', ', [...$prepared_values ]), $sigle_success_messege);  
        return self::findById($lastId);
    }
    
    //auto increament sql id
    private static function autoincreamentId($table_name, $conn){
        $sql = "SELECT MAX(ROWID) + 1 max_id FROM $table_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['max_id']?:1;
    }
    // ******************************************************************************************
    //updates records all the record
     private static function doUpdate(int|string|array $where_selector, array $values_to_set){
        $maker = self::maker($where_selector);
        $values = array_values($values_to_set);
        $set = $maker->builder->updateSetValues($values_to_set); 
        $sql = "UPDATE $maker->table_name SET $set $maker->where";
        //
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute([...$values, ...$maker->prepared_values]);
        // Fetch the records so we can display them in our template.
        $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, [...$values,...$maker->prepared_values], null, true);
        $res = is_int($where_selector)||is_string($where_selector)?self::findById($where_selector):true;
        return $res;
    } 
    //updates records all the record
     public static function update(int|string $where_selector, array $values_to_set){
        return self::doUpdate($where_selector, $values_to_set);
    } 
    //updats records all the record
    public static function updateMany(array $where_selector, array $values_to_set){
         return self::doUpdate($where_selector, $values_to_set);
    } 
    //Join section
    //join
    private static function doJoins($second_table, $type_of_join, $select='*',$where = []){
        $maker = self::maker($where, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $type_of_join $second_table ON 
        $maker->table_name.{$maker->builder->idColName($maker->table_name)} = $second_table.{$maker->builder->idColName($maker->table_name)} $where";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $results = $stmt->fetchAll($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return count($results)===0?[]:$results;
    }
    
    //join
    public static function joins(string $second_table, array $where = [], array|string $select = '*'){
        return self::doJoins($second_table, "INNER JOIN", $select, $where); 
    }
    //innerjoin
    public static function innerJoins(string $second_table, $where = [], array|string $select = '*'){
        return self::doJoins($second_table, "INNER JOIN", $select, $where);
    }
    //left joins
    public static function leftJoins(string $second_table, $where, array|string $select = '*'){
        return self::doJoins($second_table, "LEFT JOIN", $select, $where);
    }
    //right joins
    public static function rightJoins(string $second_table, $where = [], array|string $select = '*'){
        return self::doJoins($second_table, "RIGHT JOIN", $select, $where);
    }
    //fetches all the record
    public static function sql(string $sql, array $values = []){
        $maker = self::maker();
        $values = $values===[]?null:$values;
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($values);
        // Fetch the records so we can display them in our template.
        $results = $stmt->fetchAll($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $values, null, true);
        return $results;
    }
    // ******************************************************************************************
    // ******************************************************************************************
    // ******************************************************************************************
    // non static methods
    public function ___f($child_table = null){
        $maker = self::maker([]);
        //getting the table name for both Modal instanse and the custom model extnding
        $table_name = self::$passed_table_name?: $maker->table_name;
        $this->table_name = $child_table?:$table_name;
        $this->child_table = $child_table;
        //
        $this->first_table_to_join = $table_name;
        $this->activate_find = true;
        return $this;
    }
    // non static methods
    public function where(array $where_selector = []){
        if($this->___idname && is_array($where_selector)) {
            $idcolName = strtolower($this->table_name.".".$this->___idname)."_id";
            // 
            if(array_key_exists($idcolName, $where_selector)){
                unset($where_selector[$idcolName]);
            }
        }
        // 
        $maker = self::maker($where_selector);
        //getting the table name for both Modal instanse ans the custom model extnding
        $this->where .= $maker->where;
        //getting the table name for both Modal instanse and the custom model extnding
        $this->prepared_values = $maker->prepared_values;
        return $this;
    }
    // non static methods for finding on result by its id
    public function __ByPk(int|string $primary_key){
        $maker = self::maker($primary_key);
        //getting the table name for both Modal instanse ans the custom model extnding
        $table_name = self::$passed_table_name?: $maker->table_name;
        $this->___idname = strtolower($table_name);
        $where = str_replace('dite\model\model_id', strtolower($table_name.'_id'), $maker->where);
        $this->id_for_one_record = $primary_key;
        $this->sql = "SELECT * FROM $table_name $where LIMIT 1";
        // 
        $stmt = $maker->instance->connect()->prepare($this->sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $stm_result = $stmt->fetch($maker->instance->fetchMode());
        $this->result_by_id = $stm_result?:'null';
        $maker->instance->debargPrint($this->sql, $maker->prepared_values, null, true);
        return $this;
    }
    // non static methods to paginate
    // public function ___p(){
    //     $maker = self::maker([]);
    //     //getting the table name for both Modal instanse and the custom model extnding
    //     $table_name = self::$passed_table_name?: $maker->table_name;
    //     $this->table_name = $table_name;
    //     //
    //     $this->first_table_to_join = $table_name;
    //     $this->activate_paginating = true;
    //      $this->prepared_values = $maker->prepared_values;
    //     return $this;
    // }
    // ******************************************************************************************
    // ******************************************************************************************
    // ******************************************************************************************
    
    //do join
    private function doJoin(string $second_table, $type_of_join = "INNER JOIN"){
        $maker = self::maker();
        //getting the table name for both Modal instanse ans the custom model extnding
        $table_name = self::$passed_table_name;
        // 
        $this->called_class = $table_name;
        $this->first_table_to_join = $this->first_table_to_join?:$this->second_table_to_join;
        $this->second_table_to_join = $second_table;
        $this->join_result_string .= "$type_of_join $this->second_table_to_join ON $this->first_table_to_join.{$maker->builder->idColName($this->first_table_to_join)} = $this->second_table_to_join.{$maker->builder->idColName($this->first_table_to_join)} "??null;
        // 
        $this->first_table_to_join = $this->second_table_to_join;
        return $this;
    }
    
    //do join
    private function doJoinOn( string $type_of_join, string $table, string $join_condition){
        $this->join_result_string .= "$type_of_join $table ON $join_condition "??null;
        return $this;
    }
    // join
    public function join(string $second_table){
        $this->doJoin($second_table, 'INNER JOIN');
        return $this;
    }
    //inner join
    public function innerJoin(string $second_table){
        $this->doJoin($second_table, 'INNER JOIN');
        return $this;
    }
    //inner join
    public function leftJoin(string $second_table){
        $this->doJoin($second_table, 'LEFT JOIN');
        return $this;
    }
    //inner join
    public function rightJoin(string $second_table){
        $this->doJoin($second_table, 'RIGHT JOIN');
        return $this;
    }
    // join
    public function joinOn(string $table, string $join_condition){
        $this->doJoinOn('INNER JOIN',$table, $join_condition);
        return $this;
    }
    //inner join
    public function innerJoinOn(string $table, string $join_condition){
        $this->doJoinOn('INNER JOIN',$table, $join_condition);
        return $this;
    }
    //inner join
    public function leftJoinOn(string $table, string $join_condition){
        $this->doJoinOn('LEFT JOIN',$table, $join_condition);
        return $this;
    }
    //inner join
    public function rightJoinOn(string $table, string $join_condition){
        $this->doJoinOn('RIGHT JOIN',$table, $join_condition);
        return $this;
    }
    // select som fields
    public function select(string|array $select = "*"){
        $_select = null;
        if(is_string($select)){
            $_select = $select;
        }elseif(is_array($select)){
            $_select = join(", ", $select);
        }
        $this->select = $this->select=='*'?$_select:$this->select.$_select;
        return $this;
    }
    // select some fields
    private static function selector(string|array $select = "*"){
        $selector = $select;
        if(is_string($select)){
            $selector = $select;
        }elseif(is_array($select)){
            $selector = join(", ", $select);
        }
        return $selector;
    }

    // group by 
    public function groupBy(string $group_by){
        $this->group_by = $group_by;
        return $this;
    }
    // generate the limit
    public function limit(string|int $limit = 12){
        self::$limit = $limit<1?12:$limit;
        $this->activate_find = true;
        return $this;
    }
    // generate the skip
    public function skip(string|int $skip=0){
        self::$skip = $skip<0?0:$skip;
        $this->activate_find = true;
        return $this;
    }
    // generate the offset
    public function offset(string|int $skip=0){
        return $this->skip($skip);
    }
    // generate the skip
    public function page(string|int $page=1){
        self::$page = $page<1?1:$page;
        $this->activate_paginating = true;
        return $this;
    }
    // generate the skip
    public function perpage(string|int $per_page=12){
        self::$per_page = $per_page<1?12:$per_page;
        $this->activate_paginating = true;
        return $this;
    }

    // generate orderby
    public function orderBy(string|array $order_array){
        $i = 1;
        foreach ($order_array as $key => $value) {
            if(is_int($key)){
                //it is plain array
                $this->order_by.=$i < count($order_array)?"ORDER BY $value, ":"ORDER BY $value";
            }elseif(is_string($key)){
                // associative array
                $_value = strtoupper($value);
                $this->order_by.=$i<count($order_array)?"ORDER BY $key $_value, ":" ORDER BY $key $_value";                
            }
            $i++;
        }
        return $this;
    }
    //custom userid
    private function customWhere(){
        $idcolName = strtolower($this->___idname);
        $this->table_name = strtolower($this->child_table?:$this->table_name);
        // 
        if(!$this->activate_hasone){
            $child_where = "WHERE ".$this->table_name.".".$idcolName."_id = ".$this->id_for_one_record;
            $main_where = is_string($this->where)!==''?str_replace('WHERE', ' AND ', $this->where):$this->where;
            // 
            $this->where = $this->result_by_id?$child_where.$main_where : $this->where;
            // 
        }
    }
    // generate column names
    public function get(){
        $self_limit = self::$limit;
        $self_skip = self::$skip;
        $self_page = self::$page;
        $self_perpage = self::$per_page;
        //         
        if(isset($this->result_by_id) && $this->result_by_id === 'null'){// it returns the array
            if($this->___idname && $this->activate_hasone) return false;
            return $this->___idname?[]:false;
        }elseif(($this->result_by_id || $this->child_table) && !$this->table_name){//retrns sigle record bt is
            $maker = self::maker($this->id_for_one_record);
            //getting the table name for both Modal instanse ans the custom model extnding
            $this->table_name = strtolower(self::$passed_table_name?: $maker->table_name);
            //if this chin of child table is active
            $where = str_replace('dite\model\model_id', strtolower($this->table_name .'_id'), $maker->where);
            // 
            $_sql = "SELECT $this->select FROM $this->table_name  $this->join_result_string $where LIMIT 1";
            $stmt = $maker->instance->connect()->prepare($_sql);
            $stmt->execute($maker->prepared_values);
            // Fetch the records so we can display them in our template.
            $stm_result = $stmt->fetch($maker->instance->fetchMode());
            
            return $stm_result;
        }else{ 
            //providing the where clouse of childe table
            $this->customWhere();
            // 
            $this->sql = "SELECT $this->select FROM $this->table_name $this->where";
            
            if($this->group_by){
                $this->sql.= " GROUP BY $this->group_by ";
            }
            // 
            if($this->called_class){
                $this->sql = str_replace("SELECT * FROM $this->called_class", "SELECT * FROM $this->called_class $this->join_result_string", $this->sql);
            }
            // 
            if($this->order_by){
                $this->sql.= " $this->order_by ";
            }
            // count the number before limiting pagination 
            $this->sql = "SELECT $this->select FROM $this->table_name $this->join_result_string $this->where";
            $count_sql = "SELECT * FROM $this->table_name $this->join_result_string $this->where";
            $total_count = $this->countTotal(str_replace('*', "COUNT(*) AS total_count", $count_sql));
            // 
            $DRIVER = self::maker()->instance->env()["DRIVER"];
            if($this->activate_paginating){
                self::$skip = (self::$page-1)*self::$per_page;
                self::$limit = self::$per_page;
                $skip = self::$skip;
                $limit = self::$limit;
                //             
                if($DRIVER === "postgresql"){
                    $this->sql.= " LIMIT $skip OFFSET $limit";
                }else{
                    $this->sql.= " LIMIT $skip, $limit";   
                }
            }elseif($this->activate_find && self::$limit){
                if($DRIVER === "postgresql"){
                    $this->sql.= " LIMIT $self_skip OFFSET $self_limit";
                }else{
                    $this->sql.= " LIMIT $self_skip, $self_limit";   
                }
            }
            //    
            $instance = new Connection();
            $conn = $instance->connect();
            $stmt = $conn->prepare($this->sql);
            $stmt->execute($this->prepared_values);
            // Fetch the records so we can display them in our template.
            $results = null;
            if($this->activate_paginating){
                //handles pagination calculate additional info
                $data = $stmt->fetchAll($instance->fetchMode());
                $results = [];
                $num_pages = ceil($total_count/self::$per_page); 
                $has_next = (self::$page * self::$per_page) < $total_count; 
                $has_prev = (self::$page - 1)*self::$per_page > 0; 
                $prev_page = $has_prev?self::$page-1:null; 
                $next_page = $has_next?self::$page+1:null; 
                // 
                $results['num_records'] = $total_count;
                $results['num_pages'] = $num_pages;
                $results['current_page'] = $total_count===0?null:self::$page;
                $results['has_next'] = $has_next;
                $results['has_prev'] = $has_prev;
                $results['next_page'] = $next_page;
                $results['prev_page'] = $prev_page;
                $results['per_page'] = $total_count===0?null:self::$per_page;
                $results['position'] = ((self::$page - 1) * self::$per_page) + 1;
                $results['data'] = $data;
                //
                $results = $instance->isObjMode()? (object) $results:$results;
            }else{
                $results = $this->activate_hasone?
                            $stmt->fetch($instance->fetchMode()):
                            $stmt->fetchAll($instance->fetchMode());
                }
            }
        $instance->debargPrint($this->sql, $this->prepared_values, null, true);
        $instance = null;
        
        return !$results?[]:$results;
    }  
    private function countTotal(string $sql):int{
        $maker = self::maker();
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($this->prepared_values);
        // Fetch the records so we can display them in our template.
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result->total_count;
    }
    //relationship has manay function
    public function hasMany(string $ref_table, array $where_selector=[]){
        // $maker = self::maker($where_selector);
        // $where = str_replace("WHERE", "", $this->where?:$maker->where);
        // $use_where = $where ? " AND $where ":null;
        // $prepared_values = $maker->prepared_values ?:[];
        // // 
        // $parent_col = $maker->builder->idColName($maker->table_name);
        // $this->sql = "SELECT * FROM $ref_table WHERE $parent_col = ? $use_where";
        // // 
        // $stmt = $maker->instance->connect()->prepare($this->sql);
        // $stmt->execute([$this->id_for_one_record, ...$prepared_values]);
        // // 
        // $results = $stmt->fetchAll($maker->instance->fetchMode());
        // $maker->instance->debargPrint($this->sql, [$this->id_for_one_record], null, true);
        // return $results??[];
        // *****************************************************
        // $child_table = new Model($ref_table);
        // print_r(get_called_class());
        // return $child_table->___f($ref_table);
        return $this->___f($ref_table );
    }
    
    //relationship has one function
    public function hasOne(string $ref_table){
        // $maker = self::maker();
        // $parent_col = $maker->builder->idColName($maker->table_name);
        // // 
        // $this->sql = "SELECT * FROM $ref_table WHERE $parent_col = ? LIMIT 1";
        // $stmt = $maker->instance->connect()->prepare($this->sql);
        // $stmt->execute([$this->id_for_one_record]);
        // $results = $stmt->fetch($maker->instance->fetchMode());
        // // 
        // $maker->instance->debargPrint($this->sql, [$this->id_for_one_record], null, true);
        // return $results??[];

        // ***************************
        // ***************************
        // print_r($this->result_by_id);
        $this->activate_hasone = true;
        return $this->___f($ref_table);
    }
    //relationship belongs to function
    public function belongsToOne(string $ref_table){
        // $maker = self::maker();
        // $ref_col = $maker->builder->idColName($ref_table);
        // $this->sql = "SELECT * FROM $ref_table WHERE $ref_col = ? LIMIT 1";
        // // 
        // $stmt = $maker->connect()->prepare($this->sql);
        // $stmt->execute([$this->id_for_one_record]);
        // // 
        // $results = $stmt->fetch($maker->instance->fetchMode());
        // $maker->instance->debargPrint($this->sql, [$this->id_for_one_record], null, true);
        // return $results??[];
    }
    //relationship for many to many function
    public function hasManyMany(string $ref_table, array $where_selector=[]):array{
        $maker = self::maker($where_selector);
        $where = str_replace("WHERE", "", $maker->where);
        $use_where = $where ? " AND $where ":null;
        $prepared_values = $maker->prepared_values ?:[];
        // 
        $new_table_name = $maker->table_name.="_";
        $pivot_table = $new_table_name.=$ref_table;
        $parent_col = $maker->builder->idColName($maker->table_name);
        $ref_col = $maker->builder->idColName($ref_table);
        // 
        $this->sql = "SELECT * FROM $ref_table WHERE $ref_col IN 
                     (SELECT $ref_col FROM $pivot_table WHERE $parent_col = $this->id_for_one_record) $use_where";
        $stmt = $maker->instance->connect()->prepare($this->sql);
        $stmt->execute([$this->id_for_one_record , ...$prepared_values]);
        $results = $stmt->fetchAll($maker->instance->fetchMode());
        // 
        $maker->instance->debargPrint($this->sql, [$this->id_for_one_record], null, true);
        return $results??[];
    }

    ///***************************************************************** */
    ///***************************************************************** */
    ///***************************************************************** */
    ///***************************************************************** */
    public static function findByPk($where){
        $inst = new Model(strtolower(get_called_class()));
        return $inst->__ByPk($where);
    }
    public static function find(){
        $inst = new Model(strtolower(get_called_class()));
        return $inst->___f();
    }
    //table
    public static function table($tablename){
        $inst = new Model(strtolower($tablename));
            return $inst->___f();
    }
    // ***************************************************************************
    // ***************************************************************************
    // ***************************************************************************
    // ***************************************************************************
    // //paginate
    // public static function paginate(){
    //     $inst = new Model(get_called_class());
    //     return $inst->___p();
    // }
    // //ptable for pagination
    // public static function Ptable($tablename){
    //     $inst = new Model($tablename);
    //     return $inst->___p(); 
    // }  
 }