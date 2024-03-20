<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable Generic.Files.LineLength
// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use BeycanPress\CryptoPayLite\PluginHero\Hook;
use BeycanPress\CryptoPayLite\Pages\TransactionPage;
use BeycanPress\CryptoPayLite\PluginHero\Http\Response;
use BeycanPress\CryptoPayLite\Types\Data\PaymentDataType;

// @phpcs:ignore
class MeprCryptoPayLiteCtrl extends MeprBaseCtrl
{
    /**
     * @return void
     */
    public function load_hooks(): void
    {
        if (is_admin()) {
            new TransactionPage(
                esc_html__('MemberPress transactions', 'memberpress-cryptopay'),
                'memberpress',
                9,
                []
            );
        }

        Hook::addFilter('init_memberpress', function (PaymentDataType $data) {
            if (!(new MeprTransaction())->get_one($data->getOrder()->getId())) {
                Response::error(esc_html__('The MemberPress transaction not found!', 'memberpress-cryptopay'), 'TXN_NOT_FOUND', [
                    'redirect' => 'reload'
                ]);
            }

            return $data;
        });

        Hook::addAction('payment_finished_memberpress', function (PaymentDataType $data): void {
            $txn = new MeprTransaction($data->getOrder()->getId());
            $txn->status = $data->getStatus() ? MeprTransaction::$complete_str : MeprTransaction::$failed_str;

            if (!$data->getStatus()) {
                MeprUtils::send_failed_txn_notices($txn);
            }

            if ($sub = $txn->subscription()) {
                $sub->status = MeprSubscription::$active_str;
                $sub->expires_at = $txn->expires_at;
                $sub->store();
            }

            $txn->store();

            MeprUtils::send_signup_notices($txn);
            MeprUtils::send_transaction_receipt_notices($txn);
        });

        Hook::addFilter('payment_redirect_urls_memberpress', function (PaymentDataType $data) {
            $meprOptions = MeprOptions::fetch();
            $txn = new MeprTransaction($data->getOrder()->getId());
            $prd = $txn->product();
            $query_params = [
                'membership' => sanitize_title($prd->post_title),
                'trans_num' => $txn->trans_num,
                'membership_id' => $prd->ID,
                'subscr_id' => $txn->subscription_id
            ];
            return [
                'success' => $meprOptions->thankyou_page_url(build_query($query_params)),
                'failed' => $txn->checkout_url()
            ];
        });
    }
}
