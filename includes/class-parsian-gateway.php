<?php
if (!defined('ABSPATH')) exit;

class WC_Parsian_Payment_Gateway extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'parsian_gateway';
        $this->method_title = __('بانک پارسیان', 'parsian-gateway');
        $this->method_description = __('پرداخت امن با کارت‌های عضو شتاب', 'parsian-gateway');
        $this->has_fields = false;
        
        $this->init_form_fields();
        $this->init_settings();
        
        // تنظیمات
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->terminal_id = $this->get_option('terminal_id');
        $this->success_message = $this->get_option('success_message');
        $this->failed_message = $this->get_option('failed_message');
        $this->enabled = $this->get_option('enabled');
        
        // هوک‌ها
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_wc_' . $this->id, [$this, 'handle_callback']);
    }
    
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __('فعال/غیرفعال', 'parsian-gateway'),
                'type' => 'checkbox',
                'label' => __('فعال کردن درگاه بانک پارسیان', 'parsian-gateway'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('عنوان', 'parsian-gateway'),
                'type' => 'text',
                'default' => __('پرداخت با کارت‌های بانکی', 'parsian-gateway'),
                'desc_tip' => true
            ],
            'description' => [
                'title' => __('توضیحات', 'parsian-gateway'),
                'type' => 'textarea',
                'default' => __('پرداخت امن با کارت‌های عضو شتاب از طریق بانک پارسیان', 'parsian-gateway')
            ],
            'terminal_id' => [
                'title' => __('Terminal ID', 'parsian-gateway'),
                'type' => 'text',
                'description' => __('شناسه ترمینال دریافتی از بانک پارسیان', 'parsian-gateway'),
                'desc_tip' => true
            ],
            'success_message' => [
                'title' => __('پیام پرداخت موفق', 'parsian-gateway'),
                'type' => 'textarea',
                'default' => __('پرداخت شما با موفقیت انجام شد. شماره پیگیری: {RRN}', 'parsian-gateway')
            ],
            'failed_message' => [
                'title' => __('پیام پرداخت ناموفق', 'parsian-gateway'),
                'type' => 'textarea',
                'default' => __('پرداخت شما ناموفق بود. لطفا مجددا تلاش نمایید.', 'parsian-gateway')
            ]
        ];
    }
    
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $callback_url = WC()->api_request_url('WC_' . $this->id);
        
        $data = [
            'LoginAccount' => $this->terminal_id,
            'Amount' => (int) $order->get_total() * 10, // تبدیل به ریال
            'OrderId' => $order_id,
            'CallBackUrl' => $callback_url
        ];
        
        $response = parsian_send_to_bank($data);
        
        if ($response['Status'] === 0 && !empty($response['Token'])) {
            $order->update_status('pending', __('در انتظار پرداخت', 'parsian-gateway'));
            $order->update_meta_data('_parsian_token', $response['Token']);
            $order->save();
            
            return [
                'result' => 'success',
                'redirect' => 'https://pec.shaparak.ir/NewIPG/?Token=' . $response['Token']
            ];
        } else {
            $error_message = parsian_get_status_message($response['Status']);
            wc_add_notice(__('خطا در اتصال به درگاه: ', 'parsian-gateway') . $error_message, 'error');
            return false;
        }
    }
    
    public function handle_callback() {
        $token = $_POST['Token'] ?? '';
        $status = $_POST['status'] ?? '';
        $order_id = $_POST['OrderId'] ?? '';
        
        $order = wc_get_order($order_id);
        
        if (!$order || $token !== $order->get_meta('_parsian_token')) {
            wp_die(__('خطا: اطلاعات سفارش نامعتبر است', 'parsian-gateway'));
        }
        
        if ($status == 0 && $token) {
            $verification = parsian_verify_payment($this->terminal_id, $token);
            
            if ($verification['Status'] == 0) {
                $rrn = $verification['RRN'] ?? '';
                $success_message = str_replace('{RRN}', $rrn, $this->success_message);
                
                $order->payment_complete();
                $order->update_status('completed', $success_message);
                $order->add_order_note($success_message);
                
                // خالی کردن سبد خرید
                WC()->cart->empty_cart();
                
                wp_redirect($this->get_return_url($order));
                exit;
            }
        }
        
        // پرداخت ناموفق
        $order->update_status('failed', $this->failed_message);
        wc_add_notice($this->failed_message, 'error');
        wp_redirect(wc_get_checkout_url());
        exit;
    }
    
    public function admin_options() {
        echo '<h2>' . $this->method_title . '</h2>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }
}