<?php
class User{
    public $maxMana = 100;
    public $curMana;

    public function __construct(){
        $this -> curMana = $this -> maxMana;
    }

    function editMana($amount){
        $this->curMana -= $amount;
    }
}
?>