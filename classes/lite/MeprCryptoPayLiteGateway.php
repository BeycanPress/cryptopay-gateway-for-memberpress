<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength
// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint

use BeycanPress\CryptoPayLite\Payment;
use BeycanPress\CryptoPayLite\Settings;
use BeycanPress\CryptoPayLite\PluginHero\Helpers;
use BeycanPress\CryptoPayLite\Types\Order\OrderType;

// @phpcs:ignore
class MeprCryptoPayLiteGateway extends MeprBaseRealGateway
{
    /**
     * @var string
     */
    // @phpcs:ignore
    public $id;

    /**
     * @var string
     */
    // @phpcs:ignore
    public $label;

    /**
     * @var bool
     */
    // @phpcs:ignore
    public $use_label;

    /**
     * @var bool
     */
    // @phpcs:ignore
    public $use_icon;

    /**
     * @var bool
     */
    // @phpcs:ignore
    public $use_desc;

    /**
     * @var string
     */
    // @phpcs:ignore
    public $name;

    /**
     * @var string
     */
    // @phpcs:ignore
    public $key;

    /**
     * @var string
     */
    // @phpcs:ignore
    public $icon;

    /**
     * @var string
     */
    // @phpcs:ignore
    public $desc;

    /**
     * @var bool
     */
    // @phpcs:ignore
    public $has_spc_form;

    /**
     * @var array<string>
     */
    // @phpcs:ignore
    public $capabilities;

    /**
     * @var object
     */
    // @phpcs:ignore
    public $settings;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->name = __('CryptoPay Lite', 'memberpress-cryptopay');
        $this->key  = 'cryptopay_lite';
        $this->icon = MEMBERPRESS_CRYPTOPAY_URL . 'assets/images/icon.png';
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
    public function spc_payment_fields(): string
    {
        return $this->desc;
    }

    /**
     * @param array<mixed> $settings
     * @return void
     */
    public function load($settings): void
    {
        $this->settings = (object) $settings;
        $this->set_defaults();
    }

    /**
     * Set default plugin settings
     * @return void
     */
    protected function set_defaults(): void
    {
        if (!isset($this->settings)) {
            $this->settings = [];
        }

        $this->settings = (object) array_merge(
            [
                'id' => $this->generate_id(),
                'gateway' => __CLASS__,
                'icon' => MEMBERPRESS_CRYPTOPAY_URL . 'assets/images/icon.png',
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
    public function enqueue_payment_form_scripts(): void
    {
        wp_enqueue_style('mepr-cryptopay-form', MEMBERPRESS_CRYPTOPAY_URL . '/assets/css/main.css', [], MEMBERPRESS_CRYPTOPAY_VERSION);
    }

    // Process payment

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function process_signup_form($txn): void
    {
        // Running first
        return;
    }

    /**
     * @param array<mixed> $errors
     * @return void
     */
    public function validate_payment_form($errors): void
    {
        // Running second
        return;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function process_payment_form($txn): void
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
    public function display_payment_form($amount, $user, $productId, $transactionId): void
    {
        $meprOptions = MeprOptions::fetch();

        //$this->mepr_invoice_header($txn);
        ?>
        <div class="mp_wrapper mp_payment_form_wrapper">
            <?php
                Helpers::ksesEcho((new Payment('memberpress'))
                ->setOrder(OrderType::fromArray([
                    'id' => (int) $transactionId,
                    'amount' => (float) $amount,
                    'currency' => $meprOptions->currency_code,
                ]))
                ->html(loading:true));
            ?>
        </div>
        <?php
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    private function mepr_invoice_header($txn): void
    {
        $order_bumps = [];
        try {
            // obs parameter clearing with absint method with array_map
            $orderBumpProductIds = isset($_GET['obs']) && is_array($_GET['obs']) ? array_map('absint', $_GET['obs']) : [];
            $orderBumpProducts = MeprCheckoutCtrl::get_order_bump_products($txn->product_id, $orderBumpProductIds);

            foreach ($orderBumpProducts as $product) {
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
        } catch (Exception $e) {
            // ignore exception
        }

        if (count($order_bumps)) {
            Helpers::ksesEcho(MeprTransactionsHelper::get_invoice_order_bumps($txn, '', $order_bumps));
        } else {
            Helpers::ksesEcho(MeprTransactionsHelper::get_invoice($txn));
        }
    }

    /**
     * @param int $subscription_id
     * @param array<mixed> $errors
     * @param string $message
     * @return void
     */
    public function display_update_account_form($subscription_id, $errors = [], $message = ""): void
    {
        ?>
            <p><b><?php echo esc_html__('This action is not possible with the payment method used with this Subscription!', 'memberpress-cryptopay'); ?></b></p>
        <?php
    }

    // Not usings

    /**
     * @return bool
     */
    public function is_test_mode(): bool
    {
        return (bool) Settings::get('testnet');
    }

    /**
     * @return bool
     */
    public function force_ssl(): bool
    {
        return false;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function process_payment($txn): void
    {
        return;
    }

    /**
     * @return void
     */
    public function display_options_form(): void
    {
        return;
    }

    /**
     * @param array<mixed> $errors
     * @return array<mixed>
     */
    public function validate_options_form($errors): array
    {
        return $errors;
    }

    /**
     * @param array<mixed> $errors
     * @return void
     */
    public function validate_update_account_form($errors = []): void
    {
        return;
    }

    /**
     * @param int $subscription_id
     * @return void
     */
    public function process_update_account_form($subscription_id): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_payment(): void
    {
        return;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function process_refund($txn): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_refund(): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_subscription_payment(): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_payment_failure(): void
    {
        return;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function process_trial_payment($txn): void
    {
        return;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function record_trial_payment($txn): void
    {
        return;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function process_create_subscription($txn): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_create_subscription(): void
    {
        return;
    }

    /**
     * @param int $subscription_id
     * @return void
     */
    public function process_update_subscription($subscription_id): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_update_subscription(): void
    {
        return;
    }

    /**
     * @param int $subscription_id
     * @return void
     */
    public function process_suspend_subscription($subscription_id): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_suspend_subscription(): void
    {
        return;
    }

    /**
     * @param int $subscription_id
     * @return void
     */
    public function process_resume_subscription($subscription_id): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_resume_subscription(): void
    {
        return;
    }

    /**
     * @param int $subscription_id
     * @return void
     */
    public function process_cancel_subscription($subscription_id): void
    {
        return;
    }

    /**
     * @return void
     */
    public function record_cancel_subscription(): void
    {
        return;
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    public function display_payment_page($txn): void
    {
        return;
    }
}
