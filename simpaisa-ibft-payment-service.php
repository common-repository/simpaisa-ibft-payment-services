<?php
/*
 * Plugin Name: Simpaisa IBFT Payment Services
 * Plugin URI: https://www.simpaisa.com/
 * Description: Providing Easy To Integrate IBFT Banks
 * Author: Simpaisa Pvt Ltd
 * Author URI: https://www.simpaisa.com
 * Version: 1.0.9
*/

header("Access-Control-Allow-Origin: *");

add_filter("woocommerce_payment_gateways", "simpaisa_ibft_add_gateway_class");

function simpaisa_ibft_add_gateway_class($gateways)
{
    $gateways[] = "WC_Simpaisa_IBFT_Gateway";

    return $gateways;
}


// load woo simpaisa plugin and its options
add_action('plugins_loaded', 'simpaisa_ibft_init_gateway_class');

function simpaisa_ibft_init_gateway_class()
{
    if (class_exists('WC_Payment_Gateway')) {
        class WC_Simpaisa_IBFT_Gateway extends WC_Payment_Gateway
        {
            public function __construct()
            {
                $this->id = "simpaisa_woo_ibft";
                $this->icon = "";
                $this->has_fields = true;
                $this->method_title = "Simpaisa IBFT Payment";
                $this->method_description = "Pay With Your IBFT Simpaisa Payment Services";

                $this->supports = ["products"];

                $this->init_form_fields();
                $this->init_settings(); //for custom settings fields
                $this->title = $this->get_option("ibft_title");
                $this->description = $this->get_option("ibft_description");
                $this->enabled = $this->get_option("ibft_enabled");
                $this->base_url = $this->get_option("ibft_base_url");
                $this->merchant_id = $this->get_option("ibft_merchant_id");
                $this->is_items = $this->get_option("ibft_is_items");

                add_action('wp_enqueue_scripts', array($this, 'simpaisa_ibft_payment_scripts'));

                add_action("wp_footer", [$this, "simpaisa_ibft_script"]);

                add_action("wp_enqueue_scripts", [$this, "simpaisa_ibft_payment_stylesheet"], 20);

                add_action("woocommerce_update_options_payment_gateways_" . $this->id, [$this, "process_admin_options"]);

                add_action("woocommerce_api_simpaisa_ibft_verify_otp", [$this, "simpaisa_ibft_verify_otp"]);
                add_action("woocommerce_api_simpaisa_notify", [$this, "simpaisa_notify"]);
            }

            public function payment_fields()
            {

                // I will echo() the form, but you can close PHP tags and print it directly in HTML
                printf('<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-ibft-card-form wc-payment-form" style="background:transparent;">');

                // Add this action hookif you want your custom payment gateway to support it
                do_action("simpaisa_ibft_form_start", $this->id);

                $this->HtmlContent();

                do_action("simpaisa_ibft_form_end", $this->id);

                printf(' </fieldset>');
            }



            public function simpaisa_notify()
            {
                global $woocommerce;
                $json = $this->sanitize_input(file_get_contents("php://input"));


                error_log('Simpaisa log :: IBFT - Postback Data ' . $json);

                if (strpos($json, '=') !== false) {
                    $data = [];
                    $json =  str_replace('{', '', $json);
                    $json =  str_replace('}', '', $json);
                    foreach (explode(",", $json) as $value) {
                        $data[trim(explode("=", $value)[0])] = trim(explode("=", $value)[1]);
                    }
                } else {
                    $data = json_decode($json, true);
                }


                $transactionId = $this->sanitize_input($data["userKey"]);
                $status = $this->sanitize_input($data["status"]);
                $merchantId = $this->sanitize_input($data["merchantId"]);
                if (!isset($data['transactionId'])) {
                    $sp_transactionId = 'Null';
                } else {
                    $sp_transactionId = $this->sanitize_input($data['transactionId']);
                }


                $_orderID = explode('-', $transactionId)[0];

           

                if (get_post_meta($_orderID, '_sp_payment_method', true) == 'IBFT') {
                    
                    error_log('Simpaisa log :: Postback Order No # ' . $_orderID . ' status ' . $status . ' merchantId ' . $merchantId);

                    if (isset($merchantId) && isset($status) && isset($_orderID)) {
                        $order = wc_get_order($_orderID);

                        $order_status = $order->get_status();

                        if ($order_status == "pending" || $order_status == "failed") {
                            if ($status == "0000") {
                                $order->payment_complete();
                                wc_reduce_stock_levels($_orderID);
                                $order_status = $order->get_status();
                                update_post_meta($_orderID, '_sp_transactionId', $sp_transactionId);
                                $note = __("Simpaisa Postback - Order Id : '$transactionId' , Trans Id : '$sp_transactionId' , CB Status : $status , Order Status : $order_status");
                                $order->add_order_note($note);

                                echo json_encode(["respose_code" => "0000", "order_status" => $order_status, "status" => $status, "message" => "Order status has been updated",]);
                            } else {
                                $order->update_status("failed");
                                $order_status = $order->get_status();

                                $note = __("Simpaisa Postback - Order Id : '$transactionId' , Trans Id : '$sp_transactionId' , CB Status : $status , Order Status : $order_status");
                                $order->add_order_note($note);

                                echo json_encode(["respose_code" => "0000", "order_status" => $order_status, "status" => $status, "message" => "Order status has been updated",]);
                            }
                        } else {
                            $note = __("Simpaisa Postback - Order Id : '$transactionId' , Trans Id : '$sp_transactionId'  , CB Status : $status , Order Status : $order_status");
                            $order->add_order_note($note);

                            echo json_encode(["respose_code" => "1003", "order_status" => $order_status, "status" => $status, "message" => "Order status already modified",]);
                        }
                    } else {
                        error_log('Simpaisa log :: Postback fields are missing');
                        echo json_encode(["respose_code" => "1001", "message" => "Field(s) are required",]);
                        exit();
                    }
                }
            }

            public function HtmlContent()
            {

                if (!session_id()) {
                    session_start();
                }

                printf('<div class="simpaisa-ibft-card">');

                $bank_list = "";


                $baseUrl = rtrim($this->base_url, '/') . "/";
                $baseUrl = str_replace('/index.php', '', $baseUrl) . 'api/';
                $BankUrl = $baseUrl . 'nift-backend-api.php';

                $payload = array(
                    'body' => [
                        'method' => 'list'
                    ],
                    'timeout'     => 30,
                    'redirection' => 5,  // added
                    'httpversion' => '1.0',
                    'method' => 'POST'
                );

                $response = wp_remote_post($BankUrl, $payload);

                $__response     = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($__response)) {
                    foreach ($__response as $key => $value) {
                        $bank_list .= "<option value='" . $value['id'] . "'>" . $value['name'] . "</option>";
                    }
                }
                printf('
                    <div class="simpaisa-ibft-body">
                        <div class="simpaisa-ibft-detail">
                            <label>Enter Your Bank</label>
                            <select name="sp_ibft_bank" class="sp_ibft_field" id="sp_ibft_bank">
                            <option value="0">Select Bank</option>' . $bank_list . '
                            </select>
                            <div class="simpaisa-ibft-bank-err"></div>

                            <label>Enter Your Account Number</label>
                            <input name="sp_ibft_account"  minlength="5" maxlength="15" class="sp_ibft_field" id="sp_ibft_account" placeholder="xxxxxxxxxxxxxxxx" type="text" autocomplete="off">
                            <div class="simpaisa-ibft-account-err"></div>


                            <label>Enter Your CNIC Number</label>
                            <input name="sp_ibft_cnic"  minlength="13" maxlength="13" class="sp_ibft_field" id="sp_ibft_cnic" placeholder="xxxxxxxxxxxxx" type="text" autocomplete="off">
                            <div class="simpaisa-ibft-cnic-err"></div>

                            </div>

                            <div class="simpaisa-ibft-otp" style="display:none">
                                <label>Enter Your OTP</label>
                                <input name="sp_ibft_otp"   class="sp_ibft_field" id="sp_ibft_otp" placeholder="xxxxxxxxxx" type="text" autocomplete="off">
                                <div class="simpaisa-ibft-otp-err"></div>
                                </div>
                            </div>
                        <input type="hidden" name="sp_ibft_type" id="sp_ibft_type" value="1">
                    </div>');
            }


            public function process_payment($order_id)
            {
                global $woocommerce;

                if (!session_id()) {
                    session_start();
                }

                // Create OTP Code
                if (isset($_POST['sp_ibft_type']) && $this->sanitize_input($_POST['sp_ibft_type']) == 1) {
                    $currency = strtoupper(get_woocommerce_currency());

                    if ($currency != "PKR") {
                        wc_add_notice(__("Invalid currency " . $currency . ", <strong>Wallet Payment is allowed only for Pakistani currency (PKR)</strong>"), "error");
                        return false;
                    }

                    if ($this->base_url == "" || $this->merchant_id == "") {
                        wc_add_notice(__("<strong>Payment Base URL and Merchant Id are required</strong>, please check your Simpaisa IBFT Payment Configuration."), "error");
                        return false;
                    }

                    if ($_POST['sp_ibft_bank'] == 0) {
                        wc_add_notice(__('Kindly select any Bank'), 'error');
                        return false;
                    }

                    if ($this->sanitize_input($_POST['sp_ibft_account']) == "" && strlen($_POST['sp_ibft_account']) <= 15) {
                        wc_add_notice(__('Kindly enter valid Account No'), 'error');
                        return false;
                    }

                    if ($this->sanitize_input($_POST['sp_ibft_cnic']) == "" || strlen($_POST['sp_ibft_cnic']) < 13) {
                        wc_add_notice(__('Kindly enter valid Cnic Number'), 'error');
                        return false;
                    }

                    if (isset($order_id)) {
                        $order = wc_get_order($order_id);
                        $_payment_method_title = 'Simpaisa IBFT Payment';
                        update_post_meta($order_id, '_payment_method_title', $_payment_method_title);
                        update_post_meta($order_id, '_sp_payment_method', 'IBFT');
                        $merchant_id = $this->merchant_id;
                        $order_amount = $order->get_total();

                        $merchant_id = $this->sanitize_input($this->merchant_id);

                        $baseUrl = rtrim($this->base_url, '/') . "/";
                        $baseUrl = str_replace('/index.php', '', $baseUrl) . 'api/';
                        $paymentUrl = $baseUrl . 'nift-backend-api.php';

                        $_sp_orderId = substr(md5(uniqid(rand(), true)), 0, 6);

                        $sp_userKey = $order_id . '-' . $_sp_orderId;

                        $authorization = 'Basic ' . base64_encode($sp_userKey . ':' . $this->sanitize_input($_POST['sp_ibft_account']));



                        $payload = array(
                            'body' => [
                                "userKey" => $sp_userKey,
                                "merchantId" => $merchant_id,
                                "operatorId" => "100018",
                                "amount" => $order_amount,
                                "accNo" => $this->sanitize_input($_POST['sp_ibft_account']),
                                "cnic" => $this->sanitize_input($_POST['sp_ibft_cnic']),
                                "bankId" => $this->sanitize_input($_POST['sp_ibft_bank']),
                                'method' => 'initiate'
                            ],
                            'timeout'     => 30,
                            'redirection' => 5,  // added
                            'httpversion' => '1.0',
                            'method' => 'POST',
                            'headers' => array('Authorization' => $authorization)
                        );

                        $response = wp_remote_post($paymentUrl, $payload);

                        $note = __("Simpaisa IBFT Payment initiate, Order # '$sp_userKey'");
                        $order->add_order_note($note);

                        if (wp_remote_retrieve_response_code($response) != 200) {
                            $error_message = wp_remote_retrieve_response_code($response);
                            wc_add_notice(__(" Error: HTTP Response " . $error_message . " , <strong>Order payment transaction has been failed</strong> , please try again."), "error");
                            return false;
                        } elseif (is_wp_error($response) && count($response->get_error_messages()) > 0) {
                            $error_message = $response->get_error_message();
                            wc_add_notice(__(" Error: " . $error_message . " , <strong>Order payment transaction has been failed</strong> , please try again."), "error");
                            return false;
                        }

                        $__response = json_decode(wp_remote_retrieve_body($response), true);


                        if (isset($__response['transactionId']) && isset($__response['userKey'])) {
                            $sp_transactionId =  $this->sanitize_input($__response['transactionId']);
                            $sp_userKey =  $this->sanitize_input($__response['userKey']);

                            update_post_meta($order_id, '_sp_orderId', $sp_userKey);
                            update_post_meta($order_id, '_sp_transactionId', $sp_transactionId);
                            update_post_meta($order_id, '_sp_ibft_bank', $this->sanitize_input($_POST['sp_ibft_bank']));
                            update_post_meta($order_id, '_sp_accNo', $this->sanitize_input($_POST['sp_ibft_account']));

                            $status = $__response["status"];
                            $res_message = $__response["message"];


                            switch ($status) {
                                case '0000':
                                    $order_status = $order->get_status();
                                    $note = __("Simpaisa IBFT Payment - Trans Id : '$sp_transactionId' , Order Status : $order_status , Status : $res_message ");
                                    $order->add_order_note($note);

                                    wc_add_notice(__("Kindly type the otp"), "success");
                                    $order->update_status('pending');
                                    return ['result'   => 'success', 'redirect' => wc_get_checkout_url() . "?verify_otp=1"];
                                    break;

                                default:
                                    $note = __("Simpaisa IBFT Payment failed, Error '$res_message'");
                                    $order->add_order_note($note);
                                    wc_add_notice(__($res_message), "error");
                                    $order->update_status('failed');
                                    return ['result'   => 'error', 'redirect' => wc_get_checkout_url()];
                                    break;
                            }
                        } else {
                            $status = $__response["status"];
                            $res_message = $__response["message"];
                            $note = __("Simpaisa IBFT Payment failed, Error '$res_message'");
                            $order->add_order_note($note);
                            $order->update_status('failed');
                            wc_add_notice(__(" Error: " . $res_message . ", please try again."), "error");
                            return false;
                        }
                    }
                }

                // Verify OTP Code
                if (isset($_POST['sp_ibft_type']) && $_POST['sp_ibft_type'] == 2) {
                    if (empty($_POST['sp_ibft_otp'])) {
                        wc_add_notice(__('Invalid OTP'), 'error');
                        return ['result'   => 'success', 'redirect' => wc_get_checkout_url() . "?verify_otp=1"];
                    }

                    $order = wc_get_order($this->sanitize_input($order_id));
                    $order_amount = $order->get_total();
                    $baseUrl = rtrim($this->base_url, '/') . "/";
                    $baseUrl = str_replace('/index.php', '', $baseUrl) . 'api/';
                    $paymentUrl = $baseUrl . 'nift-backend-api.php';

                    $merchant_id = $this->merchant_id;
                    $userKey = get_post_meta($order_id, '_sp_orderId', true);
                    $accNo = get_post_meta($order_id, '_sp_accNo', true);
                    $bankId = get_post_meta($order_id, '_sp_ibft_bank', true);

                    $authorization = 'Basic ' . base64_encode($userKey . ':' . $accNo);

                    $payload = array(
                        'body' => [
                            "userKey" => $userKey,
                            "merchantId" => $merchant_id,
                            "operatorId" => "100018",
                            "bankId" => $bankId,
                            "amount" => $order_amount,
                            "accNo" => $accNo,
                            "otp" => $this->sanitize_input($_POST['sp_ibft_otp']),
                            'method' => 'verify'
                        ],
                        'timeout'     => 120,
                        'redirection' => 5,  // added
                        'httpversion' => '1.0',
                        'method' => 'POST',
                        'headers' => array(
                            'Authorization' => $authorization
                        )
                    );

                    $response = wp_remote_post($paymentUrl, $payload);

                    if (wp_remote_retrieve_response_code($response) != 200) {
                        $error_message = wp_remote_retrieve_response_code($response);
                        wc_add_notice(__(" Error: HTTP Response " . $error_message . " , <strong>OTP verification has been failed</strong> , please try again."), "error");
                        return ['result'   => 'success', 'redirect' => wc_get_checkout_url() . "?verify_otp=1"];
                    } elseif (is_wp_error($response) && count($response->get_error_messages()) > 0) {
                        $error_message = $response->get_error_message();
                        wc_add_notice(__(" Error: " . $error_message . " , <strong>OTP verification transaction has been failed</strong> , please try again."), "error");
                        return ['result'   => 'success', 'redirect' => wc_get_checkout_url() . "?verify_otp=1"];
                    }


                    $note = __("Simpaisa IBFT Payment verify, Order # '$userKey'");
                    $order->add_order_note($note);

                    $__response = json_decode(wp_remote_retrieve_body($response), true);

                    $status = $__response["status"];
                    $res_message = $__response["message"];

                    switch ($status) {
                        case '0000':
                            $note = __("Simpaisa IBFT Payment success, OTP has been verified successfully");
                            $order->add_order_note($note);
                            $order->payment_complete();
                            wc_reduce_stock_levels($order_id);
                            return ['result'   => 'success', "redirect" => $order->get_checkout_order_received_url()];
                            break;

                        default:
                            $note = __("Simpaisa IBFT Payment failed, Error '$res_message'");
                            $order->add_order_note($note);
                            wc_add_notice(__($res_message), "error");
                            $order->update_status('failed');
                            return ['result'   => 'error', 'redirect' => wc_get_checkout_url()];
                            break;
                    }
                }
                wp_die();
                exit();
            }

            public function init_form_fields()
            {

                $this->form_fields = [
                    "ibft_enabled" => ["title" => "Enable/Disable", "label" => "Enable Simpaisa IBFT", "type" => "checkbox", "description" => "", "default" => "no"],
                    "ibft_is_items" => ["title" => "Enable/Disable", "label" => "Enable Items", "type" => "checkbox", "description" => "", "default" => "yes"],
                    "ibft_title" => ["title" => "Title", "type" => "text", "description" => "This controls the title which the user sees during checkout.", "default" => "IBFT", "desc_tip" => true], "ibft_description" => ["title" => "Description", "type" => "textarea", "description" => "This controls the description which the user sees during checkout.", "default" => "Pay With Your IBFT Account via Simpaisa Payment Services", "desc_tip" => true],
                    "ibft_base_url" => ["title" => "Payment Base Url", "type" => "text"],
                    "ibft_merchant_id" => ["title" => "Merchant Id", "type" => "text"],
                    "ibft_webhookUrl" => ['css' => 'pointer-events:none;background:#00000024;font-size:12px;', 'title' => 'Webhook Url', 'description' => 'This is the notification URL. Simpaisa sends notification of each transactions on the provided URL.', 'default' => rtrim(site_url(), '/') . "/index.php/wc-api/simpaisa_notify"]
                ];
            }


            public function simpaisa_ibft_payment_stylesheet()
            {
                wp_register_style("simpaisa_ibft_stylesheet", plugins_url("assets/css/style.css", __FILE__));
                wp_enqueue_style("simpaisa_ibft_stylesheet");
            }

            public function simpaisa_ibft_payment_scripts()
            {
                // we need JavaScript to process a token only on cart/checkout pages, right?
                if (!is_cart() && !is_checkout() && !isset($_GET["pay_for_order"])) {
                    return;
                }

                // //if our payment gateway is disabled, we do not have to enqueue JS too
                if ("no" === $this->enabled) {
                    return;
                }


                // and this is our custom JS in your plugin directory that works with token.js
                wp_register_script("simpaisa_ibft_script", plugins_url("assets/js/main.js", __FILE__), false, '1.0.0', true);
                wp_enqueue_script("simpaisa_ibft_script");
            }

            //add_action('wp_footer', 'simpaisa_ibft_script');

            function simpaisa_ibft_script()
            {
                wp_localize_script('simpaisa_ibft_script', 'simpaisa_ibft_ajax', array($this, 'verify_otp' => rtrim(site_url(), '/') . "/index.php/wc-api/simpaisa_ibft_verify_otp"));

                //wp_register_script("simpaisa_ibft_script", plugins_url("assets/js/main.js", __FILE__), false, '1.0.0', true);
                wp_enqueue_script("simpaisa_ibft_script");
            }

            public function sanitize_input($sanitize_input, $default = null)
            {
                return isset($sanitize_input) ? sanitize_text_field($sanitize_input) : $default;
            }
        }
    }
}

configure_simpaisa_ibft_plugin();

function configure_simpaisa_ibft_plugin()
{

    class SimpaisaIBFTPluginConfiguration
    {

        public function __construct()
        {

            //It will fire when the plugin is activated and stop the user to install this plugin if woocommerce is not installed.
            register_activation_hook(__FILE__, [$this, 'plugin_activate_hook']);

            // Admin Notice
            add_action("admin_notices", [$this, "my_plugin_admin_notices",]);

            // Woocommerce plugin Notice
            add_action("admin_notices", [$this, "woocommerce_related_notices",]);
        }

        public function plugin_activate_hook()
        {
            if (!class_exists("WC_Payment_Gateway")) {
                $notices = get_option("my_plugin_deferred_admin_notices", []);
                $url = admin_url("plugins.php?deactivate=true");
                $notices[] = "Error: Install <b>WooCommerce</b> before activating this plugin. <a href=" . $url . ">Go Back</a>";
                update_option("my_plugin_deferred_admin_notices", $notices);
            }
        }

        public function my_plugin_admin_notices()
        {
            if ($notices = get_option("my_plugin_deferred_admin_notices")) {
                foreach ($notices as $notice) {
                    printf("<div class='updated' style='background-color:#f2dede'><p>" . esc_attr($notice) . "</p></div>");
                }

                deactivate_plugins(plugin_basename(__FILE__), true);
                delete_option("my_plugin_deferred_admin_notices");
                die();
            }
        }

        public function woocommerce_related_notices()
        {
            global $woocommerce;

            if (!class_exists("WC_Payment_Gateway")) {
                printf("<div class='notice notice-success is-dismissible'>
                        <p>Simpaisa IBFT Plugin requires <b>WooCommerce</b> Plugin to make it work!</p>
                    </div>");
            }

            if (class_exists("WC_Payment_Gateway") && get_woocommerce_currency_symbol() != get_woocommerce_currency_symbol("PKR")) {
                printf("<div class='notice notice-success is-dismissible'>
                        <p>Simpaisa IBFT Plugin requires <b>PKR</b> Currency to make it work!</p>
                    </div>");
            }
        }
    }

    $SimpaisaIBFTPluginConfiguration = new SimpaisaIBFTPluginConfiguration();
}
