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

class Create_Middleware_Action extends Action_Base {
	
	const MIDDLEWARE_INTERFACE = "Gimli\Middleware\Middleware_Interface";
	const MIDDLEWARE_RESPONSE = "Gimli\Middleware\Middleware_Response";

	protected array $middleware_config = [];

	/**
	 * Execute the action
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {

		$config = load_config();

		$this->middleware_config = $config['middleware'] ?? [
			'default_save_path' => 'App/Middleware',
			'namespace' => 'App\\Middleware',
			'extends' => null
		];

		return $this->buildMiddleware($Request);
		
	}

	/**
	 * Format the path for the event
	 *
	 * @param Request $Request
	 * @return array
	 */
	protected function formatPath(Request $Request): array {
		$middleware_name = $Request->getArgument('middleware_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the middleware?', default: $this->middleware_config['default_save_path']);
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$middleware_path = ROOT . $path . $middleware_name . '.php';
		
		return [$middleware_name, $middleware_path];
	}

	/**
	 * Build a middleware
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function buildMiddleware(Request $Request): Request_Response {
		[$middleware_name, $middleware_path] = $this->formatPath($Request);
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the middleware?', default: $this->middleware_config['namespace']);
		}

		if (file_exists($middleware_path)) {
			$this->Printer->error('A middleware file with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base event or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: $this->middleware_config['extends']);
		}

		$File_Builder = new File_Builder($middleware_name, $namespace, $extends_path);
		$File_Builder->addUseStatements([self::MIDDLEWARE_INTERFACE, self::MIDDLEWARE_RESPONSE]);
		$File_Builder->addInterface(self::MIDDLEWARE_INTERFACE);
		$File_Builder->addMethods([
			[
				'name' => 'process',
				'return' => self::MIDDLEWARE_RESPONSE,
				'return_name' => 'Middleware_Response',
				'params' => []
			]
		]);

		$event = $File_Builder->getClass();

		file_put_contents($middleware_path, $event);

		$this->Printer->success("Middleware created at $middleware_path");

		return new Request_Response(true);
	}
}