<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;
use GimliDev\Builders\File_Builder;
use Symfony\Component\Yaml\Yaml;

use function GimliDev\load_config;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class Create_Migration_Action extends Action_Base {
	
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

		return $this->createMigrationFile($Request);
		
	}

	/**
	 * Build a migration file
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function createMigrationFile(Request $Request): Request_Response {
		
		$path = $Request->getArgument('save_path') ?? $this->database_config['migrations'];
		$path = rtrim($path, '/');

		$name = "Migration_" . date('YmdHis') . ".yaml";

		$full_path = $path . '/' . $name;

		$yaml_content = Yaml::dump(['migration_name' => rtrim($name, '.yaml'),'metadata' => '', 'query' => 
			[
				"create" => "",
				"revert" => "",
			]
		]);

		file_put_contents($full_path, $yaml_content);

		$this->Printer->success("Migration file created at $full_path");

		return new Request_Response(true);
	}
}