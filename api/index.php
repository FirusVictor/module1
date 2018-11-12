<?php
$url = explode("/",key($_GET));
if(count($url)>0){
    if(file_exists($url[0].'.php')){
        require_once $url[0].'.php';

        $class = new $url[0];
        $class->Call();
    }
}