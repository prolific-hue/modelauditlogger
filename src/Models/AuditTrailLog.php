<?php

namespace ProlificHue\ModelAuditLogger\Models;
use Illuminate\Database\Eloquent\Model;

class AuditTrailLog extends Model
{
	
    public function __construct()
    {
        $this->table = config('modelauditlogger.drivers.database.table');
        $this->connection = config('modelauditlogger.drivers.database.connection');
        parent::__construct();
    }

    /**
     * Get all of the owning model models.
     */
    public function model()
    {
        return $this->morphTo();
    }

	/**
     * Get all of the owning user models.
     */
    public function user()
    {
        return $this->morphTo();
    }

    /*
	 * Get deserialize payload
    */
    public function getPayloadsAttribute(){
    	if(empty($this->payload))
    		return [];
    	return json_decode($this->payload, true);
    }
}