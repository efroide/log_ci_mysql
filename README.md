# log_ci_mysql

Bibioteca codeigniter para criação de triggers geradoras de  logs no mysql. 

Gerar as tabelas de logs e registrar as triggers.

class Log extends CI_Controller {

    public function index() {

        $this->load->library('logmysql');
     
        $this->auditoriamysql
            ->create_tables_logs()
            ->create_procedures_save_logs()
            ->create_trigger_registra_logs('tb_alunos');
    }
}
