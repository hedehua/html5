<?php

//执行方法：/usr/bin/php -q websocket.php

error_reporting(E_ALL);
set_time_limit(0);
//打开/关闭隐式(绝对)刷送
ob_implicit_flush();
date_default_timezone_set("Asia/shanghai");

include 'functions.php';
include 'User.class.php';
include 'Socket.class.php';
include 'Server.class.php';

$sock = new Server();
$sock->run("10.1.49.236",30001,4);