<?php
/**
 * 
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
phpinfo();exit;
//~ require init.php
require (__DIR__.'/core/init.php');

$pass = 'huxl112233';
$salt = 'fj2yaq';
$pass_enc = gen_salt_password($pass, $salt);
echo $pass_enc;
 
/*----- END FILE: t.php -----*/