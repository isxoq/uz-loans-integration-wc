<?php
    /*
    Plugin Name: OCTO.uz Payment Gateway
    Plugin URI:  https://octo.uz
    Description: OCTO.uz Payment Gateway for WooCommerce
    Version: 1.0.0
    Author: support@octo.uz
    Text Domain: octouz
     */

// Prevent direct access
    if (!defined('ABSPATH')) {
        exit;
    }

    add_action('plugins_loaded', 'woocommerce_octouz', 0);

    function woocommerce_octouz()
    {
        load_plugin_textdomain('octouz', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        // Do nothing, if WooCommerce is not available
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        // Do not re-declare class
        if (class_exists('WC_OCTOUZ')) {
            return;
        }

        class WC_OCTOUZ extends WC_Payment_Gateway
        {
            protected $merchant_id;
            protected $secret_key;
            protected $live;
            protected $currency;
            protected $convertWithWoocs;

            const UUID_META_NAME = '_octouz_payment_uuid';
            const OCTO_REQUEST_URL = 'https://secure.octo.uz/prepare_payment';

            public function __construct()
            {
                $plugin_dir = plugin_dir_url(__FILE__);
                $this->id = 'octouz';
                $this->title = __('[OCTO.uz] Pay using Credit Card', 'octouz');
                $this->description = __('Pay using Credit Card', 'octouz');
                $this->icon = apply_filters('woocommerce_octouz_icon', '' . $plugin_dir . 'octouz.png');
                $this->has_fields = false;

                $this->init_form_fields();
                $this->init_settings();

                // Populate options from the saved settings
                $this->merchant_id = $this->get_option('merchant_id');
                $this->secret_key = $this->get_option('secret_key');
                $this->live = $this->get_option('live');
                $this->currency = $this->get_option('currency');
                $this->convertWithWoocs = isset($GLOBALS['WOOCS']) ? $this->get_option('convertWithWoocs') : false;

                add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
                add_action('woocommerce_api_wc_' . $this->id, [$this, 'callback']);

                // Register Check Endpoint
                add_action('woocommerce_api_wc_octouz_check', [$this, 'callbackCheck']);
            }

            public function admin_options()
            {
                ?>
				<h3><?php _e('OCTO.uz Settings', 'octouz'); ?></h3>

				<p><img src="<?= $this->icon ?>" alt="OCTO.uz Logo"/></p>
				<p><?php _e('Configure Payment Gateway', 'octouz'); ?></p>

				<p style="border: 1px solid #294594; padding: 10px; border-radius: 5px; background: #a3e4ff;">
					<strong><?php _e('Your Notification URL is:', 'octouz'); ?></strong>
					<input readonly style="border: 1px solid #cacaca; width: 300px;"
					       value="<?= site_url('/?wc-api=wc_octouz'); ?>" class="input-text regular-input "/>
				</p>

				<table class="form-table">
                    <?php $this->generate_settings_html(); ?>
				</table>
                <?php
            }

            public function init_form_fields()
            {
                $this->form_fields = [
                    'enabled' => [
                        'title' => __('Enabled', 'octouz'),
                        'type' => 'checkbox',
                        'label' => __('Enabled', 'octouz'),
                        'default' => 'yes'
                    ],
                    'merchant_id' => [
                        'title' => __('Merchant ID', 'octouz'),
                        'type' => 'text',
                        'description' => __('Check your Merchant ID under &quot;Integration&quot; Tab at merchant.octo.uz', 'octouz'),
                        'default' => ''
                    ],
                    'secret_key' => [
                        'title' => __('Secret Key', 'octouz'),
                        'type' => 'text',
                        'description' => __('Generate new Secret Key under &quot;Integration&quot; Tab at merchant.octo.uz', 'octouz'),
                        'default' => ''
                    ],
                    'live' => [
                        'title' => __('Live Payments', 'octouz'),
                        'type' => 'checkbox',
                        'description' => __('Enable Live Payments', 'octouz'),
                        'default' => 'no'
                    ],
                    'currency' => [
                        'title' => __('Payment Currency', 'octouz'),
                        'type' => 'select',
                        'description' => '',
                        'default' => 'USD',
                        'options' => get_woocommerce_currencies()
                    ]
                ];

                if (isset($GLOBALS['WOOCS'])) {
                    $this->form_fields['convertWithWoocs'] = [
                        'title' => __('Convert currency using WOOCS', 'octouz'),
                        'type' => 'checkbox',
                        'description' => __('Convert currency using WOOCS (WooCommerce Currency Switcher) if the payment currency isnt the same as your site\'s currency', 'octouz'),
                        'default' => 'no'
                    ];
                }
            }

            public function generate_form($order_id)
            {
                // get order by id
                $order = new WC_Order($order_id);

                // get total payment amount in base currency
                $amount = $order->get_total();
                $currency = $order->get_currency();
                $description = sprintf(__('ORDER - #%1$s', 'octouz'), $order_id);
                $creationTimestamp = $order->get_date_created()->getTimestamp();

                if ($currency !== $this->currency && $this->convertWithWoocs) {
                    $WOOCS = $GLOBALS['WOOCS'];
                    $currencies = $WOOCS->get_currencies();
                    if (isset($currencies[$this->currency]) && isset($currencies[$currency])) {
                        $ccyTo = $currencies[$this->currency];
                        //$ccyFrom = $currencies[$currency];
                        //$welcome_currency = $WOOCS->get_welcome_currency();
                        $amount = number_format($amount * $ccyTo['rate'], 2, '.', '');
                    }
                }

                $requestData = [
                    'test' => (bool)($this->live !== 'yes'),
                    'octo_shop_id' => $this->merchant_id,
                    'octo_secret' => $this->secret_key,
                    'shop_transaction_id' => $order_id,
                    'auto_capture' => false,
                    'init_time' => date('Y-m-d H:i:s', $creationTimestamp),
                    'total_sum' => $amount,
                    'currency' => $this->currency,
                    "payment_methods" => [
                        [
                            "method" => "uzcard"
                        ],
                        [
                            "method" => "humo"
                        ],
                        [
                            "method" => "bank_card"
                        ]
                    ],
                    'description' => $description,
                    'return_url' => site_url('/?wc-api=wc_octouz_check&order_id=' . $order_id)
                    //"return_url" => $order->get_checkout_order_received_url()
                ];

                $jsonData = json_encode($requestData);

                $ch = curl_init(self::OCTO_REQUEST_URL);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData),
                ));

                $result = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode === 200) {
                    $data = json_decode($result, true);

                    // Write OCTO uuid in to post meta
                    $order->add_meta_data(self::UUID_META_NAME, $data['octo_payment_UUID']);
                    $order->save_meta_data();

                    if ($data['error'] !== 0) {
                        return '<p>OCTO.uz ERROR: ' . $data['errorMessage'] . '</p>';
                    }

                    $label_pay = __('Pay', 'octouz');
                    $label_cancel = __('Cancel Payment', 'octouz');

                    $form = <<<HTML
<a class="button cancel" href="{$order->get_cancel_order_url()}">&larr; $label_cancel</a>
<a class="button " href="{$data['octo_pay_url']}">$label_pay &rarr;</a>
HTML;

                    return $form;
                }

                return 'OCTO.uz: ERROR GENERATING ORDER';
            }

            public function process_payment($order_id)
            {
                $order = new WC_Order($order_id);

                return [
                    'result' => 'success',
                    'redirect' => add_query_arg(
                        'order_pay',
                        $order->get_id(),
                        add_query_arg('key', $order->get_order_key(), $order->get_checkout_payment_url(true))
                    )
                ];

            }

            public function receipt_page($order_id)
            {
                echo '<p>' . __('You\'re almost done. Press the &quot;Pay&quot; button to proceed.', 'octouz') . '</p>';
                echo $this->generate_form($order_id);
            }

            // Handle user redirect
            public function callbackCheck()
            {
                $order_id = (int)$_GET['order_id'];
                $order = $this->getOrder($order_id);

                $data = [
                    'octo_shop_id' => $this->merchant_id,
                    'octo_secret' => $this->secret_key,
                    'shop_transaction_id' => $order->get_id(),
                    'octo_payment_UUID' => $order->get_meta(self::UUID_META_NAME),
                ];

                $jsonData = json_encode($data);

                $ch = curl_init(self::OCTO_REQUEST_URL);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData),
                ));

                $result = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode === 200) {
                    $result = json_decode($result, true);

                    if ($result['status'] === 'created') {
                        return wp_redirect($order->get_cancel_order_url_raw());
                    }
                }

                return wp_redirect($order->get_checkout_order_received_url());
            }

            // Handle Incoming Request from OCTO.uz backend
            public function callback()
            {
                // Request Payload
                $requestData = json_decode(file_get_contents('php://input'), true);

                if (json_last_error() !== JSON_ERROR_NONE) { // handle Parse error
                    return $this->response(['status' => 'error', 'message' => 'incorrect input json']);
                }

                $order = $this->getOrder($requestData['shop_transaction_id']);
                $correctUUID = $order->get_meta(self::UUID_META_NAME);

                if ($correctUUID !== $requestData['octo_payment_UUID']) {
                    return $this->response(['status' => 'error', 'message' => 'UUID mismatch']);
                }

                $data = [
                    'octo_shop_id' => $this->merchant_id,
                    'octo_secret' => $this->secret_key,
                    'shop_transaction_id' => $requestData['shop_transaction_id'],
                    'octo_payment_UUID' => $requestData['octo_payment_UUID'],
                ];

                $jsonData = json_encode($data);

                $ch = curl_init(self::OCTO_REQUEST_URL);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData),
                ));

                $result = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode === 200) {
                    $result = json_decode($result, true);
                    if ($result['status'] === 'waiting_for_capture') {
                        $order->update_status('completed', 'No-AC OCTO.uz Payment UUID: ' . $result['octo_payment_UUID'], true);

                        return $this->response(['accept_status' => 'capture']);
                    } else if ($result['status'] === 'succeeded') {
                        $order->update_status('completed', 'AC OCTO.uz Payment UUID: ' . $result['octo_payment_UUID'], true);

                        return $this->response(['accept_status' => 'completed']);
                    } else if ($result['status'] === 'canceled') {
                        $order->update_status('failed', 'OCTO.uz payment ' . $result['octo_payment_UUID'] . ' cancelled at ' . date('Y-m-d H:i:s'), true);

                        return $this->response(['accept_status' => 'canceled']);
                    }

                    return $this->response(['status' => 'error', 'message' => 'UNSUPPORTED OCTO STATUS']);
                }

                return $this->response(['status' => 'error', 'message' => 'COULD NOT CHECK BACK']);
            }


            private function response($data)
            {
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=UTF-8');
                }

                echo json_encode($data);
                die();
            }

            private function getOrder($orderId)
            {
                try {
                    $order = new WC_Order($orderId);
                } catch (Exception $ex) {
                    return $this->response(['status' => 'error', 'message' => 'can not find order by id']);
                }

                return $order;
            }
        }

        // Register new Gateway

        function register_octouz_gateway($methods)
        {
            $methods[] = 'WC_OCTOUZ';

            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'register_octouz_gateway');
    }

?>