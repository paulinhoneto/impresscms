<?php
/**
 * ICMS Services manager class definition
 *
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license    LICENSE.txt
 * @package    ImpressCMS/core
 * @since    1.3
 *
 * @internal    This class should normally be "icms_Kernel", marcan and I agreed on calling it "icms"
 * @internal    for convenience, as we are not targetting php 5.3+ yet
 */

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\CompletePackage;
use ImpressCMS\Core\Autoloader;
use ImpressCMS\Core\DataFilter;
use ImpressCMS\Core\Extensions\ComposerDefinitions\ComposerDefinitionInterface;
use ImpressCMS\Core\Extensions\ComposerDefinitions\ProvidersComposerDefinition;
use ImpressCMS\Core\Extensions\ComposerDefinitions\ServicesComposerDefinition;
use ImpressCMS\Core\Extensions\Preload\EventsPreloader;
use ImpressCMS\Core\Models\User;
use League\Container\Container;
use League\Container\Definition\DefinitionInterface;

/**
 * ICMS Kernel / Services manager
 *
 * The icms services manager handles the core bootstrap process, and paths/urls generation.
 * Global services are made available as static properties of this class.
 *
 * @package    ImpressCMS/core
 * @since    1.3
 */
final class icms extends Container {

	/**
	 * ImpressCMS paths locations
	 *
	 * @var array
	 */
	public static $paths = array(
		'www' => array(), 'modules' => array(), 'themes' => array(),
	);

	/** @var array */
	public static $urls = false;
	/**
	 * Some vars for compatibility
	 *
	 * @deprecated 2.0 Use get method for getting any of these
	 */
	public static $db, $xoopsDB, $logger, $preload, $config, $security, $session, $module;
	/**
	 * Current logged in user
	 *
	 * @var User|null
	 */
	public static $user;

	/**
	 * Creates an object instance from an object definition.
	 * The factory parameter can be:
	 * - A fully qualified class name starting with '\': \MyClass or on PHP 5.3+ \ns\sub\MyClass
	 * - A valid PHP callback
	 *
	 * @param mixed $factory
	 * @param array $args Factory/Constructor arguments
	 * @return object
	 * @throws ReflectionException
	 */
	static public function create($factory, $args = array()) {
		if (is_string($factory) && substr($factory, 0, 1) == '\\') {
// Class name
			$class = substr($factory, 1);
			if (!isset($args)) {
				$instance = new $class();
			} else {
				$reflection = new ReflectionClass($class);
				$instance = $reflection->newInstanceArgs($args);
			}
		} else {
			$instance = call_user_func_array($factory, $args);
		}
		return $instance;
	}

	/**
	 * Convert a ImpressCMS path to an URL
	 * @param    string $url
	 * @return    string
	 */
	static public function url($url)
	{
		return (false !== strpos($url, '://') ? $url : icms::getInstance()->path($url, true));
	}

	/**
	 * Convert a ImpressCMS path to a physical one
	 * @param    string $url URL string to convert to a physical path
	 * @param    boolean $virtual
	 * @return    string
	 */
	public function path($url, $virtual = false) {
		$path = '';
		@list($root, $path) = explode('/', $url, 2);
		if (!isset(self::$paths[$root])) {
			list($root, $path) = array('www', $url);
		}
		if (!$virtual) {
			// Returns a physical path
			return self::$paths[$root][0] . '/' . $path;
		}
		return !isset(self::$paths[$root][1]) ? '' : (self::$paths[$root][1] . '/' . $path);
	}

	/**
	 * Get instance
	 *
	 * @return icms
	 */
	public static function &getInstance(): icms
	{
		static $instance = null;
		if ($instance === null) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Build an URL with the specified request params
	 * @param    string $url
	 * @param    array $params
	 * @return    string
	 */
	static public function buildUrl($url, $params = array()) {
		if ($url == '.') {
			$url = $_SERVER['REQUEST_URI'];
		}
		$split = explode('?', $url);
		if (count($split) > 1) {
			list($url, $query) = $split;
			parse_str($query, $query);
			$params = array_merge($query, $params);
		}
		if (!empty($params)) {
			foreach ($params as $k => $v) {
				$params[$k] = $k . '=' . rawurlencode($v);
			}
			$url .= '?' . implode('&', $params);
		}
		return $url;
	}

	/**
	 * Gets the handler for a class
	 *
	 * @param string $name The name of the handler to get
	 * @param bool $optional Is the handler optional?
	 * @return        object        $inst        The instance of the object that was created
	 */
	public static function &handler($name, $optional = false)
	{
		$instance = static::getInstance();
		$real_name = $name . '_handler';
		if (!$instance->has($real_name)) {
			$class = $name . 'Handler';
			if (!class_exists($class)) {
				$class = $name . '_Handler';
				if (!class_exists($class)) {
					// Try old style handler loading (should be removed later, in favor of the
					// lookup table present in xoops_gethandler)
					$lower = strtolower(trim($name));
					if (file_exists($hnd_file = ICMS_ROOT_PATH . '/class/' . $lower . '.php')) {
						require_once $hnd_file;
					}
					if (!class_exists($class = 'Xoops' . ucfirst($lower) . 'Handler', false)) {
						if (!class_exists($class = 'Icms' . ucfirst($lower) . 'Handler', false)) {
							// Not found at all
							$class = false;
						}
					}
				}
			}
			$db = $instance->get('xoopsDB');
			$instance->add($real_name, $class ? (new $class($db)) : false);
		}
		$handler = $instance->get($real_name);
		if (!$handler && !$optional) {
			//trigger_error(sprintf("Handler <b>%s</b> does not exist", $name), E_USER_ERROR);
			throw new RuntimeException(sprintf('Handler <b>%s</b> does not exist', $name));
		}
		return $handler;
	}

	/**
	 * Initialize ImpressCMS before bootstrap
	 *
	 * @return $this
	 */
	public function setup()
	{
		self::$paths['www'] = array(ICMS_ROOT_PATH, ICMS_URL);
		self::$paths['modules'] = array(ICMS_ROOT_PATH . '/modules', ICMS_URL . '/modules');
		self::$paths['themes'] = array(ICMS_THEME_PATH, ICMS_THEME_URL);
		// Initialize the autoloader
		require_once dirname(__DIR__) . '/core/Autoloader.php';
		Autoloader::setup();
		$this->buildRelevantUrls();

		return $this;
	}

	/**
	 * Build URLs for global use throughout the application
	 * @return    array
	 */
	protected function buildRelevantUrls() {
		if (isset($_SERVER['HTTP_HOST']) && !self::$urls) {
			$http = strpos(ICMS_URL, "https://") === false
				?"http://"
				: "https://";

			/* $_SERVER variables MUST be sanitized! They don't necessarily come from the server */
			$filters = array(
				'SCRIPT_NAME' => 'str',
				'HTTP_HOST' => 'str',
				'QUERY_STRING' => 'str',
				'HTTP_REFERER' => 'url',
			);

			$clean_SERVER = DataFilter::checkVarArray($_SERVER, $filters, false);

			$phpself = $clean_SERVER['SCRIPT_NAME'];
			$httphost = $clean_SERVER['HTTP_HOST'];
			$querystring = $clean_SERVER['QUERY_STRING'];
			if ($querystring != '') {
				$querystring = '?' . $querystring;
			}
			$currenturl = $http . $httphost . $phpself . $querystring;
			self::$urls = array();
			self::$urls['http'] = $http;
			self::$urls['httphost'] = $httphost;
			self::$urls['phpself'] = $phpself;
			self::$urls['querystring'] = $querystring;
			self::$urls['full_phpself'] = $http . $httphost . $phpself;
			self::$urls['full'] = $currenturl;

			$previouspage = '';
			if (array_key_exists('HTTP_REFERER', $_SERVER) && isset($_SERVER['HTTP_REFERER'])) {
				self::$urls['previouspage'] = $clean_SERVER['HTTP_REFERER'];
			}
			//self::$urls['isHomePage'] = (ICMS_URL . "/index.php") == ($http . $httphost . $phpself);
		}
		return self::$urls;
	}

	/**
	 * Get extras part from composer
	 *
	 * @param string $composerJsonPath Composer.json path
	 *
	 * @return array
	 */
	private function getComposerExtras(string $composerJsonPath): array
	{
		static $extras = null;

		if ($extras === null) {
			chdir($composerJsonPath);
			putenv('COMPOSER_HOME=' . ICMS_STORAGE_PATH . '/composer');
			$composer = Factory::create(
				new NullIO()
			);
			$extras = $composer->getPackage()->getExtra();
			/**
			 * @var CompletePackage $package
			 */
			foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
				if (!in_array($package->getType(), ['impresscms-module'])) {
					continue;
				}
				/** @noinspection SlowArrayOperationsInLoopInspection */
				$extras = array_merge_recursive($extras, $package->getExtra());
			}

			chdir(__DIR__);
		}

		return $extras;
	}

	/**
	 * Loads composer definitions
	 *
	 * @param ComposerDefinitionInterface $composerDefinition Composer definition class
	 */
	protected function loadComposerDefinition(ComposerDefinitionInterface $composerDefinition)
	{
		$composerJsonPath = dirname(__DIR__);

		if ($composerDefinition->needsUpdate($composerJsonPath)) {
			$composerDefinition->updateCache(
				$this->getComposerExtras($composerJsonPath)
			);
		}
		$composerDefinition->load($this);
	}

	/**
	 * Launch bootstrap and instanciate global services
	 *
	 * @param bool $registerCommonServices Do we need to register common services?
	 *
	 * @return $this
	 */
	public function boot(bool $registerCommonServices = true)
	{
		$this->loadComposerDefinition(
			new ProvidersComposerDefinition()
		);
		$this->loadComposerDefinition(
			new ServicesComposerDefinition()
		);

		// register links for compatibility
		if ($registerCommonServices) {
			$this->registerCommonServiceVariables();
		}

		//Cant do this here until common.php 100% refactored
		//self::$preload->triggerEvent('finishCoreBoot');

		return $this;
	}

	/**
	 * Registers common services variables
	 */
	public function registerCommonServiceVariables(): void
	{
		self::$db = $this->get('db');
		self::$xoopsDB = $this->get('xoopsDB');
		self::$config = $this->get('config');
		self::$logger = $this->get('logger');
		self::$preload = $this->get(EventsPreloader::class);
		self::$security = $this->get('security');
	}

	/**
	 * Gets service definition
	 *
	 * @param string $serviceName Service name to get
	 *
	 * @return DefinitionInterface|null
	 * @throws Exception
	 */
	public function getServiceDefinition($serviceName): ?DefinitionInterface
	{
		if (!$this->definitions->has($serviceName)) {
			/**
			 * @var DefinitionInterface $definition
			 */
			foreach ($this->definitions->getIterator() as $definition) {
				if (($definition->getConcrete() === $serviceName) || ($definition->getConcrete() === '\\' . $serviceName)) {
					return $definition;
				}
			}
			return null;
		}
		return $this->definitions->getDefinition($serviceName);
	}
}
