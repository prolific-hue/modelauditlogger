<?php

namespace ProlificHue\ModelAuditLogger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ProlificHue\Exceptions\AuditLoggerException;
use Carbon\Carbon;

class LogGenerator
{
	private $model;
	private $changesArr = [];
	private $excepts;
	private $column_aliases;
	private $eval_values;
	private $primary_key;
	private $guard;

	public function __construct(Model $model)
	{
		$guards = config('modelauditlogger.auth.guards');
		$isAuthenticate = false;
		foreach ($guards as $guard) {
			if(!Auth::guard($guard)->check())
				continue;
			$isAuthenticate = true;
			$this->guard = $guard;
			break;
		}

		if(!$isAuthenticate)
			throw new AuditLoggerException("Invalid authentication.", 1);
			

		$this->model = $model;
		$this->init();
	}

	private function init(){										
		$model_class = get_class($this->model);
		$this->excepts = config('modelauditlogger.column.excepts');
		$this->column_aliases = config('modelauditlogger.column.alias');
		$this->eval_values = [];
		$this->primary_key = $this->model->getKeyName();

		if(empty($this->model->{$this->primary_key}))
			throw new AuditLoggerException("Model must have primary key", 1);
			
		if( method_exists($model_class, 'getColumnAlias'))
			$this->addColumnAlias( $model_class::getColumnAlias() );
		if( method_exists($model_class, 'getEvalValues'))
			$this->addEvalValues( $model_class::getEvalValues() );
		if( method_exists($model_class, 'getIgnoredColumn'))
			$this->addExcept( $model_class::getIgnoredColumn() );
	}

	private function addExcept(...$excepts){
		if(!empty($excepts))
			$this->excepts = array_merge($this->excepts, $excepts);
		return $this;
	}

	private function addColumnAlias($column_aliases = []){
		$this->column_aliases = array_merge($this->column_aliases, $column_aliases);
		return $this;
	}

	private function addEvalValues($evals = []){
		$this->eval_values = array_merge($this->eval_values, $evals);
		return $this;
	}

	private function pushChanges($og_column, $og_old_value, $og_new_value){

		$column = $this->column_aliases[$og_column] ?? $og_column;

		if(isset($this->eval_values[$og_column])){
			$model = clone $this->model; // prevent modification/altering/curropting
			$eval = $this->eval_values[$og_column];

			// retrive dynamic old value
			$model->{$og_column} = $og_old_value;
			!empty($eval['relation']) && $model->load($eval['relation']);
			$old_value = data_get($model, $eval['relation'].'.'.$eval['field']);

			// retrive dynamic new value
			$model->{$og_column} = $og_new_value;
			!empty($eval['relation']) && $model->load($eval['relation']);
			$new_value = data_get($model, $eval['relation'].'.'.$eval['field']);

		}else{
			$new_value = $og_new_value;
			$old_value = $og_old_value;
		}

		$this->changesArr[] = [
			'column'=>$column,
			'old'=>$old_value,
			'new'=>$new_value
		];
	}

	private function commit(){
		$log = [
			'model_type'=> get_class($this->model),
			'model_id' => $this->model->{$this->primary_key},
			'user_id'=> Auth::guard($this->guard)->id(),
			'user_type'=> Helpers::getAuthModelGuard()[$this->guard],
			'ip_address'=> request()->ip(),
			'payload'=> json_encode($this->changesArr),
			'remarks' => Log::get(),
			'created_at'=> Carbon::now()->toDateTimeString()
		];
		
		if(Helpers::isFileDriver())
			$this->pushInFile($log);
		else
			$this->pushInDB($log);
	}

	private function pushInFile($log){
		$filesettings = config('modelauditlogger.drivers.file');
		$path = $filesettings['path'] . '/'.$this->model->getTable() . '/' . $log['model_id'] . '.log';

		$content = json_encode($log);
		$disk = Storage::disk($filesettings['disk']);

		if($disk->exists($path)){
			if($disk->getSize($path) > 0)
				$content = ',' . $content;

			$disk->append($path, $content);
		}
		else
			$disk->put($path, $content);
	}

	private function pushInDB($log){
		$log['table'] = $this->model->getTable();
		$db = config('modelauditlogger.drivers.database');
		DB::connection( $db['connection'] )
		->table( $db['table'] )
		->insert( $log );
	}

	public function updating(){
		$oldValues = $this->model->getOriginal();
		foreach ($oldValues as $key => $value) {
			if(in_array($key, $this->excepts) || $value == $this->model->{$key})
				continue;
			$this->pushChanges($key, $value, $this->model->{$key});
		}
		$this->commit();
	}


	public function __destruct()
	{
		/* reset log class */
		Log::set(null);
	}
}