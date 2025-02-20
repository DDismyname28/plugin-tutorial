<?php
/*
Plugin Name: Custom Settings Page
Description: A simple plugin to create a custom settings page using the Settings API.
Version: 1.0
Author: Your Name
*/

add_action('admin_menu', 'render_custom_settings_page');
add_action('admin_init', 'initialize_settings_sections');
add_action('wp_footer', 'render_settings_on_page');
add_action('admin_enqueue_scripts', 'enqueue_custom_media_scripts');

register_activation_hook(__FILE__, 'initialize_default_settings');
register_deactivation_hook(__FILE__, 'remove_default_settings');

function initialize_default_settings() {
    if (!get_option('dev_custom_settings')) {
        $settings = array(
            'enable' => false,
            'color' => '#cecece',
            'message' => 'This is a paragraph.'
        );
        add_option('dev_custom_settings', $settings);
    }
}

function remove_default_settings() {
    delete_option('dev_custom_settings');
}

function enqueue_custom_media_scripts() {
    // Only enqueue on the settings page
    if (isset($_GET['page']) && $_GET['page'] === 'custom-settings-page') {
        wp_enqueue_media(); // This function loads the necessary media scripts
    }
}

function render_settings_on_page() {
    $options = get_option('dev_custom_settings');
    if ($options['enable']) {
?>
        <div style="background-color: <?php echo esc_attr($options['color']); ?>;"><?php echo esc_html($options['message']); ?></div>
    <?php
    }
}

function render_custom_settings_page() {
    add_options_page('Custom Settings Page', 'Custom Settings Page', 'manage_options', 'custom-settings-page', 'custom_settings_page');
}

function custom_settings_page() {
    ?>
    <div class="wrap">
        <div class="settings-notices">
            <?php
            // Display any settings errors related to 'ch3api_options'
            settings_errors('error-admin-notices');
            ?>
        </div>

        <form action="options.php" method="POST">
            <?php
            settings_fields('custom_settings_group');
            do_settings_sections('custom-settings-page');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
<?php
}

function initialize_settings_sections() {
    register_setting('custom_settings_group', 'dev_custom_settings', ['sanitize_callback' => 'sanitize_all_input_fields']);

    add_settings_section('custom-settings-section', 'Custom Settings Section', 'custom_settings_section_description_cb', 'custom-settings-page');

    add_settings_field('custom-checkbox-field', 'Enable or Disable Custom Settings', 'render_checkbox_field', 'custom-settings-page', 'custom-settings-section');
    add_settings_field('custom-color-field', 'Enter Hexadecimal Color', 'render_color_field', 'custom-settings-page', 'custom-settings-section');
    add_settings_field('custom-message-field', 'Enter Text Message', 'render_textarea_field', 'custom-settings-page', 'custom-settings-section');
    add_settings_field('custom-select-field', 'Select one post', 'render_select_field', 'custom-settings-page', 'custom-settings-section');

    add_settings_field('custom-images-field', 'Upload Images', 'render_images_uploader', 'custom-settings-page', 'custom-settings-section');
}

function custom_settings_section_description_cb() {
    echo 'This is the settings for the custom page.';
}

function render_select_field() {
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1
    );

    $posts = [];
    // The Query.
    $query = new WP_Query($args);
    // The Loop.
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $posts[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
            ];
        }
    } else {
        $posts[] = esc_html_e('Sorry, no posts have been created. Create one');
    }
    // Restore original Post Data.
    wp_reset_postdata();

    $html = ' <select name="dev_custom_settings[post]" id="">';
    foreach ($posts as $post) {
        $html .= '<option id="' . $post['id'] . '" value="' . $post['id'] . '">' . $post['title'] . '</option>';
    }
    $html .= '</select>';

    echo $html;
}

function render_checkbox_field() {
    $options = get_option('dev_custom_settings');
    $checked = $options['enable'] ? 'checked' : '';
    echo '<input type="checkbox" name="dev_custom_settings[enable]" value="1"' . $checked . '>';
}

function render_color_field() {
    $options = get_option('dev_custom_settings');
    echo '<input type="text" name="dev_custom_settings[color]" value="' . esc_attr($options["color"]) . '">';
}

function render_textarea_field() {
    $options = get_option('dev_custom_settings');
    echo '<textarea name="dev_custom_settings[message]" cols="30" rows="5">' . esc_html($options["message"]) . '</textarea>';
}

function render_images_uploader() {
    $options = get_option('dev_custom_settings');
    $images = isset($options['images']) ? $options['images'] : []; // Get the saved image URLs
?>
    <input type="hidden" name="dev_custom_settings[images]" id="images_url" value="<?php echo esc_attr(implode(',', $images)); ?>">
    <input type="button" id="upload_images_button" class="button" value="Upload Images">
    <br>

    <div id="uploaded_images">
        <?php if ($images): ?>
            <?php foreach ($images as $image): ?>
                <div class="uploaded_image" style="display: inline-block; margin-right: 10px;">
                    <img src="<?php echo esc_url($image); ?>" style="max-width: 100px; height: auto;">
                    <button type="button" class="remove_image_button" data-image-url="<?php echo esc_url($image); ?>">Remove</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var mediaUploader;

            $('#upload_images_button').click(function(e) {
                e.preventDefault();

                // If the uploader object has already been created, reopen the dialog
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                // Extend the wp.media object to allow multiple selections
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Choose Images',
                    button: {
                        text: 'Use these images'
                    },
                    multiple: true // Allow multiple files
                });

                // When images are selected, run a callback
                mediaUploader.on('select', function() {
                    var selection = mediaUploader.state().get('selection');
                    var selectedImages = [];
                    selection.each(function(attachment) {
                        selectedImages.push(attachment.attributes.url); // Get the URL of each selected image
                    });

                    // Update the hidden field with the selected image URLs
                    var currentImages = $('#images_url').val().split(',');
                    currentImages = currentImages.concat(selectedImages);
                    $('#images_url').val(currentImages.join(','));

                    // Display the images
                    updateUploadedImagesDisplay();
                });

                // Open the uploader dialog
                mediaUploader.open();
            });

            // Update the display of uploaded images
            function updateUploadedImagesDisplay() {
                var imageUrls = $('#images_url').val().split(',');
                var displayHtml = '';
                imageUrls.forEach(function(url) {
                    displayHtml += '<div class="uploaded_image" style="display: inline-block; margin-right: 10px;">' +
                        '<img src="' + url + '" style="max-width: 100px; height: auto;">' +
                        '<button type="button" class="remove_image_button" data-image-url="' + url + '">Remove</button>' +
                        '</div>';
                });
                $('#uploaded_images').html(displayHtml);
            }

            // Remove an image when the remove button is clicked
            $(document).on('click', '.remove_image_button', function() {
                var imageUrl = $(this).data('image-url');
                var currentImages = $('#images_url').val().split(',').filter(function(url) {
                    return url !== imageUrl;
                });
                $('#images_url').val(currentImages.join(','));
                updateUploadedImagesDisplay();
            });
        });
    </script>
<?php
}

function sanitize_all_input_fields($inputs) {
    if (isset($inputs['enable'])) {
        $inputs['enable'] = true;
    } else {
        $inputs['enable'] = false;
    }

    if (isset($inputs['message'])) {
        $inputs['message'] = sanitize_text_field($inputs['message']);
    }

    if (isset($inputs['color'])) {
        $sanitized_color = sanitize_hex_color($inputs['color']);
        if (!$sanitized_color) {
            add_settings_error('error-admin-notices', 'color-checkbox-error', 'The inputted Hexadecimal color is not valid.', 'warning');
            $options = get_option('dev_custom_settings');
            $inputs['color'] = $options['color'];
        } else {
            $inputs['color'] = $sanitized_color;
        }
    }

    if (isset($inputs['images'])) {
        if (isset($inputs['images'])) {
            $inputs['images'] = array_map('esc_url_raw', explode(',', $inputs['images']));
        }
    }

    return $inputs;
}
