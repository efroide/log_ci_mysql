<?php

class MY_Model extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->db->query("SET @logado_user_id := 100;");
        $this->db->query("SET @logado_user_name := 'NOME DO USUARIO'");
    }
}

?>
