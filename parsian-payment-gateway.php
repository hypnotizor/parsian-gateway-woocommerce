<?php
/**
 * Plugin Name: درگاه پرداخت بانک پارسیان
 * Plugin URI: https://kabook.ir
 * Description: درگاه پرداخت بانک پارسیان برای ووکامرس - ایجاد شده در ۱۴۰۳/۰۳/۲۴
 * Version: 1.2.0
 * Author: Saeid Afshari
 * Author URI: https://kabook.ir
 * Text Domain: parsian-gateway
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

// افزودن گیت‌وی به ووکامرس
add_filter('woocommerce_payment_gateways', 'add_parsian_gateway');
function add_parsian_gateway($gateways) {
    $gateways[] = 'WC_Parsian_Payment_Gateway';
    return $gateways;
}

// بارگذاری کلاس گیت‌وی
add_action('plugins_loaded', 'init_parsian_gateway');
function init_parsian_gateway() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Parsian_Payment_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'parsian_gateway';
            $this->method_title = __('بانک پارسیان', 'parsian-gateway');
            $this->method_description = __('پرداخت امن با کارت‌های عضو شتاب', 'parsian-gateway');
            $this->has_fields = false;
            
            // تنظیم لوگوی پیش‌فرض
            $this->icon = apply_filters(
                'parsian_gateway_icon', 
                plugin_dir_url(__FILE__) . 'assets/parsian-logo.png'
            );
            
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->terminal_id = $this->get_option('terminal_id');
            $this->success_message = $this->get_option('success_message');
            $this->failed_message = $this->get_option('failed_message');
            $this->enabled = $this->get_option('enabled');
            $this->custom_logo = $this->get_option('custom_logo');
            
            // استفاده از لوگوی سفارشی اگر وجود دارد
            if (!empty($this->custom_logo)) {
                $this->icon = $this->custom_logo;
            }
            
            // ذخیره تنظیمات
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            
            // ثبت endpoint برای بازگشت از بانک
            add_action('woocommerce_api_wc_' . $this->id, array($this, 'handle_callback'));
        }
        
        // تنظیمات پلاگین
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('فعال/غیرفعال', 'parsian-gateway'),
                    'type' => 'checkbox',
                    'label' => __('فعال کردن درگاه بانک پارسیان', 'parsian-gateway'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('عنوان', 'parsian-gateway'),
                    'type' => 'text',
                    'default' => __('پرداخت با کارت‌های بانکی', 'parsian-gateway')
                ),
                'description' => array(
                    'title' => __('توضیحات', 'parsian-gateway'),
                    'type' => 'textarea',
                    'default' => __('پرداخت امن از طریق بانک پارسیان', 'parsian-gateway')
                ),
                'terminal_id' => array(
                    'title' => __('Terminal ID', 'parsian-gateway'),
                    'type' => 'password', // تغییر به password برای جلوگیری از مسائل امنیتی
                    'description' => __('شناسه ترمینال دریافتی از بانک', 'parsian-gateway'),
                    'desc_tip' => true,
                    'required' => true
                ),
                'custom_logo' => array(
                    'title' => __('آدرس لوگوی سفارشی', 'parsian-gateway'),
                    'type' => 'text',
                    'description' => __('آدرس کامل تصویر برای نمایش لوگوی بانک (اختیاری)', 'parsian-gateway'),
                    'desc_tip' => true,
                    'default' => ''
                ),
                'success_message' => array(
                    'title' => __('پیام پرداخت موفق', 'parsian-gateway'),
                    'type' => 'textarea',
                    'default' => __('پرداخت شما با موفقیت انجام شد. شماره پیگیری: {ref_id}', 'parsian-gateway'),
                    'description' => __('از {ref_id} برای نمایش شماره پیگیری استفاده کنید', 'parsian-gateway')
                ),
                'failed_message' => array(
                    'title' => __('پیام پرداخت ناموفق', 'parsian-gateway'),
                    'type' => 'textarea',
                    'default' => __('پرداخت شما ناموفق بود. لطفا مجددا تلاش کنید.', 'parsian-gateway')
                )
            );
        }
        
        // مدیریت ذخیره تنظیمات - رفع مشکل Access Denied
        public function process_admin_options() {
            parent::process_admin_options();
            
            // ذخیره دستی terminal_id برای جلوگیری از مسائل فایروال
            if (isset($_POST['woocommerce_parsian_gateway_terminal_id'])) {
                $terminal_id = sanitize_text_field($_POST['woocommerce_parsian_gateway_terminal_id']);
                $this->settings['terminal_id'] = $terminal_id;
                update_option($this->get_option_key(), $this->settings);
            }
        }
        
        // پردازش پرداخت
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $callback_url = WC()->api_request_url('WC_' . $this->id);

            $data = array(
                'LoginAccount' => $this->terminal_id,
                'Amount' => (int) $order->get_total() * 10, // تبدیل به ریال
                'OrderId' => $order_id,
                'CallBackUrl' => $callback_url
            );

            $response = $this->send_to_bank($data);

            if (isset($response['Status']) && $response['Status'] === 0 && !empty($response['Token'])) {
                $order->update_status('pending', __('در انتظار پرداخت', 'parsian-gateway'));
                
                // ذخیره توکن در متادیتای سفارش
                $order->update_meta_data('_parsian_token', $response['Token']);
                $order->save();
                
                return array(
                    'result' => 'success',
                    'redirect' => 'https://pec.shaparak.ir/NewIPG/?Token=' . $response['Token']
                );
            } else {
                $error_message = __('خطا در اتصال به درگاه: ', 'parsian-gateway');
                $error_message .= $this->get_status_message($response['Status'] ?? -1);
                
                wc_add_notice($error_message, 'error');
                $this->log('Error sending to bank: ' . print_r($response, true));
                return false;
            }
        }
        
        // ارسال درخواست به بانک
        private function send_to_bank($data) {
            $url = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?wsdl';
            
            try {
                $client = new SoapClient($url, array(
                    'exceptions' => true,
                    'stream_context' => stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ])
                ));
                $result = $client->SalePaymentRequest(array("requestData" => $data));
                return (array) $result->SalePaymentRequestResult;
            } catch (Exception $e) {
                $this->log('SOAP Error: ' . $e->getMessage());
                return array('Status' => -1);
            }
        }
        
        // مدیریت بازگشت از درگاه
        public function handle_callback() {
            $token = $_POST['Token'] ?? '';
            $status = $_POST['status'] ?? '';
            $order_id = $_POST['OrderId'] ?? '';
            
            if (empty($order_id)) {
                wp_die(__('خطا: سفارش یافت نشد', 'parsian-gateway'));
            }
            
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wp_die(__('خطا: سفارش نامعتبر', 'parsian-gateway'));
            }
            
            if ($order->get_payment_method() !== $this->id) {
                wp_die(__('خطا: روش پرداخت نامعتبر', 'parsian-gateway'));
            }
            
            if ($status == 0 && $token) {
                $verification = $this->verify_payment($token);
                
                if ($verification['Status'] == 0 && !empty($verification['RRN'])) {
                    // پرداخت موفق
                    $ref_id = $verification['RRN'];
                    $message = str_replace('{ref_id}', $ref_id, $this->success_message);
                    
                    $order->payment_complete($ref_id);
                    $order->update_status('completed', $message);
                    
                    // افزودن شماره پیگیری به متادیتا
                    $order->update_meta_data('_parsian_ref_id', $ref_id);
                    $order->save();
                    
                    wp_redirect($this->get_return_url($order));
                } else {
                    // تایید پرداخت ناموفق
                    $order->update_status('failed', $this->failed_message);
                    wc_add_notice($this->failed_message, 'error');
                    wp_redirect(wc_get_checkout_url());
                }
            } else {
                // پرداخت ناموفق
                $order->update_status('failed', $this->failed_message);
                wc_add_notice($this->failed_message, 'error');
                wp_redirect(wc_get_checkout_url());
            }
            exit;
        }
        
        // تایید پرداخت
        private function verify_payment($token) {
            $url = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?wsdl';
            
            $data = array(
                'LoginAccount' => $this->terminal_id,
                'Token' => $token
            );

            try {
                $client = new SoapClient($url, array(
                    'exceptions' => true,
                    'stream_context' => stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ])
                ));
                $result = $client->ConfirmPayment(array("requestData" => $data));
                return (array) $result->ConfirmPaymentResult;
            } catch (Exception $e) {
                $this->log('Verify Error: ' . $e->getMessage());
                return array('Status' => -1);
            }
        }
        
        // نمایش توضیحات و لوگو در صفحه پرداخت
        public function payment_fields() {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }
            
            if ($this->icon) {
                echo '<img src="' . esc_url($this->icon) . '" alt="' . esc_attr($this->title) . '" style="max-width:120px;display:block;margin:10px 0;" />';
            }
        }
        
        // مدیریت خطاها
        private function get_status_message($status_code) {
            $messages = array(
                0 => __('عملیات موفق', 'parsian-gateway'),
                -1 => __('خطای سرور', 'parsian-gateway'),
                -101 => __('احراز هویت پذیرنده ناموفق', 'parsian-gateway'),
                -111 => __('مبلغ تراکنش بیش از حد مجاز', 'parsian-gateway'),
                -126 => __('کد پذیرنده نامعتبر', 'parsian-gateway'),
                -130 => __('توکن منقضی شده', 'parsian-gateway'),
                -138 => __('لغو توسط کاربر', 'parsian-gateway'),
                -32768 => __('خطای ناشناخته', 'parsian-gateway')
            );
            
            return $messages[$status_code] ?? __('خطای ناشناخته', 'parsian-gateway');
        }
        
        // لاگ‌گیری
        private function log($message) {
            if (WP_DEBUG === true) {
                if (is_array($message) || is_object($message)) {
                    error_log(print_r($message, true));
                } else {
                    error_log('[Parsian Gateway] ' . $message);
                }
            }
        }
    }
}

// ترجمه
add_action('plugins_loaded', 'load_parsian_gateway_textdomain');
function load_parsian_gateway_textdomain() {
    load_plugin_textdomain('parsian-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages');
}