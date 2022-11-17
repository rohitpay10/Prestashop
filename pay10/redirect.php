<?php
/**
 * PAY10 
 */

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/pay10.php');
include(dirname(__FILE__).'/backward_compatibility/backward.php');

if (!Context::getContext()->customer)
    Tools::redirect('index.php?controller=authentication&back=order.php');

$pay10 = new PAY10();

$pay10->payment();


