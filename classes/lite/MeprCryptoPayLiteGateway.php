<?php

if (!defined('ABSPATH'))
    die;

use BeycanPress\CryptoPayLite\Settings;
use BeycanPress\CryptoPayLite\Services;

class MeprCryptoPayLiteGateway extends MeprBaseRealGateway 
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $key;

    /**
     * @var object
     */
	public $settings;

    public function __construct() 
    {
        $this->name = __('CryptoPay Lite', 'memberpress-cryptopay');
        $this->key  = 'cryptopay_lite';
        $this->icon = MEMBERPRESS_CRYPTOPAY_URL . '/assets/images/icon.png';
        $this->desc = __('Pay with cryptocurrencies via CryptoPay Lite', 'memberpress-cryptopay');
        $this->set_defaults();
        $this->has_spc_form = true;

        $this->capabilities = [
            'process-payments',
            'cancel-subscriptions'
        ];
    }

    /**
     * @return string
     */
    public function spc_payment_fields()
    {
        return $this->desc;
    }

    /**
     * @param array $settings
     * @return void
     */
    public function load($settings)
    {
        $this->settings = (object) $settings;
        $this->set_defaults();
    }

    /**
     *  Set default plugin settings
     */
    protected function set_defaults() 
    {
        if (!isset($this->settings)) {
            $this->settings = [];
        }

        $this->settings = (object) array_merge(
            [
                'id' => $this->generate_id(),
                'gateway' => __CLASS__,
                'icon' => MEMBERPRESS_CRYPTOPAY_URL . '/assets/images/icon.png',
                'label' => __('CryptoPay Lite', 'memberpress-cryptopay'),
                'desc' => __('Pay with cryptocurrencies via CryptoPay Lite', 'memberpress-cryptopay'),
                'use_label' => true,
                'use_icon' => true,
                'use_desc' => true,
            ],
            (array) $this->settings
        );

        $this->id = $this->settings->id;
        $this->label = $this->settings->label;
        $this->use_label = $this->settings->use_label;
        $this->use_icon = $this->settings->use_icon;
        $this->use_desc = $this->settings->use_desc;
    }

    /**
     * @return void
     */
    public function enqueue_payment_form_scripts() 
    {
        wp_enqueue_style('mepr-cryptopay-form', MEMBERPRESS_CRYPTOPAY_URL . '/assets/css/main.css', [], MEMBERPRESS_CRYPTOPAY_VERSION);
    }

    // Process payment

    public function process_signup_form($txn) {
        // Running first
        return;
    }

    public function validate_payment_form($errors) {
        // Running second
        return;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function process_payment_form($txn) 
    {
        if ($txn->amount <= 0.00) {
            MeprTransaction::create_free_transaction($txn);
            return;
        }
        // Running third
        MeprUtils::wp_redirect($txn->checkout_url());
    }

    /**
     * @param float $amount
     * @param MeprUser $user
     * @param int $productId
     * @param int $transactionId
     * @return void
     */
    public function display_payment_form($amount, $user, $productId, $transactionId)
    {
        $meprOptions = MeprOptions::fetch();

        //$this->mepr_invoice_header($txn);
        ?>
        <div class="mp_wrapper mp_payment_form_wrapper">
            <?php
                echo Services::startPaymentProcess([
                    'amount' => $amount,
                    'currency' => $meprOptions->currency_code,
                ], 'memberpress_lite', true, [
                    'MemberPress' => [
                        'userId' => (int) $user->ID,
                        'productId' => (int) $productId,
                        'transactionId' => (int) $transactionId,
                    ]
                ]);
            ?>
        </div>
        <?php
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    private function mepr_invoice_header($txn)
    {
        $order_bumps = [];
        try {
            $orderBumpProductIds = isset($_GET['obs']) && is_array($_GET['obs']) ? array_map('intval', $_GET['obs']) : [];
            $orderBumpProducts = MeprCheckoutCtrl::get_order_bump_products($txn->product_id, $orderBumpProductIds);

            foreach($orderBumpProducts as $product) {
                list($transaction, $subscription) = MeprCheckoutCtrl::prepare_transaction(
                    $product,
                    0,
                    get_current_user_id(),
                    'manual',
                    false,
                    false
                );

                $order_bumps[] = [$product, $transaction, $subscription];
            }
        } catch(Exception $e) {
            // ignore exception
        }

        if (count($order_bumps)) {
            echo MeprTransactionsHelper::get_invoice_order_bumps($txn, '', $order_bumps);
        } else {
            echo MeprTransactionsHelper::get_invoice($txn);
        }
    }

    // Not usings

    public function is_test_mode() {
        return (bool) Settings::get('testnet');
    }

    public function force_ssl() {
        return false;
    }

    public function process_payment($txn) {
        return;
    }

    public function display_options_form() { 
        return;
    }

    public function validate_options_form($errors) {
        return $errors;
    }

    public function display_update_account_form($subscription_id, $errors = array(), $message = "") {
        ?>
            <p><b><?php echo esc_html__('This action is not possible with the payment method used with this Subscription!','memberpress-cryptopay'); ?></b></p>
        <?php
    }

    public function validate_update_account_form($errors = array()) {
        return;
    }

    public function process_update_account_form($subscription_id) {
        return;
    }

    public function record_payment() {
        return;
    }

    public function process_refund(MeprTransaction $txn) {
        return;
    }

    public function record_refund() {
        return;
    }

    public function record_subscription_payment() {
        return;
    }

    public function record_payment_failure() {
        return;
    }

    public function process_trial_payment($txn) {
        return;
    }

    public function record_trial_payment($txn) {
        return;
    }

    public function process_create_subscription($txn) {
        return;
    }

    public function record_create_subscription() {
        return;
    }

    public function process_update_subscription($subscription_id) {
        return;
    }

    public function record_update_subscription() {
        return;
    }

    public function process_suspend_subscription($subscription_id) {
        return;
    }

    public function record_suspend_subscription() {
        return;
    }

    public function process_resume_subscription($subscription_id) {
        return;
    }

    public function record_resume_subscription() {
        return;
    }

    public function process_cancel_subscription($subscription_id) {
        return;
    }

    public function record_cancel_subscription() {
        return;
    }

    public function display_payment_page($txn) {
        return;
    }
}
