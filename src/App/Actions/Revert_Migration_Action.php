<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;
use GimliDev\Database\Database;
use PDOException;
use Symfony\Component\Yaml\Yaml;

use function GimliDev\load_config;

class Revert_Migration_Action extends Action_Base {
	
	protected array $database_config = [];

	/**
	 * Execute the action
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {

		$config = load_config();

		$this->database_config = $config['database'] ?? [
			"name" => "gimli",
			"user" => "dev_root",
			"password" => "dev_root",
			"host" => "0.0.0.0",
			"port" => "34003",
			"migrations" => ROOT . '/sql',
		];

		return $this->revertMigrationFiles($Request);
		
	}

	/**
	 * revert migration files
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function revertMigrationFiles(Request $Request): Request_Response {
		
		$path = $Request->getArgument('save_path') ?? $this->database_config['migrations'];
		$path = rtrim($path, '/');

		// get all yaml files in path
		$file_name = $Request->getArgument('file_name');
		$file_name = rtrim($file_name, '.yaml');
		$file_path = $path . '/' . $file_name . '.yaml';
		// get devtools.migration.json file
		$devtools_migration_file = ROOT . '/devtools.migration.json';
		$migration_log = json_decode(file_get_contents($devtools_migration_file), true);
		$database = (new Database($this->database_config))->getDb();

		if (!in_array($file_path, $migration_log['processed'])) {
			$this->Printer->error("Migration $file_name has not been run");
			exit(1);
		}

		$yaml = Yaml::parseFile($file_path);

		// run migration
		$this->Printer->info("Reverting migration: $file_name");

		// run create query
		$this->Printer->info("Running revert query");
		
		try {
			$database->beginTransaction();
			$database->exec($yaml['query']['revert']);

			$error = $database->errorInfo();

			if ($error[0] !== '00000') {
				if ($database->inTransaction()) {
					$database->rollBack();
				}
				$this->Printer->error('Error running query: ' . $error[2]);
				exit(1);
			}

			if ($database->inTransaction()) {
				$database->commit();
			}
		} catch (PDOException $e) {
			if ($database->inTransaction()) {
				$database->rollBack();
				$database->commit();
			}
			$this->Printer->error('Error running query: ' . $e->getMessage());
			exit(1);
		}

		if ($Request->getArgument('erase_log') === true) {
			$migration_log['processed'] = array_filter($migration_log['processed'], function($item) use ($file_path) {
				return $item !== $file_path;
			});
		}

		$this->Printer->success("Migration $file_name complete. " . $yaml['metadata']);

		$migration_log['last_run'] = date('YmdHis');
		file_put_contents($devtools_migration_file, json_encode($migration_log, JSON_PRETTY_PRINT));

		$this->Printer->success("Migrations reverted");

		return new Request_Response(true);
	}
}