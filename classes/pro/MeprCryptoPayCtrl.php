<?php

if (!defined('ABSPATH'))
    die;

use BeycanPress\Http\Response;
use BeycanPress\CryptoPay\PluginHero\Hook;
use BeycanPress\CryptoPay\Pages\TransactionPage;

class MeprCryptoPayCtrl extends MeprBaseCtrl
{
    public function load_hooks() 
    {
        if (is_admin()) {
            new TransactionPage(
                esc_html__('MemberPress transactions', 'cryptopay'),
                'memberpress_transactions',
                'memberpress',
                9,
                [],
                true,
                ['updatedAt']
            );
        }

        Hook::addFilter('init_memberpress', function(object $data) {
            if (!(new MeprTransaction())->get_one($data->params->MemberPress->transactionId)) {
                Response::error(esc_html__('The MemberPress transaction not found!', 'memberpress-cryptopay'), 'TXN_NOT_FOUND', [
                    'redirect' => 'reload'
                ]);
            }
        });

        Hook::addFilter('before_payment_started_memberpress', function(object $data) {
            $data->order->id = $data->params->MemberPress->transactionId;
            return $data;
        });
        
        Hook::addFilter('payment_finished_memberpress', function(object $data) {
            $txn = new MeprTransaction($data->params->MemberPress->transactionId);
            $txn->status = $data->status ? MeprTransaction::$complete_str : MeprTransaction::$failed_str;

            if (!$data->status) {
                MeprUtils::send_failed_txn_notices($txn);
            }

            if ($sub = $txn->subscription()) {
                $sub->status = MeprSubscription::$active_str;
                $sub->expires_at = $txn->expires_at;
                $sub->store();
            }

            $txn->store();
            MeprUtils::send_transaction_receipt_notices($txn);
        });

        Hook::addFilter('payment_redirect_urls_memberpress', function(object $data) {
            $meprOptions = MeprOptions::fetch();
            $txn = new MeprTransaction($data->params->MemberPress->transactionId);
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