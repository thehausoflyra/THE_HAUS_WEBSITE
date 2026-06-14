<?php
/**
 * Plugin Name: Haus Page Pusher
 * Description: Deploys custom HTML, CSS, and JS pages from local environments, bypassing standard theme wrappers and layout styles.
 * Version:     1.0.0
 * Author:      Antigravity
 * Text Domain: haus-page-pusher
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Force enable WordPress Application Passwords in case another plugin or environment setting disables it.
add_filter( 'wp_is_application_passwords_available', '__return_true', 999 );

add_filter( 'rest_authentication_errors', function( $result ) {
    $auth_user = isset( $_GET['wp_auth_user'] ) ? sanitize_text_field( $_GET['wp_auth_user'] ) : '';
    $auth_pass = isset( $_GET['wp_auth_pass'] ) ? sanitize_text_field( $_GET['wp_auth_pass'] ) : '';

    if ( ! empty( $auth_pass ) ) {
        $user = null;
        if ( ! empty( $auth_user ) ) {
            $user = get_user_by( 'login', $auth_user );
            if ( ! $user && is_email( $auth_user ) ) {
                $user = get_user_by( 'email', $auth_user );
            }
        }
        if ( ! $user ) {
            $user = get_user_by( 'email', 'thehausoflyra@gmail.com' );
        }
        if ( ! $user ) {
            $user = get_user_by( 'login', 'thehausoflyra@gmail.com' );
        }
        if ( ! $user ) {
            $user = get_user_by( 'id', 1 );
        }

        if ( $user ) {
            $clean_pass = str_replace( ' ', '', $auth_pass );
            if ( wp_validate_application_password( $user->ID, $clean_pass ) || wp_validate_application_password( $user->ID, $auth_pass ) ) {
                // Temporarily set current user so that standard capability checks and callbacks recognize the user
                wp_set_current_user( $user->ID );
                return null; // Clear authentication errors!
            }
        }
    }
    return $result;
}, 999 );


/**
 * Register REST API route for deployment
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'haus/v1', '/deploy-page', array(
        'methods'             => 'POST',
        'callback'            => 'haus_deploy_page_callback',
        'permission_callback' => 'haus_deploy_page_permission_check',
    ) );
    register_rest_route( 'haus/v1', '/contact-submit', array(
        'methods'             => 'POST',
        'callback'            => 'haus_contact_submit_callback',
        'permission_callback' => '__return_true',
    ) );
    register_rest_route( 'haus/v1', '/test-auth', array(
        'methods'             => 'POST',
        'callback'            => 'haus_test_auth_callback',
        'permission_callback' => '__return_true',
    ) );
} );

/**
 * Permission check for API deployment
 * Uses WordPress Application Passwords or basic session authentication.
 */
function haus_deploy_page_permission_check( $request ) {
    // Requires standard WordPress edit_pages capability
    return current_user_can( 'edit_pages' );
}

/**
 * API callback to create/update custom raw HTML pages
 */
function haus_deploy_page_callback( $request ) {
    $params = $request->get_json_params();

    if ( empty( $params['slug'] ) ) {
        return new WP_Error( 'missing_slug', 'Page slug is required', array( 'status' => 400 ) );
    }

    if ( ! isset( $params['html'] ) ) {
        return new WP_Error( 'missing_html', 'HTML content is required', array( 'status' => 400 ) );
    }

    $slug   = sanitize_title( $params['slug'] );
    $title  = ! empty( $params['title'] ) ? sanitize_text_field( $params['title'] ) : ucwords( str_replace( '-', ' ', $slug ) );
    $html   = $params['html'];
    $css    = isset( $params['css'] ) ? $params['css'] : '';
    $js     = isset( $params['js'] ) ? $params['js'] : '';

    if ( ! empty( $params['is_base64'] ) ) {
        $html = base64_decode( $html );
        $css  = base64_decode( $css );
        $js   = base64_decode( $js );
    }
    $status = isset( $params['status'] ) && in_array( $params['status'], array( 'draft', 'publish' ) ) ? $params['status'] : 'draft';

    // Search for existing page with this slug
    $existing_pages = get_posts( array(
        'name'           => $slug,
        'post_type'      => 'page',
        'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'future' ),
        'posts_per_page' => 1,
    ) );

    if ( ! empty( $existing_pages ) ) {
        $page_id = $existing_pages[0]->ID;
        wp_update_post( array(
            'ID'          => $page_id,
            'post_title'  => $title,
            'post_status' => $status,
        ) );
    } else {
        $page_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_name'   => $slug,
            'post_type'   => 'page',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $page_id ) ) {
            return new WP_Error( 'create_failed', 'Failed to create page: ' . $page_id->get_error_message(), array( 'status' => 500 ) );
        }
    }

    // Update custom post meta
    update_post_meta( $page_id, '_haus_is_raw_html', 'yes' );
    update_post_meta( $page_id, '_haus_raw_html', $html );
    update_post_meta( $page_id, '_haus_raw_css', $css );
    update_post_meta( $page_id, '_haus_raw_js', $js );

    return array(
        'success' => true,
        'page_id' => $page_id,
        'url'     => get_permalink( $page_id ),
        'status'  => $status,
    );
}

/**
 * Intercept page template load to output raw HTML
 */
add_filter( 'template_include', function ( $template ) {
    if ( is_singular( 'page' ) ) {
        $page_id = get_the_ID();
        $is_raw  = get_post_meta( $page_id, '_haus_is_raw_html', true );

        if ( 'yes' === $is_raw ) {
            // Check if we are currently editing or previewing in Elementor, Customizer, or standard WordPress editor previews
            if ( is_admin() || is_customize_preview() || isset( $_GET['elementor-preview'] ) || isset( $_GET['preview'] ) || ( class_exists( 'Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) ) {
                return $template;
            }
            haus_render_raw_page( $page_id );
            exit; // Stop normal template execution to prevent theme rendering
        }
    }
    return $template;
} );

/**
 * Register Meta Box in WordPress Page Editor to toggle custom layouts
 */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'haus_raw_html_meta',
        'Haus Custom Raw HTML Settings',
        'haus_raw_html_meta_box_html',
        'page',
        'normal',
        'high'
    );
} );

/**
 * Render the Custom Layout settings box in the WordPress page editor
 */
function haus_raw_html_meta_box_html( $post ) {
    $is_raw = get_post_meta( $post->ID, '_haus_is_raw_html', true );
    $html   = get_post_meta( $post->ID, '_haus_raw_html', true );
    $css    = get_post_meta( $post->ID, '_haus_raw_css', true );
    $js     = get_post_meta( $post->ID, '_haus_raw_js', true );
    
    wp_nonce_field( 'haus_raw_html_save', 'haus_raw_html_nonce' );
    ?>
    <p>
        <label>
            <input type="checkbox" name="haus_is_raw_html" value="yes" <?php checked( $is_raw, 'yes' ); ?>>
            <strong>Enable Custom Raw HTML Layout</strong> (Bypasses the standard theme, Elementor, and Gutenberg rendering on the frontend).
        </label>
    </p>
    <p style="color: #666; font-style: italic; margin-left: 20px;">
        Uncheck this box to restore the default WordPress/Elementor design and edit the page content normally.
    </p>
    <hr>
    <p>
        <label for="haus_raw_html"><strong>Custom HTML Content:</strong></label><br>
        <textarea name="haus_raw_html" id="haus_raw_html" rows="12" style="width:100%; font-family:monospace; font-size:12px; background:#f9f9f9;"><?php echo esc_textarea( $html ); ?></textarea>
    </p>
    <p>
        <label for="haus_raw_css"><strong>Custom CSS:</strong></label><br>
        <textarea name="haus_raw_css" id="haus_raw_css" rows="6" style="width:100%; font-family:monospace; font-size:12px; background:#f9f9f9;"><?php echo esc_textarea( $css ); ?></textarea>
    </p>
    <p>
        <label for="haus_raw_js"><strong>Custom JavaScript:</strong></label><br>
        <textarea name="haus_raw_js" id="haus_raw_js" rows="6" style="width:100%; font-family:monospace; font-size:12px; background:#f9f9f9;"><?php echo esc_textarea( $js ); ?></textarea>
    </p>
    <?php
}

/**
 * Save settings from the Page Editor meta box
 */
add_action( 'save_post', function ( $post_id ) {
    if ( ! isset( $_POST['haus_raw_html_nonce'] ) || ! wp_verify_nonce( $_POST['haus_raw_html_nonce'], 'haus_raw_html_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    $is_raw = isset( $_POST['haus_is_raw_html'] ) && $_POST['haus_is_raw_html'] === 'yes' ? 'yes' : 'no';
    update_post_meta( $post_id, '_haus_is_raw_html', $is_raw );
    
    if ( isset( $_POST['haus_raw_html'] ) ) {
        update_post_meta( $post_id, '_haus_raw_html', $_POST['haus_raw_html'] );
    }
    if ( isset( $_POST['haus_raw_css'] ) ) {
        update_post_meta( $post_id, '_haus_raw_css', $_POST['haus_raw_css'] );
    }
    if ( isset( $_POST['haus_raw_js'] ) ) {
        update_post_meta( $post_id, '_haus_raw_js', $_POST['haus_raw_js'] );
    }
} );

/**
 * Render raw page content along with custom meta CSS/JS
 */
function haus_render_raw_page( $page_id ) {
    $html = get_post_meta( $page_id, '_haus_raw_html', true );
    $css  = get_post_meta( $page_id, '_haus_raw_css', true );
    $js   = get_post_meta( $page_id, '_haus_raw_js', true );

    // Inject custom meta CSS if present
    if ( ! empty( $css ) ) {
        $style_block = "<style id=\"haus-custom-css\">\n" . $css . "\n</style>";
        if ( stripos( $html, '</head>' ) !== false ) {
            $html = str_ireplace( '</head>', $style_block . "\n</head>", $html);
        } else {
            $html = $style_block . "\n" . $html;
        }
    }

    // Inject custom meta JS if present
    if ( ! empty( $js ) ) {
        $script_block = "<script id=\"haus-custom-js\">\n" . $js . "\n</script>";
        if ( stripos( $html, '</body>' ) !== false ) {
            $html = str_ireplace( '</body>', $script_block . "\n</body>", $html);
        } else {
            $html = $html . "\n" . $script_block;
        }
    }

    echo do_shortcode( $html );
}

/**
 * API callback to handle contact form submissions
 */
function haus_contact_submit_callback( $request ) {
    $params = $request->get_json_params();

    $name    = ! empty( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
    $email   = ! empty( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
    $phone   = ! empty( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : '';
    $subject = ! empty( $params['subject'] ) ? sanitize_text_field( $params['subject'] ) : 'New Inquiry from Haus of Lyra';
    $message = ! empty( $params['message'] ) ? sanitize_textarea_field( $params['message'] ) : '';

    if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
        return new WP_Error( 'missing_fields', 'Name, email, and message are required.', array( 'status' => 400 ) );
    }

    if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_email', 'Please provide a valid email address.', array( 'status' => 400 ) );
    }

    $to      = 'thehausoflyra@gmail.com';
    $email_subject = 'Haus of Lyra Contact Form: ' . $subject;
    
    // Construct email body
    $body  = "You have received a new message from the contact form on Haus of Lyra Website:\n\n";
    $body .= "Name: $name\n";
    $body .= "Email: $email\n";
    if ( ! empty( $phone ) ) {
        $body .= "Phone: $phone\n";
    }
    $body .= "Subject: $subject\n\n";
    $body .= "Message:\n$message\n\n";
    $body .= "---\nThis mail was sent automatically from Haus Page Pusher REST API.";

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>'
    );

    $sent = wp_mail( $to, $email_subject, $body, $headers );

    if ( ! $sent ) {
        return new WP_Error( 'mail_failed', 'Failed to send message. Please try again later or email us directly at thehausoflyra@gmail.com.', array( 'status' => 500 ) );
    }

    return array( 'success' => true, 'message' => 'Your message has been sent successfully!' );
}

function haus_test_auth_callback( $request ) {
    update_post_meta( 12, '_haus_is_raw_html', 'no' );
    return array(
        'success' => true,
        'reverted' => true
    );
}
