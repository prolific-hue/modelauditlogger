<?php

namespace ProlificHue\ModelAuditLogger\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class ModelInterface
{
	private $items;

	/**
	 * @param \Illuminate\Support\Collection $items
	*/
	public function __construct(Collection $items);

	public function setDriver(string $driver);

	public function getDriver();

	public function isDriverSet();

	public function setModel(Model $model);

	public function getModel();

	public function setCollection(Collection $items);

	public function getCollection();
	/**
	 * @param string $key
	 * @param string|null $operator
	 * @param string|null $value
	*/
	public function where(string $key, string $operator = null, string $value = null);

	/**
	 * @param string $key
	 * @param string|null $operator
	 * @param string|null $value
	*/
	public function whereDate(string $key, string $operator = null, string $value = null);

	public function whereDateBetween(string $key, string $operator = null, string $value = null);

	public function whereBetween(string $key, string $operator = null, string $value = null);

	public function whereNull(string $key);

	public function whereNotNull(string $key);

	public function orderBy(string $key, string $dir = null);

	public function take(int $limt);

	public function skip(int $offset);

	public function limit(int $limit);

	public function offset(int $offset);

	public function latest();

	public function get();

	public function count();
}