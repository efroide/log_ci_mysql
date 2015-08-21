# log_ci_mysql

Bibioteca codeigniter para criação de triggers geradoras de  logs no mysql. 

Gerar as tabelas de logs e registrar as triggers.

```
class Log extends CI_Controller {
    public function index() {
        $this->load->library('logmysql');
        $this->logmysql
            ->create_tables_logs()
            ->create_procedures_save_logs()
            ->create_trigger_registra_logs('tb_alunos');
    }
}
```

É preciso informar o código e nome do usuario logado para funcionar corretamente.
Para isso basta criar a classe MY_Model e incluir a herança todos os models.
```
class MY_Model extends CI_Model {
    function __construct() {
        parent::__construct();
        $this->db->query("SET @logado_user_id := 1;");
        $this->db->query("SET @logado_user_name := 'NOME DO USUARIO'");
    }
}
```
