<?php
class Token{
    public TokenType $type;
    public bool $isOptional;
    public function __construct(
        TokenType $type,
        bool $isOptional = false,
    ){
        $this->type = $type;
        $this->isOptional = $isOptional;
    }

}