<?php
require(dirname(__DIR__)."/vendor/autoload.php");
include(dirname(__DIR__)."/core/modules/extras.php");
include(dirname(__DIR__)."/core/modules/twig.php");
@include(dirname(__DIR__)."/core/modules/error.php");
use core\conf;

spl_autoload_register(function ($class_name) {
    include dirname(__DIR__).'/'.str_replace('\\', '/', $class_name).'.php';
});

define("__START_EXECTIME__", microtime(true)); 
//  dirname(__dir__) is quite stupid, lets change that.
define('__FWDIR__', dirname(__DIR__));
// i know, $_SERVER['DOCUMENT_ROOT'] exist but uh no i wont use that :), ok time for other defines!11
define('__DB__', __FWDIR__."/database");

$subdomainArray = explode('.', $_SERVER['HTTP_HOST']);
$subdomain = $subdomainArray[0];
define('__SUBDOMAIN__', $subdomain);

@define('__REFERER__', $_SERVER['HTTP_REFERER'] ?? NULL);
define('__DOMAIN__', $_SERVER['HTTP_HOST']);
define('__UA__', $_SERVER['HTTP_USER_AGENT']);
define('__IP__', (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER['REMOTE_ADDR']));
define('__SERVER_IP__', $_SERVER['SERVER_ADDR']);
define('nil', null);
define('nan', null);
define('__TRANSDIR__', __FWDIR__.'/translations');
define('__URL__', $_SERVER['REQUEST_URI']);
define('__METHOD__', $_SERVER['REQUEST_METHOD']);
@define('__SESSION__', $_SESSION ?? 0);
define('__COOKIE__', $_COOKIE);
define('__baseurl__', "goober.biz");
define('__webhook__', 'https://discord.com/api/webhooks/1196476775098753154/Srl5uHhuxE2t16JSxvPg4_rDZKExRQV2vLzukoZq7-1dqNyt36yN1IKnwkLPPSzLyRvR');
define('__URL_NOQUERY__', explode('?', __URL__)[0]);
define('__PAGE__', str_replace(["_", ".php", "/"], [" ", "", " "], explode('?', ltrim((($_SERVER['REQUEST_URI'] == "/") ? "index" : $_SERVER['REQUEST_URI']), "/") ?? "")[0]));
@date_default_timezone_set(conf::get()['fw']['locale']);

if(conf::get()['fw']['enableqKitHeaders']) {
header(conf::get()['fw']['name'].": ".conf::get()['fw']['version']);
}

if(conf::get()['fw']['debug']) {
    ini_set('display_errors', 1);
    error_reporting(1);
    @opcache_invalidate(__FILE__, true);
}