<?php

namespace ProlificHue\ModelAuditLogger\Exceptions;
use Exception;

class AuditLoggerException extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}