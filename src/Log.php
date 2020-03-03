<?php

namespace ProlificHue\ModelAuditLogger;


class Log
{
	private static $remark;

	public static function set($remark = 'NO REMARKS')
	{
		self::$remark = $remark;
	}

	public static function get()
	{
		return self::$remark;
	}
}