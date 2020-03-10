<?php

namespace ProlificHue\ModelAuditLogger;


class Log
{
	private static $remark;

	/**
	 * Set remarks
	 * @param string $remark
	 * @return void
	*/
	public static function set(string $remark = 'NO REMARKS')
	{
		self::$remark = $remark;
	}

	/**
	 * Get remarks
	 * @return string
	*/
	public static function get()
	{
		return self::$remark;
	}
}