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

class Run_Migration_Action extends Action_Base {
	
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

		return $this->runMigrationFiles($Request);
		
	}

	/**
	 * run migration files
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function runMigrationFiles(Request $Request): Request_Response {
		
		$path = $Request->getArgument('save_path') ?? $this->database_config['migrations'];
		$path = rtrim($path, '/');

		// get all yaml files in path
		$files = glob($path . '/*.yaml');

		// get devtools.migration.json file
		$devtools_migration_file = ROOT . '/devtools.migration.json';
		$migration_log = json_decode(file_get_contents($devtools_migration_file), true);
		$database = (new Database($this->database_config))->getDb();
		foreach($files as $file) {
			// check if migration has been run
			if (in_array($file, $migration_log['processed'])) {
				continue;
			}

			$yaml = Yaml::parseFile($file);

			// run migration
			$this->Printer->info("Running migration: $file");

			// run create query
			$this->Printer->info("Running create query");
			
			try {
				$database->beginTransaction();
				$database->exec($yaml['query']['create']);

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

			// log migration
			$migration_log['processed'][] = $file;

			$this->Printer->success("Migration $file complete. " . $yaml['metadata']);
		}

		$migration_log['last_run'] = date('YmdHis');
		file_put_contents($devtools_migration_file, json_encode($migration_log, JSON_PRETTY_PRINT));

		$this->Printer->success("Migrations run");

		return new Request_Response(true);
	}
}