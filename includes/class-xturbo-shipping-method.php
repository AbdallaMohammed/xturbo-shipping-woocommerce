<?php

class XTurbo_Shipping_Method extends WC_Shipping_Method {
    /**
     * XTurbo_Shipping_Method Constructor.
     */
    public function __construct() {
        $this->id = 'xturbo';

        $this->method_title = __('XTurbo', 'xturbo');
        $this->method_description = __('XTurbo Shipping Method', 'xturbo');
        $this->title = __('XTurbo Shipping', 'xturbo');

        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';

        $this->init();
    }

    public function init() {
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function calculate_shipping($packages = []) {
        if (! empty($this->settings['shipping_rate'])) {
            $this->add_rate([
                'id' => $this->id,
                'title' => $this->title,
                'cost' => (int) $this->settings['shipping_rate'],
            ]);
        }
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enabled', 'xturbo'),
                'type' => 'checkbox',
                'description' => __('Enable XTurbo Shipping', 'xturbo'),
                'default' => 'yes',
            ],
            'email' => [
                'title' => __('Email', 'xturbo'),
                'type' => 'text',
            ],
            'password' => [
                'title' => __('Password', 'xturbo'),
                'type' => 'text',
            ],
            'shipping_rate' => [
                'title' => __('Shipping Rate', 'xturbo'),
                'type' => 'number',
            ],
        ];
    }
}