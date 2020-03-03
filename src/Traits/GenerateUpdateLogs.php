<?php

namespace ProlificHue\ModelAuditLogger\Traits;

use ProlificHue\ModelAuditLogger\LogGenerator;
use ProlificHue\ModelAuditLogger\Repository\AuditLogRepository;
use Carbon\Carbon;

trait GenerateUpdateLogs
{
	public static function bootGenerateUpdateLogs()
	{
        static::updating(function($model) 
        {
            $lg = new LogGenerator( $model );
            $lg->updating();
        });
    }

    public function setAuditLogs()
    {
    	$id = $this->{$this->getKeyName()};
    	if(empty($id))
    		return null;
    	return AuditLogRepository::make()->setTable($this->getTable(), $id)->setLogs();
    }

    public function setArchiveAuditLogs(string $fromDate, string $toDate = null)
    {
        $id = $this->{$this->getKeyName()};
        if(empty($id))
            return null;
        $fromDate = Carbon::parse($fromDate);
        $toDate = empty($toDate) ? Carbon::today() : Carbon::parse($toDate);
        return AuditLogRepository::make()->setTable($this->getTable(), $id)->setArchivedLogs($fromDate, $toDate);
    }
}