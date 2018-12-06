<?php

/**
 * 快速增删改查的脚手架
 */
class PadLib_Crud {
	public $options;
	public $entityName;
	public $entityConfig;
	public $gridFields = array();

	public function __construct($req, $res) {
		$entityName = $req->param('entity');
		
		// 鉴权
		PadDebug::debugAuth();

		$res->setDisplayParam('layout_name', '&' . PAD_RC_DIR . '/crud/_layout.php');
		$res->set('menuList', pad('mvc')->crudMenuList);
		
		if (!$entityName) {
			return;
		}
		
		$this->entityName = $entityName;
		$this->entityConfig = $GLOBALS['pad_core']->orm->getEntityConfig($this->entityName);
		
		$response = $GLOBALS['pad_core']->mvc->activeResponse;
		$response->set('entityConfig', $this->entityConfig);
		$response->set('pkField', $this->entityConfig->pkField);
		
		$belongtoArray = array();
		foreach ($this->entityConfig->relations as $key => $config) {
			if ($config['type'] == 'belongto') {
				$config['belong_key'] = $key;
				$belongtoArray[$config['link_field']] = $config;
			}
		}
		$response->set('belongtoArray', $belongtoArray);
		$response->set('_entity', $entityName);
	}
	
	public function setOptions ($options) {
		$this->options = $options;
	}

	public function httpAuth ($userName, $password) {
		$isPass = true;
		if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
			$isPass = false;
		} else if ($_SERVER['PHP_AUTH_USER'] != $userName || $_SERVER['PHP_AUTH_PW'] != $password) {
			$isPass = false;
		}
		
		if (!$isPass) {
			header('WWW-Authenticate: Basic realm="PadPHP CRUD Auth"');
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}
	}
	
	public function doPfs($request, $response) {
		$pfs = new PadLib_Pfs(array());
		$pfs->managerExecute($request, $response);
	}

	public function doIndex($request, $response) {
		if (!$this->entityName) {
			$response->template('&' . PAD_RC_DIR . '/crud/index_main.php');
			return;
		}
		
		$loader = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName)->page($request->param('page', 1), 20);
		
		if (isset($this->entityConfig->fields['is_delete'])) {
			$loader->query('where is_delete = 0');
		}
		
		$gridFields = $this->entityConfig->fieldsGroups[0];
		$response->set('gridFields', $gridFields);
		
		$response->set('datalist', $loader->getList());
		$response->set('datalistPageInfo', $loader->getPageInfo());
		$response->template('&' . PAD_RC_DIR . '/crud/index.php');
	}

	public function doNew($request, $response) {
		$response->set('ety', $GLOBALS['pad_core']->orm->entityNull);
		$response->template('&' . PAD_RC_DIR . '/crud/new.php');
	}

	public function doNewPost($request, $response) {
		$vars = $request->param('vars');
		$entity = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName)->newly();
		$entity->sets($vars);
		$entity->create();
		$entity->flush();
		$id = $entity->id;
		
		$response->message('新建成功', array(
			array('text' => '返回列表','url' => $request->getReferUrl()),
			array('text' => '返回新建项','url' => $GLOBALS['pad_core']->mvc->getUrl('Update', array(
				'entity' => $this->entityName,
				'id' => $id,
				'referUrl' => $request->getReferUrl(),
			)))
		));
		$response->displayParams['template_path'] = '&' . PAD_RC_DIR . '/message.phtml';
	}

	public function doUpdate($request, $response) {
		$id = $request->param('id');
		$entity = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName)->get($id);
		$response->set('ety', $entity);
		$response->template('&' . PAD_RC_DIR . '/crud/new.php');
	}

	public function doUpdatePost($request, $response) {
		$vars = $request->param('vars');
		$id = $request->param('id');
		
		$entity = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName)->get($id);
		$entity->sets($vars);
		
		$response->template('ety', $entity);
		$response->message('编辑成功', array(
			array('text' => '返回列表','url' => $request->getReferUrl()),
			array('text' => '返回编辑项','url' => $GLOBALS['pad_core']->mvc->getUrl('Update', array(
				'entity' => $this->entityName,
				'id' => $id,
				'referUrl' => $request->getReferUrl(),
			)))
		));
		$response->displayParams['template_path'] = '&' . PAD_RC_DIR . '/message.phtml';
	}

	public function doDeletePost($request, $response) {
		$id = $request->param('id');
		$entity = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName)->get($id);
		if (isset($this->entityConfig->fields['is_delete'])) {
			$entity->set('is_delete', 1);
		} else if (isset($this->entityConfig->fields['isDelete'])) {
			$entity->set('isDelete', 1);
		} else {
			$entity->delete();
		}
		
		$response->message('删除成功', 
				array(
					array(
						'text' => '返回列表',
						'url' => $GLOBALS['pad_core']->mvc->getUrl('Index', 'entity=' . $this->entityName)
					)
				));
		$response->displayParams['template_path'] = '&' . PAD_RC_DIR . '/message.phtml';
	}

	public function doDestroyPost($request, $response) {
		$id = $request->param('id');
		$entity = $GLOBALS['pad_core']->orm->getEntityModel($this->entityName)->get($id);
		$entity->delete();
		
		$response->message('彻底删除成功', 
				array(
					array(
						'text' => '返回列表',
						'url' => $GLOBALS['pad_core']->mvc->getUrl('Index', 'entity=' . $this->entityName)
					)
				));
		$response->displayParams['template_path'] = '&' . PAD_RC_DIR . '/message.phtml';
	}
	
	public function doAutoBuilderShow (PadMvcRequest $req, PadMvcResponse $res) {
		$filepath = $req->param('filepath');
		
		$res->setDisplayParam('layout_name', null);
		$res->template('&' . PAD_RC_DIR . '/crud/auto_builder_show.php');
		$content = $res->getDisplayContent();
		
		$content = 'document.write("' . str_replace('"', '\"', $content) . '");';
		$content = str_replace(array("\n","\r"), '', $content);
		$res->dtext($content);
	}
	
	public function doAutoBuildPost (PadMvcRequest $req, PadMvcResponse $res) {
		$filepath = $req->param('filepath');
		$command = $req->param('command');
		
		$content = null;
		$entityNameAction = null;
		$entityName = null;
		if ($command == 'blank') {
			$content = file_get_contents(PAD_RC_DIR . '/crud/builder_tpl_blank.php');
		} else if (strpos($command, 'index.') === 0) {
			$entityNameAction = 'index';
			$entityName = str_replace('index.', '', $command);
		} else if (strpos($command, 'item.') === 0) {
			$entityNameAction = 'item';
			$entityName = str_replace('item.', '', $command);
		}
		
		if ($entityName) {
			$entityConfig = $GLOBALS['pad_core']->orm->getEntityConfig($entityName);
			$fields = $entityConfig->fields;
			print_r($fields);
			exit;
		}
		
		if ($content !== null) {
			file_put_contents($filepath, $content);
		}
		
		$res->redirect('&'.$req->getReferUrl());
	}
}





