<?php

namespace ProlificHue\ModelAuditLogger\Traits;

use ProlificHue\ModelAuditLogger\Helpers;
use ProlificHue\Exceptions\AuditLoggerException;

trait UserAuditLog
{

    public function __construct()
    {
        if(Helpers::isFileDriver())
            throw new AuditLoggerException("require database for modelauditlogger.default", 1);
            
    }

	public function logs()
    {
        $model = config('modelauditlogger.model');
        return $this->morphMany($model, 'model');
    }
}