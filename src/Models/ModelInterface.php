<?php

namespace ProlificHue\ModelAuditLogger\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

interface ModelInterface
{
	/**
	 * @param string $driver
	 * @return self new instance
	*/
	public static function make(string $driver);

	/**
	 * @param string $driver
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	 * @return void
	*/
	public function setDriver(string $driver);

	/**
	 * @return string $driver
	*/
	public function getDriver();

	/**
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	*/
	public function isDriverSet();

	/**
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @return $this
	*/
	public function setModel(Model $model);

	/**
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	 * @return \Illuminate\Database\Eloquent\Model
	*/
	public function getModel();

	/**
	 * @param \Illuminate\Support\Collection $items
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	 * @return $this
	*/
	public function setCollection(Collection $items);

	/**
	 * @return Illuminate\Support\Collection
	 * @throws \ProlificHue\ModelAuditLogger\Exceptions\AuditLoggerException
	*/
	public function getCollection();

	/**
	 * @param string $key
	 * @param string|null $operator
	 * @param string|null $value
	 * @return $this
	*/
	public function where(string $key, string $operator = null, string $value = null);

	/**
	 * @param string $key
	 * @param string|null $operator
	 * @param string|null $value
	 * @return $this
	*/
	public function whereDate(string $key, string $operator, string $value = null);

	/**
	 * @param string $key
	 * @param string $fromDate
	 * @param string|null $toDate
	 * @return $this
	*/
	public function whereDateBetween(string $key, string $fromDate, string $toDate = null);

	/**
	 * @param string $key
	 * @param string $from
	 * @param string $to
	 * @return $this
	*/
	public function whereBetween(string $key, string $from, string $to);

	/**
	 * @param string $key
	 * @return $this
	*/
	public function whereNull(string $key);

	/**
	 * @param string $key
	 * @return $this
	*/
	public function whereNotNull(string $key);

	/**
	 * @param string $key
	 * @param array $values
	 * @return $this
	*/
	public function whereIn(string $key, array $values);

	/**
	 * @param string $key
	 * @param array $values
	 * @return $this
	*/
	public function whereNotIn(string $key, array $values);

	/**
	 * @param string $key
	 * @param string|null $dir
	 * @return $this
	*/
	public function orderBy(string $key, string $dir = null);

	/**
	 * @param integer $limit
	 * @return $this
	*/
	public function take(int $limit);

	/**
	 * @param integer $offset
	 * @return $this
	*/
	public function skip(int $offset);

	/**
	 * @param integer $limit
	 * @return $this
	*/
	public function limit(int $limit);

	/**
	 * @param integer $offset
	 * @return $this
	*/
	public function offset(int $offset);

	/**
	 * @return $this
	*/
	public function latest();

	/**
	 * @return \Illuminate\Database\Eloquent\Collection
	*/
	public function get();

	/**
	 * @return integer
	*/
	public function count();
}