<?php
namespace Dite\Schema;

use Dite\DB\Connection;
use Dite\Table\Table;

/**
 * extetend TableBuilder from schema in order to inherit all the methods for bulding the table
 */
class Schema extends Table{
    public $setup = null;
    // 
    public static function setup($setup = null){   
        self::$setup = $setup;
    }
    // 
    public static  function create($table_name, $func = null){

        $conn = new Connection();

        if($conn->runSchema() && $conn->env()['RUN_SCHEMA'] === '1'){
            $Builder = new Table($conn->renameTable($table_name));
            $func($Builder);
            $Builder->migreate();
        }
    }
}