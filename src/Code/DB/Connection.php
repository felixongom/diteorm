<?php
namespace Dite\DB;
use PDO;
use PDOException;

class Connection
{
    private $env = [];
    public function __construct(){
        $this->env = parse_ini_file('.env');
    }

    //connection
    public function connect()
    {
        $connected = null;
        try {
            if ($this->env['DRIVER'] === 'mysql') {
                $server_name = $this->env['SERVER_NAME'];
                $database_name = $this->env['DATABASE_NAME'];
                $user_name = $this->env['USER_NAME'];
                $database_password = $this->env['DATABASE_PASSWORD'];
                // 
                $connected = new PDO("mysql:host=" . "$server_name;dbname=$database_name", "$user_name", "$database_password");
                $connected->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                //sqlite
            } else if ($this->env['DRIVER'] === 'sqlite') {
                $databaseFile = $this->env['DATABASE_NAME'] . ".sqlite";
                $connected = new PDO("sqlite:$databaseFile");
                $connected->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } else if ($this->env['DRIVER'] === 'sqlserver') {
                $server_name = $this->env['SERVER_NAME'];
                $database_name = $this->env['DATABASE_NAME'];
                $user_name = $this->env['USER_NAME'];
                $database_password = $this->env['DATABASE_PASSWORD'];
                // 
                $connected = new PDO("sqlsrv:Server=" . "$server_name;Database=$database_name", "$user_name", "$database_password");
                $connected->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            }else if ($this->env['DRIVER'] === 'postgresql' || $this->env['DRIVER']===trim('pgsql')) {
                $server_name = $this->env['SERVER_NAME'];
                $database_name = $this->env['DATABASE_NAME'];
                $user_name = $this->env['USER_NAME'];
                $database_password = $this->env['DATABASE_PASSWORD'];
                // 
                $connected = new PDO("sqlsrv:host=" . "$server_name;dbname=$database_name", "$user_name", "$database_password");
                $connected->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            }
            return $connected;
           
        } catch (PDOException $e) {
            exit("Failed to connect to database!<br> {$e->getMessage()}");
        }
    }
    //env value
    public function env()
    {
        return $this->env;
    }
    //FETCH MODE 
    public function fetchMode()
    {
        if(!array_key_exists('FETCH_MODE', $this->env())){
            return PDO::FETCH_OBJ;
        }elseif(strtolower($this->env()['FETCH_MODE'])==='std_obj'){
            return PDO::FETCH_OBJ;
        }elseif(strtolower($this->env()['FETCH_MODE'])==='std_array'){
            return PDO::FETCH_ASSOC;
        }
    }
    //FETCH MODE 
    public function isObjMode()
    {
        if(!array_key_exists('FETCH_MODE', $this->env())){
            return true;
        }elseif(strtolower($this->env()['FETCH_MODE'])==='std_obj'){
            return true;
        }elseif(strtolower($this->env()['FETCH_MODE'])==='std_array'){
            return false;
        }
    }
    //FETCH MODE 
    public function runSchema()
    {
        if(!array_key_exists('RUN_SCHEMA', $this->env())){
            return false;
        }elseif($this->env()['RUN_SCHEMA']==='1'){
            return true;
        }else{
            return false;
        }
    }

    //chech if a column exists 
    public function includeTime(string $table, string $column)
    {
        $sql = null;
        $conn = $this->connect();
        if ($this->env['DRIVER'] === 'mysql') {
            return false;
        } elseif ($this->env['DRIVER'] === 'sqlite') {
            $sql = "PRAGMA table_info($table)";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            // 
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($result && $this->col_exist($result, $column)){
                return true;
            }else{
                return false;
            }
        }
    }
    private function col_exist($rows, $colmn_name){
        $is_found = false;
        foreach($rows as $row){
            if($row['name']===$colmn_name){
                $is_found =  true;
                break;
            } 
        }
        return $is_found;
    }
    //renaming table witg underscor
    public function renameTable(string $table_name){
        $new_table_name = '';
        for($i = 0; $i< strlen($table_name); $i++){
            if($i!= 0 && $i<strlen($table_name) && preg_match('/[A-Z]/', $table_name[$i])){
                $new_table_name .= "_".$table_name[$i];
            }else{
                $new_table_name .= $table_name[$i];
            }
        }
        return strtolower($new_table_name);
    }

    //debarg print
    public  function debargPrint($SQL=null , $PREPARED_VALUE=null, $messege=null, bool $many = false){
        if(array_key_exists('IS_DEVMODE', $this->env) && $this->env['IS_DEVMODE']==1){
            $val =  json_encode($PREPARED_VALUE);
            $check_many = $many ?$val:"[$val]";
            $sql = $SQL===null?'':"<div style='margin:0;'>sql = {$this->colorfullSql($SQL,'SQL_COLOR','#cc00cc')}<div> <br>";
            $value = $PREPARED_VALUE===null?"":"<div>values = $check_many</div>";
            $mess = $messege===null?"":"<div>messege = $messege<div><br>";
            // 
            $separator = $this->setColor('SEPARATOR', '#777');
            $hr = ($SQL!==null || $PREPARED_VALUE!==null || $messege !==null)?"<hr style='background-color: $separator; height:2px;border:none'>":'';
            // 
            $nonsql_color = $this->setColor('NONSQL_COLOR', '#cccccc');
            $color_bg = $this->setColor('SQL_BG', '#011222');
            //
            $new_sql = '';
            
            if(is_array($PREPARED_VALUE)){
                for($i = 0; $i < strlen($sql); $i++){
                    if($sql[$i] =='?'){
                        $new_sql.=$PREPARED_VALUE[0];
                        $PREPARED_VALUE = array_slice($PREPARED_VALUE,1);
                    }else{
                        $new_sql.=$sql[$i];
                    }
                }
            }
            $new_sql = (array_key_exists('FULL_SQL', $this->env) && $this->env['FULL_SQL']==0)?$sql:$new_sql;
            $value = (array_key_exists('FULL_SQL', $this->env) && $this->env['FULL_SQL']==0)?$value:null;
            echo("
                <div style='font-size:15px; background-color: $color_bg; color:$nonsql_color; padding:5px;margin:0; font-family:tahoma'>
                    $mess $new_sql $value $hr
                </div>");
        }
    }
    //
    private function colorfullSql($sql, $key, $default_color){
        $key_words = [' DATABASE  ',' DROP ',' ALTER ', ' COLUMN ',' PRIMARY ',' FOREIGN ', 'REFERENCES',' KEY ',' CONSTRAINT ',' ADD ',' CHECK ',' ADD ',' DEFAULT ',
        'SELECT ', ' FROM ',' WHERE ', ' JOIN ',' LEFT JOIN ',' RIGHT JOIN ',' INNER JOIN ', ' FULL OUTER JOIN ', ' ON ', ' LIMIT ', ' OFFSET ','CREATE ','IF',
        ' CREATE ',' TABLE ',' LIKE ',' AS ',' TOP ',' DELETE ',' UPDATE ',' SET ',' IS ',' NOT ',' UNIQUE ','NULL',' INSERT ',' INTO ',' VALUES ',' GROUP ',' ORDER ',' HAVING '
        ,' ORDER ', ' BY ',' ASC ',' DESC ',' AND ',' OR ',' NOT ',' COUNT ',' DISTINCT ',' IN ',' BETWEEN ',' EXISTS ',' ALL ',' ANY ',' COALESCE '];
        // 
        $sql_color = $this->setColor($key, $default_color);
        
        foreach ($key_words as $key_word) {
            $sql = str_replace($key_word, " <span style='color:$sql_color;'>$key_word</span> ", $sql);
        }
        return $sql;
    }

    private function setColor($key, $default_color){
        $color = null;
        if(array_key_exists($key, $this->env())){
            $color = $this->env()[$key]?str_replace(':','#', $this->env()[$key]):$default_color;
        }else{
            $color = $default_color;
        }
        return $color;
    }
}
