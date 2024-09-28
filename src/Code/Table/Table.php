<?php
namespace Dite\Table;

use Dite\DB\Connection;
use Dite\Field\FieldBuilder;
use PDOException;

// 
class Table{
    protected $table_name;
    Protected $SQL = "";
    // 
    Protected $_id = null;
    Protected $_string = null;
    Protected $_timestamp = null;
    Protected $BuildColumn;
    Protected $conn;
    Protected $sqlite_engeine = false;

    public function __construct($table_name='', $setup){
        $this->table_name = strtolower($table_name);
        $this->BuildColumn = new FieldBuilder(); 
        $this->conn = new Connection($setup);
    }
  //Running the query
    public function migreate(){
        $conn = new Connection();
        $this->_string = $this->BuildColumn->getBuild();
        $constriain = $this->BuildColumn->getBuildRef();
        // 
        $this->SQL = str_ireplace('_*_',' , ',  "CREATE TABLE IF NOT EXISTS $this->table_name (
            $this->_id 
            $this->_string
            $this->_timestamp
            $constriain
        )");
        //connections
        try {
            $conn->connect()->exec($this->SQL);
            // 
            $messege = "Table $this->table_name created successfully\n<br>";
            $conn->debargPrint($this->SQL, null, $messege);
        } catch(PDOException $e){
            exit("Failed to connect to database!<br>\n $this->SQL <br> {$e->getMessage()}");
        }
    }
    
    //An automatic primary key id
    public function id(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('postgresql') || $this->conn->env()['DRIVER']===trim('pgsql');
        $_NAME = strtolower($this->table_name."_id");
        $INT = $sqlite_engeine?"SERIAL":"INT";
        $this->_id = "$_NAME $INT AUTO_INCREMENT PRIMARY KEY NOT NULL";
        return $this;
    }

    //*************************************************************************** *
    //TYPES
    //QSL
    public function sql(string $sql){
        if(str_contains(strtoupper($sql), 'CREATE TABLE'))
        $this->SQL = $sql; 
    }
    //Varible character
    public function string(string $name, int $length=255){
        $Text  = "_*_{$this->conn->renameTable($name)} VARCHAR($length) ";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Text
    public function text(string $name, int $size=65535){
        $Text  = "_*_{$this->conn->renameTable($name)} TEXT($size) ";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //long Text
    public function longText(string $name){
        $Text  = "_*_{$this->conn->renameTable($name)} LONGTEXT ";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //enum
    public function enum(string $name, array $values=[]){
        $result = '';
        foreach ($values as $key => $value) {
            $comma = ($key+1==count($values)?null:',');
            $result .="'$value'".$comma;
        }
        $Text  = null;
        if($this->conn->env()['DRIVER']===trim('sqlite')){
            $Text = "_*_{$this->conn->renameTable($name)} TEXT CHECK($name IN ($result) ) ";
        }else{
            $Text  = "_*_{$this->conn->renameTable($name)} ENUM($result) ";
        }
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Integer
    public function int(string $name){
        $INT = "INT";
        $Text  = "_*_{$this->conn->renameTable($name)} $INT";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Integer
    public function unsigned(string $name){
        $Text  = "_*_{$this->conn->renameTable($name)} UNSIGNED";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Big Integer
    public function bigInt(string $name){
        $Text  = "_*_{$this->conn->renameTable($name)} BIGINT";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Boolean
    public function boolean(string $name){
        $Text  = "_*_{$this->conn->renameTable($name)} BOOLEAN";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Float
    public function float(string $name, int $p=6){
        $Text  = "_*_{$this->conn->renameTable($name)} FLOAT($p)";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Double
    public function double(string $name, int $size=6, int $d=4){
        $Text  = "_*_{$this->conn->renameTable($name)} DOUBLE($size, $d)";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Decimal
    public function decimal(string $name, int $size=65, int $d=0){
        $Text  = "_*_{$this->conn->renameTable($name)} DECIMAL($size, $d)";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //Year
    public function year(int $name){
        $Text  = "_*_{$this->conn->renameTable($name)} YEAR";
        $this->BuildColumn->Build($Text);
        return $this;
    }
    //*************************************************************************** *
    // CONSTRIAN
    //For allowing primary key values
    public function primarykey(){
        $this->BuildColumn->Build(' PRIMARY KEY');
        return $this;
    }
    //For allowing primary key values
    public function autoincrement(){
        $auto = $this->conn->env()['DRIVER']===trim('sqlserver')?' IDENTITY(1,1)':' AUTOINCREMENT';
        $this->BuildColumn->Build($auto);
        return $this;
    }
    //For allowing null values
    public function notnull(){
        $this->BuildColumn->Build(' NOT NULL');
        return $this;
    }
    
    //For integer values
    public function unique(){
        $this->BuildColumn->Build(' UNIQUE');
        return $this;
    }
    
    //For integer values
    public function check(){
        $this->BuildColumn->Build(' UNIQUE');
        return $this;
    }
    //For foreign key
    public function foreignKey(string $column_name){
        $INT = "INT";
        $Text  = "_*_$column_name $INT ";
        // 
        $this->BuildColumn->Build($Text);
        $refernce_table = str_replace("_id", "", $column_name);
        $Ref_Text = ", FOREIGN KEY ($column_name) REFERENCES $refernce_table ($column_name)";
        $this->BuildColumn->BuildRef($Ref_Text);
        return $this;
    }

    //For cascade
    public function cascade(){
        $Text = " ON DELETE CASCADE ON UPDATE CASCADE";
        $this->BuildColumn->BuildRef($Text);
        return $this;
    }
    //For cascade  on delete
    public function cascadeDelete(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON DELETE CASCADE ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For cascade  on UPDATE
    public function cascadeUpdate(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON UPDATE CASCADE ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For restrict
    public function restrict(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON DELETE RESTRICT ON UPDATE RESTRICT ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);

        return $this;
    }
    //For restrict
    public function restrictDelete(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON DELETE RESTRICT ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For restrict
    public function restrictUpdate(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON UPDATE RESTRICT ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For set null
    public function setnull(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON DELETE SET NULL ON UPDATE SET NULL ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For set null
    public function setnullDelete(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON DELETE SET NULL ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For set null
    public function setnullUpdate(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON UPDATE SET NULL";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For set null
    public function noaction(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON DELETE NO ACTION ON UPDATE NO ACTION";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For set null
    public function noactionDelete(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON DELETE NO ACTION ";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //For set null
    public function noactionUpdate(){
        $sqlite_engeine = $this->conn->env()['DRIVER']===trim('sqlite');
        $Text = " ON UPDATE NO ACTION";
        $sqlite_engeine?$this->BuildColumn->BuildRef($Text):$this->BuildColumn->Build($Text);
        return $this;
    }
    //Setting up timestamp
    public function timestamp(){
        $sqlite_engeine = $this->conn->env()['DRIVER'] ==='sqlite';
        $auto_update = $sqlite_engeine==1?null:' ON UPDATE CURRENT_TIMESTAMP';
        $this->_timestamp  = ", created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP $auto_update";
        return $this;
    }
}
