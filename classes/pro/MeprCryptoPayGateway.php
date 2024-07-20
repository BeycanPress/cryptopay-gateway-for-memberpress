<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength
// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint

use BeycanPress\CryptoPay\Payment;
use BeycanPress\CryptoPay\Settings;
use BeycanPress\CryptoPay\PluginHero\Hook;
use BeycanPress\CryptoPay\PluginHero\Helpers;
use BeycanPress\CryptoPay\Types\Order\OrderType;

// @phpcs:ignore
class MeprCryptoPayGateway extends MeprBaseRealGateway
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
        $this->name = __('CryptoPay', 'memberpress-cryptopay');
        $this->key  = 'cryptopay';
        $this->icon = MEMBERPRESS_CRYPTOPAY_URL . 'assets/images/icon.png';
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
    public function enqueue_payment_form_scripts(): void
    {
        wp_enqueue_style(
            'mepr-cryptopay-form',
            MEMBERPRESS_CRYPTOPAY_URL . '/assets/css/main.css',
            [],
            MEMBERPRESS_CRYPTOPAY_VERSION
        );
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
        $this->show_cryptopay_payment_form(new MeprTransaction($transactionId));
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
        $mepr_options = MeprOptions::fetch();
        if (isset($this->settings->cryptopay_theme)) {
            $cryptopayTheme = trim($this->settings->cryptopay_theme);
        } else {
            $cryptopayTheme = 'light';
        }

        $invoiceHeader = $this->settings->invoice_header ?? false;
        ?>
        <table >
            <tr>
                <td><?php echo esc_html__('Theme:', 'memberpress-cryptopay'); ?></td>
                <td>
                    <select name="<?php echo esc_attr($mepr_options->integrations_str); ?>[<?php echo esc_attr($this->id);?>][cryptopay_theme]" class="mepr-auto-trim">
                        <option value="light" <?php echo esc_attr('light' == $cryptopayTheme ? 'selected' : '') ?>><?php echo esc_html__('Light', 'memberpress-cryptopay') ?></option>
                        <option value="dark" <?php echo esc_attr('dark' == $cryptopayTheme ? 'selected' : '') ?>><?php echo esc_html__('Dark', 'memberpress-cryptopay') ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Header:', 'memberpress-cryptopay'); ?></td>
                <td>
                    <select name="<?php echo esc_attr($mepr_options->integrations_str); ?>[<?php echo esc_attr($this->id);?>][invoice_header]" class="mepr-auto-trim">
                        <option value="1" <?php echo esc_attr($invoiceHeader ? 'selected' : '') ?>><?php echo esc_html__('Show', 'memberpress-cryptopay') ?></option>
                        <option value="0" <?php echo esc_attr(!$invoiceHeader ? 'selected' : '') ?>><?php echo esc_html__('Hide', 'memberpress-cryptopay') ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
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
     * @param int $subscription_id
     * @param array<mixed> $errors
     * @param string $message
     * @return void
     */
    public function display_update_account_form($subscription_id, $errors = [], $message = ""): void
    {
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
            $this->show_cryptopay_payment_form((new MeprTransaction($existsTxn->id)));
            return;
        }

        $txn->user_id    = $usr->ID;
        $txn->product_id = sanitize_key($prd->ID);
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

        if ('lifetime' != $sub->limit_cycles_action) {
            $expires_at = $sub->get_expires_at(strtotime($txn->created_at));

            switch ($sub->limit_cycles_expires_type) {
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
            } elseif (!$sub) {
                MeprEvent::record('non-recurring-transaction-completed', $txn);
            }
        }

        $this->show_cryptopay_payment_form($txn);
    }

    /**
     * @param MeprTransaction $txn
     * @return void
     */
    private function show_cryptopay_payment_form($txn): void
    {
        $meprOptions = MeprOptions::fetch();
        $amount = MeprUtils::maybe_round_to_minimum_amount($txn->total);

        Hook::addFilter('theme', function (array $theme) {
            if (isset($this->settings->cryptopay_theme)) {
                $theme['mode'] = trim($this->settings->cryptopay_theme);
            } else {
                $theme['mode'] = 'light';
            }
            return $theme;
        });

        if ($this->settings->invoice_header ?? false) {
            $this->mepr_invoice_header($txn);
        }
        ?>
        <div class="mp_wrapper mp_payment_form_wrapper">
            <?php
                Helpers::ksesEcho((new Payment('memberpress'))
                ->setOrder(OrderType::fromArray([
                    'id' => (int) $txn->id,
                    'amount' => (float) $amount,
                    'currency' => $meprOptions->currency_code,
                ]))
                ->html(loading:true));
            ?>
        </div>
        <?php
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
