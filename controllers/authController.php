<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
ini_set('upload_max_filesize', '25M');
ini_set('post_max_size', '25M');
use core\route;

function GID()
{
  $data = random_bytes(32);

  $data[12] = chr(ord($data[12]) & 0x0f | 0x40);
  $data[16] = chr(ord($data[16]) & 0x3f | 0x80);
  $symbols = '!@#$%^&*(;_-,.';   
  $symbol1 = $symbols[rand(0, strlen($symbols) - 1)];
  return vsprintf('_GooberCrypt%-%s%s-%s%s-%s%s-%s%s-%s%s-%s%s-%s%s-v1', str_split(bin2hex($data), 4));
}

route::get("/GIDGen", function () {
    return GID();
});
route::get("/UUIDGen", function () {
    return uuidv4();
});