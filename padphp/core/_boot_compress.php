<?php

date_default_timezone_set('PRC');

PadAutoload::initialize();
PadCore::autoload('PadLib_', dirname(__FILE__).'/lib');
PadCore::autoload('PadScript_', dirname(__FILE__).'/script');
define('PAD_RC_DIR', dirname(__file__).'/_rc');
class pad extends PadApi {}








