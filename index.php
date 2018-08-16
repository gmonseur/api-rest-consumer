<?php
require_once('vendor/autoload.php');
require_once('config.php');
require_once('helpers/Debug.php');
require_once('helpers/Format.php');
require_once('class/ApiClient.php');


prof_flag("Start");

//////////////////////////////////////////////////////////////////////////////
// Api Init.
//////////////////////////////////////////////////////////////////////////////
prof_flag("Api Init");
$api = new ApiClient($config['login']);

prof_flag("End");
prof_print();