<?php
/**
 * Plugin Name: MTLS Centerwise (For Quiz Maker)
 * Plugin URI:  https://mtls.tech/quiz-maker-franchise-plugin/
 * Description: Premium Lite Version with advanced UI, Super Admin Registration Control, and strict WP Security fixes mapped for AYS Quiz Maker.
 * Version:     1.0.3
 * Author:      MTLS
 * License:     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ==========================================
// 🔴 MASTER PRO UPGRADE LINK 
// ==========================================
define('MTLS_QM_PRO_LINK', 'https://mtls.tech/quiz-maker-franchise-plugin/'); 

// Global variables for showing form messages
global $mtls_qm_sa_msg, $mtls_qm_student_msg, $mtls_login_error;
$mtls_qm_sa_msg = '';
$mtls_qm_student_msg = '';
$mtls_login_error = false;

// 1. DATABASE TABLES SETUP
register_activation_hook( __FILE__, 'mtls_qm_centerwise_activate_plugin' );
function mtls_qm_centerwise_activate_plugin() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    
    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }
    
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $sql_centers = "CREATE TABLE {$wpdb->prefix}mtls_qm_centers (id mediumint(9) NOT NULL AUTO_INCREMENT, custom_center_id varchar(50) NOT NULL, center_name varchar(255) NOT NULL, manager_id bigint(20) NOT NULL, city varchar(100) DEFAULT '' NOT NULL, address text, status varchar(20) DEFAULT 'active' NOT NULL, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id), UNIQUE KEY custom_center_id (custom_center_id)) {$charset};";
    dbDelta($sql_centers);
    
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $sql_students = "CREATE TABLE {$wpdb->prefix}mtls_qm_students (id mediumint(9) NOT NULL AUTO_INCREMENT, user_id bigint(20) NOT NULL, custom_center_id varchar(50) NOT NULL, student_name varchar(255) NOT NULL, phone_number varchar(20) NOT NULL, address text NOT NULL, city varchar(100) DEFAULT '' NOT NULL, course_name varchar(100) NOT NULL, admission_date date NOT NULL, status varchar(20) DEFAULT 'active' NOT NULL, PRIMARY KEY (id)) {$charset};";
    dbDelta($sql_students);
    
    add_role( 'center_owner', 'Center Owner', array( 'read' => true ) );
    add_role( 'center_student', 'Center Student', array( 'read' => true ) );
}

function mtls_qm_get_manager_custom_center_id($user_id) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    return $wpdb->get_var( $wpdb->prepare( "SELECT custom_center_id FROM {$wpdb->prefix}mtls_qm_centers WHERE manager_id = %d LIMIT 1", intval($user_id) ) );
}

function mtls_qm_get_dashboard_url() {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $page_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[mtls_qm_dashboard]%' AND post_status = 'publish' LIMIT 1");
    return $page_id ? get_permalink($page_id) : home_url();
}

// 2. ASSETS ENQUEUEING (WP.ORG COMPLIANT)
add_action('wp_enqueue_scripts', 'mtls_qm_enqueue_assets');
// Removed admin_enqueue_scripts here so it doesn't break the WP Admin backend CSS!
function mtls_qm_enqueue_assets() {
    // Registered with handle, source (false), dependencies (empty array), and version string
    wp_register_style('mtls-qm-frontend-css', false, array(), '1.1.2');
    wp_enqueue_style('mtls-qm-frontend-css');
    $css = "
    .entry-header, .page-header, .entry-title, .page-title { display: none !important; }
    .sa-box { font-family: 'Segoe UI', system-ui, sans-serif; max-width: 1100px; margin: 30px auto; background: #fff; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; overflow: hidden; }
    .sa-header { background: linear-gradient(135deg, #1e293b, #0f172a); padding: 45px 30px; text-align: center; color: white; position: relative; }
    .sa-header h2 { margin: 0; font-size: 34px; font-weight: 900; letter-spacing: 1px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); color:#fff;}
    .sa-body { padding: 40px 30px; }
    .sa-stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
    .sa-stat-card { flex: 1; min-width: 250px; background: #f8fafc; border: 2px solid #e2e8f0; padding: 25px; border-radius: 12px; text-align: center; }
    .sa-stat-card h3 { margin: 0 0 10px 0; color: #64748b; font-size: 16px; text-transform: uppercase; }
    .sa-stat-card p { margin: 0; font-size: 40px; font-weight: 900; color: #334155; line-height: 1; }
    .sa-center-list { list-style: none; padding: 0; margin: 0; }
    .sa-center-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); overflow: hidden; }
    .sa-center-head { padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; cursor: pointer; transition: 0.3s; }
    .sa-center-head:hover { background: #f1f5f9; }
    .sa-center-title { font-size: 16px; font-weight: bold; color: #1e293b; margin: 0; }
    .sa-center-meta { font-size: 13px; color: #64748b; }
    .sa-content-area { display: none; padding: 20px; border-top: 1px solid #e2e8f0; background: #fff; }
    .sa-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
    .sa-table th, .sa-table td { padding: 10px; text-align: left; border-bottom: 1px solid #f1f5f9; }
    .sa-table th { color: #64748b; text-transform: uppercase; font-size: 11px; background: #f8fafc; }
    .sa-badge { background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
    .our-centers-title { background: linear-gradient(90deg, #1e293b, #334155); color: #fff; padding: 15px 20px; border-radius: 8px; text-align: center; font-size: 24px; font-weight: 800; margin: 40px 0 25px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); letter-spacing: 0.5px;}
    
    .mtls-login-box { max-width:400px; margin:50px auto; background:#fff; padding:40px 30px; border-radius:16px; box-shadow:0 15px 35px rgba(0,0,0,0.05); border:1px solid #f1f5f9; font-family:'Segoe UI', sans-serif;}
    .mtls-input { width:100%; padding:12px; margin-bottom:20px; border:2px solid #e2e8f0; border-radius:8px; outline:none; transition:0.3s; box-sizing:border-box;}
    .mtls-input:focus { border-color:#4f46e5;}
    .mtls-btn { width:100%; padding:12px; background:linear-gradient(135deg, #4f46e5, #3b82f6); color:#fff; border:none; border-radius:8px; font-weight:bold; font-size:16px; cursor:pointer; transition:0.3s;}
    .mtls-btn:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(79,70,229,0.3);}
    
    .mtls-portal-box { width: 100%; max-width: 1100px; margin: 10px auto 40px auto; background: #fff; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.08); font-family: 'Segoe UI', system-ui, sans-serif; overflow: hidden; border: 1px solid #e2e8f0; }
    .mtls-portal-header { background: linear-gradient(135deg, #4f46e5, #06b6d4); padding: 35px 30px; text-align: center; color: #fff;}
    .mtls-portal-header h2 { margin:0; font-size:32px; font-weight:900; letter-spacing:0.5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);}
    .mtls-portal-tabs { display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin: 0; padding: 0 20px; list-style: none; overflow-x: auto;}
    .mtls-tab-item a { display: block; padding: 18px 25px; text-decoration: none; color: #64748b; font-weight: 700; font-size: 15px; border-bottom: 3px solid transparent; transition: 0.3s;}
    .mtls-tab-item.active a { color: #6366f1; border-bottom: 3px solid #6366f1; background: #fff; }
    .mtls-portal-body { padding: 40px 30px; min-height: 400px; position: relative; }
    .mtls-data-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px; background:#fff; border-radius:10px; overflow:hidden;}
    .mtls-data-table th, .mtls-data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f5f9; }
    .mtls-data-table th { background: #f8fafc; font-weight: 700; color:#475569; text-transform:uppercase; font-size:12px; letter-spacing:0.5px;}
    .mtls-data-table tr:hover td { background: #f8fafc; }
    .mtls-badge { background: #dcfce7; color: #166534; padding: 5px 12px; font-weight: bold; border-radius: 50px; font-size:13px; }
    .mtls-profile-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:25px; margin-bottom:40px; }
    .mtls-card { background:#fff; border:1px solid #e2e8f0; padding:25px; border-radius:16px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);}
    .mtls-card h4 { margin-top:0; color:#1e293b; font-size:18px; margin-bottom:15px; border-bottom:2px solid #f1f5f9; padding-bottom:10px;}
    .mtls-delete-btn { background: #fee2e2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px; transition: 0.3s; }
    .mtls-delete-btn:hover { background: #ef4444; color: #fff; }
    @media screen and (max-width: 600px) {
        .mtls-portal-header h2 { font-size: 24px; }
        .mtls-portal-body { padding: 20px 15px; }
        .mtls-data-table td, .mtls-data-table th { padding: 10px; font-size: 13px; }
    }
    ";
    wp_add_inline_style('mtls-qm-frontend-css', $css);

    // Registered with handle, source (false), dependencies (empty array), version string, and in_footer flag (true)
    wp_register_script('mtls-qm-frontend-js', false, array(), '1.1.2', true);
    wp_enqueue_script('mtls-qm-frontend-js');
    $js = "
    function mtlsQmToggleCenterData(id) {
        var el = document.getElementById('mtls-qm-center-data-' + id);
        if(el) { el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none'; }
    }
    ";
    wp_add_inline_script('mtls-qm-frontend-js', $js);
}

// 3. CENTRALIZED FORM PROCESSING (Hooked to init)
add_action( 'init', 'mtls_qm_handle_all_forms' );
function mtls_qm_handle_all_forms() {
    global $wpdb, $mtls_qm_sa_msg, $mtls_qm_student_msg, $mtls_login_error;

    // Login Form Processing
    if ( isset($_POST['mtls_frontend_login_submit'], $_POST['log_username'], $_POST['log_password']) ) {
        if ( isset($_POST['mtls_login_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mtls_login_nonce'])), 'mtls_login_action') ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $password = $_POST['log_password'];
            $creds = array( 
                'user_login' => sanitize_user(wp_unslash($_POST['log_username'])), 
                'user_password' => $password, 
                'remember' => true 
            );
            $user_signon = wp_signon( $creds, false );
            if ( ! is_wp_error($user_signon) ) { 
                wp_safe_redirect( mtls_qm_get_dashboard_url() ); 
                exit; 
            } else { 
                $mtls_login_error = true; 
            }
        }
    }

    // Super Admin: Center Deletion
    if ( isset($_POST['mtls_delete_center'], $_POST['mtls_del_center_nonce'], $_POST['delete_center_id']) && current_user_can('manage_options') ) {
        if( wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mtls_del_center_nonce'])), 'mtls_del_center_action') ) {
            $del_center_id = sanitize_text_field(wp_unslash($_POST['delete_center_id']));
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $mgr_id = $wpdb->get_var($wpdb->prepare("SELECT manager_id FROM {$wpdb->prefix}mtls_qm_centers WHERE custom_center_id = %s", $del_center_id));
            if ($mgr_id) {
                if ( ! function_exists( 'wp_delete_user' ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; }
                wp_delete_user($mgr_id);
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $orphan_students = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}mtls_qm_students WHERE custom_center_id = %s", $del_center_id));
            if (!empty($orphan_students)) {
                if ( ! function_exists( 'wp_delete_user' ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; }
                foreach ($orphan_students as $os_id) { wp_delete_user($os_id); }
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->delete("{$wpdb->prefix}mtls_qm_students", ['custom_center_id' => $del_center_id]);
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete("{$wpdb->prefix}mtls_qm_centers", ['custom_center_id' => $del_center_id]);
            $mtls_qm_sa_msg = '<div style="color:#166534; background:#dcfce7; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;">Center removed successfully.</div>';
        }
    }

    // Super Admin: Center Registration
    if ( isset($_POST['mtls_sa_reg_submit'], $_POST['mtls_sa_reg_nonce']) && current_user_can('manage_options') ) {
        if( wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mtls_sa_reg_nonce'])), 'mtls_sa_reg_action') ) {
            $custom_center_id = isset($_POST['custom_center_id']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['custom_center_id']))) : '';
            $username         = isset($_POST['center_username']) ? sanitize_user(wp_unslash($_POST['center_username'])) : '';
            $email            = isset($_POST['center_email']) ? sanitize_email(wp_unslash($_POST['center_email'])) : '';
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $password         = isset($_POST['center_password']) ? $_POST['center_password'] : '';
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $confirm_pass     = isset($_POST['confirm_center_password']) ? $_POST['confirm_center_password'] : '';
            $center_name      = isset($_POST['center_name']) ? sanitize_text_field(wp_unslash($_POST['center_name'])) : '';
            $address          = isset($_POST['center_address']) ? sanitize_textarea_field(wp_unslash($_POST['center_address'])) : '';
            $city             = isset($_POST['center_city']) ? sanitize_text_field(wp_unslash($_POST['center_city'])) : '';
            
            if ($password !== $confirm_pass) {
                $mtls_qm_sa_msg = '<div style="color:#ef4444; background:#fee2e2; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;">Error: Passwords do not match.</div>';
            } elseif ( username_exists($username) || email_exists($email) ) { 
                $mtls_qm_sa_msg = '<div style="color:#ef4444; background:#fee2e2; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;">Error: Username or Email already taken.</div>'; 
            } else {
                $user_id = wp_create_user( $username, $password, $email );
                if ( ! is_wp_error($user_id) ) {
                    wp_update_user(array('ID' => $user_id, 'role' => 'center_owner'));
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->insert($wpdb->prefix . 'mtls_qm_centers', array('custom_center_id' => $custom_center_id, 'center_name' => $center_name, 'manager_id' => $user_id, 'address' => $address, 'city' => $city));
                    $mtls_qm_sa_msg = '<div style="color:#166534; background:#dcfce7; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;">Success! New Center successfully registered.</div>';
                }
            }
        }
    }

    // Center Owner: Student Deletion
    if ( isset($_POST['mtls_delete_student'], $_POST['mtls_del_stud_nonce'], $_POST['delete_student_id']) && is_user_logged_in() ) {
        if( wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mtls_del_stud_nonce'])), 'mtls_del_stud_action') ) {
            $del_id = intval($_POST['delete_student_id']);
            $current_user_id = get_current_user_id();
            $assigned_custom_id = mtls_qm_get_manager_custom_center_id($current_user_id);
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $verify = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}mtls_qm_students WHERE user_id = %d AND custom_center_id = %s", $del_id, $assigned_custom_id));
            if ($verify) {
                if ( ! function_exists( 'wp_delete_user' ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; }
                wp_delete_user($del_id);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->delete("{$wpdb->prefix}mtls_qm_students", ['user_id' => $del_id]);
                $mtls_qm_student_msg = '<div style="color:#166534; padding:15px; background:#dcfce7; border-radius:8px; margin-bottom:20px; font-weight:bold;">Student removed successfully.</div>';
            }
        }
    }

    // Center Owner: Student Admission
    if ( isset($_POST['mtls_front_add_student'], $_POST['mtls_add_stud_nonce']) && is_user_logged_in() ) {
        if( wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mtls_add_stud_nonce'])), 'mtls_add_stud_action') ) {
            $student_name   = isset($_POST['student_name']) ? sanitize_text_field(wp_unslash($_POST['student_name'])) : '';
            $stud_username  = isset($_POST['stud_username']) ? sanitize_user(wp_unslash($_POST['stud_username'])) : '';
            $stud_email     = !empty($_POST['stud_email']) ? sanitize_email(wp_unslash($_POST['stud_email'])) : $stud_username . '@mtls.local';
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $stud_password  = isset($_POST['stud_password']) ? $_POST['stud_password'] : '';
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $confirm_pass   = isset($_POST['confirm_stud_password']) ? $_POST['confirm_stud_password'] : '';
            $course_name    = isset($_POST['course_name']) ? sanitize_text_field(wp_unslash($_POST['course_name'])) : '';
            $phone_number   = isset($_POST['phone_number']) ? sanitize_text_field(wp_unslash($_POST['phone_number'])) : '';
            $address        = isset($_POST['student_address']) ? sanitize_textarea_field(wp_unslash($_POST['student_address'])) : '';
            $city           = isset($_POST['student_city']) ? sanitize_text_field(wp_unslash($_POST['student_city'])) : '';
            $admission_date = !empty($_POST['admission_date']) ? sanitize_text_field(wp_unslash($_POST['admission_date'])) : gmdate('Y-m-d');
            
            $assigned_custom_id = mtls_qm_get_manager_custom_center_id(get_current_user_id());
            $target_id = (current_user_can('manage_options') && isset($_POST['target_custom_id'])) ? sanitize_text_field(wp_unslash($_POST['target_custom_id'])) : $assigned_custom_id;

            if ($stud_password !== $confirm_pass) {
                $mtls_qm_student_msg = '<div style="color:#ef4444; background:#fee2e2; padding:10px; border-radius:6px; margin-bottom:15px;">Error: Passwords do not match.</div>';
            } elseif ( username_exists($stud_username) || (!empty($_POST['stud_email']) && email_exists($stud_email)) ) {
                $mtls_qm_student_msg = '<div style="color:#ef4444; background:#fee2e2; padding:10px; border-radius:6px; margin-bottom:15px;">Error: Username or Email already exists.</div>';
            } else {
                $student_user_id = wp_create_user( $stud_username, $stud_password, $stud_email );
                if ( ! is_wp_error($student_user_id) ) {
                    wp_update_user( array( 'ID' => $student_user_id, 'role' => 'center_student' ) );
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->insert( "{$wpdb->prefix}mtls_qm_students", array(
                            'user_id'          => $student_user_id, 
                            'custom_center_id' => $target_id, 
                            'student_name'     => $student_name, 
                            'phone_number'     => $phone_number, 
                            'address'          => $address, 
                            'city'             => $city, 
                            'course_name'      => $course_name, 
                            'admission_date'   => $admission_date, 
                            'status'           => 'active' 
                        ) 
                    );
                    $mtls_qm_student_msg = '<div style="color:#166534; padding:15px; background:#dcfce7; border-radius:8px; margin-bottom:20px; font-weight:bold;">Student Enrolled Successfully!</div>';
                } else {
                    $mtls_qm_student_msg = '<div style="color:#ef4444; background:#fee2e2; padding:10px; border-radius:6px; margin-bottom:15px;">Error: ' . esc_html($student_user_id->get_error_message()) . '</div>';
                }
            }
        }
    }
}

add_filter( 'login_redirect', 'mtls_qm_centerwise_default_login_redirect', 10, 3 );
function mtls_qm_centerwise_default_login_redirect( $redirect_to, $request, $user ) {
    if ( is_a( $user, 'WP_User' ) && isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'center_owner', $user->roles ) || in_array( 'center_student', $user->roles ) ) {
            return mtls_qm_get_dashboard_url();
        }
    }
    return $redirect_to;
}

// 4. WP BACKEND INSTRUCTIONS
add_action('admin_menu', 'mtls_qm_centerwise_admin_menu');
function mtls_qm_centerwise_admin_menu() {
    add_menu_page('MTLS Centerwise', 'MTLS Centerwise', 'manage_options', 'mtls-centerwise', 'mtls_qm_centerwise_admin_page', 'dashicons-bank', 99);
}

function mtls_qm_centerwise_admin_page() {
    echo '<div class="wrap" style="font-family:-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width:850px; margin-top:30px;">';
    echo '<div style="background:#fff; padding:40px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.05); border:1px solid #e2e8f0;">';
    echo '<h1 style="color:#1e293b; margin-top:0; font-weight:800; font-size:28px;">MTLS Centerwise Setup Guide</h1>';
    echo '<p style="font-size:16px; color:#475569; margin-bottom:30px;">Welcome to the core management system. To deploy the application interfaces on your website, please embed the respective shortcodes into your WordPress pages.</p>';
    
    echo '<div style="background:#f8fafc; padding:25px; border-radius:10px; border-left:5px solid #6366f1; margin-bottom:25px;">';
    echo '<h3 style="margin-top:0; color:#4f46e5; font-size:18px;">1. Client Portal (Student & Center Dashboard)</h3>';
    echo '<p style="color:#475569;">Create a public page titled "Dashboard" and paste the following shortcode. This interface dynamically adapts based on whether a Student or a Center Owner logs in.</p>';
    echo '<code style="background:#e0e7ff; color:#4338ca; padding:10px 18px; font-size:16px; border-radius:6px; font-weight:bold; display:inline-block;">[mtls_qm_dashboard]</code>';
    echo '</div>';

    echo '<div style="background:#f8fafc; padding:25px; border-radius:10px; border-left:5px solid #10b981;">';
    echo '<h3 style="margin-top:0; color:#059669; font-size:18px;">2. Super Admin Interface & Registration</h3>';
    echo '<p style="color:#475569;">Create a hidden or password-protected page for Administrator use only. This interface allows you to view all operational data and register new centers.</p>';
    echo '<code style="background:#dcfce7; color:#166534; padding:10px 18px; font-size:16px; border-radius:6px; font-weight:bold; display:inline-block;">[mtls_qm_super_admin_dashboard]</code>';
    echo '</div>';
    
    echo '</div></div>';
}

// 5. SUPER ADMIN FRONTEND DASHBOARD SHORTCODE
add_shortcode('mtls_qm_super_admin_dashboard', 'mtls_qm_render_super_admin_dashboard');

function mtls_qm_render_super_admin_dashboard() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<p style="text-align:center; padding:30px; background:#fee2e2; color:#991b1b; border-radius:10px;">Security Alert: Only Super Admins can access this interface.</p>';
    }

    global $wpdb, $mtls_qm_sa_msg;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $total_centers_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mtls_qm_centers");
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
    $centers = $wpdb->get_results("SELECT c.*, u.user_email FROM {$wpdb->prefix}mtls_qm_centers c LEFT JOIN {$wpdb->prefix}users u ON c.manager_id = u.ID");
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
    $total_students = (int) $wpdb->get_var("SELECT COUNT(s.id) FROM {$wpdb->prefix}mtls_qm_students s INNER JOIN {$wpdb->prefix}mtls_qm_centers c ON s.custom_center_id = c.custom_center_id");
    
    ob_start();
    ?>
    <div class="sa-box">
        <div class="sa-header">
            <h2>Super Admin Dashboard</h2>
        </div>
        <div class="sa-body">
            
            <div style="background: linear-gradient(135deg, #4f46e5, #ec4899); padding: 35px; border-radius: 12px; color: white; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);">
                <h2 style="color: white; margin-top: 0; font-size: 26px; font-weight: 900;">Unlock MTLS Centerwise PRO (For Quiz Maker)</h2>
                <p style="font-size: 16px; opacity: 0.95; line-height: 1.6; max-width:800px; margin-bottom:20px;">
                    Elevate your platform to the next level. Upgrade to the PRO version to unlock unrestricted access and advanced tools designed for scalability:</strong>
                </p>
                <a href="<?php echo esc_url(MTLS_QM_PRO_LINK); ?>" target="_blank" style="display: inline-block; padding: 14px 28px; background: #FFD700; color: #1e1b4b; text-decoration: none; font-weight: 800; border-radius: 8px; font-size:15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: 0.3s;">Explore PRO Features &rarr;</a>
            </div>

            <div class="sa-stats">
                <div class="sa-stat-card" style="border: 2px solid #6366f1;">
                    <h3 style="color:#6366f1;">Total Centers</h3>
                    <p><?php echo esc_html($total_centers_count); ?></p>
                </div>
                <div class="sa-stat-card">
                    <h3>Total Students System-wide</h3>
                    <p><?php echo esc_html($total_students); ?></p>
                </div>
            </div>

            <div style="background:#fff; border:1px solid #e2e8f0; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.02); margin-bottom:40px;">
                <h3 style="margin-top:0; color:#1e293b; font-size:22px; border-bottom:2px solid #f1f5f9; padding-bottom:12px;">➕ Register New Center</h3>
                <?php echo wp_kses_post($mtls_qm_sa_msg); ?>
                <form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <?php wp_nonce_field('mtls_sa_reg_action', 'mtls_sa_reg_nonce'); ?>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">Center ID *</label>
                        <input type="text" name="custom_center_id" placeholder="e.g. JHANSI-01" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">Branch Name *</label>
                        <input type="text" name="center_name" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">Address</label>
                        <textarea name="center_address" rows="1" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;"></textarea>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">City</label>
                        <input type="text" name="center_city" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">Manager Username *</label>
                        <input type="text" name="center_username" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">Manager Email *</label>
                        <input type="email" name="center_email" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">Password *</label>
                        <input type="password" name="center_password" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:bold; color:#64748b;">Confirm Password *</label>
                        <input type="password" name="confirm_center_password" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; box-sizing:border-box;">
                    </div>
                    <div style="grid-column: span 2; margin-top:10px;">
                        <button type="submit" name="mtls_sa_reg_submit" style="background:#1e293b; color:#fff; border:none; padding:14px; width:100%; font-weight:bold; font-size:16px; border-radius:8px; cursor:pointer;">Register Center</button>
                    </div>
                </form>
            </div>

            <h3 class="our-centers-title">Our Centers</h3>
            
            <ul class="sa-center-list">
                <?php 
                if($centers) {
                    foreach($centers as $c) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $c_students = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mtls_qm_students WHERE custom_center_id = %s ORDER BY id DESC", $c->custom_center_id));
                        
                        // Modified Query for Quiz Maker Results Integration
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $c_results = $wpdb->get_results($wpdb->prepare("
                            SELECT q.*, s.student_name, qz.title as quiz_title 
                            FROM {$wpdb->prefix}aysquiz_reports q 
                            JOIN {$wpdb->prefix}mtls_qm_students s ON q.user_id = s.user_id 
                            LEFT JOIN {$wpdb->prefix}aysquiz_quizes qz ON q.quiz_id = qz.id 
                            WHERE s.custom_center_id = %s 
                            ORDER BY q.id DESC LIMIT 50
                        ", $c->custom_center_id));

                        echo '<li class="sa-center-item">';
                        echo '<div class="sa-center-head" onclick="mtlsQmToggleCenterData(\''.esc_attr($c->custom_center_id).'\')">';
                        echo '<h4 class="sa-center-title">🏢 '.esc_html($c->center_name).' <span style="background:#e0e7ff; color:#4338ca; padding:2px 8px; border-radius:4px; font-size:12px; margin-left:10px;">'.esc_html($c->custom_center_id).'</span></h4>';
                        echo '<div><span class="sa-center-meta" style="margin-right:15px;">Students: <strong>'.esc_html(count($c_students)).'</strong> | Results: <strong>'.esc_html(count($c_results)).'</strong> ⬇️</span>';
                        
                        echo '<form method="POST" onsubmit="event.stopPropagation(); return confirm(\'Are you sure you want to remove this center and its manager account completely?\');" style="display:inline-block; margin:0;">';
                        wp_nonce_field('mtls_del_center_action', 'mtls_del_center_nonce');
                        echo '<input type="hidden" name="delete_center_id" value="'.esc_attr($c->custom_center_id).'">
                                <button type="submit" name="mtls_delete_center" style="background:#fee2e2; color:#dc2626; border:none; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; cursor:pointer; position:relative; z-index:10;">🗑️ Remove</button>
                              </form></div>';
                        
                        echo '</div>';
                        
                        echo '<div id="mtls-qm-center-data-'.esc_attr($c->custom_center_id).'" class="sa-content-area">';
                        
                        echo '<h4 style="margin:0 0 10px 0; color:#1e293b;">👨‍🎓 Enrolled Students</h4>';
                        if($c_students) {
                            echo '<table class="sa-table"><thead><tr><th>Name</th><th>Course</th><th>Phone</th><th>Address</th><th>City</th><th>Admitted On</th></tr></thead><tbody>';
                            foreach($c_students as $s) {
                                echo "<tr><td><strong>".esc_html($s->student_name)."</strong></td><td>".esc_html($s->course_name)."</td><td>".esc_html($s->phone_number)."</td><td>".esc_html($s->address)."</td><td>".esc_html($s->city)."</td><td>".esc_html(gmdate('d M Y', strtotime($s->admission_date)))."</td></tr>";
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<p style="color:#64748b; font-size:13px; margin:0 0 20px 0;">No students enrolled.</p>';
                        }

                        echo '<h4 style="margin:20px 0 10px 0; color:#1e293b;">📊 Recent Exam Results</h4>';
                        if($c_results) {
                            echo '<table class="sa-table"><thead><tr><th>Date</th><th>Student Name</th><th>Quiz</th><th>Time Taken</th><th>Score</th></tr></thead><tbody>';
                            foreach($c_results as $res) {
                                $quiz_name = !empty($res->quiz_title) ? $res->quiz_title : "Quiz #" . $res->quiz_id;
                                $date = gmdate("d M Y h:i A", strtotime($res->end_date));
                                $score = isset($res->score) ? intval($res->score) : 0;
                                
                                $duration_val = $res->duration ?? '';
                                if (is_numeric($duration_val)) {
                                    $mins = floor($duration_val / 60);
                                    $secs = $duration_val % 60;
                                    $time_taken = sprintf("%02d:%02d", $mins, $secs);
                                } else {
                                    $time_taken = 'N/A';
                                }

                                echo "<tr><td style='color:#64748b;'>".esc_html($date)."</td><td><strong>".esc_html($res->student_name)."</strong></td><td style='color:#6366f1;'>".esc_html($quiz_name)."</td><td style='color:#475569; font-size:12px;'>⏱️ ".esc_html($time_taken)."</td><td><span class='sa-badge'>".esc_html($score)."%</span></td></tr>";
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<p style="color:#64748b; font-size:13px; margin:0;">No exam results yet.</p>';
                        }
                        echo '</div></li>';
                    }
                } else {
                    echo '<p>No centers found.</p>';
                }
                ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// 6. MAIN FRONTEND DASHBOARD SHORTCODE [mtls_qm_dashboard]
add_shortcode( 'mtls_qm_dashboard', 'mtls_qm_frontend_portal_shortcode' );

function mtls_qm_frontend_portal_shortcode() {
    global $wpdb, $mtls_login_error, $mtls_qm_student_msg;
    ob_start();
    $current_page_url = mtls_qm_get_dashboard_url();

    // LOGIN FORM
    if ( ! is_user_logged_in() ) {
        echo '<div class="mtls-login-box"><h2 style="text-align:center; margin-top:0; color:#1e293b; font-weight:800;">Portal Login</h2><p style="text-align:center; color:#64748b; margin-bottom:25px;">Enter your credentials to access your dashboard.</p>';
        if ( isset($mtls_login_error) && $mtls_login_error ) echo '<p style="color:#ef4444; text-align:center; background:#fee2e2; padding:10px; border-radius:6px;">Invalid username or password.</p>';
        echo '<form method="POST">';
        wp_nonce_field('mtls_login_action', 'mtls_login_nonce');
        echo '<label style="font-weight:600; color:#475569; font-size:14px; margin-bottom:5px; display:block;">Username / Email</label><input type="text" name="log_username" class="mtls-input" required><label style="font-weight:600; color:#475569; font-size:14px; margin-bottom:5px; display:block;">Password</label><input type="password" name="log_password" class="mtls-input" required><button type="submit" name="mtls_frontend_login_submit" class="mtls-btn">Login Securely</button></form></div>';
        return ob_get_clean();
    }

    $current_user      = wp_get_current_user();
    $is_student_logged = in_array('center_student', (array)$current_user->roles);
    
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $active_tab     = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'profile';

    ?>
    <div class="mtls-portal-box">
        <div class="mtls-portal-header">
            <h2>Welcome, <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?> ✨</h2>
        </div>

        <?php 
        // ==========================================
        // STUDENT DASHBOARD
        // ==========================================
        if ( $is_student_logged ): 
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $student_profile = $wpdb->get_row( $wpdb->prepare("SELECT s.*, c.center_name FROM {$wpdb->prefix}mtls_qm_students s JOIN {$wpdb->prefix}mtls_qm_centers c ON s.custom_center_id = c.custom_center_id WHERE s.user_id = %d", $current_user->ID) );
        ?>
            <ul class="mtls-portal-tabs">
                <li class="mtls-tab-item <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'profile', $current_page_url)); ?>">My Profile</a></li>
                <li class="mtls-tab-item <?php echo $active_tab === 'tests' ? 'active' : ''; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'tests', $current_page_url)); ?>">Exam Results</a></li>
            </ul>
            <div class="mtls-portal-body">
                <?php if ( $active_tab === 'profile' ): ?>
                    <h3 style="margin-top:0; color:#1e293b; font-size:22px;">Academic Profile</h3>
                    <?php if($student_profile): ?>
                        <div class="mtls-profile-grid">
                            <div class="mtls-card">
                                <h4>Personal Info</h4>
                                <p style="margin:8px 0; color:#475569;"><strong>Name:</strong> <?php echo esc_html($student_profile->student_name); ?></p>
                                <p style="margin:8px 0; color:#475569;"><strong>Phone:</strong> <?php echo esc_html($student_profile->phone_number); ?></p>
                                <p style="margin:8px 0; color:#475569;"><strong>Address:</strong> <?php echo esc_html($student_profile->address ?: 'N/A'); ?> <?php if(!empty($student_profile->city)) echo ', '.esc_html($student_profile->city); ?></p>
                                <p style="margin:8px 0; color:#475569;"><strong>Email:</strong> <?php echo strpos($current_user->user_email, '@mtls.local') === false ? esc_html($current_user->user_email) : 'Not Provided'; ?></p>
                            </div>
                            <div class="mtls-card">
                                <h4>Enrollment Details</h4>
                                <p style="margin:8px 0; color:#475569;"><strong>Center ID:</strong> <span style="background:#e0e7ff; color:#4338ca; padding:4px 10px; border-radius:6px; font-weight:bold;"><?php echo esc_html($student_profile->custom_center_id); ?></span></p>
                                <p style="margin:8px 0; color:#475569;"><strong>Center Name:</strong> <?php echo esc_html($student_profile->center_name); ?></p>
                                <p style="margin:8px 0; color:#475569;"><strong>Course:</strong> <?php echo esc_html($student_profile->course_name); ?></p>
                                <p style="margin:8px 0; color:#475569;"><strong>Admission Date:</strong> <?php echo esc_html(gmdate('d M Y', strtotime($student_profile->admission_date))); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                
                <?php elseif ( $active_tab === 'tests' ): ?>
                    <h3 style="margin-top:0; color:#1e293b; font-size:22px;">My Exam Results</h3>
                    <?php 
                    // Updated Query for Quiz Maker Integration
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $results = $wpdb->get_results( $wpdb->prepare("
                        SELECT q.*, qz.title as quiz_title 
                        FROM {$wpdb->prefix}aysquiz_reports q 
                        LEFT JOIN {$wpdb->prefix}aysquiz_quizes qz ON q.quiz_id = qz.id 
                        WHERE q.user_id=%d ORDER BY q.id DESC
                    ", $current_user->ID) );
                    
                    if ( $results ) {
                        echo '<div style="overflow-x:auto;"><table id="mtlsStudentTable" class="mtls-data-table">
                              <thead>
                                  <tr>
                                      <th>Date</th>
                                      <th>Quiz Name</th>
                                      <th>Time Taken</th>
                                      <th>Score</th>
                                  </tr>
                              </thead><tbody>';
                        
                        foreach($results as $result) {
                            $quiz_name = !empty($result->quiz_title) ? $result->quiz_title : "Quiz #" . $result->quiz_id;
                            $date = gmdate("d M Y h:i A", strtotime($result->end_date));
                            $score = isset($result->score) ? intval($result->score) : 0;
                            
                            $duration_val = $result->duration ?? '';
                            if (is_numeric($duration_val)) {
                                $mins = floor($duration_val / 60);
                                $secs = $duration_val % 60;
                                $time_taken = sprintf("%02d:%02d", $mins, $secs);
                            } else {
                                $time_taken = 'N/A';
                            }
                            
                            $score_color = ($score >= 80) ? 'background:#dcfce7; color:#166534;' : (($score >= 40) ? 'background:#fef9c3; color:#92400e;' : 'background:#fee2e2; color:#991b1b;');

                            echo "<tr>
                                    <td style='color:#64748b; font-weight:600; white-space:nowrap;'>".esc_html($date)."</td>
                                    <td><strong style='color:#6366f1; font-size:15px;'>".esc_html($quiz_name)."</strong></td>
                                    <td style='color:#475569;'>⏱️ ".esc_html($time_taken)."</td>
                                    <td><span class='mtls-badge' style='".esc_attr($score_color)."'>".esc_html($score)."%</span></td>
                                  </tr>";
                        }
                        echo '</tbody></table></div>';
                    } else {
                        echo '<div style="padding:40px; background:#f8fafc; border-radius:12px; text-align:center; color:#64748b; border: 2px dashed #cbd5e1;"><h3 style="margin-top:0; color:#1e293b;">No Attempts Yet!</h3><p>Complete a quiz to see your results here.</p></div>';
                    }
                    ?>
                <?php endif; ?>
                <div style="margin-top: 20px;"><a href="<?php echo esc_url(wp_logout_url($current_page_url)); ?>">Sign Out Account &rarr;</a></div>
            </div>

        <?php 
        // ==========================================
        // CENTER OWNER DASHBOARD
        // ==========================================
        else: 
            $assigned_custom_id = mtls_qm_get_manager_custom_center_id($current_user->ID);
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total_students = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}mtls_qm_students WHERE custom_center_id = %s", $assigned_custom_id));
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $center_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mtls_qm_centers WHERE custom_center_id = %s", $assigned_custom_id));
        ?>
            <ul class="mtls-portal-tabs">
                <li class="mtls-tab-item <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'profile', $current_page_url)); ?>">Center Profile</a></li>
                <li class="mtls-tab-item <?php echo $active_tab === 'students' ? 'active' : ''; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'students', $current_page_url)); ?>">Students</a></li>
                <li class="mtls-tab-item <?php echo $active_tab === 'qm_results' ? 'active' : ''; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'qm_results', $current_page_url)); ?>">Exam Results</a></li>
            </ul>
            <div class="mtls-portal-body">
                <?php if ( $active_tab === 'profile' ): ?>
                    <h3 style="margin-top:0; color:#1e293b; font-size:22px;">Center Overview</h3>
                    <div class="mtls-profile-grid">
                        <div class="mtls-card" style="background:linear-gradient(135deg, #f8fafc, #f1f5f9); border:2px solid #6366f1;">
                            <h4 style="color:#4f46e5; border-color:#e2e8f0;">Total Enrollments</h4>
                            <p style="font-size:54px; font-weight:900; color:#1e293b; margin:10px 0; line-height:1;"><?php echo esc_html($total_students); ?></p>
                        </div>
                        <div class="mtls-card">
                            <h4>Manager Details</h4>
                            <p style="margin:8px 0; color:#475569;"><strong>Center ID:</strong> <span style="background:#e2e8f0; padding:4px 8px; border-radius:6px; font-weight:bold; color:#334155;"><?php echo esc_html($assigned_custom_id); ?></span></p>
                            <p style="margin:8px 0; color:#475569;"><strong>Center Name:</strong> <?php echo esc_html($center_details ? $center_details->center_name : 'N/A'); ?></p>
                            <p style="margin:8px 0; color:#475569;"><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
                            <p style="margin:8px 0; color:#475569;"><strong>Registered On:</strong> <?php echo esc_html($center_details ? gmdate('d M Y', strtotime($center_details->created_at)) : 'N/A'); ?></p>
                        </div>
                    </div>

                <?php elseif ( $active_tab === 'students' ): ?>
                    <h3 style="margin-top:0; color:#1e293b; font-size:22px;">Manage Enrollments</h3>
                    <?php echo wp_kses_post($mtls_qm_student_msg); ?>
                    <div style="display:flex; flex-direction:column; gap:35px;">
                        <div style="width:100%; background:#fff; padding:25px; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 4px 10px rgba(0,0,0,0.03);">
                            <h4 style="margin-top:0; color:#6366f1; border-bottom:2px solid #e0e7ff; padding-bottom:12px;">Register New Student</h4>
                            <form method="POST">
                                <?php wp_nonce_field('mtls_add_stud_action', 'mtls_add_stud_nonce'); ?>
                                <label style="font-size:12px; font-weight:bold; color:#64748b;">Full Name *</label>
                                <input type="text" name="student_name" required style="width:100%; margin-bottom:12px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
                                
                                <label style="font-size:12px; font-weight:bold; color:#64748b;">Phone Number *</label>
                                <input type="text" name="phone_number" required style="width:100%; margin-bottom:12px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
                                
                                <label style="font-size:12px; font-weight:bold; color:#64748b;">Address</label>
                                <textarea name="student_address" rows="1" style="width:100%; margin-bottom:12px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;"></textarea>
                                
                                <label style="font-size:12px; font-weight:bold; color:#64748b;">City</label>
                                <input type="text" name="student_city" style="width:100%; margin-bottom:12px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">

                                <label style="font-size:12px; font-weight:bold; color:#64748b;">Course Name</label>
                                <input type="text" name="course_name" style="width:100%; margin-bottom:12px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
                                
                                <label style="font-size:12px; font-weight:bold; color:#64748b;">Admission Date (Default: Today)</label>
                                <input type="date" name="admission_date" style="width:100%; margin-bottom:20px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; color:#475569; box-sizing:border-box;">
                                
                                <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px dashed #cbd5e1; margin-bottom:20px;">
                                    <p style="margin:0 0 10px 0; font-size:13px; font-weight:800; color:#475569; text-transform:uppercase;">Login Credentials</p>
                                    <label style="font-size:12px; font-weight:bold; color:#64748b;">Username *</label>
                                    <input type="text" name="stud_username" required style="width:100%; margin-bottom:10px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
                                    
                                    <label style="font-size:12px; font-weight:bold; color:#64748b;">Password *</label>
                                    <input type="password" name="stud_password" required style="width:100%; margin-bottom:10px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
                                    
                                    <label style="font-size:12px; font-weight:bold; color:#64748b;">Confirm Password *</label>
                                    <input type="password" name="confirm_stud_password" required style="width:100%; margin-bottom:10px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
                                    
                                    <label style="font-size:12px; font-weight:bold; color:#64748b;">Email (Optional)</label>
                                    <input type="email" name="stud_email" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
                                </div>
                                <button type="submit" name="mtls_front_add_student" style="background:linear-gradient(135deg, #6366f1, #4f46e5); color:#fff; border:none; padding:14px; width:100%; font-weight:bold; border-radius:8px; cursor:pointer; font-size:15px;">Enroll Student</button>
                            </form>
                        </div>

                       <div style="width:100%;">
                            <h4 style="margin-top:0; color:#1e293b; font-size:18px;">Enrolled Students (<?php echo esc_html($total_students); ?>)</h4>
                            <?php 
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
                            $student_records = $wpdb->get_results($wpdb->prepare("SELECT s.*, u.user_login FROM {$wpdb->prefix}mtls_qm_students s LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID WHERE s.custom_center_id = %s ORDER BY s.id DESC", $assigned_custom_id));
                            if($student_records) {
                                echo '<div style="background:#fff; border-radius:12px; overflow-x:auto; border:1px solid #e2e8f0;"><table class="mtls-data-table" style="margin:0;"><thead><tr><th>Name</th><th>Username</th><th>Phone</th><th>Adm Date</th><th>Action</th></tr></thead><tbody>';
                                foreach($student_records as $row) {
                                    echo "<tr>
                                            <td><strong style='color:#1e293b; white-space:nowrap;'>".esc_html($row->student_name)."</strong></td>
                                            <td style='color:#4f46e5; font-size:13px;'><strong>".esc_html($row->user_login)."</strong></td>
                                            <td>".esc_html($row->phone_number)."</td>
                                            <td style='color:#64748b; font-size:13px; white-space:nowrap;'>".esc_html(gmdate('d M Y', strtotime($row->admission_date)))."</td>
                                            <td>
                                                <form method='POST' onsubmit='return confirm(\"Are you sure you want to completely remove this student?\");' style='margin:0;'>";
                                                
                                                wp_nonce_field('mtls_del_stud_action', 'mtls_del_stud_nonce');
                                                
                                    echo "      <input type='hidden' name='delete_student_id' value='".esc_attr($row->user_id)."'>
                                                <button type='submit' name='mtls_delete_student' class='mtls-delete-btn'>Remove</button>
                                                </form>
                                            </td>
                                          </tr>";
                                }
                                echo '</tbody></table></div>';
                            } else {
                                echo '<p style="color:#64748b;">No students enrolled yet.</p>';
                            }
                            ?>
                        </div>
                    </div>

                <?php elseif ( $active_tab === 'qm_results' ): ?>
                    <h3 style="margin-top:0; color:#1e293b; font-size:22px;">Center Exam Results</h3>
                    <?php
                    if (current_user_can('manage_options')) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
                        $qm_results_data = $wpdb->get_results("
                            SELECT q.*, s.student_name, s.custom_center_id, qz.title as quiz_title 
                            FROM {$wpdb->prefix}aysquiz_reports q 
                            JOIN {$wpdb->prefix}mtls_qm_students s ON q.user_id = s.user_id 
                            LEFT JOIN {$wpdb->prefix}aysquiz_quizes qz ON q.quiz_id = qz.id 
                            ORDER BY q.id DESC
                        ");
                    } else {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $qm_results_data = $wpdb->get_results($wpdb->prepare("
                            SELECT q.*, s.student_name, s.custom_center_id, qz.title as quiz_title 
                            FROM {$wpdb->prefix}aysquiz_reports q 
                            JOIN {$wpdb->prefix}mtls_qm_students s ON q.user_id = s.user_id 
                            LEFT JOIN {$wpdb->prefix}aysquiz_quizes qz ON q.quiz_id = qz.id 
                            WHERE s.custom_center_id = %s 
                            ORDER BY q.id DESC
                        ", $assigned_custom_id));
                    }
                    
                    if ( ! empty($qm_results_data) ) {
                        echo '<div style="overflow-x:auto;"><table id="mtlsCenterTable" class="mtls-data-table">
                              <thead>
                                  <tr>
                                      <th>Date</th>
                                      <th>Student Name</th>
                                      <th>Quiz Name</th>
                                      <th>Time Taken</th>
                                      <th>Score</th>
                                  </tr>
                              </thead><tbody>';
                              
                        foreach($qm_results_data as $result) {
                            $quiz_name = !empty($result->quiz_title) ? $result->quiz_title : "Quiz #" . $result->quiz_id;
                            $date = gmdate("d M Y h:i A", strtotime($result->end_date));
                            $score = isset($result->score) ? intval($result->score) : 0;
                            
                            $duration_val = $result->duration ?? '';
                            if (is_numeric($duration_val)) {
                                $mins = floor($duration_val / 60);
                                $secs = $duration_val % 60;
                                $time_taken = sprintf("%02d:%02d", $mins, $secs);
                            } else {
                                $time_taken = 'N/A';
                            }
                            
                            $score_color = ($score >= 80) ? 'background:#dcfce7; color:#166534;' : (($score >= 40) ? 'background:#fef9c3; color:#92400e;' : 'background:#fee2e2; color:#991b1b;');

                            echo "<tr>
                                    <td style='color:#64748b; font-weight:600; white-space:nowrap;'>".esc_html($date)."</td>
                                    <td><strong style='color:#1e293b; white-space:nowrap;'>".esc_html(isset($result->student_name) ? $result->student_name : 'Unknown')."</strong></td>
                                    <td style='color:#6366f1; font-weight:600;'>".esc_html($quiz_name)."</td>
                                    <td style='color:#475569;'>⏱️ ".esc_html($time_taken)."</td>
                                    <td><span class='mtls-badge' style='".esc_attr($score_color)."'>".esc_html($score)."%</span></td>
                                  </tr>";
                        }
                        echo '</tbody></table></div>';
                    } else {
                        echo '<div style="padding:40px; background:#f8fafc; border-radius:12px; text-align:center; color:#64748b; border: 2px dashed #cbd5e1;"><h3 style="margin-top:0; color:#1e293b;">No Exam Results Found</h3><p>Students from your center have not completed any quizzes yet.</p></div>';
                    }
                    ?>
                <?php endif; ?>
                <div style="margin-top: 20px;"><a href="<?php echo esc_url(wp_logout_url($current_page_url)); ?>">Sign Out Account &rarr;</a></div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// 7. Auto-Sync WP User Deletion with Dashboard
add_action( 'delete_user', 'mtls_qm_centerwise_sync_backend_delete' );
function mtls_qm_centerwise_sync_backend_delete( $user_id ) {
    global $wpdb;
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $center_id = $wpdb->get_var($wpdb->prepare("SELECT custom_center_id FROM {$wpdb->prefix}mtls_qm_centers WHERE manager_id = %d", $user_id));
    
    if ( !empty($center_id) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete("{$wpdb->prefix}mtls_qm_students", ['custom_center_id' => $center_id]);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete("{$wpdb->prefix}mtls_qm_centers", ['manager_id' => $user_id]);
    }
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->delete("{$wpdb->prefix}mtls_qm_students", ['user_id' => $user_id]);
}
