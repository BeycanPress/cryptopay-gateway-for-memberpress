<?php

if (!defined('ABSPATH'))
    die;

use BeycanPress\CryptoPay\Settings;
use BeycanPress\CryptoPay\Services;
use BeycanPress\CryptoPay\PluginHero\Hook;

class MeprCryptoPayGateway extends MeprBaseRealGateway 
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
        $this->name = __('CryptoPay', 'memberpress-cryptopay');
        $this->key  = 'cryptopay';
        $this->icon = MEMBERPRESS_CRYPTOPAY_URL . '/assets/images/icon.png';
        $this->desc = __('Pay with cryptocurrencies via CryptoPay', 'memberpress-cryptopay');
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
                'label' => __('CryptoPay', 'memberpress-cryptopay'),
                'desc' => __('Pay with cryptocurrencies via CryptoPay', 'memberpress-cryptopay'),
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
        $this->show_cryptopay_payment_form(new MeprTransaction($transactionId));
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
        $mepr_options = MeprOptions::fetch();
        if (isset($this->settings->cryptopay_theme)) {
            $cryptopayTheme = trim($this->settings->cryptopay_theme);
        } else {
            $cryptopayTheme = 'default';
        }
        ?>
        <table >
            <tr>
                <td><?php echo esc_html__('Theme:', 'memberpress-cryptopay'); ?></td>
                <td>
                    <select name="<?php echo esc_attr($mepr_options->integrations_str); ?>[<?php echo esc_attr($this->id);?>][cryptopay_theme]" class="mepr-auto-trim">
                        <option value="default" <?php echo esc_attr($cryptopayTheme == 'default' ? 'selected' : '') ?>><?php echo esc_html__('Default', 'memberpress-cryptopay') ?></option>
                        <option value="dark" <?php echo esc_attr($cryptopayTheme == 'dark' ? 'selected' : '') ?>><?php echo esc_html__('Dark', 'memberpress-cryptopay') ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function validate_options_form($errors) {
        return $errors;
    }

    public function display_update_account_form($subscription_id, $errors = array(), $message = "") {
        $sub = new MeprSubscription($subscription_id);
        $usr = $sub->user();
        $prd = $sub->product();

        $txn = new MeprTransaction();

        $mepr_db = new MeprDb();
        $existsTxn = $mepr_db->get_one_record($mepr_db->transactions, [
            'user_id' => $usr->ID,
            'product_id' => $prd->ID,
            'status' => MeprTransaction::$pending_str,
        ]);
        if ($existsTxn) {
            return $this->show_cryptopay_payment_form((new MeprTransaction($existsTxn->id)));
        }
    
        $txn->user_id    = $usr->ID;
        $txn->product_id = sanitize_key($prd->ID);
        // $txn->set_subtotal($_POST['amount']); //Don't do this, it doesn't work right on existing txns
        $txn->amount     = MeprUtils::format_currency_us_float($sub->price);
        $txn->tax_amount = MeprUtils::format_currency_us_float($sub->tax_amount);
        $txn->total      = $txn->amount + $txn->tax_amount;
        $txn->tax_rate   = MeprUtils::format_currency_us_float($sub->tax_rate);
        $txn->status     = MeprTransaction::$pending_str;
        $txn->gateway    = $sub->gateway;
        $txn->subscription_id = $sub->id;
        $sub->store();
        
        if ($sub->expires_at > date('Y-m-d H:i:s', time())) {
            $txn->created_at = $sub->expires_at;
        } else {
            $txn->created_at = MeprUtils::ts_to_mysql_date(time());
        }
    
        if ($sub->limit_cycles_action != 'lifetime') {
            $expires_at = $sub->get_expires_at(strtotime($txn->created_at));

            switch($sub->limit_cycles_expires_type) {
                case 'days':
                    $expires_at += MeprUtils::days($sub->limit_cycles_expires_after);
                    break;
                case 'weeks':
                    $expires_at += MeprUtils::weeks($sub->limit_cycles_expires_after);
                    break;
                case 'months':
                    $expires_at += MeprUtils::months($sub->limit_cycles_expires_after, strtotime($txn->created_at));
                    break;
                case 'years':
                    $expires_at += MeprUtils::years($sub->limit_cycles_expires_after, strtotime($txn->created_at));
            }
            $txn->expires_at = MeprUtils::ts_to_mysql_date($expires_at); 
        } else {
            $txn->expires_at = MeprUtils::db_lifetime();
        }

        $txn->store();

        if ($txn->status == MeprTransaction::$complete_str) {
            MeprEvent::record('transaction-completed', $txn);
            if (($sub = $txn->subscription()) && $sub->txn_count > 1) {
                MeprEvent::record('recurring-transaction-completed', $txn);
            } elseif(!$sub) {
                MeprEvent::record('non-recurring-transaction-completed', $txn);
            }
        }
        
        $this->show_cryptopay_payment_form($txn);
    }

    private function show_cryptopay_payment_form($txn) {
        $meprOptions = MeprOptions::fetch();
        $amount = MeprUtils::maybe_round_to_minimum_amount($txn->total);

        Hook::addFilter('theme', function() {
            if (isset($this->settings->cryptopay_theme)) {
                return trim($this->settings->cryptopay_theme);
            } else {
                return 'light';
            }
        });

        //$this->mepr_invoice_header($txn);
        ?>
        <div class="mp_wrapper mp_payment_form_wrapper">
            <?php
                echo Services::startPaymentProcess([
                    'amount' => $amount,
                    'currency' => $meprOptions->currency_code,
                ], 'memberpress', true, [
                    'MemberPress' => [
                        'userId' => (int) $txn->user_id,
                        'productId' => (int) $txn->product_id,
                        'transactionId' => (int) $txn->id,
                    ]
                ]);
            ?>
            <style>
                .cp-modal .waiting-icon svg {
                    width: 94px!important;
                    height: 94px!important;
                }
                .cp-explorer-btn {
                    height: auto!important;
                }
            </style>
        </div>
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
