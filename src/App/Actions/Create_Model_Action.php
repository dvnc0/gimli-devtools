<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;
use GimliDev\Builders\File_Builder;
use GimliDev\Database\Database;

use function GimliDev\load_config;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class Create_Model_Action extends Action_Base
{

	/**
	 * @var array
	 */
	protected array $model_config;

	/**
	 * Execute the action
	 * 
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {
		$config = load_config();
		$namespace = $Request->getArgument('namespace') ?? '';

		$this->model_config = $config['models'] ?? [
			'default_save_path' => 'App/Models',
			'namespace' => 'App\Models',
			'extends' => 'Gimli\Database\Model',
		];

		[$model_name, $model_path] = $this->formatPath($Request);

		if (empty($namespace)) {
			$namespace = text('Enter the namespace for the model file', default: $this->model_config['namespace']);
		}


		if (file_exists($model_path)) {
			$this->Printer->error("The model file already exists at {$model_path}");
			exit(1);
		}

		$extends = confirm('Would you like to extend a base model file or class?');
		$extends_path = $this->model_config['extends'] ?? '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', default: $extends_path);
		}

		$table_name = text('Enter the name of the table for this model', default: $Request->getArgument('table'));

		$props = [];
		$props[] = [
			'name' => 'table_name',
			'type' => 'string',
			'comment' => 'The name of the table in the database',
			'value' => $table_name,
			'protected' => true,
		];
		$props[] = [
			'name' => 'primary_key',
			'type' => 'string',
			'comment' => 'The primary key for the table',
			'value' => text('Enter the primary key for the table', default: $Request->getArgument('primary_key')),
			'protected' => true,
		];

		$props = array_merge($props, $this->getProps($table_name));

		$File_Builder = new File_Builder($model_name, $namespace, $extends_path);
		$File_Builder->addUseStatements(["Gimli\Database\Model"]);
		$File_Builder->addClassProperties($props);
		$File_Builder->addMethods([
			[
				'name' => 'beforeSave',
				'return' => null,
				'body' => 'return;',
				'params' => [],
			],
			[
				'name' => 'afterSave',
				'return' => null,
				'body' => 'return;',
				'params' => [],
			],
		]);
		$logic_class_output = $File_Builder->getClass();

		file_put_contents($model_path, $logic_class_output);

		$this->Printer->success("Model file created at $model_path");

		return new Request_Response(true);

	}

	/**
	 * Format the path for the logic file
	 * 
	 * @param Request $Request
	 * @return array
	 */
	protected function formatPath(Request $Request): array {
		$model_name = $Request->getArgument('model_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the logic file?', default: $this->model_config['default_save_path']);
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$model_path = ROOT . $path . $model_name . '.php';
		
		return [$model_name, $model_path];
	}

	protected function getProps(string $table_name): array {
		$database = new Database();
		$schema = $database->getTableSchema($table_name);

		$props = [];

		foreach ($schema as $column) {
			$props[] = [
				'name' => $column['Field'],
				'type' => $database->getPhpType($column['Type']),
				'comment' => $column['Comment'] ?? '',
			];
		}

		return $props;
	}
}