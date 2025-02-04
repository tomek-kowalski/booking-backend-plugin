<?php 

namespace StartBooking;

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Zwik_panel{

    function __construct()
    {
    add_action('admin_menu', array($this, 'add_menu'));
    add_action('wp_ajax_admin_get_meetings_time', array($this,'admin_get_meetings_time'));
    add_action('wp_ajax_save_meetings_time', array($this, 'admin_save_meetings_time'));
    add_action('wp_ajax_admin_save_calendar_days', array($this, 'admin_save_calendar_days'));
    add_action('wp_ajax_save_calendar_days', array($this,'save_calendar_days'));
    add_action('wp_ajax_admin_set_days', array($this, 'admin_set_days'));
    add_action('wp_ajax_admin_add_new_window', array($this, 'admin_add_new_window'));
    add_action('wp_ajax_admin_add_new_date', array($this, 'admin_add_new_date'));
    add_action('wp_ajax_admin_add_edit_window', array($this, 'admin_add_edit_window'));
    add_action('wp_ajax_admin_add_edit_day_window', array($this, 'admin_add_edit_day_window'));
    add_action('wp_ajax_save_weekly_times', array($this, 'save_weekly_times'));
    add_action('wp_ajax_save_days_times', array($this, 'save_days_times'));
    add_action('wp_ajax_delete_weekly_times', array($this, 'delete_weekly_times'));
    add_action('wp_ajax_delete_days_times', array($this, 'delete_days_times'));
    add_action('wp_ajax_add_hours_On_Fly', array($this, 'add_hours_On_Fly'));
    add_action('wp_ajax_add_days_On_Fly', array($this, 'add_days_On_Fly'));
    add_action('wp_ajax_nopriv_fetch_harmonogram', array($this, 'fetch_harmonogram_callback')); 
    add_action('wp_ajax_fetch_harmonogram', array($this, 'fetch_harmonogram_callback'));

    }

    public function add_menu() {

        add_menu_page(
            __('Ustawienia', 'zwik_booking'),
            'Rezerwacje- ustawienia',
            'manage_options',
            'rezerwacje',
            array($this, 'booking_settings_page'),
            'dashicons-hourglass',
            1
        );
    

        add_submenu_page(
            'rezerwacje',
            __('Harmonogram spotkań', 'zwik_booking'),
            'Harmonogram spotkań',
            'view_harmonogram',
            'harmonogram',
            array($this, 'harmonogram_callback')
        );
    }
    

    public function harmonogram_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zwik_booking';
    
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $records_per_page = 20;
        $offset = ($current_page - 1) * $records_per_page;
    
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_records / $records_per_page);
    
        $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT $records_per_page OFFSET $offset");
    
        echo '<div class="wrap"><h2>Harmonogram</h2>
              <table class="wp-list-table widefat fixed striped" id="harmonogram-table" border="1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Urząd</th>
                        <th>Okienko</th>
                        <th>Data</th>
                        <th>Godzina</th>
                        <th>Imię</th>
                        <th>Temat</th>
                        <th>Liczba kontrolna</th>
                        <th>Status</th>
                        <th>Akcja</th
                    </tr>
                </thead>
                <tbody>';
    
        foreach ($records as $record) {
            try {
                $datetime = new \DateTime($record->date, new \DateTimeZone('Europe/Warsaw'));
    
                $formatted_date = $datetime->format('Y.m.d');
                $formatted_time = $datetime->format('H:i');
    
                echo "<tr>
                    <td>{$record->id}</td>
                    <td>{$record->target}</td>
                    <td>{$record->desk}</td>
                    <td>{$formatted_date}</td> 
                    <td>{$formatted_time}</td>
                    <td>{$record->name}</td>
                    <td>{$record->subject}</td>
                    <td>{$record->control_number}</td>
                    <td>
                                <button class='change-status' data-id='{$record->id}' " . ($record->status === "Zakończone" ? "disabled" : "") . ">
                                {$record->status}
                                </button>

                    </td>
                    <td>
                        <button class='delete-row' data-id='{$record->id}'>Usuń</button>
                    </td>
                </tr>";
    
            } catch (\Exception $e) {
                error_log("Error parsing date for ID {$record->id}: " . $e->getMessage());
            }
        }
    
        echo '</tbody></table></div>';
    
        echo '<div class="pagination" style="margin-top:10px;">';
        if ($current_page > 1) {
            echo '<a href="?page=harmonogram&paged=1">&laquo; Pierwsza</a> ';
            echo '<a href="?page=harmonogram&paged=' . ($current_page - 1) . '">Poprzednia</a> ';
        }
    
        echo " Strona {$current_page} z {$total_pages} ";
    
        if ($current_page < $total_pages) {
            echo '<a href="?page=harmonogram&paged=' . ($current_page + 1) . '">Następna</a> ';
            echo '<a href="?page=harmonogram&paged=' . $total_pages . '">Ostatnia &raquo;</a>';
        }
        echo '</div>';
    
        echo '<script src="' . ZWIK_URL . 'assets/js/harmonogram.js"></script>';
    }
    

    function fetch_harmonogram_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zwik_booking';
    
        $current_page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $records_per_page = 20;
        $offset = ($current_page - 1) * $records_per_page;
    
        $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT $records_per_page OFFSET $offset");
    
        foreach ($records as $record) {
            try {
                $datetime = new \DateTime($record->date, new \DateTimeZone('Europe/Warsaw'));
    
                $formatted_date = $datetime->format('Y.m.d');
                $formatted_time = $datetime->format('H:i');
    
                echo "<tr>
                    <td>{$record->id}</td>
                    <td>{$record->target}</td>
                    <td>{$record->desk}</td>
                    <td>{$formatted_date}</td> 
                    <td>{$formatted_time}</td>
                    <td>{$record->name}</td>
                    <td>{$record->subject}</td>
                    <td>{$record->control_number}</td>
                    <td>
                        <button class='change-status' data-id='{$record->id}' " . ($record->status === "Zakończone" ? "disabled" : "") . ">
                        {$record->status}
                        </button>

                    </td>
                    <td>
                        <button class='delete-row' data-id='{$record->id}'>Usuń</button>
                    </td>
                </tr>";
    
            } catch (\Exception $e) {
                error_log("Error parsing date for ID {$record->id}: " . $e->getMessage());
            }
        }
    
        wp_die();
    }

    public function booking_settings_page() {
        ?>
        <div class="wrap wrap-background-white"> 
            <div class=header-wrap>
                <div class="column-logo">
                    <h1 class="am-logo">ZWIK</h1>
                </div>
                <div class="column-title">
                    <h2 class="am-page-title"><?php _e('Ustawienia', 'zwik_booking'); ?></h2>
                </div>
            </div>
        <div class="content-frame">
        <div class="wrap-content-menu">
                <div class="wrap-conent-column">
                    <div class="am-settings-card"><h3><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 24" class="inlined-svg" role="img">
                    <path fill="#1A84EE" fill-rule="evenodd" d="M26.083 10.219c-.102-.558-.65-1.128-1.223-1.251l-.425-.097A4.231 4.231 0 0 1 21.98 6.97a4.087 4.087 0 0 1-.453-3.052l.135-.394c.17-.543-.051-1.292-.498-1.665 0 0-.401-.332-1.528-.97-1.13-.634-1.615-.81-1.615-.81-.548-.197-1.319-.01-1.72.408l-.297.311a4.25 4.25 0 0 1-2.91 1.131c-1.13 0-2.16-.432-2.918-1.14L9.889.487C9.493.07 8.72-.116 8.169.078c0 0-.492.177-1.621.811-1.13.643-1.526.975-1.526.975-.446.368-.668 1.11-.497 1.66l.123.397a4.092 4.092 0 0 1-.453 3.049 4.207 4.207 0 0 1-2.469 1.906l-.41.092c-.564.126-1.118.69-1.223 1.251 0 0-.093.502-.093 1.78s.093 1.78.093 1.78c.102.564.65 1.128 1.223 1.252l.401.09a4.192 4.192 0 0 1 2.472 1.913c.564.96.699 2.054.453 3.052l-.117.388c-.17.544.05 1.293.497 1.666 0 0 .402.332 1.529.97 1.13.637 1.615.81 1.615.81.548.197 1.318.009 1.72-.408l.282-.297a4.289 4.289 0 0 1 2.924-1.143 4.28 4.28 0 0 1 2.925 1.146l.282.297c.395.417 1.169.602 1.72.408 0 0 .491-.176 1.621-.81 1.13-.638 1.525-.97 1.525-.97.447-.367.669-1.117.498-1.666l-.123-.402a4.062 4.062 0 0 1 .452-3.038 4.235 4.235 0 0 1 2.473-1.912l.401-.091c.564-.127 1.118-.69 1.223-1.252 0 0 .093-.502.093-1.78-.006-1.278-.1-1.783-.1-1.783zm-12.988 6.897c-2.88 0-5.22-2.288-5.22-5.117 0-2.823 2.335-5.111 5.22-5.111 2.88 0 5.22 2.288 5.22 5.117-.005 2.823-2.34 5.111-5.22 5.111z"></path>
                        </svg>
                        Czas spotkań
                        </h3> 
                            <p>Użyj tych ustawień, aby ustawić czas trwania spotkania w zalezności od formularza użytego przez użytkownika </p> 
                            <p id="meeting-time" class="link">Pokaż ustawienia czasu spotkań</p>
                    </div>
                </div>
                <div class="wrap-conent-column">
                    <div class="am-settings-card"><h3><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="inlined-svg" role="img">
                       <path d="M22.7213333,16.994 L21.8193333,16.7666667 L21.8193333,16.7226667 L22.2666667,15.9306667 C22.3520189,15.7857476 22.3278347,15.6013426 22.208,15.4833333 L20.9613333,14.2366667 C20.8433241,14.116832 20.658919,14.0926477 20.514,14.178 L20.0446667,14.4566667 L20.0446667,3.93333333 C20.0446667,3.1233157 19.3880176,2.46666667 18.578,2.46666667 L16.0333333,2.46666667 C15.8308289,2.46666667 15.6666667,2.63082893 15.6666667,2.83333333 L15.6666667,4.66666667 C15.6681531,5.04029932 15.3884871,5.35531108 15.017309,5.398094 C14.6461309,5.44087692 14.3021326,5.19775027 14.2185776,4.83357705 C14.1350226,4.46940382 14.3386295,4.10063604 14.6913333,3.97733333 C14.8365141,3.92484493 14.9332677,3.78704436 14.9333333,3.63266667 L14.9333333,1.36666667 C14.9333333,1.16416226 14.7691711,1 14.5666667,1 C14.3641623,1 14.2,1.16416226 14.2,1.36666667 L14.2,2.46666667 L7.23333333,2.46666667 C7.03082893,2.46666667 6.86666667,2.63082893 6.86666667,2.83333333 L6.86666667,4.66666667 C6.86815305,5.04029932 6.58848714,5.35531108 6.21730903,5.398094 C5.84613092,5.44087692 5.50213257,5.19775027 5.4185776,4.83357705 C5.33502263,4.46940382 5.53862947,4.10063604 5.89133333,3.97733333 C6.03651406,3.92484493 6.13326765,3.78704436 6.13333333,3.63266667 L6.13333333,1.36666667 C6.13333333,1.16416226 5.96917107,1 5.76666667,1 C5.56416226,1 5.4,1.16416226 5.4,1.36666667 L5.4,2.46666667 L2.46666667,2.46666667 C1.65664903,2.46666667 1,3.1233157 1,3.93333333 L1,18.6 C1,19.4100176 1.65664903,20.0666667 2.46666667,20.0666667 L14.464,20.0666667 L14.1853333,20.536 C14.0999811,20.680919 14.1241653,20.8653241 14.244,20.9833333 L15.4906667,22.23 C15.6086759,22.3498347 15.793081,22.3740189 15.938,22.2886667 L16.7373333,21.812 L16.7813333,21.812 L17.0086667,22.714 C17.0487713,22.8762603 17.1935334,22.9908637 17.3606667,22.9926667 L19.1206667,22.9926667 C19.2877999,22.9908637 19.4325621,22.8762603 19.4726667,22.714 L19.7,21.812 L19.744,21.812 L20.5433333,22.2886667 C20.6882524,22.3740189 20.8726574,22.3498347 20.9906667,22.23 L22.2373333,20.9833333 C22.357168,20.8653241 22.3813523,20.680919 22.296,20.536 L21.8193333,19.7366667 L21.8193333,19.6926667 L22.7213333,19.4653333 C22.8835936,19.4252287 22.998197,19.2804666 23,19.1133333 L23,17.3533333 C23.0015657,17.1834644 22.8862463,17.0347631 22.7213333,16.994 Z M15.4833333,14.2366667 L14.2366667,15.4833333 C14.116832,15.6013426 14.0926477,15.7857476 14.178,15.9306667 L14.6546667,16.73 L14.6546667,16.774 L13.7526667,17.0013333 C13.5877537,17.0420964 13.4724343,17.1907977 13.474,17.3606667 L13.474,18.6 L2.46666667,18.6 L2.46666667,7.6 L18.6,7.6 L18.6,13.4666667 L17.3533333,13.4666667 C17.1862001,13.4684697 17.0414379,13.5830731 17.0013333,13.7453333 L16.774,14.6473333 L16.73,14.6473333 L15.9306667,14.2 C15.7889935,14.1108351 15.6049189,14.1289408 15.4833333,14.244 L15.4833333,14.2366667 Z M5,9 L6,9 C6.55228475,9 7,9.44771525 7,10 L7,11 C7,11.5522847 6.55228475,12 6,12 L5,12 C4.44771525,12 4,11.5522847 4,11 L4,10 C4,9.44771525 4.44771525,9 5,9 Z M18.2333333,20.4333333 C17.3435164,20.4333333 16.5413166,19.8973205 16.2007984,19.0752369 C15.8602802,18.2531532 16.0485028,17.306894 16.6776984,16.6776984 C17.306894,16.0485028 18.2531532,15.8602802 19.0752369,16.2007984 C19.8973205,16.5413166 20.4333333,17.3435164 20.4333333,18.2333333 C20.4333333,19.4483598 19.4483598,20.4333333 18.2333333,20.4333333 L18.2333333,20.4333333 Z" id="Shape" fill="#1A84EE" fill-rule="nonzero"></path>
                        </svg>
                        Godziny pracy i dni wolne
                        </h3> 
                            <p>Użyj tych ustawień, aby ustawić godziny pracy i dni wolne w firmie, które będą obowiązywać dla każdego okienka</p> 
                            <p id="hours" class="link">Pokaż ustawienia godzin pracy i dni wolne</p>
                    </div>
                </div>
                <div class="wrap-conent-column" >
                    <div class="am-settings-card"><h3><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 24" class="inlined-svg" role="img">
                        <path fill="#1A84EE" fill-rule="evenodd" d="M15.714 2.667H6.286V0H2.75v2.667H.78A.783.783 0 0 0 0 3.45v19.764A.78.78 0 0 0 .783 24h20.434a.785.785 0 0 0 .783-.785V3.451a.782.782 0 0 0-.78-.784h-1.97V0h-3.536v2.667zM2.75 21.429V8h16.5v13.429H2.75zM12 14v6h6v-6h-6z"></path>
                        </svg>
                        Perspektywa czasowa rezerwacji
                        </h3> 
                            <p>Użyj tych ustawień, aby ustawić dopuszczalną perspektwę czasową rezerwacji spotkania </p> 
                            <p id="perspective" class="link">Pokaż ustawienia perspektywz czasowej rezerwacji</p>
                    </div>
                </div>
         </div>

         <div class="wrap-content-settings" id="ajax-target"></div>
         </div>
        </div>
    <?php
    }

    function admin_get_meetings_time() {
        check_ajax_referer('settings_loading_nonce', 'nonce');
    
        $duration_bok = get_option('zwik_duration_bok', '15');
        $duration_other = get_option('zwik_duration_other', '15');
        $duration_online = get_option('zwik_duration_online', '15');
    
        $div  = '<div class="window-wrap">';
        $div .= ' <div class="dialog-header"><h2>' . __('Ustawienia czasu spotkań','zwik_booking') . '</h2>';
        $div .= '  <button class="close-button">x</button>';
        $div .= '  </div>';
        $div .= '  <div class="options-wrap">';
        $div .= '    <h2>Czas trwania spotkania:</h2>';
        $div .= '    <div class="select-frame">';
        $div .= '    <label for="duration-bok">Formularz BOK:</label>';
        $div .= '    <select name="duration-bok" id="duration-bok">';
        foreach (['10', '15', '20', '25', '30'] as $value) {
            $selected = $value == $duration_bok ? 'selected' : '';
            $div .= "<option value='$value' $selected>$value min</option>";
        }
        $div .= '    </select>';
        $div .= '    </div>';
        $div .= '    <div class="select-frame">';
        $div .= '    <label for="duration-other">Formularz Inne Lokalizacje:</label>';
        $div .= '    <select name="duration-other" id="duration-other">';
        foreach (['10', '15', '20', '25', '30'] as $value) {
            $selected = $value == $duration_other ? 'selected' : '';
            $div .= "<option value='$value' $selected>$value min</option>";
        }
        $div .= '    </select>';
        $div .= '    </div>';
        $div .= '    <div class="select-frame">';
        $div .= '    <label for="duration-online">Formularz Online:</label>';
        $div .= '    <select name="duration-online" id="duration-online">';
        foreach (['10', '15', '20', '25', '30'] as $value) {
            $selected = $value == $duration_online ? 'selected' : '';
            $div .= "<option value='$value' $selected>$value min</option>";
        }
        $div .= '    </select>';
        $div .= '    </div>';
        $div .= '  </div>';
        $div .= '  <div class="button-row">';
        $div .= '       <input type="button" id="close-button" class="button-primary-cancel" value="' . __('Anuluj', 'zwik_booking') . '">';
        $div .= '       <input type="button" id="save-settings" class="button-primary-save" value="' . __('Zapisz', 'zwik_booking') . '">';
        $div .= '  </div>';
        $div .= '</div>';
    
        wp_send_json_success($div);
    }

    function admin_save_meetings_time() {
        check_ajax_referer('settings_loading_nonce', 'nonce');
    
        update_option('zwik_duration_bok', sanitize_text_field($_POST['duration_bok']));
        update_option('zwik_duration_other', sanitize_text_field($_POST['duration_other']));
        update_option('zwik_duration_online', sanitize_text_field($_POST['duration_online']));
    
        wp_send_json_success(__('Settings saved successfully.', 'zwik_booking'));
    }

    function admin_save_calendar_days() {
        check_ajax_referer('settings_loading_nonce', 'nonce');

        $duration_calendar = get_option('zwik_duration_calendar', '365');

        $div  = '<div class="window-wrap">';
        $div .= ' <div class="dialog-header"><h2>' . __('Ustawienia perspektywy czasowej kalendarza','zwik_booking') . '</h2>';
        $div .= '  <button class="close-button">x</button>';
        $div .= '  </div>';
        $div .= '  <div class="options-wrap">';
        $div .= '    <h2>Okres dostępny do rezerwacji z wyprzedzeniem:</h2>';
        $div .= '    <div class="select-frame">';

        $div .= '    <div class="el-form-item__content">';
        $div .= '    <div class="el-input-number">';
        $div .= '      <span role="button" class="el-input-number__decrease"> - </span>';
        $div .= '      <span role="button" class="el-input-number__increase"> + </span>';
        $div .= '      <div class="el-input">';
        $div .= '      <input type="number" id="duration-calendar" min="1" class="el-input__inner" value="' . esc_attr($duration_calendar) . '">';
        $div .= '    </div>';
        $div .= '    </div>';
        $div .= '    </div>';
        $div .= '    </div>';
        $div .= '  </div>';
        $div .= '  <div class="button-row">';
        $div .= '       <input type="button" id="close-button" class="button-primary-cancel" value="' . __('Anuluj', 'zwik_booking') . '">';
        $div .= '       <input type="button" id="save-settings-calendar" class="button-primary-save" value="' . __('Zapisz', 'zwik_booking') . '">';
        $div .= '  </div>';
        $div .= '</div>';
    
        wp_send_json_success($div);
    }

    function save_calendar_days() {
        check_ajax_referer('settings_loading_nonce', 'nonce');
    
        if (isset($_POST['duration_calendar']) && is_numeric($_POST['duration_calendar'])) {
            $duration_calendar = intval($_POST['duration_calendar']);
            update_option('zwik_duration_calendar', $duration_calendar);
            wp_send_json_success(__('Settings saved successfully!', 'zwik_booking'));
        } else {
            wp_send_json_error(__('Invalid input. Please try again.', 'zwik_booking'));
        }
    }

    function admin_set_days() {    
        check_ajax_referer('settings_loading_nonce', 'nonce');

        $time_monday_1      = get_option('zwik_time_monday_1', '');
        $time_monday_2      = get_option('zwik_time_monday_2', '');
        $time_monday_3      = get_option('zwik_time_monday_3', '');

        $time_tuesday_1     = get_option('zwik_time_tuesday_1', '');
        $time_tuesday_2     = get_option('zwik_time_tuesday_2', '');
        $time_tuesday_3     = get_option('zwik_time_tuesday_3', '');

        $time_wednesday_1   = get_option('zwik_time_wednesday_1', '');
        $time_wednesday_2   = get_option('zwik_time_wednesday_2', '');
        $time_wednesday_3   = get_option('zwik_time_wednesday_3', '');

        $time_thursday_1    = get_option('zwik_time_thursday_1', '');
        $time_thursday_2    = get_option('zwik_time_thursday_2', '');
        $time_thursday_3    = get_option('zwik_time_thursday_3', '');

        $time_friday_1      = get_option('zwik_time_friday_1', '');
        $time_friday_2      = get_option('zwik_time_friday_2', '');
        $time_friday_3      = get_option('zwik_time_friday_3', '');

        $time_saturday_1    = get_option('zwik_time_saturday_1', '');
        $time_saturday_2    = get_option('zwik_time_saturday_2', '');
        $time_saturday_3    = get_option('zwik_time_saturday_3', '');

        $time_sunday_1      = get_option('zwik_time_sunday_1', '');
        $time_sunday_2      = get_option('zwik_time_sunday_2', '');
        $time_sunday_3      = get_option('zwik_time_sunday_3', '');

        $days = [];
        $day_names = [];
        
        for ($i = 1; $i <= 99; $i++) {
            $days[] = get_option('zwik_day_' . $i, '');
            $day_names[] = get_option('zwik_name_' . $i, '');
        }


        $div  = '<div class="window-wrap">';
        $div .= '  <div class="dialog-header"><h2>' . __('Ustawienia godzin pracy i dni wolne','zwik_booking') . '</h2>';
        $div .= '    <button class="close-button">x</button>';
        $div .= '  </div>';
        $div .= '  <div class="options-wrap">';
        $div .= '  <div class="tab">';
        $div .= '    <button class="tablinks active" data-tab="godziny_pracy">Godziny pracy</button>';
        $div .= '    <button class="tablinks" data-tab="dni_wolne">Dni wolne</button>';
        $div .= '  </div>';
        $div .= '  <div id="godziny_pracy" class="tabcontent" style="display: block;">';
        $div .= '       <div class="am-working-hours">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="day">Poniedziałek</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right el-col el-col-12">';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        if($time_monday_1) {
            $div .= '            <div class="added-hours">' . $time_monday_1 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button></div></div>';
        }

        if($time_monday_2) {
            $div .= '            <div class="added-hours">' . $time_monday_2 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_monday_3) {
            $div .= '            <div class="added-hours">' . $time_monday_3 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }
        
        $div .= '       </div>';

        $div .= '       <div class="am-working-hours">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="day">Wtorek</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right el-col el-col-12">';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        if($time_tuesday_1) {
            $div .= '            <div class="added-hours">' . $time_tuesday_1 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_tuesday_2) {
            $div .= '            <div class="added-hours">' . $time_tuesday_2 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_tuesday_3) {
            $div .= '            <div class="added-hours">' . $time_tuesday_3 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        $div .= '       </div>';

        $div .= '       <div class="am-working-hours">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="day">Środa</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right el-col el-col-12">';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        if($time_wednesday_1) {
            $div .= '            <div class="added-hours">' . $time_wednesday_1 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_wednesday_2) {
            $div .= '            <div class="added-hours">' . $time_wednesday_2 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_wednesday_3) {
            $div .= '            <div class="added-hours">' . $time_wednesday_3 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        $div .= '       </div>';

        $div .= '       <div class="am-working-hours">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="day">Czwartek</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right el-col el-col-12">';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        if($time_thursday_1) {
            $div .= '            <div class="added-hours">' . $time_thursday_1 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_thursday_2) {
            $div .= '            <div class="added-hours">' . $time_thursday_2 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_thursday_3) {
            $div .= '            <div class="added-hours">' . $time_thursday_3 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        $div .= '       </div>';

        $div .= '       <div class="am-working-hours">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="day">Piątek</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right el-col el-col-12">';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        if($time_friday_1) {
            $div .= '            <div class="added-hours 1">' . $time_friday_1 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_friday_2) {
            $div .= '            <div class="added-hours 2">' . $time_friday_2 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_friday_3) {
            $div .= '            <div class="added-hours 3">' . $time_friday_3 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }


        $div .= '       </div>';

        $div .= '       <div class="am-working-hours">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="day">Sobota</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right el-col el-col-12">';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        if($time_saturday_1) {
            $div .= '            <div class="added-hours 1 ">' . $time_saturday_1 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_saturday_2) {
            $div .= '            <div class="added-hours 2">' . $time_saturday_2 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_saturday_3) {
            $div .= '            <div class="added-hours 3">' . $time_saturday_3 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }


        $div .= '       </div>';

        $div .= '       <div class="am-working-hours">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="day">Niedziela</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right el-col el-col-12">';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        if($time_sunday_1) {
            $div .= '            <div class="added-hours">' . $time_sunday_1 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_sunday_2) {
            $div .= '            <div class="added-hours">' . $time_sunday_2 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        if($time_sunday_3) {
            $div .= '            <div class="added-hours">' . $time_sunday_3 . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
        }

        $div .= '       </div>';

        $div .= '  </div>';
        $div .= '  <div id="dni_wolne" class="tabcontent" style="display: none;">';

        $div .= '       <div class="am-working-days">';
        $div .= '           <div class="am-dialog-table">';
        $div .= '               <div class="am-dialog-table-head hours el-row">';
        $div .= '                   <div class="el-col el-col-12">';
        $div .= '                       <span id="date">Data</span>';
        $div .= '                   </div>';
        $div .= '                   <div class="column-right-day el-col el-col-12">';
        $div .= '                       <span id="name-day">Nazwa dnia wolnego</span>';
        $div .= '                           <div class="plus-icon-frame"> ';
        $div .= '                               <div class="el-icon-day-plus">+</div>';
        $div .= '                           </div>';
        $div .= '                   </div>';
        $div .= '               </div>';
        $div .= '           </div>';

        foreach ($days as $index => $day) {
            if (!empty($day) && !empty($day_names[$index])) {
                $div .= '<div datasrc="' . esc_attr($index + 1) . '" class="added-hours"><div class="day-set">' . esc_html($day) . '</div>' . '<div class="say-set-name">' . esc_html($day_names[$index]) . '</div>' .
                        '<div class="buttons-frame">' . 
                        '<button class="delete-days">Usuń</button>' . 
                        '<button class="edit-days">Edytuj</button>' . 
                        '</div></div>';
            }
        }

        $div .= '  </div>';
        $div .= '    </div>';
        $div .= '  <div class="button-row">';
        $div .= '       <input type="button" id="close-button" class="button-primary-cancel" value="' . __('Anuluj', 'zwik_booking') . '">';
        $div .= '       <input type="button" id="save-settings-days" class="button-primary-save" value="' . __('Zapisz', 'zwik_booking') . '">';
        $div .= '   </div>';
        $div .= '   </div>';
        $div .= '  </div>';
    
        wp_send_json_success($div);
    }

    function admin_add_new_window() {
        $div = '<div class="am-add-period">';
        $div .= '<form class="el-form el-form--label-top">';
        $div .= '  <div class="el-row el-row--flex">';
        $div .= '    <div class="el-col el-col-24">';
        $div .= '      <div class="el-row el-row--flex timetable-container">';
        $div .= '        <div class="el-col el-col-24">';
        $div .= '          <span>Godziny pracy</span>';
        $div .= '        </div>';
        $div .= '        <div class="el-col el-col-24">';
        $div .= '          <div class="el-form-item is-required">';
        $div .= '            <div class="el-form-item__content">';
        $div .= '              <div class="el-date-editor el-input el-input--mini el-input--prefix el-input--suffix el-date-editor--time-select" style="margin-bottom: 12px;">';
        $div .= '                <input value id="starting_time" type="text" autocomplete="off" name="" class="el-input__inner starting_time">';
        $div .= '                <span class="el-input__prefix">';
        $div .= '                  <span class="el-input__icon el-icon-time"></span>';
        $div .= '                </span>';
        $div .= '                <span class="el-input__suffix">';
        $div .= '                  <span class="el-input__suffix-inner"><span class="el-input__icon"></span>';
        $div .= '                </span>';
        $div .= '              </span>';
        $div .= '            </div>';
        $div .= '          </div>';
        $div .= '        </div>';
        $div .= '      </div>';
        $div .= '      <div class="el-col el-col-24">';
        $div .= '        <div class="el-form-item is-required">';
        $div .= '          <div class="el-form-item__content">';
        $div .= '            <div class="el-date-editor el-input el-input--mini el-input--prefix el-input--suffix el-date-editor--time-select">';
        $div .= '              <input value id="ending_time" type="text" autocomplete="off" name="" class="el-input__inner ending_time">';
        $div .= '              <span class="el-input__prefix"><span class="el-input__icon el-icon-time"></span></span>';
        $div .= '              <span class="el-input__suffix">';
        $div .= '                <span class="el-input__suffix-inner"><span class="el-input__icon"></span></span>';
        $div .= '              </span>';
        $div .= '            </div>';
        $div .= '          </div>';
        $div .= '        </div>';
        $div .= '      </div>';
        $div .= '    </div>';
        $div .= '  </div>';
        $div .= '  <div class="am-working-hours-buttons">';
        $div .= '    <div class="align-left">';
        $div .= '      <button id="hours-cancel-button" type="button" class="el-button el-button--default el-button--small">';
        $div .= '        <span>Anuluj</span>';
        $div .= '      </button>';
        $div .= '      <button id="save-settings-hours" type="button" class="el-button el-button--primary el-button--small">';
        $div .= '        <span>Zachowaj</span>';
        $div .= '      </button>';
        $div .= '    </div>';
        $div .= '  </div>';
        $div .= '</form>';
        $div .= '</div>';
    
        wp_send_json_success($div);
    }

    function admin_add_new_date() {

        $div = '<div class="am-add-period">';
        $div .= '<div class="days-frame">';
        $div .= '<div class="date-free-frame">';
        $div .= '<input id="date-selected" placeholder="Wybierz datę"/>';
        $div .= '</div>';
        $div .= '<div class="date-name-frame">';
        $div .= '<input id="name-date-selected" placeholder="Nazwa dnia wolnego"/>';
        $div .= '</div>';
        $div .= '</div>';
    

    
        $div .= '<div class="am-working-hours-buttons">';
        $div .= '<div class="align-left">';
        $div .= '<button id="days-cancel-button" type="button" class="el-button el-button--default el-button--small">';
        $div .= '<span>Anuluj</span>';
        $div .= '</button>';
        $div .= '<button id="save-free-days" type="button" class="el-button el-button--primary el-button--small">';
        $div .= '<span>Zachowaj</span>';
        $div .= '</button>';
        $div .= '</div>';
        $div .= '</div>';
        $div .= '</div>';
    
        wp_send_json_success($div);
    }
    

    function admin_add_edit_day_window() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to save this data.');
            return;
        }
    
        if (isset($_POST['count']) && is_numeric($_POST['count'])) {
            $count = sanitize_text_field($_POST['count']);
            $option_key = 'zwik_day_' . $count;
            $option_name = 'zwik_name_' . $count;
    
            $day = get_option($option_key, '');
            $name = get_option($option_name, '');
    
            $output = sprintf(
                '<div class="am-add-period-days">
                    <div class="days-frame">
                        <div class="date-free-frame">
                            <input value="%s" id="date-selected" placeholder="Wybierz datę"/>
                        </div>
                        <div class="date-name-frame">
                            <input value="%s" id="name-date-selected" placeholder="Nazwa dnia wolnego"/>
                        </div>
                    </div>
                    <div class="am-working-hours-buttons">
                        <div class="align-left">
                            <button id="days-cancel-button" type="button" class="el-button el-button--default el-button--small">
                                <span>Anuluj</span>
                            </button>
                            <button id="save-free-days-edit" type="button" class="el-button el-button--primary el-button--small">
                                <span>Zachowaj</span>
                            </button>
                        </div>
                    </div>
                </div>',
                esc_attr($day),
                esc_attr($name)
            );
    
            wp_send_json_success($output);
        } else {
            wp_send_json_error('Invalid data.');
        }
    }
    

    function admin_add_edit_window() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to save this data.');
            return;
        }
    
        if (isset($_POST['times']) && is_string($_POST['times'])) {
            $time_range = sanitize_text_field($_POST['times']);
    
            list($starting_time, $ending_time) = explode(' - ', $time_range);
    
            $output = sprintf(
                '<div class="am-add-period">
                    <form class="el-form el-form--label-top">
                        <div class="el-row el-row--flex">
                            <div class="el-col el-col-24">
                                <div class="el-row el-row--flex timetable-container">
                                    <div class="el-col el-col-24">
                                        <span>Godziny pracy</span>
                                    </div>
                                    <div class="el-col el-col-24">
                                        <div class="el-form-item is-required">
                                            <div class="el-form-item__content">
                                                <div class="el-date-editor el-input el-input--mini el-input--prefix el-input--suffix el-date-editor--time-select" style="margin-bottom: 12px;">
                                                    <input value="%s" id="starting_time" type="text" autocomplete="off" name="" class="el-input__inner starting_time">
                                                    <span class="el-input__prefix">
                                                        <span class="el-input__icon el-icon-time"></span>
                                                    </span>
                                                    <span class="el-input__suffix">
                                                        <span class="el-input__suffix-inner"><span class="el-input__icon"></span></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="el-col el-col-24">
                                        <div class="el-form-item is-required">
                                            <div class="el-form-item__content">
                                                <div class="el-date-editor el-input el-input--mini el-input--prefix el-input--suffix el-date-editor--time-select">
                                                    <input value="%s" id="ending_time" type="text" autocomplete="off" name="" class="el-input__inner ending_time">
                                                    <span class="el-input__prefix">
                                                        <span class="el-input__icon el-icon-time"></span>
                                                    </span>
                                                    <span class="el-input__suffix">
                                                        <span class="el-input__suffix-inner"><span class="el-input__icon"></span></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="am-working-hours-buttons">
                            <div class="align-left">
                                <button id="hours-cancel-button" type="button" class="el-button el-button--default el-button--small">
                                    <span>Anuluj</span>
                                </button>
                                <button id="save-edited-hours" type="button" class="el-button el-button--primary el-button--small">
                                    <span>Zachowaj</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>',
                esc_attr($starting_time),
                esc_attr($ending_time)
            );
    
            wp_send_json_success($output);
        } else {
            wp_send_json_error('Invalid data.');
        }
    }
    

    function add_hours_On_Fly() {

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to save this data.');
            return;
        }
    
        if (isset($_POST['times']) && is_array($_POST['times'])) {

    
            foreach ($_POST['times'] as $day => $time_intervals) {
    
                if (isset($time_intervals) && !empty($time_intervals)) {
                    foreach ($time_intervals as $time) {
                        $time_combined = '';
    
                        if (isset($time['starting_time']) && isset($time['ending_time'])) {
                            $starting_time = sanitize_text_field($time['starting_time']);
                            $ending_time = sanitize_text_field($time['ending_time']);
                            $time_combined = $starting_time . ' - ' . $ending_time;
                        }
    
                        if (!empty($time_combined)) {
                            $div = '<div class="added-hours">' . $starting_time . ' - ' . $ending_time . '<div class="buttons-frame"><button class="delete-hour">Usuń</button><button class="edit-hour">Edytuj</button"></div></div>';
                            wp_send_json_success($div);
                        }
                    }
                }
            }
    
            wp_send_json_success('Times added successfully.');
        } else {
            wp_send_json_error('Invalid data.');
        }
    }

    function add_days_On_Fly() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to save this data.');
            return;
        }

        if (isset($_POST['countDays']) && is_numeric($_POST['countDays'])) {
            $count = sanitize_text_field($_POST['countDays']);
    
            $day = get_option('zwik_day_' . $count, '');
            $day_name = get_option('zwik_name_' . $count, '');
    

            if (!empty($day) && !empty($day_name)) {
                $div = '<div datasrc="' . esc_attr($count) . '" class="added-hours">';
                $div .= '<div class="day-set">' . esc_html($day) . '</div>';
                $div .= '<div class="say-set-name">' . esc_html($day_name) . '</div>';
                $div .= '<div class="buttons-frame">';
                $div .= '<button class="delete-days">Usuń</button>';
                $div .= '<button class="edit-days">Edytuj</button>';
                $div .= '</div>';
                wp_send_json_success($div);
            } else {
                wp_send_json_error('Invalid data. Day or name is empty.');
            }
        } else {
            wp_send_json_error('Invalid data.');
        }
    }
    

    function save_weekly_times() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to save this data.');
            return;
        }
    
        if (isset($_POST['times']) && is_array($_POST['times'])) {
            $days_translation = array(
                'Poniedziałek' => 'monday',
                'Wtorek' => 'tuesday',
                'Środa' => 'wednesday',
                'Czwartek' => 'thursday',
                'Piątek' => 'friday',
                'Sobota' => 'saturday',
                'Niedziela' => 'sunday',
            );
    
            foreach ($_POST['times'] as $day => $time_intervals) {
                $translated_day = isset($days_translation[$day]) ? $days_translation[$day] : strtolower($day);
                $count = $_POST['count'];
    
                if (isset($time_intervals) && !empty($time_intervals)) {
                    foreach ($time_intervals as $time) {
                        $time_combined = '';
    
                        if (isset($time['starting_time']) && isset($time['ending_time'])) {
                            $starting_time = sanitize_text_field($time['starting_time']);
                            $ending_time = sanitize_text_field($time['ending_time']);
                            $time_combined = $starting_time . ' - ' . $ending_time;
                        }
    
                        if (!empty($time_combined)) {
                            update_option('zwik_time_' . $translated_day . '_' . $count, $time_combined);
                            error_log('Saved time option for ' . $translated_day . ' as zwik_time_' . $translated_day . '_' . $count . ': ' . $time_combined);
                        }
                    }
                }
            }
    
            wp_send_json_success('Times saved successfully.');
        } else {
            wp_send_json_error('Invalid data.');
        }
    }

    function save_days_times() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to save this data.');
            return;
        }

        if (isset($_POST['countDays'], $_POST['date'], $_POST['dayName']) && !empty($_POST['countDays']) && !empty($_POST['date']) && !empty($_POST['dayName'])) {
            $count = sanitize_text_field($_POST['countDays']);
            $date = sanitize_text_field($_POST['date']);
            $dayName = sanitize_text_field($_POST['dayName']);
            
            error_log("Received count: $count");
            error_log("Received date: $date");
            error_log("Received dayName: $dayName");
    
            update_option('zwik_day_' . $count, $date);
            update_option('zwik_name_' . $count, $dayName);
    
            error_log("Saved day: $date with name: $dayName for zwik_day_$count and zwik_name_$count");
    
            wp_send_json_success('Days saved successfully.');
        } else {
            wp_send_json_error('Invalid or missing data.');
        }
    }

    function delete_weekly_times() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to delete this data.');
            return;
        }
    
        if (isset($_POST['day']) && !empty($_POST['day']) && isset($_POST['count']) && is_numeric($_POST['count'])) {
            $days_translation = array(
                'Poniedziałek' => 'monday',
                'Wtorek' => 'tuesday',
                'Środa' => 'wednesday',
                'Czwartek' => 'thursday',
                'Piątek' => 'friday',
                'Sobota' => 'saturday',
                'Niedziela' => 'sunday',
            );
    
            $day = sanitize_text_field($_POST['day']);
            $translated_day = isset($days_translation[$day]) ? $days_translation[$day] : strtolower($day);
            $count = intval($_POST['count']);
    
            if ($count === 1) {
                $options_cleared = true;
                for ($i = 1; $i <= 3; $i++) {
                    $option_key = 'zwik_time_' . $translated_day . '_' . $i;
                    if (!delete_option($option_key)) {
                        error_log('Failed to delete option: ' . $option_key);
                        $options_cleared = false;
                    } else {
                        error_log('Deleted time option: ' . $option_key);
                    }
                }
    
                if ($options_cleared) {
                    wp_send_json_success('All time options for ' . $translated_day . ' cleared successfully.');
                } else {
                    wp_send_json_error('Failed to clear all time options for ' . $translated_day . '.');
                }
            } else {
                $option_key = 'zwik_time_' . $translated_day . '_' . $count;
    
                if (delete_option($option_key)) {
                    error_log('Deleted time option: ' . $option_key);
                    wp_send_json_success('Time option deleted successfully.');
                } else {
                    wp_send_json_error('Failed to delete time option: ' . $option_key);
                }
            }
        } else {
            wp_send_json_error('Invalid data.');
        }
    }

    function delete_days_times() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'settings_loading_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to delete this data.');
            return;
        }
    
        if (isset($_POST['count']) && is_numeric($_POST['count'])) {
            $count = intval($_POST['count']);
    
            $option_key = 'zwik_day_' . $count;
            $option_name = 'zwik_name_' . $count;
    
            $day_deleted = delete_option($option_key);
            $name_deleted = delete_option($option_name);
    
            if ($day_deleted && $name_deleted) {
                error_log('Deleted time options: ' . $option_key . ' and ' . $option_name);
                wp_send_json_success('Day and name options deleted successfully.');
            } else {

                error_log('Failed to delete options: ' . $option_key . ' or ' . $option_name);
                wp_send_json_error('Failed to delete options.');
            }
        } else {
            wp_send_json_error('Invalid data.');
        }
    }
    
}

new Zwik_panel();