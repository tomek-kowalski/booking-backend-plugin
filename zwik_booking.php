<?php

/**
 * Plugin Name: ZWIK Booking
 * Plugin URI: https://kowalski-consulting.com
 * Description: ZWIK Booking
 * Author: Tomasz Kowalski
 * Author URI: https://kowalski-consulting.com
 * Text Domain: zwik_booking
 * License: GPL2
 */

 namespace StartBooking;

 if ( ! defined( 'WPINC' ) ) {
     die;
 }
 
 class Zwik_booking {
 
     public function __construct() {
         $this->set_constants();
         $this->get_API();
         $this->get_panel();
         $this->manage_db();
         add_action('admin_enqueue_scripts', array($this, 'booking_plugin'));
         add_action('admin_enqueue_scripts', array($this, 'harmongram_live_enqueued'));
         register_activation_hook(__FILE__, array($this, 'zwik_add_custom_role'));
         add_filter('login_redirect', array($this, 'redirect_harmonogram_manager_after_login'), 10, 3);
     }
 
     public function set_constants() {
         if (!defined('ZWIK_PATH')) {
             define('ZWIK_PATH', plugin_dir_path(__FILE__));
         }
         if (!defined('ZWIK_URL')) {
             define('ZWIK_URL', plugin_dir_url(__FILE__));
         }
         if (!defined('ZWIK_VERSION')) {
             define('ZWIK_VERSION', '1.0.0');
         }
     }
 
     public function get_API() { 
         require_once ZWIK_PATH . 'api/zwik_api.php';
     }
 
     public function get_panel() {
         require_once ZWIK_PATH . 'panels/zwik_panel.php';
     }
 
     function manage_db() {
         require_once ZWIK_PATH . 'db/zwik_db.php';
     }
 
     function booking_plugin($hook) {
         if ($hook !== 'toplevel_page_rezerwacje') {
             return;
         }
 
         $css_file_path = ZWIK_URL . 'assets/css/booking-style.css';
         $css_file_path_1 = ZWIK_URL . 'assets/css/datepicker-style.css';
 
         wp_register_style('booking-style', $css_file_path, array(), ZWIK_VERSION);
         wp_register_style('datepicker-style', $css_file_path_1, array(), ZWIK_VERSION);
         wp_enqueue_style('booking-style');
         wp_enqueue_style('datepicker-style');
 
         if(is_admin()) {
             $js_file_path = ZWIK_URL . 'assets/js/settings-loading.js';
             $js_file_path_1 = ZWIK_URL . 'assets/js/datepicker.js';
 
             wp_register_script('settings-loading', $js_file_path, array('jquery'));
             wp_register_script('datepicker', $js_file_path_1, array('jquery'));
 
             wp_localize_script('settings-loading', 'settingsloadingAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('settings_loading_nonce'),
             ]);
             wp_enqueue_script('settings-loading');
             wp_enqueue_script('datepicker');
         }
     }

     function harmongram_live_enqueued($hook) {
        if ($hook !== 'rezerwacje_page_harmonogram') {
            return;
        }

       wp_enqueue_script('harmonogram-live', ZWIK_URL . 'assets/js/harmonogram.js', array('jquery'), null, true);
       wp_localize_script('harmonogram-live', 'ajaxurl', admin_url('admin-ajax.php'));

    }
 
    public function zwik_add_custom_role() {

        add_role(
            'harmonogram_manager',
            __('Harmonogram Manager', 'zwik_booking'),
            array(
                'read' => true,
            )
        );
    
        $harmonogram_role = get_role('harmonogram_manager');
        if ($harmonogram_role) {
            $harmonogram_role->add_cap('view_harmonogram');
            $harmonogram_role->add_cap('delete_harmonogram_row');
            $harmonogram_role->add_cap('change_harmonogram_status');
        }
    
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('view_harmonogram');
            $admin_role->add_cap('delete_harmonogram_row');
            $admin_role->add_cap('change_harmonogram_status');
        }
    }
    

     function redirect_harmonogram_manager_after_login($redirect_to, $request, $user) {
        if (is_a($user, 'WP_User')) {
            if (in_array('harmonogram_manager', (array) $user->roles)) {
                return admin_url('admin.php?page=harmonogram');
            }
        }
        return $redirect_to;
    }
       
 }
 
 new Zwik_booking();
 



