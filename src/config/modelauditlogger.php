<?php

return [
	'default' => 'file', // database
	'drivers' => [
		'file' => [
			'disk' => null, // filesystem driver, null for default
			'path' => 'logs/audit_trail_logs',
			'archive_path' => 'logs/audit_trail_logs_archived'
		],
		'database' => [
			'connection' => null, // database driver, null for default
			'table' => 'audit_trail_logs',
			'archive_table' => 'audit_trail_logs_archived'
		]
	],
	'column' => [
		'excepts' => ['id', 'created_at', 'updated_at', 'deleted_at'],
		'alias' => [], // "created_at"=>"created on"
		'primary_key' => 'id'
	],
	'auth' => [
		'guards' => ['web'] // list of guards, for which trail logs will be generated
	],
	'model' => \App\AuditTrailLog::class, // model used for preserving logs
];