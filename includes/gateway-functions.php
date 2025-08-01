<?php
if (!defined('ABSPATH')) exit;

// ارسال درخواست به بانک
function parsian_send_to_bank($data) {
    $url = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?wsdl';
    
    try {
        $client = new SoapClient($url, ['encoding' => 'UTF-8']);
        $result = $client->SalePaymentRequest(['requestData' => $data]);
        return (array) $result->SalePaymentRequestResult;
    } catch (Exception $e) {
        return ['Status' => -1, 'Message' => $e->getMessage()];
    }
}

// تایید پرداخت
function parsian_verify_payment($terminal_id, $token) {
    $url = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?wsdl';
    
    $data = [
        'LoginAccount' => $terminal_id,
        'Token' => $token
    ];
    
    try {
        $client = new SoapClient($url, ['encoding' => 'UTF-8']);
        $result = $client->ConfirmPayment(['requestData' => $data]);
        return (array) $result->ConfirmPaymentResult;
    } catch (Exception $e) {
        return ['Status' => -1, 'Message' => $e->getMessage()];
    }
}

// دریافت پیام خطا
function parsian_get_status_message($status_code) {
    $messages = [
        0 => __('عملیات موفق', 'parsian-gateway'),
        -1 => __('خطای سرور', 'parsian-gateway'),
        -101 => __('احراز هویت پذیرنده ناموفق', 'parsian-gateway'),
        -111 => __('مبلغ تراکنش بیش از حد مجاز', 'parsian-gateway'),
        -126 => __('کد پذیرنده نامعتبر', 'parsian-gateway'),
        -130 => __('توکن منقضی شده', 'parsian-gateway'),
        -138 => __('لغو توسط کاربر', 'parsian-gateway'),
        -32768 => __('خطای ناشناخته', 'parsian-gateway')
    ];
    
    return $messages[$status_code] ?? sprintf(__('خطای ناشناخته (کد: %d)', 'parsian-gateway'), $status_code);
}