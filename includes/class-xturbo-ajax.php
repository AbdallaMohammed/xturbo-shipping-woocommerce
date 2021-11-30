<?php

class XTurbo_Ajax {
    /**
     * Register ajax actions.
     * 
     * @since 1.0.0
     */
    public function init() {
        add_action('wp_ajax_xturbo_get_cities', [$this, 'get_cities']);
        add_action('wp_ajax_xturbo_get_order_settings', [$this, 'get_order_settings']);
        add_action('wp_ajax_xturbo_shipment', [$this, 'create_shipment']);
        add_action('wp_ajax_xturbo_get_shipment', [$this, 'get_shipment']);
    }

    public function get_cities() {
        check_admin_referer('xturbo_nonce', 'nonce');

        wp_send_json_success(xturbo_get_cities());
    }

    public function get_order_settings() {
        check_admin_referer('xturbo_nonce', 'nonce');

        $order_id = absint($_REQUEST['order_id']);

        $order = wc_get_order($order_id);

        if (! $order) {
            return wp_send_json_error();
        }

        $total_quantity = 0;
        $total_weight = 0;
        $total_length  = 0;
        $total_width = 0;
        $total_height = 0;

        $products_array = [];
        foreach ($order->get_items() as $key => $item) {
            $product = $item->get_product();

            if (is_numeric($item->get_quantity())) {
                $total_quantity += (int) $item->get_quantity();
            }

            if (is_numeric($product->get_weight())) {
                $total_weight += (int) $product->get_weight();
            }

            if (is_numeric($product->get_length())) {
                $total_length += (int) $product->get_length();
            }

            if (is_numeric($product->get_width())) {
                $total_width += (int) $product->get_width();
            }
            
            if (is_numeric($product->get_height())) {
                $total_height += (int) $product->get_height();
            }

            $products_array[] = [
                'id' => $item->get_id(),
                'quantity' => $item->get_quantity(),
                'name' => $item->get_name(),
                'total' => $item->get_total(),
                'sku' => $product->get_sku(),
                'weight' => $product->get_width(),
                'height' => $product->get_height(),
                'length' => $product->get_length(),
                'width' => $product->get_width(),
            ];
        }

        $receiver_name = $order->get_shipping_first_name();
        if (empty($receiver_name)) {
            $receiver_name = $order->get_billing_first_name();
        }

        $receiver_phone = $order->get_shipping_phone();
        if (empty($receiver_phone)) {
            $receiver_phone = $order->get_billing_phone();
        }

        $deliver_address = $order->get_shipping_address_1();
        if (empty($deliver_address)) {
            $deliver_address = $order->get_billing_address_1();
        }

        $deliver_city = $order->get_shipping_city();
        if (empty($deliver_city)) {
            $deliver_city = $order->get_billing_city();
        }

        $cities = xtubro_get_formatted_cities();
        
        $deliver_city = isset($cities[$deliver_city]) ? $cities[$deliver_city] : 0;
        $pickup_city = WC()->countries->get_base_city();
        $pickup_city = isset($cities[$pickup_city]) ? $cities[$pickup_city] : 0;

        return wp_send_json_success([
            'pickupAddress' => WC()->countries->get_base_address(),
            'receiverName' => $receiver_name,
            'receiverPhone' => $receiver_phone,
            'deliverAddress' => $deliver_address,
            'packaging' => 5,
            'fragile' => 1,
            'weight' => round($total_weight),
            'length' => round($total_length),
            'width' => round($total_width),
            'height' => round($total_height),
            'quantity' => round($total_quantity),
            'clientRef' => $order->get_id(),
            'payment_type' => $order->get_payment_method(),
            'pickupCity' => $pickup_city,
            'deliverCity' => $deliver_city,
            'cod' => $order->get_total(),
            'note' => 'WordPress',
            'comment' => json_encode($products_array, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function create_shipment() {
        check_admin_referer('xturbo_nonce', 'nonce');

        $token = xturbo_get_token();
        if (! $token) {
            return wp_send_json_error([
                'success' => false,
                'message' => __('XTurbo: Invalid email, or password.'),
            ]);
        }

        $order = wc_get_order(absint($_REQUEST['order_id']));

        if (! $order) {
            return wp_send_json_error([
                'success' => false,
                'message' => __('XTurbo: Invalid WooCommerce order.'),
            ]);
        }

        $post_data = array_diff_key(
            $_REQUEST,
            array_flip([
                'action',
                'nonce'
            ])
        );

        $response = wp_remote_post('https://portal.xturbox.com/api/v1/client/createOrder', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode($post_data),
        ]);

        if (is_wp_error($response)) {
            return wp_send_json_error($response);
        }

        $body = json_decode($response['body']);

        if (! $body->success) {
            if ($body->message == 'Duplicate') {
                $order->update_status('xturbo');
            } else {
                $order->update_status('xturbo-failed');
            }

            if ($body->error) {
                $errors = get_object_vars($body->error);

                return wp_send_json_error([
                    'success' => false,
                    'message' => implode(', ', array_keys($errors)),
                ]);
            }

            return wp_send_json_error([
                'success' => false,
                'message' => $body->message,
            ]);
        }

        xturbo_set_tracking_id($body->order->id, $order);

        return wp_send_json_success([
            'success' => true,
            'message' => __('XTurbo: Order Created Successfully.'),
        ]);
    }

    public function get_shipment() {
        check_admin_referer('xturbo_nonce', 'nonce');

        $tracking_id = xtrubo_get_order_tracking_id(absint($_REQUEST['order_id']));

        $data = [
            'status' => xturbo_get_order_statuses($tracking_id),
            'tracking_id' => $tracking_id,
        ];

        // Hmm, it's a bug!
        if (is_wp_error($response) || empty($data['status'])) {
            $data['status'] = __('Order Created', 'xturbo');

            return wp_send_json_success($data);
        }

        return wp_send_json_success($data);
    }
}