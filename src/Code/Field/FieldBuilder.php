<?php
namespace Dite\Field;
class FieldBuilder{
    public $column = '';
    public $ref = '';
    public $forien_key_feilds = '';
    public $WHERE ='WHERE ';

    public function Build($Text){
        $this->column .= $Text;
    }
    public function BuildRef($Text){
        $this->ref .= $Text;
    }
    public function ListOfForienKeyFelds($Text){
        $this->forien_key_feilds .= $Text;
    }
    public function getBuild(){
        return $this->column;
    }
    public function getBuildRef(){
        return $this->ref===''?null:$this->ref;
    }
}
