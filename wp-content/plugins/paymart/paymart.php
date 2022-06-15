<?php
/**
 * Plugin Name: Paymart Muddatli to'lov
 * Plugin URI:
 * Description: Paymart orqali muddatli to'lov.
 * Author: Isxoqjon Axmedov
 * Author URI: http://www.isaak.uz/
 * Version: 1.1.0
 * Text Domain: wcpg-special
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 20220
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   wcpg-special
 * @author    Isxoqjon Axmedov
 * @category  Admin
 * @copyright Copyright (c)  2022
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
defined('ABSPATH') or exit;
// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}
/**
 * Add the gateway to WC Available Gateways
 *
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Custom Special gateway
 * @since 1.0.0
 */


add_action( 'wp_footer', 'pexlechris_custom_checkout_jqscript' );
function pexlechris_custom_checkout_jqscript() {
    if ( is_checkout()) :
        ?>
        <script type="text/javascript">
            jQuery( function($){
                $('form.checkout').on('change', 'input[name="payment_method"]', function(){
                    $(document.body).trigger('update_checkout');
                });
            });
        </script>
    <?php
    endif;
}


add_action( 'woocommerce_calculate_totals', 'woocommerce_calculate_totals', 30 );
function woocommerce_calculate_totals( $cart ) {
    // make magic happen here...
    // use $cart object to set or calculate anything.
    $cart->subtotal = 350;

    print_r($_POST);
    if ( 'excl' === $cart->payment_method ) {
        $cart->subtotal_ex_tax  = 400;
    } else {
    }

}



function wc_add_special_to_gateways($gateways)
{
    $gateways[] = 'WC_Paymart';
    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'wc_add_special_to_gateways');
/**
 * Adds plugin page links
 *
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 * @since 1.0.0
 */
function wc_special_gateway_plugin_links($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paymart_payment') . '">' . __('Configure', 'wcpg-special') . '</a>'
    );
    return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_special_gateway_plugin_links');
/**
 * Paymart Payment Gateway
 *
 * Provides an Paymart Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Paymart
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Me
 */
add_action('plugins_loaded', 'wc_special_gateway_init', 11);
function wc_special_gateway_init()
{
    class WC_Paymart extends WC_Payment_Gateway
    {

        public $domain;

        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            $this->id = 'paymart_payment';
            $this->domain = 'wcpg-special';
            $this->icon = $this->get_option('logo_image');
            $this->has_fields = false;
            $this->method_title = __('Paymart Payment', $this->domain);

            // Define "payment type" radio buttons options field
            $this->options = array(
                3 => __('3 oy', $this->domain),
                6 => __('6 oy', $this->domain),
                9 => __('9 oy', $this->domain),
                12 => __('12 oy', $this->domain),
            );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');
            $this->order_status = $this->get_option('order_status');
            $this->status_text = $this->get_option('status_text');

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_checkout_create_order', array($this, 'save_order_payment_type_meta_data'), 10, 2);
            add_filter('woocommerce_get_order_item_totals', array($this, 'display_transaction_type_order_item_totals'), 10, 3);
            add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_payment_type_order_edit_pages'), 10, 1);
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

            // Customer Emails
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields()
        {
            $this->form_fields = apply_filters('wc_paymart_payment_form_fields', array(
                'enabled' => array(
                    'title' => __('Enable/Disable', $this->domain),
                    'type' => 'checkbox',
                    'label' => __('Enable Paymart Payment', $this->domain),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', $this->domain),
                    'type' => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', $this->domain),
                    'default' => __('Paymart Payment', $this->domain),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', $this->domain),
                    'type' => 'textarea',
                    'description' => __('Payment method description that the customer will see on your checkout.', $this->domain),
                    'default' => __('Please remit payment to Store Name upon pickup or delivery.', $this->domain),
                    'desc_tip' => true,
                ),
                'instructions' => array(
                    'title' => __('Instructions', $this->domain),
                    'type' => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page and emails.', $this->domain),
                    'default' => '', // Empty by default
                    'desc_tip' => true,
                ),
                'order_status' => array(
                    'title' => __('Order Status', $this->domain),
                    'type' => 'select',
                    'description' => __('Choose whether order status you wish after checkout.', $this->domain),
                    'default' => 'wc-completed',
                    'desc_tip' => true,
                    'class' => 'wc-enhanced-select',
                    'options' => wc_get_order_statuses()
                ),
                'status_text' => array(
                    'title' => __('Order Status Text', $this->domain),
                    'type' => 'text',
                    'description' => __('Set the text for the selected order status.', $this->domain),
                    'default' => __('Order is completed', $this->domain),
                    'desc_tip' => true,
                ),

                'endpoint_paymart' => array(
                    'title' => __('Paymart Endpoint Url', $this->domain),
                    'type' => 'text'
                ),
                'merchant_id' => array(
                    'title' => __('Paymart Merchant ID', $this->domain),
                    'type' => 'text'
                ),
                'merchant_api_key' => array(
                    'title' => __('Paymart API Key', $this->domain),
                    'type' => 'text'
                ),

                //Percentages
                'month_3' => array(
                    'title' => __('3 Month Percentage', $this->domain),
                    'type' => 'number'
                ),
                'month_6' => array(
                    'title' => __('6 Month Percentage', $this->domain),
                    'type' => 'number'
                ),
                'month_9' => array(
                    'title' => __('9 Month Percentage', $this->domain),
                    'type' => 'number'
                ),
                'month_12' => array(
                    'title' => __('12 Month Percentage', $this->domain),
                    'type' => 'number'
                ),

                'logo_image' => array(
                    'title' => __('Logo URL', $this->domain),
                    'type' => 'text'
                ),


            ));
        }

        /**
         * Output the "payment type" radio buttons fields in checkout.
         */
        public function payment_fields()
        {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }

            echo '<style>#transaction_type_field label.radio { display:inline-block; margin:0 .8em 0 .4em}</style>';

            $option_keys = array_keys($this->options);

            woocommerce_form_field('transaction_type', array(
                'type' => 'radio',
                'class' => array('transaction_type form-row-wide'),
                'label' => __('Loan month', $this->domain),
                'options' => $this->options,
            ), reset($option_keys));
        }

        /**
         * Save the chosen payment type as order meta data.
         *
         * @param object $order
         * @param array $data
         */
        public function save_order_payment_type_meta_data($order, $data)
        {
            if ($data['payment_method'] === $this->id && isset($_POST['transaction_type']))
                $order->update_meta_data('_transaction_type', esc_attr($_POST['transaction_type']));
        }

        /**
         * Output for the order received page.
         *
         * @param int $order_id
         */
        public function thankyou_page($order_id)
        {
            $order = wc_get_order($order_id);

            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }

        /**
         * Display the chosen payment type on the order edit pages (backend)
         *
         * @param object $order
         */
        public function display_payment_type_order_edit_pages($order)
        {
            if ($this->id === $order->get_payment_method() && $order->get_meta('_transaction_type')) {
                $options = $this->options;
                echo '<p><strong>' . __('Transaction type') . ':</strong> ' . $options[$order->get_meta('_transaction_type')] . '</p>';
            }
        }

        /**
         * Display the chosen payment type on order totals table
         *
         * @param array $total_rows
         * @param WC_Order $order
         * @param bool $tax_display
         * @return array
         */
        public function display_transaction_type_order_item_totals($total_rows, $order, $tax_display)
        {
            if (is_a($order, 'WC_Order') && $order->get_meta('_transaction_type')) {
                $new_rows = []; // Initializing
                $options = $this->options;

                // Loop through order total lines
                foreach ($total_rows as $total_key => $total_values) {
                    $new_rows[$total_key] = $total_values;
                    if ($total_key === 'payment_method') {
                        $new_rows['payment_type'] = [
                            'label' => __("Transaction type", $this->domain) . ':',
                            'value' => $options[$order->get_meta('_transaction_type')],
                        ];
                    }
                }

                $total_rows = $new_rows;
            }
            return $total_rows;
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {
            if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()
                && $order->has_status($this->order_status)) {
                echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
            }
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            // Mark as on-hold (we're awaiting the payment)
            $order->update_status($this->order_status, $this->status_text);

            // Reduce stock levels
            wc_reduce_stock_levels($order->get_id());

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
    }
}