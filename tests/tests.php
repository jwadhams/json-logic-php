<?php 

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use JWadhams\JsonLogic;

var_dump(JsonLogic::apply(['==' => [1,"1"]]));
var_dump(JsonLogic::apply(['===' => [1,"1"]]));
