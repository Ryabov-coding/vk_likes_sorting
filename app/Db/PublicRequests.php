<?php

namespace App\Db;

use App\System\Meta;
use PDOException;
use PDO;

/**
 * Class PublicRequests
 */
class PublicRequests
{
	/** @var DB */
	private $db;

	/** @var Meta */
	private $meta;

	/**
	 * PublicRequests constructor.
	 * @param DB $db
	 * @param Meta $meta
	 */
	public function __construct(DB $db, Meta $meta)
	{
		$this->db = $db;
		$this->meta = $meta;
	}

	/**
	 * @param int $public_id
	 * @param string $public_title
	 * @param string $public_name
	 * @param int $spend_time
	 * @throws \Exception
	 */
	public function saveRequest(int $public_id, string $public_title, string $public_name, int $spend_time)
	{
		try {
			$request = $this->db->connection()->prepare('
			INSERT INTO `public_requests` SET
			`public_id` = :public_id,
			`public_title` = :public_title,
			`public_name` = :public_name,
			`spend_time` = :spend_time,
			`created_at` = :created_at
		');

			$request->execute([
				'public_id' => $public_id,
				'public_title' => $public_title,
				'public_name' => $public_name,
				'spend_time' => $spend_time,
				'created_at' => date('Y-m-d H:i:s'),
			]);
		} catch(PDOException $e) {
			throw new \Exception('Не удалось сохранить запрос в БД / ' . $e->getMessage());
		}
	}

	/**
	 * @param int $limit
	 * @return mixed
	 * @throws \Exception
	 */
	public function getLastPublicRequests($limit = 5)
	{
		try {
			$request = $this->db->connection()->prepare('
				SELECT
				   MAX(id) AS id,
				   public_id,
				   public_name,
				   ROUND(AVG(spend_time), 0) AS spend_time
				FROM public_requests
					WHERE moderated IS TRUE
				GROUP BY public_id, public_name
					ORDER BY id DESC
				LIMIT :limit
			');

			$request->execute([
				'limit' => $limit
			]);

			return $request->fetchAll(PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			throw new \Exception('Не удалось получить последние запрошенные паблики из БД / ' . $e->getMessage());
		}
	}
}
