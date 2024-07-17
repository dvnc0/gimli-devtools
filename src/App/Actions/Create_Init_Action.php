<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;
use Clyde\Tools\Printer;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

/**
 * Create a devtools.json configuration file
 */
class Create_Init_Action extends Action_Base {

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
		$using_database = select("Will you be using a database?", ["Yes", "No"]);

		if ($using_database === "Yes") {
			$database_name = text("What is the name of your database?");
			$database_user = text("What is the username for your database?");
			$database_password = text("What is the password for your database?");
			$database_host = text(label: "What is the host for your database?", placeholder: "localhost");
			$database_port = text("What is the port for your database?");
		}

		$devtools_config = [
			"project_name" => $project_name,
		];

		if ($using_database === "Yes") {
			$devtools_config["database"] = [
				"name" => $database_name,
				"user" => $database_user,
				"password" => $database_password,
				"host" => $database_host,
				"port" => $database_port,
			];
		}

		spin(
			fn() => file_put_contents(ROOT . "/devtools.json", json_encode($devtools_config, JSON_PRETTY_PRINT)), "
			Saving configuration file..."
		);

		$this->Printer->success("Created devtools configuration file!");
		return new Request_Response(true);
	}
}