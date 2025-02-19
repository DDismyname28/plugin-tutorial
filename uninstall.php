<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    # code...
    exit();
}

if (!get_option('option_settings_dev')) {
    # code...
    delete_option('option_settings_dev');
}
