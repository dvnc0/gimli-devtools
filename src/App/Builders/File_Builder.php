<?php
declare(strict_types=1);

namespace GimliDev\Builders;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;

class File_Builder {

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

	/**
	 * Add class attributes
	 *
	 * @param string $name
	 * @param array $args
	 * @return self
	 */
	public function addClassAttribute(string $name, array $args): self {
		foreach ($args as $arg) {
			$this->Class_Shell->addAttribute($name, [$arg]);
		}
		
		return $this;
	}

	/**
	 * Add use statements to the class
	 *
	 * @param array $use_statements
	 * @return self
	 */
	public function addUseStatements(array $use_statements): self {
		foreach ($use_statements as $use_statement) {
			$this->Class_File->addUse($use_statement);
		}

		return $this;
	}

	/**
	 * Add an interface to the class
	 *
	 * @param string $interface
	 * @return self
	 */
	public function addInterface(string $interface): self {
		$this->Class_Shell->addImplement($interface);
		return $this;
	}

	/**
	 * Add properties to the class
	 *
	 * @param array $props
	 * @return self
	 */
	public function addClassProperties(array $props): self {
		foreach ($props as $prop) {
			$comment = "\n" . $prop['comment'] ?: '';
			$comment_format = "@var {$prop['type']} \${$prop['name']} {$comment}";
			$p = $this->Class_Shell->addProperty($prop['name'])->setType($prop['type'])->addComment($comment_format);

			if (isset($prop['value'])) {
				$p->setValue($prop['value']);
			}

			if (isset($prop['protected']) && $prop['protected'] === true) {
				$p->setVisibility('protected');
			}
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

				if(array_key_exists('default', $param)) {
					$controller_method->getParameter($param['name'])->setDefaultValue($param['default']);
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

			if (!empty($method_data['body'])) {
				$controller_method->addBody($method_data['body']);
				continue;
			}

			if ($method_data['return'] === 'void') {
				$controller_method->addBody('return;');
			} else {
				$controller_method->addBody('return $Response;');
			}
		}
	
	}

	/**
	 * Get the class as a string
	 *
	 * @return string
	 */
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