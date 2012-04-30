<?php

require_once 'Log.php';
require_once 'Services/IPPower.php';
require_once 'HTTP/Request2.php';
require_once 'HTTP/Request2/Observer/Log.php';


$options = array(
    'host' => '192.168.1.150'
);

$log = Log::factory('console', '', 'ippower', array(), Services_IPPower::LOG_DEBUG);
$http = new HTTP_Request2();
$observer = new HTTP_Request2_Observer_Log($log);
$http->attach($observer);

$ippower = new Services_IPPower($options);
$ippower->accept($log);
$ippower->accept($http);

$ippower->setPowerMulti(
    array(
        Services_IPPower::OUTLET_ONE   => Services_IPPower::STATE_ON,
        Services_IPPower::OUTLET_TWO   => Services_IPPower::STATE_OFF,
        Services_IPPower::OUTLET_THREE => Services_IPPower::STATE_OFF,
        Services_IPPower::OUTLET_FOUR  => Services_IPPower::STATE_OFF
    )
);
