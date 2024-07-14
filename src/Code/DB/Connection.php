<?php
namespace Dite\DB;
use PDO;
use PDOException;

class Connection
{
    private $env = [];
    public function __construct()
    {
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

        // if($this->env()['DRIVER']==='sqlite') return $result;
        // if ($result['count'] > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
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
    //debarg print
    public  function debargPrint(string $SQL=null , array|string $PREPARED_VALUE=null, $messege=null, bool $many = false){
        if(array_key_exists('LOGGER', $this->env) && $this->env['LOGGER']==1){
            $val =  json_encode($PREPARED_VALUE);
            $check_many = $many ?$val:"[$val]";
            $sql = $SQL===null?'':"<div>sql = $SQL<div> <br>";
            $value = $PREPARED_VALUE===null?"":"<div>values = $check_many</div>";
            $mess = $messege===null?"":"<div>messege = $messege<div><br>";
            $hr = ($SQL!==null || $PREPARED_VALUE!==null || $messege !==null)?"<hr style='background-color: #333; height:2px;border:none'>":'';
            // 
            echo("
                <div style='background-color: black; color:#e6e6e6 ; padding:15px 5px; font-family:tahoma'>
                    $mess $sql $value $hr
                </div>");
        }
    }
}
