<?php

namespace App\Exceptions;

class BusinessException extends \Exception {
    public $errorValue;

    public function __construct($msg, $errorValue = null)
    {
        parent::__construct($msg);
        $this->errorValue = $errorValue;
    }
 }
