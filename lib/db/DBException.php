<?php
/**
 *
 * DBException
 *
 * @author Thejo
 */
class DBException extends PDOException {
    public function __construct($message) {
        parent::__construct($message, 0);
    }
}
?>