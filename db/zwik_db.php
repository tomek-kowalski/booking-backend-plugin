<?php
namespace StartBooking;

if (!defined('WPINC')) {
    die;
}

require_once ZWIK_PATH . 'zwik_booking.php';

class Zwik_Db extends Zwik_Booking
{
    public function __construct()
    {
        parent::__construct();
        $this->setup_table();
        add_action('wp_ajax_delete_row', array($this,'delete_row_callback'));
        add_action('wp_ajax_change_status', array($this,'change_status_callback'));
    }

    private function setup_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zwik_booking';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                target VARCHAR(255) NOT NULL,
                desk VARCHAR(255) NOT NULL,
                date VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                control_number VARCHAR(255) NOT NULL,
                status VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }
    }

    function delete_row_callback() {
        global $wpdb;
        $id = intval($_POST['id']);
        $table_name = $wpdb->prefix . 'zwik_booking';
    
        $result = $wpdb->delete($table_name, ['id' => $id]);

        error_log('result: ' . $result );
    
        if ($result) {
            wp_send_json_success(['message' => 'Row deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Error deleting row']);
        }
    }
    
    function change_status_callback() {
        global $wpdb;
    
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            wp_send_json_error(['message' => 'Invalid ID']);
        }
    
        $id = intval($_POST['id']);
        $table_name = $wpdb->prefix . 'zwik_booking';
    
        $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $id));
    
        if (!$current_status) {
            wp_send_json_error(['message' => 'Record not found']);
        }

        if ($current_status === 'Oczekujące') {
            $new_status = 'Rozpoczęte';
        } elseif ($current_status === 'Rozpoczęte') {
            $new_status = 'Zakończone';
        } else {
            wp_send_json_error(['message' => 'Already completed']);
        }
    
        $result = $wpdb->update($table_name, ['status' => $new_status], ['id' => $id]);
    
        if ($result !== false) {
            wp_send_json_success([
                'message' => "Status changed to $new_status",
                'new_status' => $new_status
            ]);
        } else {
            wp_send_json_error(['message' => 'Error updating status']);
        }
    
        wp_die();
    }
    
    
}

new Zwik_Db;




