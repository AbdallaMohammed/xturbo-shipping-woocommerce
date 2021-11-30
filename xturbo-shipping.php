<?php

/*
Plugin Name:  XTurbo Shipping WooCommerce
Plugin URI:   https://github.com/AbdallaMohammed
Description:  XTurbo Shipping WooCommerce Plugin.
Version:      1.0.0
Author:       AbdallahMohammed
Author URI:   https://github.com/AbdallaMohammed
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  xturbo
Domain Path:  /i18n
*/

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin activation.
 * 
 * @since 1.0.0
 * @return void
 */
function xturbo_is_requirements_meet() {
    if (
        version_compare(phpversion(), '7.2', '<')
        ||
        version_compare(get_bloginfo('version'), '5.2', '<')
        ||
        ! is_plugin_active('woocommerce/woocommerce.php')
    ) {
        add_action('admin_init', 'xturbo_auto_deactivate');
        add_action('admin_notices', 'xturbo_activation_error');
    }
}
add_action('admin_init', 'xturbo_is_requirements_meet');

/**
 * Auto deactivate plugin.
 * 
 * @return void
 */
function xturbo_auto_deactivate() {
    deactivate_plugins(plugin_basename(__FILE__));
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}

/**
 * Display activation error.
 * 
 * @return void
 */
function xturbo_activation_error() {
    $messages = [
        sprintf(esc_html__('You are using the outdated WordPress, please update it to version %s or higher.', 'xturbo'), '5.2'),
        sprintf(esc_html__('XTurbo requires PHP version %s or above. Please update PHP to run this plugin.', 'xturbo' ), '7.2'),
        sprintf(esc_html__('XTurbo requires %s. Please install that plugin to run this plugin'), 'WooCommerce')
    ];
    ?>
    <div class="notice xturbo-notice notice-error">
        <p>
            <?php echo join('<br>', $messages) ?>
        </p>
    </div>
    <?php
}

function xturbo_add_cron_schedule($schedules) {
    $schedules['every_six_hours'] = [
        'interval' => 21600,
        'display' => __('Once 6 Hours')
    ];

    return $schedules;
}
add_filter('cron_schedules', 'xturbo_add_cron_schedule');

function xturbo_plugin_activation() {
    if (! wp_next_scheduled('xturbo_order_details_cron')) {
        wp_schedule_event(time(), 'every_six_hours', 'xturbo_order_details_cron');
    }
}
register_activation_hook(__FILE__, 'xturbo_plugin_activation');

function xturbo_shipping_method() {
    require_once 'includes/class-xturbo-shipping-method.php';
}
add_action('woocommerce_shipping_init', 'xturbo_shipping_method');
add_action('woocommerce_product_meta_start', 'xturbo_shipping_method');

/**
 * Add shipping method.
 * 
 * @since 1.0.0
 * @var array $methods
 * @return array
 */
function xturbo_add_shipping_method($methods) {
    $methods[] = 'XTurbo_Shipping_Method';

    return $methods;
}
add_filter('woocommerce_shipping_methods', 'xturbo_add_shipping_method');

function xturbo_init() {
    require_once 'includes/functions.php';
    require_once 'includes/class-xturbo-shipment.php';

    add_action('woocommerce_admin_order_data_after_shipping_address', [
        new XTurbo_Shipment_Method(),
        'shipment_template',
    ]);
}
add_action('init', 'xturbo_init');

require_once 'includes/class-xturbo-ajax.php';
add_action('admin_init', [
    new XTurbo_Ajax(),
    'init',
]);

function xturbo_enqueue_scripts() {
    $screen = get_current_screen();

    if (is_admin() && $screen->id == 'shop_order') {
        wp_enqueue_style('xturbo-app-css', plugin_dir_url(__FILE__) . 'public/css/app.css', [], '1.0.0');

        wp_enqueue_script('xturbo-app-js', plugin_dir_url(__FILE__) . 'public/js/app.js', ['wp-i18n'], '1.0.0', true);
        wp_localize_script('xturbo-app-js', 'xturbo_i18n', [
            'admin_ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xturbo_nonce'),
        ]);
    }
}
add_action('admin_enqueue_scripts', 'xturbo_enqueue_scripts');

function xturbo_woocommerce_order_statuses($statuses) {
    $new_statuses = [];

    foreach ($statuses as $key => $status) {
        $new_statuses[$key] = $status;

        if ($key === 'wc-processing') {
            $new_statuses['wc-xturbo'] = __('Shipped to XTurbo', 'xturbo');
            $new_statuses['wc-xturbo-failed'] = __('XTurbo Failed', 'xturbo');
            $new_statuses['wc-xturbo-delivered'] = __('XTurbo Delivered', 'xturbo');
            $new_statuses['wc-xturbo-return_to_client'] = __('XTurbo Return to Client', 'xturbo');
        }
    }

    return $new_statuses;
}
add_filter('wc_order_statuses', 'xturbo_woocommerce_order_statuses');

function xturbo_order_details_cron_callback() {
    $orders = new WP_Query([
        'post_type' => 'shop_order',
        'numberposts' => -1,
        'post_status' => 'wc-xturbo',
        'fields' => 'ids',
    ]);

    foreach ($orders->posts as $order_id) {
        $statuses = xturbo_get_order_statuses(xtrubo_get_order_tracking_id($order_id));

        if (! empty($statuses)) {
            $status = end($statuses);

            if ($status['type'] == 'تم التوصيل') {
                $order->update_status('xturbo-delivered');
            } else if ($status['type'] == 'اعادة للعميل') {
                $order->update_status('xturbo-return_to_client');
            } else if ($status['type'] == 'ملغي') {
                $order->update_status('cancelled');
            }
        }
    }
}
add_action('xturbo_order_details_cron', 'xturbo_order_details_cron_callback');

function xturbo_register_woocommerce_statuses() {
    register_post_status('wc-xturbo', [
        'label' => 'wc-xturbo',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('XTurbo <span class="count">(%s)</span>', 'XTurbo Processing <span class="count">(%s)</span>')
    ]);

    register_post_status('wc-xturbo-failed', [
        'label' => 'wc-xturbo-failed',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('XTurbo Failed <span class="count">(%s)</span>', 'XTurbo Failed <span class="count">(%s)</span>')
    ]);
}
add_action('init', 'xturbo_register_woocommerce_statuses');