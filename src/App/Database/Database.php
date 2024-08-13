<?php
declare(strict_types=1);

namespace GimliDev\Database;

use PDO;

use function GimliDev\load_config;

class Database {

	/**
	 * @var PDO $db
	 */
	protected PDO $db;

	public function __construct() {
		$config = load_config();

		$dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['name']}";
		$this->db = new PDO($dsn, $config['database']['user'], $config['database']['password']);
	}

	/**
	 * Get the database connection
	 *
	 * @return PDO
	 */
	public function getDb(): PDO {
		return $this->db;
	}

	/**
	 * Get the schema for a table
	 *
	 * @param  string $table
	 * @return array
	 */
	public function getTableSchema(string $table): array {
		$statement = $this->db->prepare("DESCRIBE {$table}");
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Get the PHP type for a MySQL type
	 *
	 * @param  string $mysql_type
	 * @return string
	 */
	public function getPhpType(string $mysql_type): string {
		return match (true) {
			str_contains($mysql_type, 'int') => 'int',
			str_contains($mysql_type, 'bigint') => 'int',
			str_contains($mysql_type, 'smallint') => 'int',
			str_contains($mysql_type, 'tinyint') => 'int',
			str_contains($mysql_type, 'mediumint') => 'int',
			str_contains($mysql_type, 'decimal') => 'float',
			str_contains($mysql_type, 'double') => 'float',
			str_contains($mysql_type, 'float') => 'float',
			str_contains($mysql_type, 'char') => 'string',
			str_contains($mysql_type, 'varchar') => 'string',
			str_contains($mysql_type, 'text') => 'string',
			str_contains($mysql_type, 'blob') => 'string',
			str_contains($mysql_type, 'date') => 'string',
			str_contains($mysql_type, 'datetime') => 'string',
			str_contains($mysql_type, 'timestamp') => 'string',
			str_contains($mysql_type, 'time') => 'string',
			str_contains($mysql_type, 'year') => 'int',
			str_contains($mysql_type, 'enum') => 'string',
			default => '',
		};
	}
}