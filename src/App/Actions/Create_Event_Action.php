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
use function Laravel\Prompts\textarea;

class Create_Event_Action extends Action_Base {
	
	const EVENT_INTERFACE = "Gimli\Events\Event_Interface";
	const EVENT = "Gimli\Events\Event";

	protected array $event_config = [];

	/**
	 * Execute the action
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {

		$config = load_config();

		$this->event_config = $config['events'] ?? [
			'default_save_path' => 'App/Events',
			'namespace' => 'App\\Events',
			'extends' => null
		];

		return $this->buildEvent($Request);
		
	}

	/**
	 * Format the path for the event
	 *
	 * @param Request $Request
	 * @return array
	 */
	protected function formatPath(Request $Request): array {
		$event_name = $Request->getArgument('event_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the event?', default: $this->event_config['default_save_path']);
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$event_path = ROOT . $path . $event_name . '.php';
		
		return [$event_name, $event_path];
	}

	/**
	 * Build a event
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function buildEvent(Request $Request): Request_Response {
		[$event_name, $event_path] = $this->formatPath($Request);
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the event?', default: $this->event_config['namespace']);
		}

		if (file_exists($event_path)) {
			$this->Printer->error('A event with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base event or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: $this->event_config['extends']);
		}

		$listens_for = textarea(label: 'What is the event listening for? Add multiple listeners on separate lines.');

		$listens_to = explode("\n", $listens_for);

		$File_Builder = new File_Builder($event_name, $namespace, $extends_path);
		$File_Builder->addClassAttribute(self::EVENT, $listens_to);
		$File_Builder->addUseStatements([self::EVENT, self::EVENT_INTERFACE]);
		$File_Builder->addInterface(self::EVENT_INTERFACE);
		$File_Builder->addMethods([
			[
				'name' => 'execute',
				'return' => 'void',
				'return_name' => 'void',
				'params' => [
					[
						'name' => 'event_name',
						'type' => 'string',
						'type_short' => 'string',
						'comment' => 'The event name'
					],
					[
						'name' => 'args',
						'type' => 'array',
						'type_short' => 'array',
						'comment' => 'The args passed'
					],
				]
			]
		]);

		$event = $File_Builder->getClass();

		file_put_contents($event_path, $event);

		$this->Printer->success("event created at $event_path");

		return new Request_Response(true);
	}
}