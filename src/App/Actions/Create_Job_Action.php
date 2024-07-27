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

class Create_Job_Action extends Action_Base {
	
	const RESPONSE_NAMESPACE = "Gimli\Http\Response";

	protected array $job_config = [];

	/**
	 * Execute the action
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	public function execute(Request $Request): Request_Response {

		$config = load_config();

		$this->job_config = $config['jobs'] ?? [
			'default_save_path' => 'App/Jobs',
			'namespace' => 'App\\Jobs',
			'extends' => null
		];

		return $this->buildSingleAction($Request);
		
	}

	/**
	 * Format the path for the job
	 *
	 * @param Request $Request
	 * @return array
	 */
	protected function formatPath(Request $Request): array {
		$job_name = $Request->getArgument('job_name');
		$path = $Request->getArgument('save_path') ?? '';

		if (empty($path)) {
			$path = text(label: 'Where would you like to save the job?', default: $this->job_config['default_save_path']);
		}

		$path = $path[0] !== '/' ? '/' . $path : $path;
		$path = $path[strlen($path) - 1] !== '/' ? $path . '/' : $path;
		$job_path = ROOT . $path . $job_name . '.php';
		
		return [$job_name, $job_path];
	}

	/**
	 * Build a Job
	 *
	 * @param Request $Request
	 * @return Request_Response
	 */
	protected function buildSingleAction(Request $Request): Request_Response {
		[$job_name, $job_path] = $this->formatPath($Request);
		
		$namespace = $Request->getArgument('namespace') ?? '';

		if (empty($namespace)) {
			$namespace = text(label: 'What is the namespace for the job?', default: $this->job_config['namespace']);
		}

		if (file_exists($job_path)) {
			$this->Printer->error('A job with that name already exists.');
			exit(1);
		}

		$extends = confirm('Would you like to extend a base job or class?');
		$extends_path = '';
		if ($extends) {
			$extends_path = text(label: 'What is the namespace of the class you would like to extend?', placeholder: $this->job_config['extends']);
		}

		$File_Builder = new File_Builder($job_name, $namespace, $extends_path);
		$File_Builder->addUseStatements([self::RESPONSE_NAMESPACE]);
		$File_Builder->addMethods([
			[
				'name' => '__invoke',
				'return' => self::RESPONSE_NAMESPACE,
				'return_name' => 'Response',
				'params' => [
					[
						'name' => 'Response',
						'type' => self::RESPONSE_NAMESPACE,
						'type_short' => 'Response',
						'comment' => 'The Response object'
					],
					[
						'name' => 'subcommand',
						'type' => 'string',
						'type_short' => 'string',
						'comment' => 'Subcommand'
					],
					[
						'name' => 'options',
						'type' => 'array',
						'type_short' => 'array',
						'comment' => 'Array of options and their values'
					],
					[
						'name' => 'flags',
						'type' => 'array',
						'type_short' => 'array',
						'comment' => 'Array of passed flags'
					]
				]
			]
		]);

		$job = $File_Builder->getClass();

		file_put_contents($job_path, $job);

		$this->Printer->success("Job created at $job_path");

		return new Request_Response(true);
	}
}