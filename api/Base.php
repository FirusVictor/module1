<?php
class Base{
    public $result;

    public $db;
    public $url;
    public $headers;
    public $method;
    public function __construct()
    {
        $this->db = new mysqli('localhost','root','','module1');
        $this->url = explode("/",key($_GET));
        $this->headers = getallheaders();
        $this->method = $_SERVER["REQUEST_METHOD"];
    }
    public function Call(){
        echo json_encode($this->result);
    }
}