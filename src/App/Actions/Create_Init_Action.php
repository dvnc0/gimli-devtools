<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

/**
 * Create a devtools.json configuration file
 */
class Create_Init_Action extends Action_Base {

	public const MIGRATION_LOG = '/devtools.migration.json';
	public const CONFIG_FILE = '/devtools.json';

	/**
	 * Execute the action
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {
		
		$force_overwrite = $Request->getArgument('force');

		if (file_exists(ROOT . '/devtools.json') && !$force_overwrite) {
			$this->Printer->error('A config file already exists in this directory.');
			exit(1);
		}

		$this->Printer->info("Welcome to the Clyde project initializer. Let's get started!");
		$project_name = text("What is the name of your project?");

		$devtools_config = [
			"project_name" => $project_name,
		];

		$using_database = select("Will you be using a database?", ["Yes", "No"]);

		if ($using_database === "Yes") {
			$devtools_config["database"] = [
				"name" => text("What is the name of your database?"),
				"user" => text("What is the username for your database?"),
				"password" => text("What is the password for your database?"),
				"host" => text(label: "What is the host for your database?", placeholder: "localhost"),
				"port" => text("What is the port for your database?"),
				"migrations" => text(label: "What is the path to your migrations?", default: ROOT . '/sql'),
			];
		}

		$controller_defaults = select("Would you like to set controller defaults?", ["Yes", "No"]);

		if ($controller_defaults === "Yes") {
			$devtools_config["controllers"] = [
				'default_save_path' => text(label: 'What is the default save path for controllers?', default: '/src/App/Controllers'),
				'namespace' => text(label: 'What is the default namespace for controllers?', default: 'App\Controllers'),
				'extends' => text(label: 'What is the default class to extend for controllers?') ?? "",
			];
		}

		$logic_defaults = select("Would you like to set logic file defaults?", ["Yes", "No"]);

		if ($logic_defaults === "Yes") {
			$devtools_config["logic"] = [
				'default_save_path' => text(label: 'What is the default save path for logic files?', default: 'App/Logic'),
				'namespace' => text(label: 'What is the default namespace for logic files?', default: 'App\Logic'),
				'extends' => text(label: 'What is the default class to extend for logic files?') ?? "",
			];
		}

		if ($force_overwrite) {
			$continue = confirm("Would you like to continue? Doing so will overwrite your existing configuration file.");
			if (!$continue) {
				$this->Printer->error("Exiting...");
				exit(0);
			}
		}

		spin(
			fn() => file_put_contents(ROOT . self::CONFIG_FILE, json_encode($devtools_config, JSON_PRETTY_PRINT)), "
			Saving configuration file..."
		);

		if ($using_database === "Yes") {
			$log = [
				'processed' => [],
				'last_run' => date('YmdHis'),
			];

			spin(
				fn() => file_put_contents(ROOT . self::MIGRATION_LOG, json_encode($log, JSON_PRETTY_PRINT)), "
				Saving log file..."
			);
		}

		$this->Printer->success("Created devtools configuration file!");
		return new Request_Response(true);
	}
}