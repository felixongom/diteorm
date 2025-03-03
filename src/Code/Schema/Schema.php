<?php
namespace Dite\Schema;

use Dite\DB\Connection;
use Dite\Table\Table;

/**
 * extetend TableBuilder from schema in order to inherit all the methods for bulding the table
 */
class Schema extends Table{
    public static $setup = null;
    public static function setup($setup){
        self::$setup = $setup;
    }
    public static  function create($table_name=null, $func = null){
        $conn = new Connection(self::$setup);

        if($conn->runSchema() && $conn->env()['RUN_SCHEMA'] === '1'){
            $Builder = new Table($conn->renameTable($table_name), self::$setup);
            $func($Builder);
            $Builder->migreate();
        }
    }
}