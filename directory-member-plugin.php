<?php

/*
Plugin Name: Directory Member Plugin
Description: A directory plugin for members of the company.
Version: 1.0
Author: Djofil Demerin
*/

// Register our defaults or options settings

register_activation_hook(__FILE__, 'register_default_options');
register_deactivation_hook(__FILE__, 'delete_default_options');

function register_default_options() {
    if (!get_option('members_directory_options')) {
        $options = array(
            'facebook' => FALSE,
            'linkedin' => FALSE,
            'description' => FALSE,
            'email' => TRUE,
            'phone' => TRUE
        );
        add_option('members_directory_options', $options);
    }
}

function register_member_post_type() {
    $args = array(
        'labels' => array(
            'name' => 'Team Member',
            'singular_name' => 'Member',
            'menu_name' => 'Member',
            'name_admin_bar' => 'Member',
            'all_items' => 'Team Members',
            'add_new_item' => 'Add new member',
            'add_new' => 'Add team member',
            'featured_image' => 'Member Featured Image',
            'not_found' => 'No members found.'
        ),
        'public' => TRUE,
        'supports' => array('title', 'editor', 'thumbnail')
    );

    register_post_type('team-members', $args);
    flush_rewrite_rules();
}

add_action('init', 'register_member_post_type');

function register_member_metabox_cb() {
    add_meta_box('member-information', 'Member Information', 'render_metaboxes_cb', 'team-members');
}
add_action('add_meta_boxes', 'register_member_metabox_cb');

function render_metaboxes_cb($post) {

    $options = get_option('members_directory_options');

    wp_nonce_field('member_nonce_save_action', 'member_nonce_field');

    if (!empty(get_post_meta($post->ID, 'member_post_meta_values'))) {
        $member_meta_values = get_post_meta($post->ID, 'member_post_meta_values', true);
    }
    echo '<p><strong>Position Title:</strong></p>';
    echo '<input type="text" name="member_position_title_field" value="' . esc_attr(isset($member_meta_values['member_position_title_field']) ? $member_meta_values['member_position_title_field'] : '') . '">';

    if ($options['email'] == TRUE) {
        echo '<p><strong>Email:</strong></p>';
        echo '<input type="text" name="member_email_field" value="' . esc_attr(isset($member_meta_values['member_email_field']) ? $member_meta_values['member_email_field'] : '') . '">';
    }

    if ($options['linkedin'] == TRUE) {
        echo '<p><strong>Linkedin Profile:</strong></p>';
        echo '<input type="text" name="member_linkedin_field" value="' . esc_attr(isset($member_meta_values['member_linkedin_field']) ? $member_meta_values['member_linkedin_field'] : '') . '">';
    }
}

function save_metaboxes_cb($post_id) {
    $post_meta_values = array();

    if (!isset($_POST['member_nonce_field']) || !wp_verify_nonce($_POST['member_nonce_field'], 'member_nonce_save_action')) return $post_id;

    if (isset($_POST['member_position_title_field'])) {
        $post_meta_values['member_position_title_field'] = sanitize_text_field($_POST['member_position_title_field']);
    }

    if (isset($_POST['member_email_field'])) {
        $email = sanitize_email($_POST['member_email_field']);

        if (!is_email($email)) {

            wp_die(__('Error: The email address you entered is invalid. Please correct it and try again.'));
        }

        $post_meta_values['member_email_field'] = $email;
    }

    if (isset($_POST['member_linkedin_field'])) {
        $post_meta_values['member_linkedin_field'] = esc_url_raw($_POST['member_linkedin_field']);
    }

    update_post_meta($post_id, 'member_post_meta_values', $post_meta_values);
}

add_action('save_post', 'save_metaboxes_cb');

function delete_default_options($post) {
    delete_option('members_directory_options');
}

// 1. Add a settings page to admin menu

add_action('admin_menu', 'create_members_directory_menu');

function create_members_directory_menu() {
    add_options_page('Members Settings', 'Members Directory', 'manage_options', 'members-directory-plugin', 'render_members_directory_settings_page_cb');
}

function render_members_directory_settings_page_cb() {
    // Render the main menu settings page
    echo '<div class="wrap">';
    echo '<form action="options.php" method="POST">';

    settings_fields('members_directory_settings_group');
    do_settings_sections('members-directory-plugin');
    submit_button('Save Settings');

    echo '</form>';
    echo '</div>';
}

add_action('admin_init', 'render_members_directory_settings_section_cb');

function render_members_directory_settings_section_cb() {
    // register settings
    register_setting('members_directory_settings_group', 'members_directory_options', ['sanitize_callback' => 'sanitize_all_inputs_cb']);

    // add settings section
    add_settings_section('members-settings', 'Members Directory Settings', 'members_directory_description_cb', 'members-directory-plugin');

    // add fields
    add_settings_field('social_checkbox_fields', 'Enable social fields:', 'render_social_checkbox_fields_cb', 'members-directory-plugin', 'members-settings');
    add_settings_field('description_checkbox_fields', 'Enable description', 'render_description_checkbox_fields_cb', 'members-directory-plugin', 'members-settings');
}

function members_directory_description_cb() {
    echo '<p>This is the main settings section of the members directory.</p> <p>Configure settings to enable on frontend.</p>';
}

function render_social_checkbox_fields_cb() {
    $options = get_option('members_directory_options');

    echo '<span><strong>Facebook: </strong></span><input type="checkbox" name="members_directory_options[facebook]" id="facebook" value="' . esc_attr($options['facebook']) . '"' . checked(1, $options['facebook'], FALSE) . '>';
    echo '<span><strong>Linkedin: </strong></span><input type="checkbox" name="members_directory_options[linkedin]" id="linkedin" value="' . esc_attr($options['linkedin']) . '"' . checked(1, $options['linkedin'], FALSE) . '>';
    echo '<span><strong>Email: </strong></span><input type="checkbox" name="members_directory_options[email]" id="email" value="' . esc_attr($options['email']) . '"' . checked(1, $options['email'], FALSE) . '>';
}

function render_description_checkbox_fields_cb() {
    $options = get_option('members_directory_options');

    echo '<input type="checkbox" name="members_directory_options[description]" id="description" value="' . esc_attr($options['description']) . '"' . checked(1, $options['description'], FALSE) . '>';
}

function sanitize_all_inputs_cb($inputs) {
    if (isset($inputs['facebook'])) {
        $inputs['facebook'] = TRUE;
    } else {
        $inputs['facebook'] = FALSE;
    }

    if (isset($inputs['linkedin'])) {
        $inputs['linkedin'] = TRUE;
    } else {
        $inputs['linkedin'] = FALSE;
    }

    if (isset($inputs['email'])) {
        $inputs['email'] = TRUE;
    } else {
        $inputs['email'] = FALSE;
    }

    if (isset($inputs['description'])) {
        $inputs['description'] = TRUE;
    } else {
        $inputs['description'] = FALSE;
    }

    return $inputs;
}

function render_members_profile($atts) {

    /* $atts = shortcode_atts(array(
        'rows' => 1,
        'columns' => 1
    ), $atts);

    $html = '';
    for ($i = 0; $i < $atts['rows']; $i++) {
        $html .= '<div style="display:flex;justify-content: space-evenly;align-items: center;" class="' . $i . '">';
        for ($x = 0; $x < $atts['columns']; $x++) {
            $html .= '<div style="width:100px;height:100px;background:#000;color:#fff;display:flex;align-items: center;justify-content:center;" class="column-' . $x . '">' . $x . '';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    return $html; */
    $options = get_option('members_directory_options');

    $args = array(
        'post_type' => 'team-members',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'department',
                'field' => 'slug',
                'terms' => 'ceo',
            ),
        )
    );

    // Initialize the HTML structure for the carousel
    $html = '<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                <ol class="carousel-indicators">';

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $i = 0; // Initialize a counter for the slide index

        // Loop through the posts
        while ($query->have_posts()) {
            $query->the_post();

            // Add a button for the indicator (based on the post index)
            $html .= '<li data-target="#carouselExampleIndicators" data-slide-to="' . $i . '" class="' . ($i === 0 ? 'active' : '') . '"></li>';

            $i++; // Increment the counter for the slide index
        }

        // Close the indicators list
        $html .= '</ol><div class="carousel-inner">';

        // Reset the counter to create the items in the carousel
        $i = 0;

        // Loop through the posts again for the carousel items
        while ($query->have_posts()) {
            $query->the_post();
            if ($options['email'] == TRUE) {
                $members_postmeta = get_post_meta(get_the_ID(), 'member_post_meta_values', TRUE);
                /* var_dump($members_postmeta);
                exit; */
            } else {
                $members_postmeta['member_email_field'] = '';
            }
            // Add a carousel item with the post thumbnail
            $html .= '<div class="item ' . ($i === 0 ? 'active' : '') . '">
                        <img src="' . get_the_post_thumbnail_url(get_the_ID(), array(1024, 250)) . '" alt="' . get_the_title() . '" class="img-responsive center-block">' . $members_postmeta['member_email_field'] . '
                      </div>';
            $i++; // Increment the counter for the slide index
        }

        // Close the carousel-inner div
        $html .= '</div>';
    } else {
        $html .= esc_html__('Sorry, no posts matched your criteria.');
    }

    // Reset the WordPress post data after the custom query
    wp_reset_postdata();

    // Add carousel controls
    $html .= '<a class="left carousel-control" href="#carouselExampleIndicators" role="button" data-slide="prev">
                <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
              </a>
              <a class="right carousel-control" href="#carouselExampleIndicators" role="button" data-slide="next">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
              </a>
            </div>';

    // Return the final HTML
    return $html;
}

add_shortcode('members_profile', 'render_members_profile');

function create_genre_taxonomy() {
    // Register a custom taxonomy for "Genres"
    register_taxonomy(
        'department', // Taxonomy name (slug)
        'team-members', // Custom post type (for example: 'movie')
        array(
            'label' => 'Department', // Label for taxonomy
            'hierarchical' => true, // Makes it hierarchical like categories
            'show_ui' => true, // Show it in the admin panel
            'show_admin_column' => true, // Show it as a column in post list
            'query_var' => true, // Allow queries by taxonomy
            'rewrite' => array('slug' => 'department'), // URL rewrite rule
        )
    );
}
add_action('init', 'create_genre_taxonomy');
