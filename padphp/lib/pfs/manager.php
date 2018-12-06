<?php

class PadLib_Pfs_Manager {
	
	public function __construct($pfs) {
		
	}
	
	public function execute ($request, $response) {
		$response->template('&' . PAD_RC_DIR . '/crud/pfs_index.php');
		
		$action = $request->param('action');
		if ($action == 'newFilePost') {
			$fileRaw = $request->fileRaw('file');
			if ($fileRaw) {
				$GLOBALS['pad_core']->orm->getEntityModel('filedata')->createByFileRaw($fileRaw);
			}
			$response->redirect('Pfs');
			return;
		}
		
		$loader = $GLOBALS['pad_core']->orm->getEntityModel('filedata')->loader();
		$loader->page($request->param('page', 1), 20)
			->query('order by createTime desc');
		
		$response->set('datalist', $loader->getList());
		$response->set('datalistPageInfo', $loader->getPageInfo());
	}
}

