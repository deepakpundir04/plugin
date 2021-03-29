<?php
/**
 * Plugin Name: Advanced Bank Transfer
 * Plugin URI: https://wordpress.org/plugins/additional-apyment-method
 * Description: This plugin will create a advanced payment method in woocomerce payment options .
 * Version: 1.0
 * Author: Dpundir
 * Author URI: 
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action('plugins_loaded', 'init_advanced_bank_transfer');
function init_advanced_bank_transfer(){

    class WC_Gateway_Advanced_Bank_Transfer extends WC_Payment_Gateway {

        public $domain;

        public function __construct() {

            $this->domain = 'advanced_bank_transfer';

            $this->id                 = 'advanced_bank_transfer';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'Advanced Bank Transfer', $this->domain );
            $this->method_description = __( 'Allows payments with Advanced Back Transfer.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }

        public function init_form_fields() {

            $this->form_fields = apply_filters( 'wc_advanced_bank_transfer_form_fields', array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable ', $this->domain ),
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain  ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __('Advanced bank transfer', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __('Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => __('This is custom Instructions to use this plugin', $this->domain),
                    'desc_tip'    => true,
                ),
            ));
        }

        public function thankyou_page() {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }

        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'advanced_bank_transfer' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                // echo wpautop( wptexturize( $description ) );
            }
            ?>
            <form action="<?php echo admin_url('admin-ajax.php');?>" method="POST">
                <label for="myfile">Select a file:</label>
                <input type="file" id="myfile" name="advanced_bank_transfer_file">
                <input type="submit" id="myfiles" class="myfiles" value="submit">
            </form>
            <div id="custom_input">
                <input type="" id="upload_url" name="upload_url"class="upload_url" value="">
            </div>
            <?php
        }

        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );
            // var_dump(WC()->customer->get_shipping_country());die;

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            $order->update_status( $status, __( 'Checkout with Advanced_Bank_Transfer ', $this->domain ) );

            $order->reduce_order_stock();

            WC()->cart->empty_cart();

            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_abt_gateway_class' );
function add_abt_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Advanced_Bank_Transfer'; 
    return $methods;
}

add_action('woocommerce_checkout_process', 'advanced_bank_transfer_custom_payment');
function advanced_bank_transfer_custom_payment(){

    if($_POST['payment_method'] != 'advanced_bank_transfer')
        return;

    if( !isset($_POST['upload_url']) || empty($_POST['upload_url']) )
        wc_add_notice( __( 'Please upload your file'), 'error' );

}

add_action( 'woocommerce_checkout_update_order_meta', 'advanced_bank_transfer_update_order_meta' );
function advanced_bank_transfer_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'advanced_bank_transfer')
        return;

    update_post_meta( $order_id, 'upload_url', $_POST['upload_url'] );
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'advanced_bank_transfer_checkout_field_display_admin_order_meta', 10, 1 );
function advanced_bank_transfer_checkout_field_display_admin_order_meta($order){
    $oder_id = $order->get_id();
    $method = get_post_meta( $oder_id, '_payment_method', true );
    if($method != 'advanced_bank_transfer')
        return;

    $mobile = get_post_meta( $oder_id, 'upload_url', true );

    echo '<iframe src='.$mobile.' ></iframe><p><strong>'.__( 'Mobile Number' ).':</strong> ' . $mobile . '</p>';

}

add_action( 'wp_enqueue_scripts', 'add_advanced_bank_transfer_js' );
function add_advanced_bank_transfer_js(){
    wp_enqueue_script('jquery');

    wp_enqueue_script( 'ajax-script', plugins_url( 'custompayment.js', __FILE__ ), array( 'jquery' ) );

    wp_localize_script( 'ajax-script', 'ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'we_value' => wp_create_nonce('abt_transer_request')
    ) );
}

add_action( 'wp_ajax_nopriv_handle_apt_ajax_request', 'advanced_bank_transfer_handle_apt_ajax_request' ); 
add_action( 'wp_ajax_handle_apt_ajax_request', 'advanced_bank_transfer_handle_apt_ajax_request' );
function advanced_bank_transfer_handle_apt_ajax_request() {

    check_ajax_referer('abt_transer_request', 'security');

    $uploadedfile = $_FILES['file'];
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if(isset($movefile['url'])){
        echo json_encode(['code'=>200,'data' => $movefile ]);
        exit;
    }
    else{
        echo json_encode(['code'=>404, 'msg'=>'Some thing is wrong! Try again.']);
        exit;
    }
}