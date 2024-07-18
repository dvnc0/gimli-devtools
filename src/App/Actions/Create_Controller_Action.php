<?php
declare(strict_types=1);

namespace GimliDev\Actions;

use Clyde\Actions\Action_Base;
use Clyde\Request\Request;
use Clyde\Request\Request_Response;
use GimliDev\Builders\Controller_Builder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class Create_Controller_Action extends Action_Base {

	/**
	 * Execute the action
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {
		$single_action_controller = $Request->getArgument('single_action');
		$resource_controller = $Request->getArgument('resource');

		if ($single_action_controller) {
			return $this->buildSingleAction($Request);
		}

		if ($resource_controller) {
			return $this->buildResourceController($Request);
		}

		return $this->buildEmptyController($Request);
	}

	/**
	 * Build a single action controller
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function buildSingleAction(Request $Request): Request_Response {
		$controller_name = $Request->getArgument('controller_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the controller?', default: 'App/Controllers');
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$controller_path = ROOT . $path . $controller_name . '.php';
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the controller?', default: 'App\\Controllers');
		}

		if (file_exists($controller_path)) {
			$this->Printer->error('A controller with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base controller or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: 'App\Controllers\Base');
		}

		$Gimli_Response_Namespace = "Gimli\Http\Response";
		$Gimli_Latte_Engine = "Gimli\View\Latte_Engine";

		$controller_builder = new Controller_Builder($controller_name, $namespace, $extends_path);
		$controller_builder->addUseStatements([$Gimli_Response_Namespace, $Gimli_Latte_Engine]);
		$controller_builder->addMethods([
			[
				'name' => '__construct',
				'return' => null,
				'params' => [
					[
						'name' => 'Latte_Engine',
						'type' => $Gimli_Latte_Engine,
						'comment' => 'Latte Engine'
					]
				]
			],
			[
				'name' => '__invoke',
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
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
		$controller_name = $Request->getArgument('controller_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the controller?', default: 'App/Controllers');
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$controller_path = ROOT . $path . $controller_name . '.php';
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the controller?', default: 'App\\Controllers');
		}

		if (file_exists($controller_path)) {
			$this->Printer->error('A controller with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base controller or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: 'App\Controllers\Base');
		}

		$Gimli_Response_Namespace = "Gimli\Http\Response";
		$Gimli_Request_Namespace = "Gimli\Http\Request";
		$Gimli_Latte_Engine = "Gimli\View\Latte_Engine";

		$controller_builder = new Controller_Builder($controller_name, $namespace, $extends_path);
		$controller_builder->addUseStatements([$Gimli_Response_Namespace, $Gimli_Latte_Engine, $Gimli_Request_Namespace]);
		$controller_builder->addMethods([
			[
				'name' => '__construct',
				'return' => null,
				'params' => [
					[
						'name' => 'Latte_Engine',
						'type' => $Gimli_Latte_Engine,
						'comment' => 'Latte Engine'
					]
				]
			],
			[
				'name' => 'index',
				'comment' => 'List all resources',
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
						'comment' => 'The Response object'
					]
				]
			],
			[
				'name' => 'create',
				'comment' => 'Show the form to create a new resource',
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
						'comment' => 'The Response object'
					]
				]
			],
			[
				'name' => 'save',
				'comment' => 'Save a new resource',
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => $Gimli_Request_Namespace,
						'comment' => 'The Request object'
					]
				]
			],
			[
				'name' => 'change',
				'comment' => 'Show the form to update a resource',
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => $Gimli_Request_Namespace,
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
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => $Gimli_Request_Namespace,
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
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
						'comment' => 'The Response object'
					],
					[
						'name' => 'Request',
						'type' => $Gimli_Request_Namespace,
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
				'return' => $Gimli_Response_Namespace,
				'params' => [
					[
						'name' => 'Response',
						'type' => $Gimli_Response_Namespace,
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
		$controller_name = $Request->getArgument('controller_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the controller?', default: 'App/Controllers');
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$controller_path = ROOT . $path . $controller_name . '.php';
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the controller?', default: 'App\\Controllers');
		}

		if (file_exists($controller_path)) {
			$this->Printer->error('A controller with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base controller or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: 'App\Controllers\Base');
		}

		$Gimli_Response_Namespace = "Gimli\Http\Response";
		$Gimli_Latte_Engine = "Gimli\View\Latte_Engine";
		$Gimli_Request_Namespace = "Gimli\Http\Request";

		$controller_builder = new Controller_Builder($controller_name, $namespace, $extends_path);
		$controller_builder->addUseStatements([$Gimli_Response_Namespace, $Gimli_Latte_Engine, $Gimli_Request_Namespace]);
		$controller_builder->addMethods([
			[
				'name' => '__construct',
				'return' => null,
				'params' => [
					[
						'name' => 'Latte_Engine',
						'type' => $Gimli_Latte_Engine,
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