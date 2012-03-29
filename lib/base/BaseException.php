<?php
class BaseException extends Exception {

    //General constants
    const MANDATORY_PARAMETER_MISSING = 100;
    const NO_DATA_AVAILABLE = 101;
    const HEADERS_ALREADY_SENT = 102;
    const OBJECT_CONSTRUCTION_FAILED = 103;


    public function __construct($message, $code) {
            parent::__construct($message, $code);
    }
}
