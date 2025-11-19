<?php
class User{
    public $username;
    public $maxMana = 100;
    public $curMana;

    public function __construct($name){
        $this ->username = $name;
        $this -> curMana = $this -> maxMana;
    }

    function editMana($amount){
        $this->curMana -= $amount;
    }
}
?>