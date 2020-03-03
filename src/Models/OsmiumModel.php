<?php

namespace ProlificHue\ModelAuditLogger\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException;
use ProlificHue\ModelAuditLogger\Rule;

class ProlificHueModel // implements ModelInterface
{
	private $items;
	private $model;
	private $driver;
	private $queueActions = []; 

	private function __construct(string $driver)
	{
		$this->setDriver($driver);
	}

	public static function make(string $driver)
	{
		return new static($driver);
	}

	public function setDriver(string $driver)
	{
		if($driver !== Rule::COLLECTION_DRIVER && $driver !== Rule::DB_DRIVER)
			throw new AuditLoggerException(sprintf('Driver must be either %s or %s', Rule::COLLECTION_DRIVER, Rule::DB_DRIVER), 1);

		$this->driver = $driver;	
	}

	public function getDriver()
	{
		$this->isDriverSet();
		return $this->driver;
	}

	private function isDriverSet()
	{
		if(empty( $this->driver ))
			throw new AuditLoggerException('You need to call setDriver method first.');

		if($this->driver === Rule::DB_DRIVER && empty($this->model))
			throw new AuditLoggerException("Model is not set.", 1);
			
		if($this->driver === Rule::COLLECTION_DRIVER && $this->items === null)
			throw new AuditLoggerException("Collection is not set.", 1);
	}

	public function setModel(Model $model)
	{
		$driver = $this->driver;
		if($driver !== Rule::DB_DRIVER)
			throw new AuditLoggerException(sprintf('Driver must be %s', Rule::DB_DRIVER));
		$this->model = $model;
		return $this;
	}

	public function getModel()
	{
		$this->isDriverSet();
		if(empty($this->model))
			throw new AuditLoggerException("You need to call setModel method first.", 1);
		return $this->model;
	}

	public function setCollection(Collection $items)
	{
		$driver = $this->driver;
		if($driver !== Rule::COLLECTION_DRIVER)
			throw new AuditLoggerException(sprintf('Driver must be %s', Rule::COLLECTION_DRIVER));
		$this->items = $items;
		return $this;
	}

	public function getCollection()
	{
		$this->isDriverSet();
		if(empty($this->items))
			throw new AuditLoggerException("You need to call setCollection method first.", 1);
		return $this->items;
	}

	/**
	 * @param string $key
	 * @param string|null $operator
	 * @param string|null $value
	*/
	public function where(string $key, string $operator = null, string $value = null)
	{
		$driver = $this->getDriver();
		if($driver === Rule::COLLECTION_DRIVER)
			$this->items = $this->items->where($key, $operator, $value);
		elseif($driver === Rule::DB_DRIVER)
			$this->model = $this->model->where($key, $operator, $value);
		return $this;
	}

	/**
	 * @param string $key
	 * @param string|null $operator
	 * @param string|null $value
	*/
	public function whereDate(string $key, string $operator, string $value = null)
	{
		$driver = $this->getDriver();
		if(func_num_args() == 2){
			$value = $operator;
			$operator = '=';
		}

		// $value = Carbon::parse( $value );

		if($driver === Rule::DB_DRIVER){
			// $value = $value->format('Y-m-d');
			$this->model = $this->model->whereDate($key, $operator, $value);
		}elseif($driver === Rule::COLLECTION_DRIVER){
			$this->where($key, $operator, $value);
		}

		return $this;
	}

	public function whereDateBetween(string $key, string $fromDate, string $toDate = null)
	{
		if(empty($toDate))
			$toDate = Carbon::now()->format('Y-m-d');

		return $this->whereDate($key, '>=', $fromDate)->whereDate($key, '<=', $toDate);
	}

	public function whereBetween(string $key, string $from, string $to)
	{
		return $this->where($key, '>=', $from)->where($key, '<=', $to);
	}

	public function whereNull(string $key)
	{
		return $this->where($key, '=', NULL);
	}

	public function whereNotNull(string $key)
	{
		return $this->where($key, '!=', NULL);
	}

	public function whereIn(string $key, array $values)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->whereIn($key, $values);
		elseif($driver === Rule::COLLECTION_DRIVER)
			$this->items = $this->items->whereIn($key, $values);
		return $this;
	}

	public function whereNotIn(string $key, array $values)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->whereNotIn($key, $values);
		elseif($driver === Rule::COLLECTION_DRIVER)
			$this->items = $this->items->whereNotIn($key, $values);
		return $this;
	}

	public function orderBy(string $key, string $dir = null)
	{
		$driver = $this->getDriver();
		if(empty($dir))
			$dir = Rule::SORT_ORDER_ASC;
		else
			$dir = strtolower($dir);

		if($dir !== Rule::SORT_ORDER_ASC && $dir !== Rule::SORT_ORDER_DESC)
			throw new AuditLoggerException(sprintf('$dir must be either %s or %s', Rule::SORT_ORDER_ASC, Rule::SORT_ORDER_DESC), 1);

		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->orderBy($key, $dir);
		elseif ($driver === Rule::COLLECTION_DRIVER) {
			if($dir === Rule::SORT_ORDER_ASC)
				$this->items = $this->items->sortBy($key);
			elseif($dir === Rule::SORT_ORDER_DESC)
				$this->items = $this->items->sortByDesc($key);
		}

		return $this;
	}

	public function take(int $limit)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->take($limit);
		elseif ($driver === Rule::COLLECTION_DRIVER)
			$this->queueActions['take'] = $limit;

		return $this;
	}

	public function skip(int $offset)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->skip($offset);
		elseif ($driver === Rule::COLLECTION_DRIVER)
			$this->queueActions['skip'] = $offset;

		return $this;
	}

	public function limit(int $limit)
	{
		return $this->take($limit);
	}

	public function offset(int $offset)
	{
		return $this->skip($offset);
	}

	public function latest()
	{
		return $this->orderBy('created_at', Rule::SORT_ORDER_DESC);
	}

	public function get()
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			return $this->model->get();

		if($driver === Rule::COLLECTION_DRIVER)
		{
			if(isset($this->queueActions['skip']))
				$this->items = $this->items->slice($this->queueActions['skip']);
			
			if(isset($this->queueActions['take']))
				$this->items = $this->items->take($this->queueActions['take']);

			$m = config('modelauditlogger.model');
			$m = new $m;
			return $m->hydrate($this->items->values()->all());
		}
		

		return null;

	}

	public function count()
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			return $this->model->count();
		if($driver === Rule::COLLECTION_DRIVER)
			return $this->items->count();
		return 0;
	}
}