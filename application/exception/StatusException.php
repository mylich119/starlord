<?php

class StatusException extends Exception
{
    protected $sCause;

    public function __construct($sMessage, $iCode, $sCause=''){
        parent::__construct($sMessage, $iCode);
        $this->sCause = $sCause;
    }

    public function getCause(){
        return $this->sCause;
    }
}
