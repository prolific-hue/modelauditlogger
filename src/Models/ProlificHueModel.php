<?php

namespace ProlificHue\ModelAuditLogger\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException;
use ProlificHue\ModelAuditLogger\Rule;

class ProlificHueModel implements ModelInterface
{
	/**
	 * Illuminate\Support\Collection $items	
	*/
	private $items;

	/**
	 * Illuminate\Database\Eloquent\Model $model
	*/
	private $model;

	/**
	 * string $driver
	*/
	private $driver;

	/**
	 * array $queueActions
	*/
	private $queueActions = []; 

	/**
	 * @param string $driver
	*/
	private function __construct(string $driver)
	{
		$this->setDriver($driver);
	}

	/**
	 * @param string $driver
	 * @return self new instance
	*/
	public static function make(string $driver)
	{
		return new static($driver);
	}

	/**
	 * @param string $driver
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	 * @return void
	*/
	public function setDriver(string $driver)
	{
		if($driver !== Rule::COLLECTION_DRIVER && $driver !== Rule::DB_DRIVER)
			throw new AuditLoggerException(sprintf('Driver must be either %s or %s', Rule::COLLECTION_DRIVER, Rule::DB_DRIVER), 1);

		$this->driver = $driver;	
	}

	/**
	 * @return string $driver
	*/
	public function getDriver()
	{
		$this->isDriverSet();
		return $this->driver;
	}

	/**
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	*/
	private function isDriverSet()
	{
		if(empty( $this->driver ))
			throw new AuditLoggerException('You need to call setDriver method first.');

		if($this->driver === Rule::DB_DRIVER && empty($this->model))
			throw new AuditLoggerException("Model is not set.", 1);
			
		if($this->driver === Rule::COLLECTION_DRIVER && $this->items === null)
			throw new AuditLoggerException("Collection is not set.", 1);
	}

	/**
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @return $this
	*/
	public function setModel(Model $model)
	{
		$driver = $this->driver;
		if($driver !== Rule::DB_DRIVER)
			throw new AuditLoggerException(sprintf('Driver must be %s', Rule::DB_DRIVER));
		$this->model = $model;
		return $this;
	}

	/**
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	 * @return \Illuminate\Database\Eloquent\Model
	*/
	public function getModel()
	{
		$this->isDriverSet();
		if(empty($this->model))
			throw new AuditLoggerException("You need to call setModel method first.", 1);
		return $this->model;
	}

	/**
	 * @param \Illuminate\Support\Collection $items
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	 * @return $this
	*/
	public function setCollection(Collection $items)
	{
		$driver = $this->driver;
		if($driver !== Rule::COLLECTION_DRIVER)
			throw new AuditLoggerException(sprintf('Driver must be %s', Rule::COLLECTION_DRIVER));
		$this->items = $items;
		return $this;
	}

	/**
	 * @return Illuminate\Support\Collection
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	*/
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
	 * @return $this
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
	 * @return $this
	*/
	public function whereDate(string $key, string $operator, string $value = null)
	{
		$driver = $this->getDriver();
		if(func_num_args() == 2){
			$value = $operator;
			$operator = '=';
		}

		if($driver === Rule::DB_DRIVER){
			// $value = $value->format('Y-m-d');
			$this->model = $this->model->whereDate($key, $operator, $value);
		}elseif($driver === Rule::COLLECTION_DRIVER){
			$this->where($key, $operator, $value);
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @param string $fromDate
	 * @param string|null $toDate
	 * @return $this
	*/
	public function whereDateBetween(string $key, string $fromDate, string $toDate = null)
	{
		if(empty($toDate))
			$toDate = Carbon::now()->format('Y-m-d');

		return $this->whereDate($key, '>=', $fromDate)->whereDate($key, '<=', $toDate);
	}

	/**
	 * @param string $key
	 * @param string $from
	 * @param string $to
	 * @return $this
	*/
	public function whereBetween(string $key, string $from, string $to)
	{
		return $this->where($key, '>=', $from)->where($key, '<=', $to);
	}

	/**
	 * @param string $key
	 * @return $this
	*/
	public function whereNull(string $key)
	{
		return $this->where($key, '=', NULL);
	}

	/**
	 * @param string $key
	 * @return $this
	*/
	public function whereNotNull(string $key)
	{
		return $this->where($key, '!=', NULL);
	}

	/**
	 * @param string $key
	 * @param array $values
	 * @return $this
	*/
	public function whereIn(string $key, array $values)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->whereIn($key, $values);
		elseif($driver === Rule::COLLECTION_DRIVER)
			$this->items = $this->items->whereIn($key, $values);
		return $this;
	}

	/**
	 * @param string $key
	 * @param array $values
	 * @return $this
	*/
	public function whereNotIn(string $key, array $values)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->whereNotIn($key, $values);
		elseif($driver === Rule::COLLECTION_DRIVER)
			$this->items = $this->items->whereNotIn($key, $values);
		return $this;
	}

	/**
	 * @param string $key
	 * @param string|null $dir
	 * @return $this
	*/
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

	/**
	 * @param integer $limit
	 * @return $this
	*/
	public function take(int $limit)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->take($limit);
		elseif ($driver === Rule::COLLECTION_DRIVER)
			$this->queueActions['take'] = $limit;

		return $this;
	}

	/**
	 * @param integer $offset
	 * @return $this
	*/
	public function skip(int $offset)
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			$this->model = $this->model->skip($offset);
		elseif ($driver === Rule::COLLECTION_DRIVER)
			$this->queueActions['skip'] = $offset;

		return $this;
	}

	/**
	 * @param integer $limit
	 * @return $this
	*/
	public function limit(int $limit)
	{
		return $this->take($limit);
	}

	/**
	 * @param integer $offset
	 * @return $this
	*/
	public function offset(int $offset)
	{
		return $this->skip($offset);
	}

	/**
	 * @return $this
	*/
	public function latest()
	{
		return $this->orderBy('created_at', Rule::SORT_ORDER_DESC);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Collection
	*/
	public function get()
	{
		$driver = $this->getDriver();
		if($driver === Rule::DB_DRIVER)
			return $this->model->get();

		$m = config('modelauditlogger.model');
		$m = new $m;

		if($driver === Rule::COLLECTION_DRIVER)
		{
			if(isset($this->queueActions['skip']))
				$this->items = $this->items->slice($this->queueActions['skip']);
			
			if(isset($this->queueActions['take']))
				$this->items = $this->items->take($this->queueActions['take']);

			return $m->hydrate($this->items->values()->all());
		}
		
		return $m->hydrate([]);

	}

	/**
	 * @return integer
	*/
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