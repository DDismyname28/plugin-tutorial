<?php
/*
Plugin Name: DDismyname Plugin Dev
Plugin URI: http://wordpress.org/plugins/dd/
Description: Something new.
Author: Djofil Demerin
Version: 1.0.0
Author URI: http://wordpress.org/plugins/dd/
*/

register_activation_hook(__FILE__, 'set_default_options');

function set_default_options() {
    if (!get_option('ch3api_options')) {
        add_option(
            'ch3api_options',
            [
                'ga_account_name' => 'UA-00000-0',
                'url' => 'linkedin.com',
            ]
        );
    }

    $options = get_option('ch3api_options', []);

    $new_options = ['debug' => TRUE];

    $merged_options = wp_parse_args($options, $new_options);

    $compare_options = array_diff_key($new_options, $options);

    if (empty($options) || !empty($compare_options)) {
        update_option('ch3api_options', $merged_options);
    }
    return $merged_options;
}

register_deactivation_hook(__FILE__, 'remove_options_settings');

function remove_options_settings() {
    if (get_option('ch3api_options')) {
        delete_option('ch3api_options');
    }
}

add_action('admin_menu', 'plugin_dev_settings_menu');

function plugin_dev_settings_menu() {

    add_menu_page(
        'Plugin Tutorial', // page title (whatever)
        'Plugin Tutorial', // menu title (whatever)
        'manage_options', // necessary capacity
        'plugin-overview-settings', // page slug
        'plugin_tutorial_overview_page_cb', // your function to load page 1 resources
        //a cute icon from developer.wordpress.org/resource/
    );

    add_submenu_page( //Identical to the menu except for the label
        'plugin-overview-settings',  // parent slug
        'Plugin Tutorial Overview', // page title (whatever)
        'Overview',  // Label do submenu (whatever) --> (replaces duplicate My Plugin Name)
        'manage_options', // necessary capacity
        'plugin-overview-settings',   // your function to load page 1 resources
        'plugin_tutorial_overview_page_cb', // function to load page 1 resources again
    );

    add_submenu_page(
        'plugin-overview-settings',  // parent slug
        'Plugin Tutorial Tools', // page title (whatever)
        'Tools',  // Submenu label  (whatever) 
        'manage_options', // necessary capacity
        'plugin-tools-settings',   // page slug
        'plugin_tutorial_tools_main_cb', // your function to load page 2 resources
    );
}

add_action('admin_init', 'ch3api_admin_init');

function plugin_tutorial_overview_page_cb() {
    // Add HTML content to display on the page.
?>
    <div class="wrap">
        <div class="settings-notices">
            <?php
            // Display any settings errors related to 'ch3api_options'
            settings_errors('ga_account_name_notices');
            ?>
        </div>
        <form method="post" action="options.php">
            <?php
            // Add the necessary hidden fields for saving the settings
            settings_fields('plugin_tutorial_overview_group_settings');

            do_settings_sections('plugin-overview-settings');

            submit_button('Save Settings');
            ?>
        </form>
    </div>
<?php
}

function plugin_tutorial_tools_main_cb() {
    // Add HTML content to display on the page.
?>
    <div class="wrap">
        <div class="settings-notices">
            <?php
            // Display any settings errors related to 'ch3api_options'
            settings_errors('debug_notices');
            ?>
        </div>

        <form method="post" action="options.php">
            <?php
            // Add the necessary hidden fields for saving the settings
            settings_fields('plugin_tutorial_overview_group_settings');

            do_settings_sections('plugin-tools-settings');

            submit_button('Save Settings');
            ?>
        </form>
    </div>
<?php
}



function ch3api_admin_init() {

    register_setting('plugin_tutorial_overview_group_settings', 'ch3api_options', ['sanitize_callback' => 'validate_all_input_settings']);

    // Overview Settings
    add_settings_section('overview_main_section', 'Overview Settings', 'plugin_tutorial_overview_section_description_cb', 'plugin-overview-settings');
    add_settings_field('ga_account_name', 'Account Name', 'display_ga_account_name_text_field', 'plugin-overview-settings', 'overview_main_section');

    // Tools Settings
    add_settings_section('tools_main_section', 'Tools Settings', 'plugin_tutorial_tools_section_description_cb', 'plugin-tools-settings');
    add_settings_field('plugin-tools-settings', 'Enable Debug', 'display_debug_checkbox_field', 'plugin-tools-settings', 'tools_main_section');
}

function plugin_tutorial_overview_section_description_cb() {
    echo 'Please enter your Overview configuration file in the inputs below.';
}

function plugin_tutorial_tools_section_description_cb() {
    echo 'Please enter your Tools configuration file in the inputs below.';
}

function display_ga_account_name_text_field($args) {
    // Get the 'ch3api_options' array from the database
    $option_value = get_option('ch3api_options');

    // Retrieve the specific option (e.g., 'ga_account_name') from the array
    $value = isset($option_value['ga_account_name']) ? esc_attr($option_value['ga_account_name']) : '';

    // Generate the input field with the correct name and value attributes
    echo '<input type="text" value="' . $value . '" name="ch3api_options[ga_account_name]" />';
}

function display_debug_checkbox_field($args) {
    // Get the 'ch3api_options' array from the database
    $option_value = get_option('ch3api_options');

    // Retrieve the specific option (e.g., 'debug') from the array
    // If the 'debug' option is set to '1', checkbox will be checked; otherwise, it will be unchecked
    $value = isset($option_value['debug']) ? $option_value['debug'] : 0; // default to 0 if not set

    // Check if the checkbox should be checked or not
    $checked = ($value == 1) ? 'checked' : '';

    // Generate the checkbox input with the correct name and value attributes
    echo '<input type="checkbox" name="ch3api_options[debug]" value="1" ' . $checked . ' />';
}


function validate_all_input_settings($input) {
    // Get the current options array from the database
    $options = get_option('ch3api_options');

    // If the input has a value for ga_account_name, update it in the options array
    if (isset($input['ga_account_name'])) {
        $options['ga_account_name'] = sanitize_text_field($input['ga_account_name']);

        add_settings_error(
            'ga_account_name_notices',            // Settings group
            'ga_account_name-text-field-updated',      // Error code (unique identifier)
            'Account has been updated.', // Error message
            'updated'                      // Type of error (you can use 'error', 'updated', or 'warning')
        );
    }

    // If the 'debug' checkbox was checked, set it to 1; otherwise, set it to 0
    if (isset($input['debug'])) {
        // If 'debug' is set in the form, it was checked, so set to 1
        $options['debug'] = 1;

        add_settings_error(
            'debug_notices',            // Settings group
            'debug-checkbox-updated',      // Error code (unique identifier)
            'Debug option is enabled.', // Error message
            'updated'                      // Type of error (you can use 'error', 'updated', or 'warning')
        );
    } else {
        // If 'debug' is not set, it means the checkbox was unchecked, so set to 0
        $options['debug'] = 0;

        add_settings_error(
            'debug_notices',            // Settings group
            'debug-checkbox-warning',      // Error code (unique identifier)
            'Debug option must be enabled for troubleshooting.', // Error message
            'warning'                      // Type of error (you can use 'error', 'updated', or 'warning')
        );
    }

    return $options; // Return the updated options array
}
