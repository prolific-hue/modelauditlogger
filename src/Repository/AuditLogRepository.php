<?php

namespace ProlificHue\ModelAuditLogger\Repository;

use ProlificHue\ModelAuditLogger\Models\AuditTrailLogs;
use ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Filesystem\FilesystemAdapter;
use ProlificHue\ModelAuditLogger\Models\ProlificHueModel;
use ProlificHue\ModelAuditLogger\Rule;
use Illuminate\Support\Collection;

class AuditLogRepository
{
	private $model;
	private $driver;
	private $table;
	private $table_id;

	/**
	 * @return void 
	*/
	private function __construct()
	{
		$model = config('modelauditlogger.model') ?? AuditTrailLogs::class;
		$this->model = new $model;
		$this->driver = config('modelauditlogger.default');
	}

	public static function make()
	{
		return new static();
	}

	/**
	 * @param string $table 
	 * @param string $table_id
	 * @return $this
	*/
	public function setTable(string $table, string $table_id)
	{
		$this->table = $table;
		$this->table_id = $table_id;
		return $this;
	}

	/**
	 * @return $this
	*/
	public function setLogs()
	{
		if($this->driver === Rule::CONFIG_DB_DRIVER)
		{
			return ProlificHueModel::make(Rule::DB_DRIVER)
			->setModel($this->model)
			->where('table', '=', $this->table)
			->where('model_id', '=',$this->table_id);
		}
		elseif($this->driver === Rule::CONFIG_FILE_DRIVER){
			$filesettings = config('modelauditlogger.drivers.file');
			$path = $filesettings['path'] . DIRECTORY_SEPARATOR . $this->table . DIRECTORY_SEPARATOR . $this->table_id . '.log';

			$storage = Storage::disk( $filesettings['disk'] );
			if($storage->exists($path))
				$logs = json_decode( '[' . $storage->get($path) . ']', true );
			else
				$logs = [];
			
			return ProlificHueModel::make(Rule::COLLECTION_DRIVER)
			->setCollection(Collection::make($logs));
		}
		return ProlificHueModel::make(Rule::COLLECTION_DRIVER)
		->setCollection(Collection::make());
	}

	/**
	 * @param \Carbon\Carbon $fromDate 
	 * @param \Carbon\Carbon|null $toDate
	 * @return $this
	*/
	public function setArchivedLogs(Carbon $fromDate, Carbon $toDate = null)
	{
		if($toDate === null)
			$toDate = Carbon::now();


		if($this->driver === Rule::CONFIG_DB_DRIVER)
		{
			$this->model = $this->model->setTable(config('modelauditlogger.database.archive_table'));
			return ProlificHueModel::make(Rule::DB_DRIVER)
			->setModel($this->model)
			->where('table', '=',$this->table)
			->where('model_id', '=',$this->model_id)
			->whereDateBetween('archived_at', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d'));
		}
		elseif($this->driver === Rule::CONFIG_FILE_DRIVER)
		{
			$dates = [];
			$periods = CarbonPeriod::create($fromDate->toDateString(), $toDate->toDateString());
			foreach ($periods as $period) {
				$dates[] = $period->format('Y-m-d');
			}

			$filesettings = config('modelauditlogger.drivers.file');
			$storage = Storage::disk( $filesettings['disk'] );
			$path = $filesettings['archive_path'];

			$avail_dates = array_map(function($item) use($path){
	 			return str_replace($path . DIRECTORY_SEPARATOR, '', $item);
			}, $storage->directories($path));
			
			$dates = array_intersect($avail_dates, $dates);

			$logs = Collection::make();
			foreach ($dates as $date) {
				$logs = $logs->merge($this->setArchivedDateLogs($storage, $path, $date));
			}

			return ProlificHueModel::make(Rule::COLLECTION_DRIVER)
			->setCollection($logs);
		}
			

		return $this;
	}
	
	/**
	 * @param \Illuminate\Filesystem\FilesystemAdapter $storage 
	 * @param string $archive_path
	 * @param string $date
	 * @return array
	*/
	private function setArchivedDateLogs(FilesystemAdapter $storage, string $archive_path, string $date)
	{
		$path = $archive_path . DIRECTORY_SEPARATOR . $date . DIRECTORY_SEPARATOR . $this->table . DIRECTORY_SEPARATOR . $this->table_id . '.log';
		
		if(!$storage->exists($path))
			return [];

		$logs = json_decode('['.$storage->get($path).']', true);
		$logs = array_map(function($log) use($date){
			$log['archived_at'] = $date;
			return $log;
		}, $logs);

		return $logs;
	}
}