<?php
namespace Dite\Query;

use Dite\DB\Connection;

class QueryBuilder{
    public $SET ='';
    public $WHERE = null;
    private $PREPARED_VALUES = [];
    private $instance = null;

    public function __construct()
    {
        $this->instance = new Connection();
    }
    public function getPreparedValues(){
        return count($this->PREPARED_VALUES)===0?null:$this->PREPARED_VALUES;
    }
    //* ***************************************************************************************
    //setting values for updating
    public function updateSetValues(array $valuesArr){
        $column_name = array_keys($valuesArr);
        $column_values = array_values($valuesArr);
        foreach ($column_name as $key=>$value) {
            $comma = $key==count($column_name)-1?null:',';
            $this->SET.="$value = ?$comma "; 
        } 
        //push the keys to the prepared values
        $this->PREPARED_VALUES = [...$this->PREPARED_VALUES, ...$column_values];
        return $this->SET;
    }

    //* ***************************************************************************************
    //setting values for updating
    public function where(string $table_name = null,  array|int $where_selector = []){
        //case of interger value
        if(is_int($where_selector)){
            $this->whereSetId($table_name);
            array_push($this->PREPARED_VALUES, $where_selector);

        }elseif(is_array($where_selector)){
            //case of array where_selector 
            $plainArray = []; //palin array
            $dolarArray = []; //for array with inner dolar
            $orArray = []; //or array
            $norArray = []; //nagated or array
            $andArray = []; //and array
            $nandArray = []; //nagated and array

            foreach ($where_selector as $key => $values) { 
                if(!is_array($values)){
                    //separating plain array or arrays that has no dollar in them
                    $plainArray = array_merge($plainArray, [$key=>$values]);
                }elseif(is_array($values) &&  $this->checkDoller($values) && (
                    $key!=='$not' || 
                    $key!==':not' ||
                    $key!=='$nor' ||
                    $key!==':nor' ||
                    $key!=='$and' ||
                    $key!==':and' ||
                    $key!=='$nand' ||
                    $key!==':nand')){
                    //separating arrays with dollar                    
                    $dolarArray = array_merge($dolarArray, [$key=>$values]);
                }elseif($this->checkAnd($key)){
                    //separating arrays with $and
                    $andKey = $key ==='$and'?:':and';
                    $andArray = $where_selector[$andKey]; 
                    $andArray = array_merge($andArray, [$key=>$values]);
                }elseif($this->checkOr($key)){
                    //separating arrays with $or
                    $orKey = $key ==='$or'?:':or';
                    $orArray = $where_selector[$orKey];

                }elseif($this->checkNor($key)){
                     //separating arrays with $or
                     $orKey = $key ==='$nor'?:':nor';
                     $norArray = $where_selector[$orKey];

                }elseif($this->checkNand($key)){
                    //separating arrays with $_and
                    $nandKey = $key ==='$nand'?:':nand';
                    $nandArray = $where_selector[$nandKey];                    
                    $nandArray = array_merge($nandArray, [$key=>$values]);
                }
            }
            $this->matchPlainAnd($plainArray);
            $this->matchInnerDollerOfAnd($dolarArray);
            $this->matchOr($orArray, 'or');
            $this->matchOr($andArray, 'and');
            $this->matchOr($norArray, 'nor');
            $this->matchOr($nandArray, 'nand');
        }
        // 
        $this->WHERE = trim($this->WHERE);
        // removing unnneccesary trailing "AND )" and "OR )"
        $this->WHERE = str_replace('( OR', '(', $this->WHERE);
        $this->WHERE = str_replace('OR  )', ')', $this->WHERE);
        $this->WHERE = str_replace('AND  )', ')', $this->WHERE);
        // removing unnneccesary trailing "AND"
        $last_3_charactors = substr($this->WHERE, strlen($this->WHERE) - 3, strlen($this->WHERE));
        if($last_3_charactors ==='AND'){
            $this->WHERE = substr($this->WHERE, 0, strlen($this->WHERE) - 3);
        }
        return str_replace("WHERE AND", "WHERE", $this->WHERE);
    }

    //renaming the id coumn
    public function idColName(string $str){
        return $this->instance->renameTable($str).'_id';
    }

    private function whereSetId(string $table_name){
        $WHERE = '';
        $WHERE.= $this->idColName($table_name)."= ?";
        $this->WHERE = "WHERE $WHERE ";
    }
    //Removing the underscores (__) from the field ame.
    private function removeLeadingUderscors(string $field_name){
        $new_name ="";
        for ($i=0; $i < strlen($field_name); $i++){
            if($field_name[$i]==="_" && strlen($new_name)===0) continue;
            $new_name.=$field_name[$i];
        }
        return $new_name;
    }
    private function checkDoller($arr){
        if(count($arr)===0) return;
        return str_split(array_keys($arr)[0])[0] ==="$" || str_split(array_keys($arr)[0])[0] ===":";
    }
    private function checkAnd($and){
        $_and = strtolower($and);
        return $_and==='$and' || $_and===':and' ;   
    }
    private function checkOr($or){
        $_or = strtolower($or);
        return $_or==='$or' || $_or===':or' ;
        
    }
    private function checkNor($nor){
        $_nor = strtolower($nor);
        return $_nor==='$nor' || $_nor===':nor' ;
    }
    private function checkNand($nand){
        $nand = strtolower($nand);
        return $nand==='$nand' || $nand===':nand' ;
        
    }
    private function removingWhere(string $query = null){
        return str_replace('WHERE', '', $query);
    }

    // matching plain array or array without dollar
    private function matchPlainAnd(array $plainArray, bool $allow_return = false){
        if(count(array_keys($plainArray))===0) return;
        $counter =1;
        $WHERE ='';
        $OVERALL_QUERRY = null;
        
        foreach ($plainArray as $key => $value) {
            $AND = $counter===count(array_keys($plainArray))?null:" AND "; 
            if(trim(strtolower($value))==='null' ||$value===null){
                $WHERE.=  $this->removeLeadingUderscors($key)." IS NULL $AND ";
                $counter++;
            }elseif(trim(strtolower($value))==='not null'){
                $WHERE.=  $this->removeLeadingUderscors($key)." IS NOT NULL $AND ";
                $counter++;
            }else{
                $WHERE.=  $this->removeLeadingUderscors($key)." = ? $AND ";
                $counter++;
                array_push($this->PREPARED_VALUES, $value);
            }
        }
        
        //assigning values to overall query
        if($OVERALL_QUERRY === null){
            $OVERALL_QUERRY = "WHERE $WHERE";
        }else{
            $OVERALL_QUERRY.=" AND $WHERE";
        }
        
        //allowing return
        if($allow_return === false){
            $this->WHERE = $OVERALL_QUERRY;
        }else{
            return $this->removingWhere($OVERALL_QUERRY);
        }
    }

    // generating the query for inner doller for plain array
    private function matchInnerDollerOfAnd(array $dollerArray, bool $allow_return = false){
        if(count(array_keys($dollerArray))===0) return;

        // spliting the not operator 
        $not_operator =[];
        $none_not_operator =[];
        $OVERALL_QUERRY = '';
        // 
        foreach ($dollerArray as $key => $value) {
            if(strtolower(trim(array_keys($value)[0])[1])==='n'){
                $not_operator = array_merge($not_operator,[$key=>$value]);
            }else{
                $none_not_operator = array_merge($none_not_operator, [$key=>$value]);
            }
        }    
        
        // generating the actual query for $not_operator
        if(count(array_keys($not_operator)) > 0){
            $not_counter =1;
            $AND = null;
            $WHERE_NOT = '';

            foreach ($not_operator as $key => $value) {
                $AND = $not_counter===count(array_keys($not_operator))?null:" AND "; 
                $not_counter++;
                $value_key = array_keys($value)[0];

                // removing the n that makes it not
                $new_value_key ='';
                for($i=0; $i<strlen($value_key); $i++){
                    if($i!==1){
                        $new_value_key.=$value_key[$i];
                    }
                } 
                $operator = $this->getOperator($new_value_key);
                $_key = $this->removeLeadingUderscors($key);
                $operator; 
                if($operator ===' BETWEEN '){
                    $operator = "$_key BETWEEN ? and ?";
                    $this->PREPARED_VALUES =[...$this->PREPARED_VALUES, ...array_values($value)[0]];
                    
                }elseif(trim($operator) ==='IN'){
                    $marks = str_repeat("?,", count(array_values($value)[0])-1)."?";
                    $operator = "$_key IN($marks)";
                    $this->PREPARED_VALUES =[...$this->PREPARED_VALUES, ...array_values($value)[0]];
                    
                }else{
                    $operator = "$_key $operator ?";
                    $this->PREPARED_VALUES =[...$this->PREPARED_VALUES, array_values($value)[0]];

                }

                $WHERE_NOT.="$operator $AND";
            }
            //appending the result to $OVERALL_QUERRY
            if($OVERALL_QUERRY === null){
                $OVERALL_QUERRY = "NOT ($WHERE_NOT)";
            }else{
                $OVERALL_QUERRY.="AND NOT ($WHERE_NOT)";
            }
        }

         // generating the actual query for $none_not_operator
        if(count(array_keys($none_not_operator))>0){
            $not_counter =1;
            $AND = null;
            $WHERE = '';
            // 
            foreach ($none_not_operator as $key => $value) {
                $AND = $not_counter===count(array_keys($none_not_operator))?null:" AND "; 
                $not_counter++;
                $value_key = array_keys($value)[0];
    
                $operator = $this->getOperator($value_key);
                $_key = $this->removeLeadingUderscors($key);
                $operator; 
                if($operator ===' BETWEEN '){
                    $operator = "$_key BETWEEN ? AND ?";
                    $this->PREPARED_VALUES =[...$this->PREPARED_VALUES, ...array_values($value)[0]];
                    
                }elseif(trim($operator) ==='IN'){
                    $marks = str_repeat("?,", count(array_values($value)[0])-1)."?";
                    $operator = "$_key IN($marks)";
                    $this->PREPARED_VALUES =[...$this->PREPARED_VALUES, ...array_values($value)[0]];
                    
                }else{
                    $operator = "$_key $operator ?";
                    $this->PREPARED_VALUES =[...$this->PREPARED_VALUES, array_values($value)[0]];
    
                }
                $WHERE.="$operator $AND ";
            }
             //appending the result to the $OVERALL_QUERRY
             if($OVERALL_QUERRY === null){
                $OVERALL_QUERRY = "$WHERE";
            }else{
                $OVERALL_QUERRY.="AND $WHERE ";
            }
        }

        //deciding wheather to return avalue or not
        if($allow_return===false){
            //appending the result to the $this->where
            if($this->WHERE === null){
               $this->WHERE.="WHERE $OVERALL_QUERRY ";
           }else{
               $this->WHERE.=$OVERALL_QUERRY;
           }
        }else{
            return $this->removingWhere($OVERALL_QUERRY);
        }
    }

    // generating the query for inner doller for plain array
    private function matchOr(array $arrayOfOr, string $key_type = 'or'){
        if(count(array_keys($arrayOfOr))===0) return;
        // split the array in to plain and inner doller and array of arrays
        $plainArray = [];
        $dollarArray = [];
        $array_of_arrays = [];
        // 
        foreach($arrayOfOr as $key => $value) {
            if(!is_array($value)){
                $plainArray = array_merge($plainArray, [$key=>$value]);
            }elseif(is_array($value) && ($this->checkDoller($value))){
                $dollarArray= array_merge($dollarArray, [$key=>$value]);
            }elseif(is_array($value) && is_int($key)){
                //check the type of the array passed array of arrays of 
                array_push($array_of_arrays, $value);
            }
        }
        // 
        $result_generated = '';
        $result_Of_array_of_arrays='';

        foreach($array_of_arrays as $each_value) {
            $result = $this->matchOrAndArray($each_value);
            if($result_Of_array_of_arrays ='' && $result!==''){
                $result_Of_array_of_arrays .= $result;
            }else{
                //check each kay type for or, nor, and, nand to choose weather to connect them with OR or AND 
                if($key_type === 'or' && $result!==''){
                    $result_Of_array_of_arrays .=" OR $result";
                }elseif($key_type === 'and' && $result!==''){
                    $result_Of_array_of_arrays .=" AND $result";
                }
            }
            $result_generated.=$result_Of_array_of_arrays;
        }
        $result_Of_plainArray = $this->matchPlainAnd($plainArray, true);
        $result_Of_dollarArray = $this->matchInnerDollerOfAnd($dollarArray, true);
        //join all together
        if($key_type ==='or' ||$key_type ==='nor'){
            $result_generated.=str_replace("AND", "OR", $result_generated === ""?$result_Of_plainArray:" OR $result_Of_plainArray");            
            $result_generated.=str_replace("AND", "OR", $result_generated === ""?$result_Of_dollarArray:" OR $result_Of_dollarArray");            
        }elseif($key_type === 'and' || $key_type ==='nand'){
            $result_generated.=$result_generated===""?str_replace("OR", "AND", $result_Of_plainArray):" AND $result_Of_plainArray";
            $result_generated.=$result_generated===""?str_replace("OR", "AND", $result_Of_dollarArray):" AND $result_Of_dollarArray";
        }
        $result_generated=count($array_of_arrays)>0?substr(trim($result_generated),3):$result_generated;
        $result_generated = str_replace("AND AND", "AND", $result_generated);
        $result_generated = str_replace("OR OR", "OR", $result_generated);
        $result_generated = str_replace("BETWEEN ? OR ?", "BETWEEN ? AND ?", $result_generated);
        //checking if the it is and or nand inorder to nagate
        if($key_type==='nor' || $key_type==="nand"){
            $result_generated="NOT ( $result_generated )";
        }elseif($key_type==="or"){
            $result_generated="( $result_generated )";
        }
        //attach it to $this->WHERE
        if($this->WHERE === null){
            $this->WHERE = "WHERE $result_generated";
            
        }else{
            $this->WHERE .= " AND $result_generated";  
        }
    }
    
    //matching the associative aray in the $or and $and
    public function matchOrAndArray(array $result_Of_innerArray){
        if(count(array_keys($result_Of_innerArray))===0) return '';
        // run a loop to separate the plain arrays from the dolar arrays 
        $combined_result = '';
            foreach($result_Of_innerArray as $key => $value) {
                $combined_result.="AND {$this->combinedPlainAndDolar([$key=>$value])}";
            }

        $clean = substr($combined_result, 4);
        return trim($combined_result)===trim("AND")?'':"($clean)";
    }
    //combined plain and dolar array
    private function combinedPlainAndDolar(array $Array){
        if(count(array_values($Array))===0) return null;
        // 
        if(is_string(array_values($Array)[0]) && is_string(array_keys($Array)[0])){
            //match the inner doller
            return $this->matchPlainAnd($Array, true);
        }
        elseif(is_string(array_keys($Array)[0]) && is_array(array_values($Array))){
            //match the plain doller
            return $this->matchInnerDollerOfAnd($Array, true);
        }
    }
    //get operators
    private function getOperator(string $operator){
        $_operator  = str_replace(':', '$', strtolower($operator));
        if(stripos($_operator, '$gt')===0 || stripos($_operator, '$>')===0){
            return " > ";
        }elseif(stripos($_operator, '$lt')===0 ||stripos($_operator, '$<')===0){
            return " < ";
        }elseif(stripos($_operator, '$gte')===0 || stripos($_operator, '$>=')===0){
            return " >= ";
        }elseif(stripos($_operator, '$lte')===0 ||stripos($_operator, '$<=')===0){
            return " <= ";
        }elseif(stripos($_operator, '$in')===0){
            return " IN ";
        }elseif(stripos($_operator, '$like')===0){
            return " LIKE ";
        }elseif(stripos($_operator, '$between')===0 || stripos($_operator, '$bt')===0){
            return " BETWEEN ";
        }elseif(stripos($_operator, '$regexp')===0){
            return " REGEXP ";
        }elseif(stripos($_operator, '$eq')===0 || stripos($_operator, '$=')===0 || stripos($_operator, '$equal')===0){
            return " = ";
        }
    }
}