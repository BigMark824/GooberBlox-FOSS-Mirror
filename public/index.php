<?php
ini_set('upload_max_filesize', '25M');
ini_set('post_max_size', '25M');
//ini_set("display_errors", 1);
//ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require (dirname(__DIR__) . '/core/bootstrap.php');
require (dirname(__DIR__) . '/goobercli');
@require (__FWDIR__ . '/controllers/rbxAPIs.php');
@require (__FWDIR__ . '/controllers/frontEnd.php');
@require (__FWDIR__ . '/controllers/soapController.php');
@require (__FWDIR__ . '/controllers/donationController.php');
@require (__FWDIR__ . '/controllers/arbiterController.php');
@require (__FWDIR__ . '/controllers/economyController.php');
@require (__FWDIR__ . '/controllers/authController.php');
@require (__FWDIR__ . '/controllers/renderController.php');
//@require(__FWDIR__.'/controllers/miscAPIs.php');
use core\route;
use core\modules\twig;

route::$r404 = function () {
    return twig::view("gooberblox/responses/404.twig", ["url" => __URL__]);
};
route::$r403 = function () {
    return twig::view("gooberblox/responses/403.twig", ["url" => __URL__]);
};
route::$r500 = function () {
    return twig::view("gooberblox/responses/500.twig", ["url" => __URL__]);
};
