<?php

namespace ProlificHue\ModelAuditLogger;
use ProlificHue\Exceptions\AuditLoggerException;
use Storage;

class Helpers
{
	public static function getAuthModelGuard()
	{
		$guards = config('auth.guards');
		$models = [];
		foreach ($guards as $guard=>$value) {
			$provider = config('auth.guards.'.$guard.'.provider');
			$models[$guard] = config('auth.providers.'.$provider.'.model') ?? null;
		}
		return $models;
	}

	public static function isFileDriver()
	{
		return config('modelauditlogger.default') === 'file';
	}

	public static function driverCheck()
	{
		$driver = config('modelauditlogger.default');
		if( empty($driver) )
			throw new AuditLoggerException("Invalid driver configuaration.", 1);
		
		if(!in_array($driver, ['database', 'file']))
			throw new AuditLoggerException("Invalid driver configuaration.", 1);
	}
}