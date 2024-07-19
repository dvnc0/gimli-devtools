<?php
declare(strict_types=1);

namespace GimliDev\Builders;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;

class Controller_Builder {

	protected $Controller_Name;
	protected $Namespace;
	protected $Class_File;
	protected $Class_Shell;

	public function __construct(string $Controller_Name, string $Namespace, string $Extends = '')
	{
		$this->Controller_Name = $Controller_Name;
		$this->Namespace = $Namespace;
		$this->Class_File = new PhpNamespace($this->Namespace);
		// $this->Class_File->addClass($this->Controller_Name);
		$this->Class_Shell = new ClassType($this->Controller_Name);
		if ($Extends) {
			$this->Class_Shell->setExtends($Extends);
		}
	}

	public function addUseStatements(array $use_statements): self
	{
		foreach ($use_statements as $use_statement) {
			$this->Class_File->addUse($use_statement);
		}

		return $this;
	}

	/**
	 * Add methods to the controller
	 *
	 * @param array $methods
	 * @return void
	 */
	public function addMethods(array $methods) {
		foreach ($methods as $method_data) {
			$method = $method_data['name'];
			$method_return = '';
			$controller_method = $this->Class_Shell->addMethod($method);

			if (isset($method_data['return'])) {
				$controller_method->setReturnType($method_data['return']);
				$method_return = "@return {$method_data['return_name']}";
			}
			
			$param_comments = '';

			foreach ($method_data['params'] as $param) {
				if ($method === '__construct') {
					$controller_method->addPromotedParameter($param['name'])->setType($param['type']);
				} else {
					$controller_method->addParameter($param['name'])->setType($param['type']);
				}
				$param_comments .= "@param {$param['type_short']} \${$param['name']} {$param['comment']}\n";
			}

			$method_comment = $method_data['comment'] ?? $method;

			$controller_comment =<<<COMMENT
			$method_comment

			$param_comments
			$method_return
			
			COMMENT;

			$controller_method->setComment($controller_comment);
			if ($method === '__construct') {
				continue;
			}
			$controller_method->addBody('return $Response->setResponse("Hello World!");');
		}
	
	}

	public function getClass(): string {
		$p = new class extends Printer {
			public int $wrapLength = 130;
			public bool $bracesOnNextLine = false;
		};
		$this->Class_File->add($this->Class_Shell);
		$file = new PhpFile;
		$file->addComment('Made with Gimli Devtools');
		$file->setStrictTypes();
		$file->addNamespace($this->Class_File);
		return $p->printFile($file);
	}
}

// $controller = new Controller_Builder('Some_Class', 'Controllers', 'Shared\Controller_Helper');
// $controller->addUseStatements(['Shared\Controller_Helper', 'Shared\Response', 'Shared\Request']);
// $controller->addMethods(['index', 'create', 'update', 'delete']);
// echo $controller->getClass();
// file_put_contents('Demo.php', $controller->getClass());
