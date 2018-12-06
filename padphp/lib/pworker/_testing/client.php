<?php

$client = new PadLib_Pworker_Client(array(
	'redisHost' => '127.0.0.1',
	'redisPort' => 6379,
));

// 单行任务
$res = $client->runJob(function($jobInfo, $a) {
	foreach (array(11,22,33) as $line) {
		echo ($line);
	}
	return 'hello';
}, array('a' => $a));
var_dump($res);

// 串行多任务
$serial = $client->newSerial();
$serial->addJob('t0', array(), function ($job) {
	echo 't0 - '.time();
	return 't0-'.time();
});
$serial->addJob('t1', array('t0'), function ($job, $p0) {
	echo 't1 - '.time();
	return $p0.'t1-'.time();
});
$serial->addJob('t2', array('t0', 't1'), function ($job, $p0, $p1) {
	echo 't2 - '.time();
	return $p0.$p1.'t2-'.time();
});
//$res = $client->execBackground($serial);
$res = $client->execBackground($serial);
print_r($res);
