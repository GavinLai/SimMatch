<?php
/**
 * Mobile端请求入口
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
error_reporting(E_ALL);
ini_set('display_errors', 'on');

//~ require init.php
require (__DIR__.'/core/init.php');

$request  = new Request();
$response = new Response();

//$response->send('<center style="font-size:36px;">系统维护中，请稍后再访问</center>');

try {
  SimPHP::I(['modroot'=>'mobiles'])
  ->boot(RC_ALL ^ RC_MEMCACHE)
  ->dispatch($request,$response);
}
catch (SimPHPException $me) {
  $response->dump($me->getMessage());
}
catch (Exception $e) {
  $response->dump($e->getMessage());
}

 
/*----- END FILE: mobile.php -----*/