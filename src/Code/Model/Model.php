<?php
namespace Dite\Model;

use Dite\DB\Connection;
use Dite\Query\QueryBuilder;
use PDO;
use PDOException;

class Model{

    private static $setup = null;
    protected $id_for_one_record;
    private $sql = null;
    private $pk_sql = null;
    private $sql_hasmany = null;
    private $sql_hasone = null;
    private $sql_belongs_to = null;
    private $sql_has_many_through = null;
    private $belongs_to_ = null;
    private $has_many_through_table = null;
    private $sql_blongs_to_many = null;
    private $activate_findby_pk = false;
    // 
    private $with_one_array = [];
    private $with_array = [];
    private $attach_array = [];
    private $with_through_array = [];
    private $attach_through_array = [];
    // 
    private $with_most_data = [];
    private static $with_least = false;
    private static $without = false;
    // 
    private static $sql_with_most = null; 
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
    private $___idname;
    private $activate_hasone;

    private static $passed_table_name;
    private $belongs_to_table;
    // 
    public function __construct($table = null){   
        self::$passed_table_name = self::$passed_table_name?:$table;
    }
    // 
    public static function setup(array $setup_array){
        var_dump(self::$setup);
        self::$setup = $setup_array;
    }
    //organising some key variables into on object for static methods
    private static function maker($where = [], $select = '*'){
        $instance = new Connection(self::$setup);
        $builder = new QueryBuilder();
        $genResult = [];
        $table_name = $instance->renameTable(self::$passed_table_name??get_called_class());
        // 
        $genResult['instance'] = $instance;
        $genResult['builder'] = $builder;
        $genResult['table_name'] = $table_name;
        $genResult['where'] = $builder->where($table_name, $where??[]);
        $genResult['prepared_values'] = $builder->getPreparedValues();
        $genResult['star'] = self::selector($select);
        $instance = null;
        return (object) $genResult;
    }
    // ******************************************************************************************
    //checks if there is some result, return booleanall the record
     public static function exist(int|string|array $where=[]):bool{
        $result = null;
        if(is_int($where) || is_string($where)){
            $result = self::findById($where);
        }else{
            $result = self::findOne($where);
        }
        return $result?true:false;
    }
    //dropping the table
    public static function drop():bool{
        try{
            $maker = self::maker();
            $sql = "DROP TABLE $maker->table_name";
            $stmt = $maker->instance->connect()->prepare($sql);
            $stmt->execute();
            // Close connection
            return true;
        }catch(PDOException $e){
            return false;
        }
    }
    //fetches all the record
     public static function all(array $where, array|string $select = '*'){
        $maker = self::maker($where??null, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where";
        return self::sql($sql, $maker->prepared_values);
    }
    //fetches one record by id
    public static function findById(int|string|array $where, array|string $select = '*'){
        if(is_array($where) && is_int(array_keys($where)[0])){
            $maker = self::maker($where, $select);
            $where = [$maker->table_name.'_id'=>[':in'=>$where]];
            return self::all($where, $maker->star);
        }else{
            $maker = self::maker($where, $select);
            $sql = "SELECT $maker->star FROM $maker->table_name $maker->where LIMIT 1";
            return self::sql($sql,$maker->prepared_values, false);
        }
        
    }
    //fatches on record that matches the query
    public static function findOne(array $where = null, array|string $select = '*'){
        $maker = self::maker($where??null, $select);
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where LIMIT 1";
        return self::sql($sql, $maker->prepared_values, false);
    }
    //fetches the last record that matches the query
    public static function last(array $where = null, array|string $select = '*'){
        $maker = self::maker($where??null, $select);
        $id_column = $maker->table_name.'_id';
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where ORDER BY $id_column DESC LIMIT 1";
        return self::sql($sql, $maker->prepared_values, false);
        
    }
    //fetches the first record that matches the query
    public static function first(array $where = null, array|string $select = '*'){
        $maker = self::maker($where??null, $select);
        $id_column = $maker->table_name.'_id';
        $sql = "SELECT $maker->star FROM $maker->table_name $maker->where ORDER BY $id_column LIMIT  1";
        return self::sql($sql, $maker->prepared_values, false);
    }
    //delete by id
    private static function doDelete(int|string|array $where){
        $maker = self::maker($where);
        $deleted_record = true;
        // 
        if(is_int($where) || is_string($where)){
            $deleted_record = self::findById($where);
        }
        // 
        if(!$deleted_record){
            return false;
        }
        $sql = "DELETE FROM $maker->table_name $maker->where";
        self::sql($sql, $maker->prepared_values, false);
        return (is_int($where)||is_string($where))?$deleted_record:false;
    }
    //delete by id
    public static function delete(int|string|array $where){
        if(is_array($where) && is_int(array_keys($where)[0])){
            foreach ($where as $id) {
                self::doDelete($id);
            }
        }elseif(is_array($where)){
            self::doDelete($where);
        }else{
            return self::doDelete($where);
        }
    }
    //count records
    public static function countRows(array $where = []):int{
        $maker = self::maker($where);
        $id_column = $maker->table_name.'_id';
        $sql = "SELECT COUNT($id_column) AS total FROM $maker->table_name $maker->where";
        return self::sql($sql, $maker->prepared_values, false);

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
        $table_name = $instance->renameTable(get_called_class()); //table name
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
            $columns = $table_name.'_id, '.join(", ",array_keys($data)).", $timestamp";
            $SQL = "INSERT INTO $table_name ($columns) VALUES $qnmarks";
            $prepared_values = [$max_id, ...$values, ...$createdat_val];
        }elseif($isSqlite && !$time_colmn_exist){//is sqlite and timestamp column does not exist
            $qnmarks = '('. str_repeat('?,', count($data)).'?)'; //get question marks
            $columns = $table_name.'_id, '.join(", ",array_keys($data));
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
     private static function doUpdate(int|string|array $where, array $values_to_set){
        $maker = self::maker($where);
        $values = array_values($values_to_set);
        $set = $maker->builder->updateSetValues($values_to_set); 
        $sql = "UPDATE $maker->table_name SET $set $maker->where";
        //
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute([...$values, ...$maker->prepared_values]);
        // Fetch the records so we can display them in our template.
        $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, [...$values,...$maker->prepared_values], null, true);
        $res = is_int($where)||is_string($where)?self::findById($where):true;
        return $res;
    } 
    //updates records all the record
     public static function update(int|string $where, array $values_to_set){
         return self::doUpdate($where, $values_to_set);
    } 

    //Join section
    //join
    private static function doJoins($second_table, $join_condition, $type_of_join, $select='*',$where = []){
        $sql = null;
        $maker = self::maker($where, $select);
        if($join_condition){
            $second_table = $maker->instance->renameTable($second_table);
            // 
            $join_result_string = "$type_of_join $second_table ON $join_condition "??null;
            $sql = "SELECT $maker->star FROM $maker->table_name $join_result_string $maker->where";
        }else{
            $sql = "SELECT $maker->star FROM $maker->table_name $type_of_join $second_table ON 
            $maker->table_name.{$maker->builder->idColName($maker->table_name)} = $second_table.{$maker->builder->idColName($maker->table_name)} $maker->where";
        }
        return self::sql($sql, $maker->prepared_values);
    }
    
    //join
    public static function joins(string $second_table, string $join_condition = null, array $where = [], array|string $select = '*'){
        return self::doJoins($second_table, $join_condition, "JOIN", $select, $where); 
    }
    //innerjoin
    public static function innerJoins(string $second_table, $join_condition = null, $where = [], array|string $select = '*'){
        return self::doJoins($second_table, $join_condition, "INNER JOIN", $select, $where);
    }
    //left joins
    public static function leftJoins(string $second_table, $join_condition = null, $where = [], array|string $select = '*'){
        return self::doJoins($second_table, $join_condition, "LEFT JOIN", $select, $where);
    }
    //right joins
    public static function rightJoins(string $second_table, $join_condition,$where = [], array|string $select = '*'){
        return self::doJoins($second_table, $join_condition, "RIGHT JOIN", $select, $where);
    }
    //right outer joins
    public static function rightOuterJoins(string $second_table, $join_condition,$where = [], array|string $select = '*'){
        return self::doJoins($second_table, $join_condition, "RIGHT OUTER JOIN", $select, $where);
    }
    //right outer joins
    public static function leftOuterJoins(string $second_table, $join_condition,$where = [], array|string $select = '*'){
        return self::doJoins($second_table, $join_condition, "RIGHT OUTER JOIN", $select, $where);
    }
    //fetches all the record
    public static function sql(string $sql, array $values = null, $return_many = true){
        $values = null??[];
        $maker = self::maker();
        $values = $values===[]?null:$values;
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($values);
        // Fetch the records so we can display them in our template.
        $results = $return_many?
                    $stmt->fetchAll($maker->instance->fetchMode()):
                    $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $values, null, true);
        return $results;
    }
    // ******************************************************************************************
    // ******************************************************************************************
    // ******************************************************************************************
    // non static methods
    public function ___f(){
        $maker = self::maker();
        //getting the table name for both Modal instanse and the custom model extnding
        $table_name = $maker->instance->renameTable(self::$passed_table_name)?: $maker->table_name;
        $this->table_name = $table_name;
        $this->first_table_to_join = $table_name;
        $this->activate_find = true;
        return $this;
    }
    // non static methods
    public function where(array $where = []){
        if(is_array($where)) {
            $idcolName = "$this->table_name.$this->___idname"."_id";
            // 
            if(array_key_exists($idcolName, $where)){
                unset($where[$idcolName]);
            }
        }
        // 
        
        $maker = self::maker($where);
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
        $this->table_name = $maker->instance->renameTable(self::$passed_table_name?: $maker->table_name);
        $this->___idname = $this->table_name;
        $where = str_replace('dite\_model\_model_id', $this->table_name.'_id', $maker->where);
        $this->id_for_one_record = $primary_key;
        // 
        $id_col = $this->table_name.'_id';
        $this->sql = "SELECT * FROM $this->table_name $where LIMIT 1";
        $this->pk_sql = "SELECT $id_col FROM $this->table_name $where LIMIT 1";
        $this->activate_findby_pk = true;
        return $this;
    }
    // ******************************************************************************************
    // ******************************************************************************************
    // ******************************************************************************************
    
    //do join
    private function doJoin(string $second_table, string $join_condition = null , $type_of_join = "INNER JOIN"){
       $second_table = strtolower($second_table);
        if($join_condition){
            $this->join_result_string .= "$type_of_join $second_table ON $join_condition "??null;
        return $this;
        }else{

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
    }

    public function join(string $second_table, string $join_condition = null){
        $this->doJoin($second_table, $join_condition, 'JOIN' );
        return $this;
    }
    //inner join
    public function innerJoin(string $second_table, string $join_condition = null){
        $this->doJoin($second_table, $join_condition, 'INNER JOIN');
        return $this;
    }
    //LEFT OUTER JOIN
    public function letftOuterJoin(string $second_table,string $join_condition = null){
        $this->doJoin($second_table, $join_condition, 'LEFT OUTER JOIN');
        return $this;
    }
    //right outer join
    public function rightOuterJoin(string $second_table, string $join_condition = null){
        $this->doJoin($second_table, $join_condition, 'RIGHT OUTER JOIN' );
        return $this;
    }
    //left OUTER join
    public function leftOuterJoin(string $second_table, string $join_condition = null){
        $this->doJoin($second_table, $join_condition, 'LEFT OUTER JOIN');
        return $this;
    }
    //inner join
    public function rightJoin(string $second_table, string $join_condition = null){
        $this->doJoin($second_table, $join_condition, 'RIGHT JOIN');
        return $this;
    }
    //left join
    public function leftJoin(string $second_table, string $join_condition = null){
        $this->doJoin($second_table, $join_condition, 'LEFT JOIN');
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
        if(is_array($order_array)){

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
        }else{
            $this->order_by.="ORDER BY $order_array";                
        }
        return $this;
    }
    //custom WHERE
    private function customWhere($id){
        $idcolName = $this->___idname."_id";
        $_where = str_replace("WHERE", "AND", $this->where);
        
        if($this->sql_hasmany && $this->where){
            $this->where = str_replace('?',$id,"WHERE $idcolName = ( $this->pk_sql )"). " $_where";
        }elseif($this->sql_hasmany){
            $this->where = str_replace('?',$id,"WHERE $idcolName = ( $this->pk_sql )"). " $_where";
        }elseif($this->sql_hasone){
            $this->where = str_replace('?',$id,"WHERE $idcolName = ( $this->pk_sql )"). " $_where";
        }elseif($this->sql_has_many_through || $this->sql_blongs_to_many){
            $ther_other_table = $this->has_many_through_table."_id";
            $this_other_table = self::$passed_table_name."_id";
            $intermidiate_table = null;
            // 
            if($this->sql_has_many_through){
                $intermidiate_table = self::$passed_table_name."_".$this->has_many_through_table;
            }else{
                $intermidiate_table = $this->has_many_through_table."_".self::$passed_table_name;
            }
            // 
            $_sql_to_link_intemidiate = "SELECT $ther_other_table FROM $intermidiate_table WHERE $this_other_table = ? ";
            $this->where = str_replace('?',$id,"WHERE $ther_other_table IN ( $_sql_to_link_intemidiate )"). " $_where";
            // 
            if($this->sql_has_many_through){
                $this->sql_has_many_through = "$this->sql_has_many_through $this->where";
            } else{
                $this->sql_has_many_through = "$this->sql_blongs_to_many $this->where";
            }
        }
        return $this;
    }
   
    //with
    public function withOne(string $table, array $where = null, string $select = '*'){
        $with_one_table = $table;
        $with_one_where = $where;
        $with_one_select = $select;
        $this->with_one_array = [...$this->with_one_array, [$with_one_table, $with_one_where??[], $with_one_select]];
        return $this;
    }
    public function withAll(string $table, array $where = null, string $select = '*'){
        $with_table = $table;
        $with_where = $where;
        $with_select = $select;
        $this->with_array = [...$this->with_array,[$with_table, $with_where??[], $with_select]];
        return $this;
    }
    public function attach(string $table, string $select = '*'){
        $attach_table = $table;
        $attach_select = $select;
        $this->attach_array = [... $this->attach_array,[$attach_table, $attach_select]];
        return $this;
    }
    // 
    public function withThrough(string $table, string $select = '*'){
        $with_through_table = $table;
        $with_through_select = $select;
        $this->with_through_array = [... $this->with_through_array,[$with_through_table, $with_through_select]];
        return $this;
    }
    // 
    public function attachThrough(string $table, string $select = '*'){
        $attach_through_table = $table;
        $attach_through_select = $select;
        $this->attach_through_array = [... $this->attach_through_array,[$attach_through_table, $attach_through_select]];
        return $this;
    }
    // 
    private function appendWithToResult($result, $instance, $table_name, $with_one_array, bool $multiple_results){
        if($result) return $result;
        $maker = self::maker();
        $table_name = $maker->instance->renameTable($table_name);
        if(!$result) return $result;
        if( is_array($result) && count(array_keys($result))){ //for array of records
            //access individual record
            $response = [];
            foreach($result as $res){
                $res =  $instance->isObjMode()? get_object_vars($res):$res;
                $tie_to_main_table = $table_name."_id = ". $res[$table_name."_id"];
                $res = $this->processWith($res, $multiple_results, $tie_to_main_table, $with_one_array, $maker);
                $response =  [...$response, ($instance->isObjMode()? (object)$res:$res)];
            }
            return $response;
        }else{ //for a single record
            $result =  $instance->isObjMode()? get_object_vars($result):$result;
            $tie_to_main_table = $table_name."_id = ". $result[$table_name."_id"];
            $result = $this->processWith($result, $multiple_results, $tie_to_main_table, $with_one_array, $maker);
            // 
            return $instance->isObjMode()? (object)$result:$result;
        }
    }
    // 
    private function processWith($result, $many, $tie_to_main_table, $with_table, $maker){
        foreach ($with_table as $one_array){// call the function that gets the database
            $result[$maker->instance->renameTable($one_array[0])] = $this->queryAndReturnData($one_array, $many, $tie_to_main_table); 
        }
        return $result;
    }
    
    // *************************************************************************************
    private function queryAndReturnData($one_array, $many = false, $tie_to_main_table){
        $maker = self::maker($one_array[1], $one_array[2]);
        $table = $maker->instance->renameTable($one_array[0]);
        $limit = $many?null:"LIMIT 1";
        //custom where      
        $_where = $maker->where?str_replace("WHERE", "AND", $maker->where):null;
        $sql = "SELECT * FROM $table WHERE $tie_to_main_table $_where $limit";
        // 
        $stmt = $maker->instance->connect()->prepare($sql);
        $stmt->execute($maker->prepared_values);
        // Fetch the records so we can display them in our template.
        $results = $many?
                    $stmt->fetchAll($maker->instance->fetchMode()):
                    $stmt->fetch($maker->instance->fetchMode());
        $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
        return $results??null;
    }

    // *************************************************************************************
    private function attachUpperLevelRecord($result, $instance, $table_name){
        if(!$result || !count($result)) return $result;

        $table_name = $instance->renameTable($table_name);
        if(is_array($result) && count(array_keys($result))){
            $response = [];
            foreach ($result as $one_result) {
                $one_result =  is_object($one_result)? get_object_vars($one_result):$one_result;
                $response = [...$response, $this->processAttach($one_result)];
            }
            return $response;
        }else{
            $result =  is_object($result)? get_object_vars($result):$result;
            $result = $this->processAttach($result);
            return $result;
        }
    }
    //
    private function processAttach($result){
        // Runn a loop to access each table being attached 
        foreach ($this->attach_array as $one_array) {
            $maker = self::maker();
            $table = $maker->instance->renameTable($one_array[0]);
            $where = $table."_id = ".$result[$table."_id"];
            //
            $sql = "SELECT $one_array[1] FROM $table WHERE $where LIMIT 1";
            $result[$table] = self::sql($sql, null, false);
        }
        return $result;
    }
    // *************************************************************************************
    private function appendWithThrough($result, $instance, $table_name){
        $table_name = $instance->renameTable($table_name);
        if(!$result) return $result;
        if(is_array($result) && count(array_keys($result))){
            $response = [];
            foreach ($result as $one_result) {
                $one_result =  $instance->isObjMode()? get_object_vars($one_result):$one_result;
                $response = [...$response, $this->processThrough($one_result, $table_name, true)];
            }           
            return $response;
        }else{
            $result =  $instance->isObjMode()? get_object_vars($result):$result;
            $result = $this->processThrough($result, $table_name, true);
            return $result;
        }
    }
    // 
    private function attachThroughRecord($result, $instance, $table_name){
        $table_name = $instance->renameTable($table_name);
        if(!$result) return $result;
        if(is_array($result) && count(array_keys($result))){
            $response = [];
            foreach ($result as $one_result) {
                $one_result =  is_object($one_result)? get_object_vars($one_result):$one_result;
                $response = [...$response, $this->processThrough($one_result, $table_name, false)];
            }           
            return $response;
        }else{
            $result =  $instance->isObjMode()? get_object_vars($result):$result;
            $result = $this->processThrough($result, $table_name,false);
            return $result;
        }
    }
    // 
    private function processThrough($result, $main_table, $is_with = true){
        // Runn a loop to access each table being attached 
        if(!$result) return $result;
        $table_arrays = $is_with?$this->with_through_array:$this->attach_through_array;
        
        foreach ($table_arrays as $one_array) {
            $maker = self::maker();
            $table = $maker->instance->renameTable($one_array[0]);
            $table_id = $table."_id";
            $main_table_id = $main_table."_id";
            //
            $intermidiat_table = $is_with?
                                $maker->instance->renameTable($main_table)."_".$table:
                                $table."_".$maker->instance->renameTable($main_table);
                                // 
            $where = $table_id." IN ( SELECT $table_id FROM  $intermidiat_table WHERE $main_table_id = $result[$main_table_id])";
            // 
            $sql = "SELECT $one_array[1] FROM $table WHERE $where";
            $maker->instance->debargPrint($sql, null, null, true);
            $result[$table] = self::sql($sql);
        }
        return $result ;
    }
    // *************************************************************************************
    public static function withMost(string $table_name){
        $maker = self::maker();
        $table_name = $maker->instance->renameTable($table_name);
        $first_table_id = $maker->table_name."_id";
        $order = self::$with_least?'ASC':'DESC';
        $sql = "SELECT $maker->table_name.$first_table_id AS table_id, COUNT($maker->table_name.$first_table_id) AS total_count 
                FROM $table_name 
                JOIN  $maker->table_name 
                ON $maker->table_name.$first_table_id = $table_name.$first_table_id
                GROUP BY $table_name.$first_table_id
                ORDER BY total_count $order";
        
        self::$sql_with_most =  $sql;
        return self::find();
    }
    // 
    public static function withLeast(string $table_name){
        self::$with_least = true; 
        return self::withMost($table_name);
    }
    // 
    public static function withOut(string $table_name){
        self::$without = true;
        return self::withMost($table_name);
    }
    // *************************************************************************************

    // binging everything comes together
    public function get(){
        $self_limit = self::$limit;
        $self_skip = self::$skip;
        $maker = self::maker([]);
        //  
        if($this->activate_findby_pk && !($this->sql_hasmany || $this->sql_hasone || $this->sql_belongs_to || $this->sql_has_many_through || $this->sql_blongs_to_many)){// it returns the array found bypk
            $maker = self::maker($this->id_for_one_record);
            $sql = str_replace('*', $this->select, $this->sql);
            $stmt = $maker->instance->connect()->prepare($sql);
            $stmt->execute($maker->prepared_values);
            // Fetch the records so we can display them in our template.
            $stm_result = $stmt->fetch($maker->instance->fetchMode());
            $maker->instance->debargPrint($sql, $maker->prepared_values, null, true);
            $this->activate_findby_pk = false;
            // with one to append several results from the other tables down
            $stm_result = $this->appendWithToResult($stm_result, $maker->instance, $this->table_name, $this->with_one_array, false);
            $stm_result = $this->appendWithToResult($stm_result, $maker->instance, $this->table_name,$this->with_array, true);
            $stm_result = $this->attachUpperLevelRecord($stm_result, $maker->instance, $this->table_name, $this->attach_array);
            $stm_result = $this->appendWithThrough($stm_result, $maker->instance, $this->table_name);
            $stm_result = $this->attachThroughRecord($stm_result, $maker->instance, $this->table_name);
            return $stm_result;
        }else{ 
            //providing the where clouse of childe table
            $this->customWhere($this->id_for_one_record);
            // count the number before limiting pagination 
            $count_sql = null;
            
            if($this->sql_hasmany){
                $_sql = "SELECT $this->select FROM $this->table_name $this->join_result_string $this->where";
                $_sql_count = "SELECT * FROM $this->table_name $this->join_result_string $this->where";
                // 
                $this->sql = str_replace("SELECT $this->select FROM $this->table_name", $this->sql_hasmany, $_sql);
                $count_sql = str_replace("SELECT * FROM $this->table_name",$this->sql_hasmany,$_sql_count);
                
                $this->sql_hasmany = null;
            }elseif($this->sql_hasone){
                $this->sql = str_replace('*',$this->select, "$this->sql_hasone $this->join_result_string $this->where")." LIMIT 1";
                $this->sql_hasone = null;
                
            }elseif($this->sql_belongs_to){
                $_sql = "SELECT $this->select FROM $this->belongs_to_table $this->join_result_string $this->where";
                $belongs_to_id = $this->belongs_to_."_id";
                $partial_where_query = "WHERE $belongs_to_id = ( SELECT $belongs_to_id FROM ".self::$passed_table_name." WHERE $belongs_to_id = $this->id_for_one_record LIMIT 1 )" ;
                // 
                $this->sql = str_replace("SELECT $this->select FROM $this->table_name", $this->sql_belongs_to, $_sql)."$partial_where_query LIMIT 1";
                
            }elseif($this->sql_has_many_through || $this->sql_blongs_to_many){
                $this->sql = $this->sql_has_many_through;
                // 
                $this->sql_blongs_to_many = null;
                $this->sql_has_many_through = null;
            }else{
                $this->sql = "SELECT $this->select FROM $this->table_name $this->join_result_string $this->where";
                //without
                
                if(self::$without && self::$sql_with_most){
                    
                    $ids = [];
                    $response = self::sql(self::$sql_with_most);
                    foreach ($response as $each_res) {
                        $each_res =  is_object($each_res)? get_object_vars($each_res):$each_res;
                        $ids = [...$ids, $each_res['table_id']];
                    }
                    $csv_ids = join(',', $ids);
                    $id_column = $this->table_name."_id";
                    $_where = $this->where?str_replace("WHERE ", " AND ", $this->where):null;
                    // 
                    $sql = "SELECT * FROM $this->table_name WHERE $id_column NOT IN ($csv_ids) $_where"; 
                    $this->sql = str_replace("*", $this->select, $sql);
                }
            }
            //    
            $total_count = $this->countTotal(str_replace('*', "COUNT(*) AS total_count", $this->sql));
            // 
            if($this->sql_hasone ){
                $total_count = $this->countTotal(str_replace('*', "COUNT(*) AS total_count", $count_sql));
            }
            // **************************************************************************************
            // with most and with least
            if(self::$sql_with_most && !self::$without){
                $limit = (self::$limit >0)?" LIMIT ".self::$limit:null;
                // 
                $sql = self::$sql_with_most.$limit;
                $response = self::sql($sql);
                // extract the table_id from the
                if(count($response)){
                    foreach ($response as $each_res) {
                        $each_res =  is_object($each_res)? get_object_vars($each_res):$each_res;
                        // 
                        $main_table = $this->table_name."_id";
                        $each_sql = $this->sql." WHERE $main_table = ".$each_res['table_id'];
                        $one = self::sql($each_sql,null,false);
                        $this->with_most_data = [...$this->with_most_data, $one];
                    }
                }
            }
            
            // **************************************************************************************
            if($this->group_by && $this->group_by){
                $this->sql.= " GROUP BY $this->group_by ";
            }
            // 
            if($this->called_class){
                $this->sql = str_replace("SELECT * FROM $this->called_class", "SELECT * FROM $this->called_class $this->join_result_string", $this->sql);
            }
            // 
            if($this->order_by && $this->order_by){
                $this->sql.= " $this->order_by ";
            }
            // 
            
            $DRIVER = $maker->instance->env()["DRIVER"];
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
            }elseif($this->activate_find && self::$limit && !self::$sql_with_most){
                if($DRIVER === "postgresql"){
                    $this->sql.= " LIMIT $self_skip OFFSET $self_limit";
                }else{
                    $this->sql.= " LIMIT $self_skip, $self_limit";   
                }
            }
            // 
            $instance = $maker->instance;
            $conn = $maker->instance->connect();
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
                    if(!self::$sql_with_most || self::$without){
                        $results = ($this->activate_hasone || $this->sql_belongs_to)?
                        $stmt->fetch($instance->fetchMode()):
                        $stmt->fetchAll($instance->fetchMode());
                    }
                }
            }
            
            self::$sql_with_most??$instance->debargPrint($this->sql, $this->prepared_values, null, true);
        if($this->activate_paginating){
            $results =  $instance->isObjMode()? get_object_vars($results):$results;
            $results['data'] = $this->appendWithToResult($results['data'] , $maker->instance, $this->table_name, $this->with_one_array, false);
            $results['data']  = $this->appendWithToResult($results['data'] , $maker->instance, $this->table_name, $this->with_array, true);
            $results['data']  = $this->appendWithThrough($results['data'] , $maker->instance, $this->table_name);
            $results['data']  = $this->attachThroughRecord($results['data'] , $maker->instance, $this->table_name);
            $results['data']  = $this->attachUpperLevelRecord($results['data'] , $maker->instance, $this->table_name);
            $results = $instance->isObjMode()? (object)$results:$results;
        }else{
            $results = count($this->with_most_data)?$this->with_most_data:$results;
            $results = $this->appendWithToResult($results, $maker->instance, $this->table_name, $this->with_one_array, false);
            $results = $this->appendWithToResult($results, $maker->instance, $this->table_name, $this->with_array, true);
            $results = $this->appendWithThrough($results, $maker->instance, $this->table_name);
            $results = $this->attachThroughRecord($results, $maker->instance, $this->table_name);
            $results = $this->attachUpperLevelRecord($results, $maker->instance, $this->table_name);
        }
        //
        $this->resetVariables();
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
    // resetting somevariables
    private function resetVariables(){
        $this->activate_hasone = false;
        $this->belongs_to_table = false;
        $this->sql_belongs_to = null;
        $this->sql = null;
        $this->has_many_through_table = null;
        $this->with_one_array = [];
        $this->with_array = [];
    }

    //relationship has manay function
    public function hasMany(string $ref_table){
        $this->sql_hasmany = "SELECT * FROM $ref_table";
        return $this->___f();
    }
    
    //relationship has one function
    public function hasOne(string $ref_table){
        $this->sql_hasone = "SELECT * FROM $ref_table";
        $this->activate_hasone = true;
        return $this->___f();
    }
    //relationship belongs to function
    public function belongsToOne(string $ref_table){
        $this->sql_belongs_to = "SELECT * FROM $ref_table";
        $this->belongs_to_ = strtolower($ref_table);
        return $this;
    }
    //relationship for many to many function
    public function hasManyThrough(string $ref_table){
        $maker = self::maker();
        $ref_table =  $maker->instance->renameTable($ref_table);
        $this->sql_has_many_through = "SELECT * FROM $ref_table";
        $this->has_many_through_table = strtolower($ref_table);
        return $this;
    }
    //relationship for many to many function
    public function belongsToMany(string $ref_table){
        $this->sql_blongs_to_many = "SELECT * FROM $ref_table";
        $this->has_many_through_table = strtolower($ref_table);
        return $this;
    }

    ///***************************************************************** */
    ///***************************************************************** */
    private static function findByPk($where){
        $inst = new Model(get_called_class());
        return $inst->__ByPk($where);
    }
    public static function find(int|string $id = null){
        if($id){
           return self::findBypk($id );
        }else{
            $inst = new Model(get_called_class());
            return $inst->___f();
        }
    }
    //table
    public static function table($tablename){
        $inst = new Model($tablename);
        $response = $inst->___f();
        return $response;
    }
 }