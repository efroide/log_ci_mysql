<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class LogMySQL {

    private $CI;

    function __construct() {
        $this->CI = &get_instance();
    }

	function create_tables_logs(){
       
        $sql = "CREATE TABLE IF NOT EXISTS `log_data` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `operationid` int(11) NOT NULL,
                  `column_name` varchar(31) DEFAULT NULL,
                  `old_value` varchar(2048) DEFAULT NULL,
                  `new_value` varchar(2048) DEFAULT NULL,
                  `old_blob` blob,
                  `new_blob` blob,
                  `old_text` text,
                  `new_text` text,
                  PRIMARY KEY (`id`)
                );";

        $this->CI->db->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `log_operations` (
                  `operationid` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `table_name` varchar(31) DEFAULT '',
                  `operation` varchar(1) DEFAULT '',
                  `user_id` int(11) DEFAULT NULL,
                  `user_name` varchar(100) DEFAULT NULL,
                  `systime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                  `pk1` varchar(50) DEFAULT NULL,
                  `pk2` varchar(50) DEFAULT NULL,
                  `pk3` varchar(50) DEFAULT NULL,
                  `pk4` varchar(50) DEFAULT NULL,
                  `pk5` varchar(50) DEFAULT NULL,
                  `pk6` varchar(50) DEFAULT NULL,
                  PRIMARY KEY (`operationid`)
                );";

        $this->CI->db->query($sql);

        return $this;
    }

    function create_procedures_save_logs() {

        $this->CI->db->query("DROP PROCEDURE IF EXISTS sp_in_log_data");

        $sql = "CREATE PROCEDURE  sp_in_log_data (p_operationid int,
                                                    p_column_name varchar(31),
                                                    p_old_value varchar(2048), 
                                                    p_new_value varchar(2048),
                                                    p_old_blob blob, 
                                                    p_new_blob blob, 
                                                    p_old_text text, 
                                                    p_new_text text)
				BEGIN
					insert into log_data
						(operationid, column_name, old_value, new_value, old_blob, new_blob, old_text, new_text)
					values
						(p_operationid, p_column_name, p_old_value, p_new_value, p_old_blob, p_new_blob, p_old_text, p_new_text);
				END";

        $this->CI->db->query($sql);

        $this->CI->db->query("DROP PROCEDURE IF EXISTS sp_in_log_operation");

        $sql = "CREATE PROCEDURE  sp_in_log_operation (inout p_operationid int, 
                                                        p_table_name varchar(31), 
                                                        p_operation varchar(1), 
                                                        p_pk1 varchar(50), 
                                                        p_pk2 varchar(50), 
                                                        p_pk3 varchar(50),
                                                        p_pk4 varchar(50), 
                                                        p_pk5 varchar(50), 
                                                        p_pk6 varchar(50))
				BEGIN
					insert into log_operations
						(table_name, operation, user_id, user_name, systime, pk1, pk2, pk3, pk4, pk5, pk6)
					values
						(p_table_name, p_operation, @logado_user_id, @logado_user_name, current_timestamp, p_pk1, p_pk2, p_pk3, p_pk4, p_pk5, p_pk6);
					
                    SET p_operationid = LAST_INSERT_ID();
				END";

        $this->CI->db->query($sql);

        return $this;
    }

    function create_trigger_registra_logs($table_name, $afterInsert = true, $afterUpdate = true, $afterDelete = true) {

        $table_name = strtoupper($table_name);


        //============================================================================

        if (($table_name == 'log_data') || ($table_name == 'log_operations'))
        	throw new Exception("Não é possível criar logs nas próprias tabelas de logs.");
        
        //============================================================================

        $sql = "SELECT * 
        		FROM information_schema.COLUMNS 
        		WHERE TABLE_SCHEMA = SCHEMA() 
        		  AND upper(TABLE_NAME) = ?";

        $fields = $this->CI->db->query($sql, array($table_name))->result();

        //============================================================================

        $pk1 = 'null';
        $pk2 = 'null';
        $pk3 = 'null';
        $pk4 = 'null';
        $pk5 = 'null';
        $pk6 = 'null';

        $sql = "SELECT k.column_name
				FROM information_schema.table_constraints t
				JOIN information_schema.key_column_usage k
				USING(constraint_name,table_schema,table_name)
				WHERE t.constraint_type = 'PRIMARY KEY'
				  AND t.table_schema = SCHEMA()
				  AND upper(t.table_name) = ?";

        $p = array();
        $p[] = $table_name;

        $pks = $this->CI->db->query($sql, array($table_name))->result();

        foreach ($pks as $pk) {

            if ($pk1 == 'null') {
                $pk1 = $pk->column_name;
            } else
            if ($pk2 == 'null') {
                $pk2 = $pk->column_name;
            } else
            if ($pk3 == 'null') {
                $pk3 = $pk->column_name;
            } else
            if ($pk4 == 'null') {
                $pk4 = $pk->column_name;
            } else
            if ($pk5 == 'null') {
                $pk5 = $pk->column_name;
            } else
            if ($pk6 == 'null') {
                $pk6 = $pk->column_name;
            }
        }

		if ($afterInsert)
        	$this->_create_trigger_insert($table_name, $fields, $pk1, $pk2, $pk3, $pk4, $pk5, $pk6);

        if ($afterUpdate)
        	$this->_create_trigger_update($table_name, $fields, $pk1, $pk2, $pk3, $pk4, $pk5, $pk6);

        if ($afterDelete)
        	$this->_create_trigger_delete($table_name, $fields, $pk1, $pk2, $pk3, $pk4, $pk5, $pk6);
    }

	function _create_trigger_insert($table_name, $fields, $pk1, $pk2, $pk3, $pk4, $pk5, $pk6) {

        $name_trigger = "LOG_IN_" . $table_name;

        if ($this->exist_trigger($name_trigger)) {
            $this->drop_trigger($name_trigger);
        }

        if ($pk1 != 'null') {
            $pk1 = 'NEW.' . $pk1;
        }
        if ($pk2 != 'null') {
            $pk2 = 'NEW.' . $pk2;
        }
        if ($pk3 != 'null') {
            $pk3 = 'NEW.' . $pk3;
        }
        if ($pk4 != 'null') {
            $pk4 = 'NEW.' . $pk4;
        }
        if ($pk5 != 'null') {
            $pk5 = 'NEW.' . $pk5;
        }
        if ($pk6 != 'null') {
            $pk6 = 'NEW.' . $pk6;
        }                

        $sql = "CREATE TRIGGER " . $name_trigger . " AFTER INSERT on " . $table_name;

        $sql .= chr(13) . "FOR EACH ROW";
        $sql .= chr(13) . "BEGIN";
        $sql .= chr(13) . "    DECLARE operationid int;";
        $sql .= chr(13) . "    SET operationid = 0;";
        $sql .= chr(13) . "    CALL sp_in_log_operation(operationid, '" . $table_name . "', 'I', " . $pk1 . ", " . $pk2 . ", " . $pk3 . ", " . $pk4 . ", " . $pk5 . ", " . $pk6 . "); ";
        $sql .= chr(13);

        foreach ($fields as $field) {

            $params = "operationid, '". $field->COLUMN_NAME . "', null, NEW." . $field->COLUMN_NAME . ", null, null, null, null";
            if (strtoupper($field->DATA_TYPE) == 'BLOB') {
                $params = "operationid, '". $field->COLUMN_NAME . "', null, null, null, NEW." . $field->COLUMN_NAME . ", null, null";
            }else
            if (strtoupper($field->DATA_TYPE) == 'TEXT') {
                $params = "operationid, '". $field->COLUMN_NAME . "', null, null, null, null, null, NEW." . $field->COLUMN_NAME;
            }

            $sql .= chr(13) . "    CALL sp_in_log_data (" . $params . "); ";
            $sql .= chr(13);
        }

        $sql .= chr(13) . "END";

        $this->CI->db->query($sql);
    }

    function _create_trigger_update($table_name, $fields, $pk1, $pk2, $pk3, $pk4, $pk5, $pk6) {

        $name_trigger = "LOG_UP_" . $table_name;

        if ($this->exist_trigger($name_trigger)) {
            $this->drop_trigger($name_trigger);
        }

        if ($pk1 != 'null') {
            $pk1 = 'OLD.' . $pk1;
        }
        if ($pk2 != 'null') {
            $pk2 = 'OLD.' . $pk2;
        }
        if ($pk3 != 'null') {
            $pk3 = 'OLD.' . $pk3;
        }
        if ($pk4 != 'null') {
            $pk4 = 'OLD.' . $pk4;
        }
        if ($pk5 != 'null') {
            $pk5 = 'OLD.' . $pk5;
        }
        if ($pk6 != 'null') {
            $pk6 = 'OLD.' . $pk6;
        }        

        $sql = "CREATE TRIGGER " . $name_trigger . " AFTER UPDATE on " . $table_name;

        $sql .= chr(13) . "FOR EACH ROW";
        $sql .= chr(13) . "BEGIN";
        $sql .= chr(13) . "    DECLARE operationid int;";
        $sql .= chr(13) . "    SET operationid = 0;";
        $sql .= chr(13) . "    CALL sp_in_log_operation(operationid, '" . $table_name . "', 'U', " . $pk1 . ", " . $pk2 . ", " . $pk3 . ", " . $pk4 . ", " . $pk5 . ", " . $pk6 . "); ";
        $sql .= chr(13);

        foreach ($fields as $field) {

            $params = "operationid, '". $field->COLUMN_NAME . "', OLD." . $field->COLUMN_NAME . ", NEW." . $field->COLUMN_NAME . ", null, null, null, null";
            
            if (strtoupper($field->DATA_TYPE) == 'BLOB') {
                $params = "operationid, '". $field->COLUMN_NAME . "', null, null, OLD." . $field->COLUMN_NAME . ", NEW." . $field->COLUMN_NAME . ", null, null";
            }else
            if (strtoupper($field->DATA_TYPE) == 'TEXT') {
                $params = "operationid, '". $field->COLUMN_NAME . "', null, null, null, null, OLD." . $field->COLUMN_NAME . ", NEW." . $field->COLUMN_NAME;
            }

            $sql .= chr(13) . "    IF (NEW." . $field->COLUMN_NAME . " != OLD." . $field->COLUMN_NAME . ") THEN ";
            $sql .= chr(13) . "        CALL sp_in_log_data (" . $params . "); ";
            $sql .= chr(13) . "    END IF; ";
            $sql .= chr(13);
        }

        $sql .= chr(13) . "END";

        $this->CI->db->query($sql);
    }

    function _create_trigger_delete($table_name, $fields, $pk1, $pk2, $pk3, $pk4, $pk5, $pk6) {

        $name_trigger = "LOG_DL_" . $table_name;

        if ($this->exist_trigger($name_trigger)) {
            $this->drop_trigger($name_trigger);
        }

        if ($pk1 != 'null') {
            $pk1 = 'OLD.' . $pk1;
        }
        if ($pk2 != 'null') {
            $pk2 = 'OLD.' . $pk2;
        }
        if ($pk3 != 'null') {
            $pk3 = 'OLD.' . $pk3;
        }
        if ($pk4 != 'null') {
            $pk4 = 'OLD.' . $pk4;
        }
        if ($pk5 != 'null') {
            $pk5 = 'OLD.' . $pk5;
        }
        if ($pk6 != 'null') {
            $pk6 = 'OLD.' . $pk6;
        }
        $sql = "CREATE TRIGGER " . $name_trigger . " AFTER DELETE on " . $table_name;

        $sql .= chr(13) . "FOR EACH ROW";
        $sql .= chr(13) . "BEGIN";
        $sql .= chr(13) . "    DECLARE operationid int;";
        $sql .= chr(13) . "    SET operationid = 0;";
        $sql .= chr(13) . "    CALL sp_in_log_operation(operationid, '" . $table_name . "', 'D', " . $pk1 . ", " . $pk2 . ", " . $pk3 . ", " . $pk4 . ", " . $pk5 . ", " . $pk6 . "); ";
        $sql .= chr(13);

        foreach ($fields as $field) {

            $params = "operationid, '". $field->COLUMN_NAME . "', OLD." . $field->COLUMN_NAME . ", null, null, null, null, null";

            if (strtoupper($field->DATA_TYPE) == 'BLOB') {
                $params = "operationid, '". $field->COLUMN_NAME . "', null, null, OLD." . $field->COLUMN_NAME . ", null, null, null";
            }else
            if (strtoupper($field->DATA_TYPE) == 'TEXT') {
                $params = "operationid, '". $field->COLUMN_NAME . "', null, null, null, null, OLD." . $field->COLUMN_NAME . ", null";
            }

            $sql .= chr(13) . "    CALL sp_in_log_data (" . $params . "); ";
            $sql .= chr(13);
        }

        $sql .= chr(13) . "END";

        $this->CI->db->query($sql);
    }

    function exist_trigger($name) {

        $sql = "SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = SCHEMA() and upper(TRIGGER_NAME) = ?;";

        $query = $this->CI->db->query($sql, array(strtoupper($name)));

        return $query->num_rows() > 0;
    }

    function drop_trigger($trigger_name) {

        $this->CI->db->query('DROP TRIGGER ' . $trigger_name);
    }    
}