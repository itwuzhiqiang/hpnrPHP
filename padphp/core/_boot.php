<?php

$dirname = dirname(__file__);
include ($dirname . '/pad.php');
include ($dirname . '/function.php');

date_default_timezone_set('PRC');

include (dirname(__file__) . DIRECTORY_SEPARATOR . '/autoload.php');
PadAutoload::initialize();
define('PAD_RC_DIR', dirname(__file__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '_rc');
define('PAD_RES_DIR', dirname(__file__).'/../res');

$classList = array(
	'PadBaseString' => $dirname . '/base/string.php',
	'PadBaseDes' => $dirname . '/base/des.php',
	'PadBaseUrl' => $dirname . '/base/url.php',
	'PadBasePassport' => $dirname . '/base/passport.php',
	'PadBaseArray' => $dirname . '/base/array.php',
	'PadBaseFactory' => $dirname . '/base/factory.php',
	'PadBaseFile' => $dirname . '/base/file.php',
	'PadBasePtask' => $dirname . '/base/ptask.php',
	'PadBasePaddocBuilder' => $dirname . '/base/paddoc_builder.php',
	'PadBaseGearman' => $dirname . '/base/gearman.php',
	'PadBaseGearmanJob' => $dirname . '/base/gearman.php',
	'PadBaseKeepProcess' => $dirname . '/base/keep_process.php',
	'PadBaseScaffoldCrud' => $dirname . '/base/scaffold_crud.php',
	'PadBaseProcessContainer' => $dirname . '/base/process_container.php',
	'PadBaseUnitTest' => $dirname . '/base/unit_test.php',
	'PadBaseRedis' => $dirname . '/base/redis.php',
	'PadBaseShell' => $dirname . '/base/shell.php',
	'PadBaseVbrowser' => $dirname . '/base/vbrowser.php',
	'PadBaseValidation' => $dirname . '/base/validation.php',

	'PadReport' => $dirname . '/report.php',
	'PadBaseReport' => $dirname . '/base/report.php',
	'PadBaseReportData' => $dirname . '/base/report.php',
	'PadBaseReportAbstract' => $dirname . '/base/report.php',
	'PadBaseReportMixAbstract' => $dirname . '/base/report.php',

	'PadBaseCrontab' => $dirname . '/base/crontab.php',
	'PadBaseCrontabAbstract' => $dirname . '/base/crontab.php',

	'PadCache' => $dirname . '/cache.php',
	'PadDatabase' => $dirname . '/database.php',
	'PadDatabasePerform' => $dirname . '/database/perform.php',
	'PadCachePerform' => $dirname . '/cache/perform.php',
	'PadCpt' => $dirname . '/cpt.php',
	'PadRest' => $dirname . '/rest.php',
	'PadOrm' => $dirname . '/orm.php',
	'PadDebug' => $dirname . '/debug.php',
	'PadOrmModel' => $dirname . '/orm/model.php',
	'PadOrmEntity' => $dirname . '/orm/entity.php',
	'PadOrmEntityConfig' => $dirname . '/orm/entity_config.php',
	'PadOrmEntityNull' => $dirname . '/orm/entity.php',
	'PadOrmEntitySimple' => $dirname . '/orm/entity_simple.php',
	'PadOrmLoader' => $dirname . '/orm/loader.php',
	'PadOrmLoaderSimple' => $dirname . '/orm/loader_simple.php',
	'PadOrmLoaderFree' => $dirname . '/orm/loader_free.php',
	'PadOrmFlush' => $dirname . '/orm/flush.php',
	'PadMvc' => $dirname . '/mvc.php',
	'PadMvcRouter' => $dirname . '/mvc/router.php',
	'PadMvcRequest' => $dirname . '/mvc/request.php',
	'PadMvcResponse' => $dirname . '/mvc/response.php',
	'PadMvcResponseLayout' => $dirname . '/mvc/response/layout.php',
	'PadMvcHelperForm' => $dirname . '/mvc/helper/form.php',
	'PadMvcHelperPager' => $dirname . '/mvc/helper/pager.php',
	'PadMvcHelperError' => $dirname . '/mvc/helper/error.php',
	'PadMvcHelper' => $dirname . '/mvc/helper.php',
	'PadException' => $dirname . '/exception.php',
	'PadErrorException' => $dirname . '/exception.php',
	'PadBizException' => $dirname . '/exception.php',
	'PadMvcSysController_System' => $dirname . '/mvc/sys_controller/system.php',
	'PadModel' => $dirname . '/model.php',
	'PadWorkflow' => $dirname . '/workflow.php',
	'PadMixin_WorkflowHost' => $dirname . '/mixin/workflow_host.php',
	'PadLib_' => $dirname . '/../lib',
	'PadScript_' => $dirname . '/../script',
	'PadDaemon_' => $dirname . '/../daemon',

	'PadApi' => $dirname . '/api.php',
	'PadApiPadinx' => $dirname . '/api/padinx.php',
);

foreach ($classList as $key => $value) {
	PadCore::autoload($key, $value);
}

class pad extends PadApi {}




