<?php
require_once "Base.php";
class auth extends Base{
    public function __construct()
    {
        parent::__construct();
        if(count($this->url) === 1 && $this->method =="POST"){
            $login = $_POST["login"];
            $pass = $_POST["password"];
            $result = $this->db->query("SELECT `id` FROM `users` WHERE `login` = '$login' AND `pass` = '$pass'");
            if($result->num_rows>0){
                $token = base64_encode($login.":".$pass);
                $this->db->query("INSERT INTO `tokens` (`token`) VALUES ('$token')");
                $this->result = array(
                    ("status code") => 200,
                    ("status text") => "Successful authorization",
                    ("body") => array(
                        ("status") => true,
                        ("token") => $token
                    )
                );
            }else{
                $this->result = array(
                    ("status code") => 401,
                    ("status text") => "Invalid authorization data",
                    ("body") => array(
                        ("status") => false,
                        ("message") => "Invalid authorization data"
                    )
                );
            }
        }
    }
}