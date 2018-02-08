<?php
namespace Lchat;
use Lchat\Server\SwooleServer;

class autoload {

	private $directory;
	private $prefix;
	private $prefixLength;

	/**
	 * @param string $baseDirectory Base directory where the source files are located.
	 */
	public function __construct($baseDirectory = __DIR__) {
		$this->directory = $baseDirectory;
		$this->prefix = __NAMESPACE__ . '\\';
		$this->prefixLength = strlen($this->prefix);
		spl_autoload_register(array('\\Lchat\\autoload', 'autoload'), true, false);
	}

	/**
	 * Loads a class from a file using its fully qualified name.
	 *
	 * @param string $className Fully qualified name of a class.
	 */
	public function autoload($className) {
		$filepath = $this->directory . DIRECTORY_SEPARATOR;
		if (strpos($className, '\\') === false && substr($className, -6) === 'Server') {
			$filepath .= 'Server' . DIRECTORY_SEPARATOR . $className;
		} else {
			if (strpos($className, $this->prefix) === 0) {
				$parts = explode('\\', substr($className, $this->prefixLength));
			} else {
				$parts = explode('\\', $className);
			}
			$filepath .= implode(DIRECTORY_SEPARATOR, $parts);
		}
		$filepath .= '.php';
		if (is_file($filepath)) {
			require $filepath;
		}
	}

	public function start() {
		new SwooleServer();
	}
}