<?php

class PadBasePaddocBuilder {

	private $_tempFileList;

	public $ideTempDir;

	public $coreDir;

	public $entityDir;

	public $entityLoaderDir;

	public $mvcDir;

	private $_phpToolJson = array(
		'registrar' => array(),
		'providers' => array(),
	);

	public function __construct($ideTempDir) {
		$this->ideTempDir = $ideTempDir;

		if (false) {
			$this->_phpToolJson['registrar'][] = array(
				'provider' => 'entityModel',
				'language' => 'php',
				'signatures' => array(
					array('function' => 'ety', 'type' => 'type')
				),
			);

			$this->_phpToolJson['providers']['entityModel'] = array(
				'name' => 'entityModel',
				'items' => array(),
			);

			$this->_phpToolJson['registrar'][] = array(
				'provider' => 'entityModelLoopUp',
				'language' => 'php',
				'signatures' => array(
					array('function' => 'ety', 'index' => '0', 'array' => 'array_key')
				),
			);

			$this->_phpToolJson['providers']['entityModelLoopUp'] = array(
				'name' => 'entityModelLoopUp',
				'lookup_strings' => array(),
			);
		}

		$this->_phpToolJson['registrar'][] = array(
			'provider' => 'cptModel',
			'language' => 'php',
			'signatures' => array(
				array('function' => 'cpt', 'type' => 'type')
			),
		);

		$this->_phpToolJson['registrar'][] = array(
			'provider' => 'cptModelLookUp',
			'language' => 'php',
			'signatures' => array(
				array('function' => 'cpt', 'index' => 0, 'array' => 'array_key')
			),
		);

		$this->_phpToolJson['providers']['cptModelLookUp'] = array(
			'name' => 'cptModelLookUp',
			'lookup_strings' => array(),
		);

		$this->_phpToolJson['providers']['cptModel'] = array(
			'name' => 'cptModel',
			'items' => array(),
		);
	}

	public function buildOrmDocs($coreDir, $options = array()) {
		system('rm -rf ' . $this->ideTempDir . '/orm');
		if (!is_dir($this->ideTempDir . '/orm')) {
			mkdir($this->ideTempDir . '/orm');
		}

		$classPrefix = (isset($options['classPrefix']) ? $options['classPrefix'] : '');
		$this->coreDir = realpath($coreDir);
		$this->entityDir = realpath($this->coreDir . '/entity');
		$this->entityLoaderDir = realpath($this->coreDir . '/entity_loader');

		if (!is_dir($this->entityDir)) {
			return;
		}

		$line = array();

		$line[] = '<?php';
		$line[] = '';
		$line[] = 'class IDE_Entity_Container {';

		foreach ($this->listFiles($this->entityDir) as $file) {
			if (strpos($file, 'abstract') !== false) {
				continue;
			}

			$path = str_replace($this->entityDir, '', $file);
			$path = substr($path, 1);
			$path = substr($path, 0, -4);

			$entityName = $path;
			$className = PadBaseString::padStrtoupper($path);
			if ($classPrefix) {
				$classPrefixLower = trim(PadBaseString::padStrtolower($classPrefix), '_');

				pprint('build orm entity ' . $classPrefixLower . '/' . $entityName);
				$this->createEntityModel($classPrefixLower . '/' . $entityName, $classPrefix . $className);

				$cptName = trim($classPrefix, '_');
				$this->_phpToolJson['providers']['cpt' . $cptName . 'EntityModel']['items'][] = array(
					'lookup_string' => $entityName,
					'type' => 'IDE_Entity_' . $classPrefix . $className . '_Model',
				);
				$this->_phpToolJson['providers']['cpt' . $cptName . 'EntityModelLookUp']['lookup_strings'][] = $entityName;
			} else {
				pprint('build orm entity ' . $entityName);
				$this->createEntityModel($entityName, $className);

				$this->_phpToolJson['providers']['entityModel']['items'][] = array(
					'lookup_string' => $entityName,
					'type' => 'IDE_Entity_' . $className . '_Model',
				);
				$this->_phpToolJson['providers']['entityModelLoopUp']['lookup_strings'][] = $entityName;
			}

			$line[] = $this->getReturnFunction($className, 'IDE_Entity_' . $className . '_Model|IDE_Entity_' . $className . '_Loader');
		}
		$line[] = '}';

		//file_put_contents($this->ideTempDir . '/orm/All_Entity_Container.php', implode("\n", $line));
	}

	public function createEntityModel($entityName, $className) {
		if (!$GLOBALS['pad_core']->orm->entityDbSourceExists($entityName)) {
			return;
		}
		$etyConfig = $GLOBALS['pad_core']->orm->getEntityConfig($entityName);

		$line = array();
		$line[] = '<?php';
		$line[] = '';

		$line[] = 'class IDE_Entity_' . $className . '_Model {';
		$line[] = $this->getReturnFunction('get', 'IDE_Entity_' . $className . '|IDE_Entity_' . $className . '[]');
		$line[] = $this->getReturnFunction('getby', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('getby_field', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('getby_field1__field2', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('getby_field1__field2__field3', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('create', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('newly', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('loader', 'IDE_Entity_' . $className . '_Loader');
		$line[] = $this->getReturnFunction('getList', 'IDE_Entity_' . $className . '[]');
		$line[] = $this->getReturnFunction('query', 'IDE_Entity_' . $className . '_Loader');
		$line[] = $this->getReturnFunction('page', 'IDE_Entity_' . $className . '_Loader');
		$line[] = $this->getReturnFunction('fetcher', 'IDE_Entity_' . $className . '_DDTDataType');
		$line[] = $this->getReturnFunction('getListMap', 'IDE_Entity_' . $className . '[]');

		// 获得loader对象的方法
		if (class_exists('EntityLoader_' . $className)) {
			//print_r(get_class_methods('EntityLoader_' . $className));
		}

		$line[] = '}';
		$line[] = '';

		$line[] = 'class IDE_Entity_' . $className . '_Loader extends EntityLoader_' . $className . ' {';
		$line[] = $this->getReturnFunction('fields', 'IDE_Entity_' . $className . '_Loader');
		$line[] = $this->getReturnFunction('query', 'IDE_Entity_' . $className . '_Loader');
		$line[] = $this->getReturnFunction('page', 'IDE_Entity_' . $className . '_Loader');
		$line[] = $this->getReturnFunction('order', 'IDE_Entity_' . $className . '_Loader');
		$line[] = $this->getReturnFunction('getList', 'IDE_Entity_' . $className . '[]');
		$line[] = $this->getReturnFunction('getDataList', 'IDE_Entity_' . $className . '[]');
		$line[] = $this->getReturnFunction('get', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('getData', 'IDE_Entity_' . $className);
		$line[] = $this->getReturnFunction('fetcher', 'IDE_Entity_' . $className . '_DDTDataType');
		$line[] = '}';
		$line[] = '';

		$line[] = '/**';
		$line[] = ' *';

		if (isset($etyConfig->fields)) {
			foreach ($etyConfig->fields as $field) {
				$line[] = ' * @property string $' . $field;
			}
		}

		foreach (get_class_methods('Entity_' . $className) as $method) {
			if (strpos($method, '_get_') === 0) {
				$line[] = ' * @property string $' . str_replace('_get_', '', $method);
			}
		}

		if (isset($etyConfig->relations)) {
			foreach ($etyConfig->relations as $field => $relation) {
				if ($relation['type'] == 'belongto') {
					$line[] = ' * @property IDE_Entity_' . PadBaseString::padStrtoupper($relation['entity_name']) . ' $' . $field;
				}
			}
		}

		$line[] = ' */';
		$line[] = 'class IDE_Entity_' . $className . ' extends Entity_' . $className . ' {}';
		$line[] = '';

		$line[] = '/**';
		$line[] = ' *';
		if (isset($etyConfig->fields)) {
			foreach ($etyConfig->fields as $field) {
				$line[] = ' * @property IDE_DDT_DataType $' . $field;
			}
		}

		foreach (get_class_methods('Entity_' . $className) as $method) {
			if (strpos($method, '_get_') === 0) {
				$line[] = ' * @property IDE_DDT_DataType $' . str_replace('_get_', '', $method);
			}
		}

		if (isset($etyConfig->relations)) {
			foreach ($etyConfig->relations as $field => $relation) {
				if ($relation['type'] == 'belongto') {
					$line[] = ' * @property IDE_Entity_' . PadBaseString::padStrtoupper($relation['entity_name']) . '_DDTDataType $' . $field;
				}
			}
		}

		$line[] = ' */';
		$line[] = 'class IDE_Entity_' . $className . '_DDTDataType extends IDE_Entity_' . $className . ' {';
		$line[] = $this->getReturnFunction('fetcher', 'string');
		$line[] = '}';
		$line[] = '';

		file_put_contents($this->ideTempDir . '/orm/EntityModel_' . $className . '.php', implode("\n", $line));
	}

	public function buildMvcDocs($mvcDir, $mvcPrefix) {
		system('rm -rf ' . $this->ideTempDir . '/mvc');
		if (!is_dir($this->ideTempDir . '/mvc')) {
			mkdir($this->ideTempDir . '/mvc');
		}

		$this->mvcDir = realpath($mvcDir);
		$this->mvcControllerDir = realpath($this->mvcDir . '/controller');
		$this->mvcPrefix = $mvcPrefix . '_';

		$line[] = '<?php';
		$line[] = '';

		$line[] = '/**';
		foreach ($this->listFiles($this->mvcControllerDir) as $file) {
			if (strpos($file, 'abstract') !== false) {
				continue;
			}

			$path = str_replace($this->mvcControllerDir, '', $file);
			$path = substr($path, 1);
			$path = substr($path, 0, -4);
			$className = PadBaseString::padStrtoupper($path);
			$this->createController($className);

			$line[] = ' * @property IDE_' . $this->mvcPrefix . 'Controller_' . $className . ' $' . $className;
		}
		$line[] = ' */';
		$line[] = 'class IDE_' . $this->mvcPrefix . 'Mvc_Url_All {}';

		file_put_contents($this->ideTempDir . '/mvc/' . $this->mvcPrefix . 'All_Controller.php', implode("\n", $line));
	}

	public function buildCptDocs($cptDir) {
		system('rm -rf ' . $this->ideTempDir . '/cpt');
		if (!is_dir($this->ideTempDir . '/cpt')) {
			mkdir($this->ideTempDir . '/cpt');
		}

		$dirHanlder = opendir($cptDir);
		while (($file = readdir($dirHanlder)) !== false) {
			if (strpos($file, '.') === 0 || strpos($file, '_') === 0) {
				continue;
			}

			// cpt名称
			$cptName = PadBaseString::padStrtoupper(str_replace('.php', '', $file));

			// 文档目录
			$cptMainDir = realpath($cptDir . DS . $file);
			$cptDocMainDir = $this->ideTempDir . DS . 'cpt' . DS . $cptName;
			if (!is_dir($cptDocMainDir)) {
				mkdir($cptDocMainDir);
			}

			// model
			$modelDir = realpath($cptMainDir . DS . 'model');
			$modelFiles = $this->listFiles($modelDir);

			$line = array();
			$line[] = '<?php';
			$line[] = '';

			$line[] = 'class IDE_Cpt_' . $cptName . '_Model extends CptModel_' . $cptName . '_Default {';
			foreach ($modelFiles as $mfile) {
				if (strpos($mfile, 'default.php')) {
					continue;
				}
				$className = str_replace($modelDir . DS, '', $mfile);
				$classNameLower = str_replace('.php', '', $className);
				$className = PadBaseString::padStrtoupper($classNameLower);
				if (strpos($className, 'Webctl') === 0) {
					continue;
				}

				$line[] = $this->getReturnFunction($className, 'CptModel_' . $cptName . '_' . $className);
			}

			$this->_phpToolJson['providers']['cptModel']['items'][] = array(
				'lookup_string' => PadBaseString::padStrtolower($cptName),
				'type' => 'IDE_Cpt_' . $cptName . '_Model',
			);
			$this->_phpToolJson['providers']['cptModelLookUp']['lookup_strings'][] = PadBaseString::padStrtolower($cptName);

			$this->_phpToolJson['registrar'][] = array(
				'provider' => 'cpt' . $cptName . 'EntityModel',
				'language' => 'php',
				'signatures' => array(
					array('class' => 'IDE_Cpt_' . $cptName . '_Model', 'method' => 'ety', 'type' => 'type')
				),
			);

			$this->_phpToolJson['providers']['cpt' . $cptName . 'EntityModel'] = array(
				'name' => 'cpt' . $cptName . 'EntityModel',
				'items' => array(),
			);

			$this->_phpToolJson['registrar'][] = array(
				'provider' => 'cpt' . $cptName . 'EntityModelLookUp',
				'language' => 'php',
				'signatures' => array(
					array('class' => 'IDE_Cpt_' . $cptName . '_Model', 'method' => 'ety', 'index' => 0, 'array' => 'array_key')
				),
			);

			$this->_phpToolJson['providers']['cpt' . $cptName . 'EntityModelLookUp'] = array(
				'name' => 'cpt' . $cptName . 'EntityModelLookUp',
				'lookup_strings' => array(),
			);

			$line[] = $this->getReturnFunction('ety', 'mixed', array(
				'entityName' => 'string',
			));
			$line[] = '}';

			file_put_contents($cptDocMainDir . DS . '/Model.php', implode("\n", $line));

			// entity
			if (is_dir($cptMainDir . DS . 'entity')) {
				$loadParams = array(
					'classPrefix' => $cptName . '_',
				);

				PadAutoload::registerMulti('Entity_' . $cptName . '_', $cptMainDir . DIRECTORY_SEPARATOR . 'entity', $loadParams);
				PadAutoload::registerMulti('EntityLoader_' . $cptName . '_', $cptMainDir . DIRECTORY_SEPARATOR . 'entity_loader', $loadParams);

				$bakIdeTempDir = $this->ideTempDir;
				$this->ideTempDir = $cptDocMainDir;
				$res = $this->buildOrmDocs($cptMainDir, array(
					'classPrefix' => $cptName . '_',
				));
				$this->ideTempDir = $bakIdeTempDir;
			}
		}
		closedir($dirHanlder);
	}

	public function buildDRTJs() {
		$json = $this->_phpToolJson;
		$json['providers'] = array_values($json['providers']);
		file_put_contents($this->ideTempDir . DS . '.ide-toolbox.metadata.json', json_encode($json));

		$line[] = '<?php';
		$line[] = '';
		$line[] = 'class IDE_DDT_DataType extends PadLib_Dataer_DataType {';
		$line[] = 'public $TypeString;';
		$line[] = 'public $TypeNumber;';
		$line[] = 'public $TypeDate;';
		$line[] = '}';
		file_put_contents($this->ideTempDir . DS . 'IDE.php', implode("\n", $line));
	}

	public function createController($controllerName) {
		PadCore::autoload('Controller_', $this->mvcControllerDir);
		$classRef = new ReflectionClass('Controller_' . $controllerName);

		$line[] = '<?php';
		$line[] = '';
		$line[] = '/**';
		foreach ($classRef->getMethods() as $method) {
			$method instanceof ReflectionMethod;

			if (strpos($method->getName(), 'do') === 0) {
				$methonName = substr($method->getName(), 2);
				$line[] = ' * @method string ' . $methonName . '()';
			}
		}
		$line[] = ' */';
		$line[] = 'class IDE_' . $this->mvcPrefix . 'Controller_' . $controllerName . ' {}';
		file_put_contents($this->ideTempDir . '/mvc/' . $this->mvcPrefix . 'Contoller_' . $controllerName . '.php', implode("\n", $line));
	}

	public function createHelperDocs($helperName) {
	}

	/**
	 * @param $name
	 * @param $return
	 * @param array $params
	 * @return string
	 */
	private function getReturnFunction($name, $return, $params = array()) {
		$line[] = '';
		$line[] = "\t" . '/**';
		$paramVars = array();
		foreach ($params as $k => $v) {
			$line[] = "\t" . ' * @param ' . $v . ' $' . $k;
			$paramVars[] = '$' . $k;
		}
		$line[] = "\t" . ' * @return ' . $return;
		$line[] = "\t" . ' */';
		$line[] = "\t" . 'public function ' . $name . '(' . implode(', ', $paramVars) . '){}';
		return implode("\n", $line);
	}

	private function getFunctionComment($lines) {
		$return[] = "\t" . '/**';
		foreach ($lines as $line) {
			$return[] = "\t" . ' * ' . $title;
		}
		$return[] = "\t" . '**/';
		return implode("\n", $return);
	}

	/**
	 * 获得文件列表
	 *
	 * @param unknown $dir
	 * @param string $isFirst
	 */
	public function listFiles($dir, $isFirst = true) {
		if ($isFirst) {
			$this->_tempFileList = array();
		}

		$dirHanlder = opendir($dir);
		while (($file = readdir($dirHanlder)) !== false) {
			if (strpos($file, '.') === 0 || strpos($file, '_') === 0) {
				continue;
			}

			if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
				$this->listFiles($dir . DIRECTORY_SEPARATOR . $file, false);
			} elseif (strpos($file, '.php') == strlen($file) - 4) {
				$this->_tempFileList[] = realpath($dir . DIRECTORY_SEPARATOR . $file);
			}
		}
		closedir($dirHanlder);

		return $this->_tempFileList;
	}
}



