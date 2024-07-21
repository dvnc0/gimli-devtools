<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;
use GimliDev\Builders\File_Builder;

use function GimliDev\load_config;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class Create_Logic_Action extends Action_Base
{

	/**
	 * @var array
	 */
	protected array $logic_config;

	/**
	 * Execute the action
	 * 
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {
		$config = load_config();
		$namespace = $Request->getArgument('namespace') ?? '';

		$this->logic_config = $config['logic'] ?? [
			'default_save_path' => 'App/Logic',
		];

		[$logic_name, $logic_path] = $this->formatPath($Request);

		if (empty($namespace)) {
			$namespace = text('Enter the namespace for the logic file', default: $config['namespace']);
		}


		if (file_exists($logic_path)) {
			$this->Printer->error("The logic file already exists at {$logic_path}");
			exit(1);
		}

		$extends = confirm('Would you like to extend a base logic file or class?');
		$extends_path = $config['extends'] ?? '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', default: $extends_path);
		}

		$File_Builder = new File_Builder($logic_name, $namespace, $extends_path);
		$File_Builder->addUseStatements(["Gimli\Application"]);
		$File_Builder->addMethods([
			[
				'name' => '__construct',
				'return' => null,
				'params' => [
					[
						'name' => 'Application',
						'type' => 'Gimli\Application',
						'type_short' => 'Application',
						'comment' => 'Application instance',
					]
				]
			],
		]);
		$logic_class_output = $File_Builder->getClass();

		file_put_contents($logic_path, $logic_class_output);

		$this->Printer->success("Logic file created at $logic_path");

		return new Request_Response(true);

	}

	/**
	 * Format the path for the logic file
	 * 
	 * @param Request $Request
	 * @return array
	 */
	protected function formatPath(Request $Request): array {
		$logic_name = $Request->getArgument('logic_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the logic file?', default: $this->logic_config['default_save_path']);
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$logic_path = ROOT . $path . $logic_name . '.php';
		
		return [$logic_name, $logic_path];
	}
}