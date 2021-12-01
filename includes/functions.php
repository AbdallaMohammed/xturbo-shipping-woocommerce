<?php

if (! function_exists('xturbo_get_token')) {
    /**
     * Reterive XTurbo login token.
     * 
     * @return WP_Error|string|bool
     */
    function xturbo_get_token() {
        if (wp_cache_get('xturbo_user_token')) {
            return wp_cache_get('xturbo_user_token');
        }

        $settings = get_option('woocommerce_xturbo_settings');

        if (empty($settings['email']) || empty($settings['password'])) {
            return new WP_Error('xturbo_invalid_email_or_password', __('XTubo: Email, or password is invalid.', 'xturbo'));
        }

        $response = wp_remote_post('https://portal.xturbox.com/api/v1/client/login', [
            'headers' => [
                'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'email' => $settings['email'],
                'password' => $settings['password'],
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode($response['body']);

        if ($body->success) {
            wp_cache_set('xturbo_user_token', $body->token);

            return $body->token;
        }

        return false;
    }
}

if (! function_exists('xturbo_get_cities')) {
    /**
     * Get cities list.
     * 
     * @return array
     */
    function xturbo_get_cities() {
        $token = xturbo_get_token();
        
        if (! $token) {
            return [];
        }

        if (wp_cache_get('xturbo_cities_list')) {
            return json_decode(wp_cache_get('xturbo_cities_list'), true);
        }

        $response = wp_remote_get('https://portal.xturbox.com/api/v1/client/cities', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        wp_cache_set('xturbo_cities_list', $response['body']);

        return json_decode($response['body'], true);;
    }
}

if (! function_exists('xtubro_get_formatted_cities')) {
    /**
     * Get formatted cities.
     * 
     * @return array
     */
    function xtubro_get_formatted_cities() {
        $data = [];
        $cities = xturbo_get_cities();

        if (! empty($cities)) {
            foreach ($cities as $city) {
                $data[$city['name_ar']] = $city['id'];
            }
        }

        return $data;
    }
}

if (! function_exists('xturbo_set_tracking_id')) {
    /**
     * Set Order Traking ID.
     * 
     * @param string $tracking_id
     * @param int|WC_Order $order
     * @return bool
     */
    function xturbo_set_tracking_id($tracking_id, $order) {
        if (is_object($order)) {
            $order = $order->get_id();
        }

        return update_post_meta($order, 'xturbo_order_tracking_id', $tracking_id);
    }
}

if (! function_exists('xtrubo_get_order_tracking_id')) {
    /**
     * Reterive order's tracking id.
     * 
     * @param int|WC_Order $order
     * @return string|int
     */
    function xtrubo_get_order_tracking_id($order) {
        if (is_object($order)) {
            $order = $order->get_id();
        }

        return get_post_meta($order, 'xturbo_order_tracking_id', true);
    }
}

if (! function_exists('xturbo_get_order_statuses')) {
    /**
     * Get XTurbo order statuses.
     * 
     * @param int $tracking_id
     * @return array
     */
    function xturbo_get_order_statuses($tracking_id) {
        $token = xturbo_get_token();
        if (! $token) {
            return '';
        }

        $response = wp_remote_get('https://portal.xturbox.com/api/v1/client/trackOrder/' . $tracking_id, [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ]);

        $statuses = json_decode($response['body'], true);

        // Hmm, it's a bug!
        if (is_wp_error($response) || empty($statuses)) {
            return '';
        }

        return $statuses;
    }
}

if (! function_exists('xturbo_get_order_products')) {
    /**
     * Get order products.
     * 
     * @param WC_Order $order
     * @return array
     */
    function xturbo_get_order_products($order) {
        $products_array = [];

        foreach ($order->get_items() as $key => $item) {
            $product = $item->get_product();

            $products_array[] = $item->get_name();
        }

        return $products_array;
    }
}