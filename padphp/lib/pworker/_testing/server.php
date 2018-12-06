<?php

// include (__DIR__.'/../../core/boot.php');

if (in_array('--worker', $ARGV)) {
	$worker = new PadLib_Pworker_WorkerProcess_Worker();
	$worker->work();
} else {
	$server = new PadLib_Pworker_Server(__FILE__, array(
		'processHandlers' => array(
			'php' => '/software/php/bin/php '.__FILE__.' --worker',
			'nodejs' => '/software/nodejs/bin/node '.__DIR__.'/worker/worker.js',
		),
		'maxProcess' => 6,
		'workerDefaultOptions' => array(
			'timeout' => 2 * 60 * 1000,
			'handlerName' => 'php',
		),
	));
	$server->process();
}

