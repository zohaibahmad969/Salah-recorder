<?php
/*
Plugin Name: Salah Recorder OOP
Description: A plugin to record daily salah using OOP.
Version: 1.0
Author: Zohaib Ahmad
*/

class Salah_Recorder_Plugin {
    private $table_name; // Store the table name

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'salah_records'; // Set the table name

        // Register activation hook to create the database table
        register_activation_hook(__FILE__, array($this, 'create_table'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Shortcode to display the salah recording form
        add_shortcode('salah_recorder_form', array($this, 'display_form'));

        // Shortcode to display the user's salah history
        add_shortcode('salah_history', array($this, 'display_history'));

        // AJAX handler for marking salah as read
        add_action('wp_ajax_mark_salah_as_read', array($this, 'mark_salah_as_read_callback'));
        add_action('wp_ajax_nopriv_mark_salah_as_read', array($this, 'mark_salah_as_read_callback')); // For non-logged-in users
    }

    // Create the database table on plugin activation
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            salah_date date NOT NULL,
            fajr tinyint(1) DEFAULT 0,
            dhuhr tinyint(1) DEFAULT 0,
            asr tinyint(1) DEFAULT 0,
            maghrib tinyint(1) DEFAULT 0,
            isha tinyint(1) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql); // Execute the SQL query to create the table
    }

    // Enqueue CSS and JavaScript files
    public function enqueue_scripts() {
        wp_enqueue_style('salah-recorder-style', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('salah-recorder-script', plugin_dir_url(__FILE__) . 'js/main.js', array('jquery'), null, true);
        
        wp_localize_script('salah-recorder-script', 'salah_recorder_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    // Display the salah recording form
    public function display_form() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            global $wpdb;
            $table_name = $wpdb->prefix . 'salah_records';
    
            $salah_date = date('Y-m-d'); // Current date
            $history = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE user_id = %d AND salah_date = %s",
                    $user_id,
                    $salah_date
                )
            );
            ob_start();
            ?>
            <form id="salah-recorder-form">
                <div class="salah-row">
                    <div class="salah-label">Fajr:</div>
                    <?php if ($history && $history->fajr) : ?>
                        <button type="button" class="mark-as-read success" data-salah="fajr">Marked as Read</button>
                    <?php else : ?>
                        <button type="button" class="mark-as-read" data-salah="fajr">Mark as Read</button>
                    <?php endif; ?>
                </div>
                <div class="salah-row">
                    <div class="salah-label">Dhuhr:</div>
                    <?php if (!empty($history) && $history->dhuhr) : ?>
                        <button type="button" class="mark-as-read success" data-salah="dhuhr">Marked as Read</button>
                    <?php else : ?>
                        <button type="button" class="mark-as-read" data-salah="dhuhr">Mark as Read</button>
                    <?php endif; ?>
                </div>
                <div class="salah-row">
                    <div class="salah-label">Asr:</div>
                    <?php if (!empty($history) && $history->asr) : ?>
                        <button type="button" class="mark-as-read success" data-salah="asr">Marked as Read</button>
                    <?php else : ?>
                        <button type="button" class="mark-as-read" data-salah="asr">Mark as Read</button>
                    <?php endif; ?>
                </div>
                <div class="salah-row">
                    <div class="salah-label">Maghrib:</div>
                    <?php if (!empty($history) && $history->maghrib) : ?>
                        <button type="button" class="mark-as-read success" data-salah="maghrib">Marked as Read</button>
                    <?php else : ?>
                        <button type="button" class="mark-as-read" data-salah="maghrib">Mark as Read</button>
                    <?php endif; ?>
                </div>
                <div class="salah-row">
                    <div class="salah-label">Isha:</div>
                    <?php if (!empty($history) && $history->isha) : ?>
                        <button type="button" class="mark-as-read success" data-salah="isha">Marked as Read</button>
                    <?php else : ?>
                        <button type="button" class="mark-as-read" data-salah="isha">Mark as Read</button>
                    <?php endif; ?>
                </div>
            </form>
            <?php
            return ob_get_clean();
        } else {
            return '<p>Please log in to record salah.</p>';
        }
    }
    

    public function display_history() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            global $wpdb;
            $table_name = $wpdb->prefix . 'salah_records';
    
            $history = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY salah_date DESC", $user_id)
            );
    
            ob_start();
            if (!empty($history)) {
                ?>
                <table class="salah-history">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Fajr</th>
                            <th>Dhuhr</th>
                            <th>Asr</th>
                            <th>Maghrib</th>
                            <th>Isha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record) : ?>
                            <tr>
                                <td><?php echo $record->salah_date; ?></td>
                                <td class="<?php echo $record->fajr ? 'read' : 'not-read'; ?>"><?php echo $record->fajr ? 'Read' : 'Not Read'; ?></td>
                                <td class="<?php echo $record->dhuhr ? 'read' : 'not-read'; ?>"><?php echo $record->dhuhr ? 'Read' : 'Not Read'; ?></td>
                                <td class="<?php echo $record->asr ? 'read' : 'not-read'; ?>"><?php echo $record->asr ? 'Read' : 'Not Read'; ?></td>
                                <td class="<?php echo $record->maghrib ? 'read' : 'not-read'; ?>"><?php echo $record->maghrib ? 'Read' : 'Not Read'; ?></td>
                                <td class="<?php echo $record->isha ? 'read' : 'not-read'; ?>"><?php echo $record->isha ? 'Read' : 'Not Read'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p class="text-center">No records found.</p>';
            }
            return ob_get_clean();
        } else {
            return '<p>Please log in to view salah history.</p>';
        }
    }
    

    // AJAX callback for marking salah as read
    public function mark_salah_as_read_callback() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $salah_name = sanitize_text_field($_POST['salah_name']);
            $salah_date = date('Y-m-d'); // Current date
    
            global $wpdb;
            $table_name = $wpdb->prefix . 'salah_records';
    
            // Check if a record exists for the user and date
            $existing_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND salah_date = %s",
                $user_id,
                $salah_date
            ));
    
            // If no record exists, insert a new one
            if (!$existing_record) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => $user_id,
                        'salah_date' => $salah_date,
                        $salah_name => 1
                    )
                );
            } else {
                // Update the existing record
                $wpdb->update(
                    $table_name,
                    array($salah_name => 1),
                    array('user_id' => $user_id, 'salah_date' => $salah_date)
                );
            }
    
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}

// Initialize the plugin
new Salah_Recorder_Plugin();
