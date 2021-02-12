<?php

namespace App\Db;

use App\System\Meta;
use PDOException;
use PDO;

/**
 * Class DB
 */
class DB
{
	/** @var Meta */
	private $meta;

	/** @var */
	private $connection;

	/**
	 * DB constructor.
	 * @param Meta $meta
	 * @throws \Exception
	 */
	public function __construct(Meta $meta)
	{
		$this->meta = $meta;
		$this->connect();
	}

	/**
	 * @return mixed
	 */
	public function connection()
	{
		return $this->connection;
	}

	/**
	 *
	 * @throws \Exception
	 */
	private function connect()
	{
		try {
			$this->connection = new PDO(
				'mysql:dbname=' . $this->meta->getConfigParam('db_name') .
				';host=' . $this->meta->getConfigParam('db_host'),
				$this->meta->getConfigParam('db_login'),
				$this->meta->getConfigParam('db_password'),
				[
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
					PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				]
			);
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
		} catch (PDOException $e) {
			throw new \Exception('Не удалось установить подключение с БД / ' . $e->getMessage());
		}
	}
}
