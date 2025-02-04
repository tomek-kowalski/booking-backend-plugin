<?php
namespace StartBooking;

if (!defined('WPINC')) {
    die;
}

require_once ZWIK_PATH . 'zwik_booking.php';

class Zwik_API extends Zwik_Booking
{
    public function __construct()
    {
        parent::__construct();
        add_action('rest_api_init', [$this, 'register_api_booking']);
        add_action('rest_api_init', [$this, 'register_api_form_settings']);
        add_action('rest_api_init', [$this, 'register_api_form_submission']);
    }

    public function register_api_booking()
    {
        register_rest_route('custom/v1', '/sse', [
            'methods' => 'GET',
            'callback' => [$this, 'custom_sse_callback'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function register_api_form_settings() {
        register_rest_route('custom/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'custom_settings_callback'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function register_api_form_submission() {
        register_rest_route('custom/v1', '/submit', [
            'methods' => 'POST',
            'callback' => [$this, 'custom_submission_callback'],
            'permission_callback' => '__return_true',
        ]);
    }


    public function custom_submission_callback(\WP_REST_Request $request) {
        global $wpdb;
    
        $target = sanitize_text_field($request->get_param('target'));
        $desk = sanitize_text_field($request->get_param('desk')) ?: '';
        $date = sanitize_text_field($request->get_param('date'));
        $name = sanitize_text_field($request->get_param('name'));
        $subject = sanitize_text_field($request->get_param('subject')) ?: '';
        $control_number = sanitize_text_field($request->get_param('control_number'));
        $status = sanitize_text_field($request->get_param('status'));
    
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $date)) {
            $dateTime = new \DateTime($date);
            $dateTime->setTimezone(new \DateTimeZone('Europe/Warsaw'));
    
            $formattedDate = $dateTime->format('Y-m-d');
            $formattedTime = $dateTime->format('H:i');
        }
    
        if ($target === 'UM') {
            $desk = 'Stanowisko 4';
            $nextAvailableSlot = $this->get_next_available_time_slot($formattedDate, $formattedTime);
            if ($nextAvailableSlot) {
                $date = "{$formattedDate} {$nextAvailableSlot}";
            }
            $date = $this->ensure_unique_time_slot($formattedDate, $date);
        }
    
        if ($target === "ZWIK") {
            $role_users = get_users(array(
                'role' => 'harmonogram_manager',
                'fields' => array('ID')
            ));
            
            $userCount = count($role_users);
            
            if ($userCount === 1) {
                $desk = 'Stanowisko 1';
                $nextAvailableSlot = $this->get_next_available_time_slot($formattedDate, $formattedTime);
                if ($nextAvailableSlot) {
                    $date = "{$formattedDate} {$nextAvailableSlot}";
                }
                $date = $this->ensure_unique_time_slot($formattedDate, $date);
            } else {
                if ($userCount === 2) {
                    $availableDesks = ['Stanowisko 1', 'Stanowisko 2'];
                } elseif ($userCount === 3) {
                    $availableDesks = ['Stanowisko 1', 'Stanowisko 2', 'Stanowisko 3'];
                }
        
                $nextAvailableSlot = $this->get_next_available_time_slot_for_zwik($formattedDate, $formattedTime);
                if ($nextAvailableSlot) {
                    $date = "{$formattedDate} {$nextAvailableSlot}";
                }
        
                $bookedDesks = $wpdb->get_col($wpdb->prepare(
                    "SELECT desk FROM {$wpdb->prefix}zwik_booking WHERE DATE(date) = %s AND TIME(date) = %s",
                    $formattedDate,
                    $nextAvailableSlot
                ));
        
                $remainingDesks = array_diff($availableDesks, $bookedDesks);
        
                if (!empty($remainingDesks)) {
                    $desk = reset($remainingDesks);
                } else {
                    $nextAvailableSlot = $this->ensure_unique_time_slot($formattedDate, $date);
                    $desk = 'Stanowisko 1';
                }
            }
        }
    
        $table_name = $wpdb->prefix . 'zwik_booking';
        $result = $wpdb->insert(
            $table_name,
            [
                'target' => $target,
                'desk' => $desk,
                'date' => $date,
                'name' => $name,
                'subject' => $subject,
                'control_number' => $control_number,
                'status' => $status,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    
        if ($result === false) {
            error_log('Database Insert Error: ' . $wpdb->last_error);
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Database error: ' . $wpdb->last_error
            ], 500);
        }
    
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Data successfully saved',
            'insert_id' => intval($wpdb->insert_id)
        ], 200);
    }
    
    
    
    private function get_next_available_time_slot_for_zwik($date, $currentTime) {
        global $wpdb;
    
        $availableSlots = $this->get_available_slots_for_date($date);
    
        $bookedSlots = $wpdb->get_col($wpdb->prepare(
            "SELECT DATE_FORMAT(date, '%H:%i') FROM {$wpdb->prefix}zwik_booking WHERE DATE(date) = %s",
            $date
        ));
        error_log('Booked Slots from DB: ' . print_r($bookedSlots, true));
    
        $now = new \DateTime('now', new \DateTimeZone('Europe/Warsaw'));
        $nowTime = $now->format('H:i');
    
        foreach ($availableSlots as $slot) {
            [$startTime, $endTime] = explode(' - ', $slot);
    
            if ($startTime < $nowTime || in_array($startTime, $bookedSlots)) {
                //error_log("Skipping past/booked slot: $startTime");
                continue;
            }
    
            $alreadyBooked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}zwik_booking WHERE date = %s",
                "$date $startTime"
            ));
    
            if ($alreadyBooked < 3) {
                error_log("New time slot selected: $startTime");
                return $startTime;
            }
        }
    
        error_log("No available future time slot found for date: {$date}");
        return null;
    }
    

    private function get_next_available_time_slot($date, $currentTime) {
        global $wpdb;
    
        $availableSlots = $this->get_available_slots_for_date($date);
    
        $bookedSlots = $wpdb->get_col($wpdb->prepare(
            "SELECT DATE_FORMAT(date, '%H:%i') FROM {$wpdb->prefix}zwik_booking WHERE DATE(date) = %s",
            $date
        ));
        error_log('Booked Slots from DB: ' . print_r($bookedSlots, true));
    

        $now = new \DateTime('now', new \DateTimeZone('Europe/Warsaw'));
        $nowTime = $now->format('H:i');
    

        foreach ($availableSlots as $slot) {
            [$startTime, $endTime] = explode(' - ', $slot);
   
            if ($startTime < $nowTime || in_array($startTime, $bookedSlots)) {
                error_log("Skipping past/booked slot: $startTime");
                continue;
            }
    
            $alreadyBooked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}zwik_booking WHERE date = %s",
                "$date $startTime"
            ));
    
            if ($alreadyBooked == 0) {
                error_log("New time slot selected: $startTime");
                return $startTime;
            }
        }
    
        error_log("No available future time slot found for date: {$date}");
        return null;
    }
    
    private function get_available_slots_for_date($date) {
        $calendar_data = $this->custom_settings_callback()->get_data();

        foreach ($calendar_data['available_time_range_bok'] as $month => $days) {
            foreach ($days as $day => $dates) {
                if (isset($dates[$date]) && is_array($dates[$date])) {
                    return array_filter($dates[$date]);
                }
            }
        }
    
        return [];
    }

    private function ensure_unique_time_slot($date, $selectedDateTime) {
        global $wpdb;
    
        $selectedTime = date('H:i', strtotime($selectedDateTime));
    
        $table_name = $wpdb->prefix . 'zwik_booking';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date = %s",
            $selectedDateTime
        ));
    
        if ($existing > 0) {
            error_log("Time slot $selectedDateTime is already taken. Finding next available slot...");
    
            $nextAvailableSlot = $this->get_next_available_time_slot($date, $selectedTime);
            if ($nextAvailableSlot) {
                $newDateTime = "{$date} {$nextAvailableSlot}";
                error_log("New time slot selected: $newDateTime");
                return $newDateTime;
            }
        }
    
        return $selectedDateTime;
    }
    
    

    public function custom_settings_callback() {
        date_default_timezone_set('Europe/Warsaw');
    
        $calendar_range_days = (int)get_option('zwik_duration_calendar');
        $calendar_range_days = max(1, $calendar_range_days);
    
        $booking_start_date_timestamp = strtotime('today midnight');
    
        $calendar_dates = [];
        for ($i = 0; $i < $calendar_range_days; $i++) {
            $calendar_dates[] = date('Y-m-d', strtotime("+{$i} days", $booking_start_date_timestamp));
        }
    
        $duration_bok    = max(10, min(30, (int)get_option('zwik_duration_bok')));
        $duration_other  = max(10, min(30, (int)get_option('zwik_duration_other')));
        $duration_online = max(10, min(30, (int)get_option('zwik_duration_online')));
    
        $daysOff = array_filter(array_map(function ($i) {
            return get_option("zwik_day_$i", '');
        }, range(1, 49)));


        $translated_days_of_week = ['P', 'W', 'S', 'C', 'Pi', 'Sb', 'N'];
        $hours_range = [];
        $days_of_week = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days_of_week as $day) {
            $hours_range[$day] = array_filter(array_map(function ($i) use ($day) {
                return get_option("zwik_time_{$day}_$i", '');
            }, range(1, 3)));
        }
    
        $dates_availabe_bok    = $this->generate_time_slots($calendar_dates, $hours_range, $duration_bok, $daysOff, $translated_days_of_week);
        $dates_availabe_other  = $this->generate_time_slots($calendar_dates, $hours_range, $duration_other, $daysOff, $translated_days_of_week);
        $dates_availabe_online = $this->generate_time_slots($calendar_dates, $hours_range, $duration_online, $daysOff, $translated_days_of_week);
    
        $data = [
            'available_time_range_bok'      => $dates_availabe_bok,
            'available_time_range_other'    => $dates_availabe_other,
            'available_time_range_online'   => $dates_availabe_online,
        ];
    
        return rest_ensure_response($data);
    }
    
    private function generate_time_slots($calendar_dates, $hours_range, $duration, $daysOff, $translated_days_of_week) {
        $time_slots = [];
        $polish_months = [
            1 => 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
            'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
        ];
    
        $normalized_daysOff = array_map(function ($date) {
            return date('Y-m-d', strtotime($date));
        }, $daysOff);
    
        $today = date('Y-m-d');
    
        $calendar_start_date = reset($calendar_dates);
        $start_of_week = date('Y-m-d', strtotime('monday this week', strtotime($calendar_start_date)));
    
        $current_date = $start_of_week;
        $end_date = end($calendar_dates);
        while (strtotime($current_date) <= strtotime($end_date)) {
  
            $day_of_week = strtolower(date('l', strtotime($current_date)));
            $day_index = date('N', strtotime($current_date)) - 1;
            $translated_day = $translated_days_of_week[$day_index];
            $month_number = (int)date('n', strtotime($current_date));
            $month_name = $polish_months[$month_number];
            $year = date('Y', strtotime($current_date));
    
            $formatted_month_year = $month_name . ' ' . $year;
    
            if (!isset($time_slots[$formatted_month_year])) {
                $time_slots[$formatted_month_year] = [];
            }
            if (!isset($time_slots[$formatted_month_year][$translated_day])) {
                $time_slots[$formatted_month_year][$translated_day] = [];
            }
    
            if (strtotime($current_date) < strtotime($today)) {
                $time_slots[$formatted_month_year][$translated_day][$current_date] = [null];
            } elseif (in_array($current_date, $normalized_daysOff)) {
                $time_slots[$formatted_month_year][$translated_day][$current_date] = [null];
            } elseif (isset($hours_range[$day_of_week]) && !empty($hours_range[$day_of_week])) {
                foreach ($hours_range[$day_of_week] as $time_range) {
                    [$start_time, $end_time] = explode(' - ', $time_range);
                    $start_time = strtotime("{$current_date} {$start_time}");
                    $end_time = strtotime("{$current_date} {$end_time}");
    
                    while ($start_time + ($duration * 60) <= $end_time) {
                        $formatted_start = date('H:i', $start_time);
                        $formatted_end = date('H:i', $start_time + ($duration * 60));
    
                        $time_slots[$formatted_month_year][$translated_day][$current_date][] = "{$formatted_start} - {$formatted_end}";
    
                        $start_time += ($duration * 60);
                    }
                }
            } else {
                $time_slots[$formatted_month_year][$translated_day][$current_date] = [];
            }
    
            $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
        }
    
        return $time_slots;
    }


    public function custom_sse_callback() {

    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Content-Type: application/json");
    header("Cache-Control: no-cache");
    header("Connection: keep-alive");

    global $wpdb;
    $table_name = $wpdb->prefix . 'zwik_booking';

    $results = $wpdb->get_results(
        "SELECT id, name, desk, date, status FROM {$table_name} 
        WHERE status IN ('Rozpoczęte', 'Oczekujące') 
        ORDER BY id DESC LIMIT 10"
    );

    $data = [];
    foreach ($results as $row) {
        $data[] = [
            'id'     => $row->id,
            'name'   => $row->name,
            'time'   => $row->date,
            'desk'   => $row->desk,
            'status' => $row->status,
        ];
    }

    return rest_ensure_response($data);
    }

}


new Zwik_API;