<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;
use GimliDev\Builders\Controller_Builder;

use function GimliDev\load_config;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class Create_Controller_Action extends Action_Base {
	
	const RESPONSE_NAMESPACE = "Gimli\Http\Response";
	const LATTE_ENGINE = "Gimli\View\Latte_Engine";
	const REQUEST_NAMESPACE = "Gimli\Http\Request";

	protected array $controller_config = [];

	/**
	 * Execute the action
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {
		$single_action_controller = $Request->getArgument('single_action');
		$resource_controller = $Request->getArgument('resource');

		$config = load_config();

		$this->controller_config = $config['controllers'] ?? [
			'default_save_path' => 'App/Controllers',
			'namespace' => 'App\\Controllers',
			'extends' => null
		];

		if ($single_action_controller) {
			return $this->buildSingleAction($Request);
		}

		if ($resource_controller) {
			return $this->buildResourceController($Request);
		}

		return $this->buildEmptyController($Request);
	}

	/**
	 * Format the path for the controller
	 *
	 * @param Request $Request
	 * @return array
	 */
	protected function formatPath(Request $Request): array {
		$controller_name = $Request->getArgument('controller_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the controller?', default: $this->controller_config['default_save_path']);
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$controller_path = ROOT . $path . $controller_name . '.php';
		
		return [$controller_name, $controller_path];
	}

	/**
	 * Build a single action controller
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function buildSingleAction(Request $Request): Request_Response {
		[$controller_name, $controller_path] = $this->formatPath($Request);
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the controller?', default: $this->controller_config['namespace']);
		}

		if (file_exists($controller_path)) {
			$this->Printer->error('A controller with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base controller or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: $this->controller_config['extends']);
		}

		$controller_builder = new Controller_Builder($controller_name, $namespace, $extends_path);
		$controller_builder->addUseStatements([self::RESPONSE_NAMESPACE, self::LATTE_ENGINE]);
		$controller_builder->addMethods([
			[
				'name' => '__construct',
				'return' => null,
				'params' => [
					[
						'name' => 'Latte_Engine',
						'type' => self::LATTE_ENGINE,
						'comment' => 'Latte Engine'
					]
				]
			],
			[
				'name' => '__invoke',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					]
				]
			]
		]);

		$controller = $controller_builder->getClass();

		file_put_contents($controller_path, $controller);

		$this->Printer->success("Controller created at $controller_path");

		return new Request_Response(true);
	}

	/**
	 * Build a resource controller
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function buildResourceController(Request $Request): Request_Response {
		[$controller_name, $controller_path] = $this->formatPath($Request);
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the controller?', default: $this->controller_config['namespace']);
		}

		if (file_exists($controller_path)) {
			$this->Printer->error('A controller with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base controller or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: $this->controller_config['extends']);
		}

		$controller_builder = new Controller_Builder($controller_name, $namespace, $extends_path);
		$controller_builder->addUseStatements([self::RESPONSE_NAMESPACE, self::LATTE_ENGINE, self::REQUEST_NAMESPACE]);
		$controller_builder->addMethods([
			[
				'name' => '__construct',
				'return' => null,
				'params' => [
					[
						'name' => 'Latte_Engine',
						'type' => self::LATTE_ENGINE,
						'comment' => 'Latte Engine'
					]
				]
			],
			[
				'name' => 'index',
				'comment' => 'List all resources',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					]
				]
			],
			[
				'name' => 'create',
				'comment' => 'Show the form to create a new resource',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					]
				]
			],
			[
				'name' => 'save',
				'comment' => 'Save a new resource',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => self::REQUEST_NAMESPACE,
						'comment' => 'The Request object'
					]
				]
			],
			[
				'name' => 'change',
				'comment' => 'Show the form to update a resource',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => self::REQUEST_NAMESPACE,
						'comment' => 'The Request object'
					],
					[
						'name' => 'id',
						'type' => 'int',
						'comment' => 'The ID of the resource'
					]
				]
			],
			[
				'name' => 'update',
				'comment' => 'Update a resource',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => self::REQUEST_NAMESPACE,
						'comment' => 'The Request object'
					],
					[
						'name' => 'id',
						'type' => 'int',
						'comment' => 'The ID of the resource'
					]
				]
			],
			[
				'name' => 'view',
				'comment' => 'View a specific resource',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => self::REQUEST_NAMESPACE,
						'comment' => 'The Request object'
					],
					[
						'name' => 'id',
						'type' => 'int',
						'comment' => 'The ID of the resource'
					]
				]
			],
			[
				'name' => 'remove',
				'comment' => 'Remove a resource',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'comment' => 'The Response object'
					],
					[
						'name' => 'id',
						'type' => 'int',
						'comment' => 'The ID of the resource'
					]
				]
			],
		]);

		$controller = $controller_builder->getClass();

		file_put_contents($controller_path, $controller);

		$this->Printer->success("Controller created at $controller_path");

		return new Request_Response(true);
	}

	/**
	 * Build an empty controller
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function buildEmptyController(Request $Request): Request_Response {
		[$controller_name, $controller_path] = $this->formatPath($Request);
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the controller?', default: $this->controller_config['namespace']);
		}

		if (file_exists($controller_path)) {
			$this->Printer->error('A controller with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base controller or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: $this->controller_config['extends']);
		}

		$controller_builder = new Controller_Builder($controller_name, $namespace, $extends_path);
		$controller_builder->addUseStatements([self::RESPONSE_NAMESPACE, self::LATTE_ENGINE, self::REQUEST_NAMESPACE]);
		$controller_builder->addMethods([
			[
				'name' => '__construct',
				'return' => null,
				'params' => [
					[
						'name' => 'Latte_Engine',
						'type' => self::LATTE_ENGINE,
						'comment' => 'Latte Engine'
					]
				]
			]
		]);

		$controller = $controller_builder->getClass();

		file_put_contents($controller_path, $controller);

		$this->Printer->success("Controller created at $controller_path");

		return new Request_Response(true);
	}
}