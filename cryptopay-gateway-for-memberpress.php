<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable PSR12.Files.FileHeader
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength

/**
 * Plugin Name: CryptoPay Gateway for MemberPress
 * Version:     1.0.5
 * Plugin URI:  https://beycanpress.com/cryptopay/
 * Description: Adds Cryptocurrency payment gateway (CryptoPay) for MemberPress.
 * Author:      BeycanPress LLC
 * Author URI:  https://beycanpress.com
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: memberpress-cryptopay
 * Tags: Bitcoin, Ethereum, Cryptocurrency, Payments, MemberPress
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 8.1
*/

use BeycanPress\CryptoPay\Loader;
use BeycanPress\CryptoPay\Helpers;
use BeycanPress\CryptoPay\PluginHero\Hook;
use BeycanPress\CryptoPayLite\Loader as LiteLoader;
use BeycanPress\CryptoPayLite\Helpers as LiteHelpers;
use BeycanPress\CryptoPayLite\PluginHero\Hook as LiteHook;

define('MEMBERPRESS_CRYPTOPAY_FILE', __FILE__);
define('MEMBERPRESS_CRYPTOPAY_VERSION', '1.0.4');
define('MEMBERPRESS_CRYPTOPAY_URL', plugin_dir_url(__FILE__));
define('MEMBERPRESS_CRYPTOPAY_DIR', plugin_dir_path(__FILE__));

register_activation_hook(MEMBERPRESS_CRYPTOPAY_FILE, function (): void {
    if (class_exists(Loader::class)) {
        require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/pro/Models/MeprMemberPressCryptoPayModel.php';
        (new MeprMemberPressCryptoPayModel())->createTable();
    }
    if (class_exists(LiteLoader::class)) {
        require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/lite/Models/MeprMemberPressCryptoPayLiteModel.php';
        (new MeprMemberPressCryptoPayLiteModel())->createTable();
    }
});

/**
 * @return void
 */
function memberpress_cryptopay_addModels(): void
{
    if (class_exists(Loader::class)) {
        require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/pro/Models/MeprMemberPressCryptoPayModel.php';
        Hook::addFilter('models', function ($models) {
            return array_merge($models, [
                'memberpress' => new MeprMemberPressCryptoPayModel()
            ]);
        });
    }

    if (class_exists(LiteLoader::class)) {
        require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/lite/Models/MeprMemberPressCryptoPayLiteModel.php';
        LiteHook::addFilter('models', function ($models) {
            return array_merge($models, [
                'memberpress' => new MeprMemberPressCryptoPayLiteModel()
            ]);
        });
    }
}

memberpress_cryptopay_addModels();

add_action('plugins_loaded', function (): void {

    memberpress_cryptopay_addModels();

    load_plugin_textdomain('memberpress-cryptopay', false, basename(__DIR__) . '/languages');

    if (!defined('MEPR_VERSION')) {
        add_action('admin_notices', function (): void {
            ?>
                <div class="notice notice-error">
                    <p><?php echo wp_kses_post(sprintf(__('CryptoPay Gateway for MemberPress: This plugin requires MemberPress to work. You can buy MemberPress by %s.', 'memberpress-cryptopay'), '<a href="https://memberpress.com/" target="_blank">' . esc_html__('clicking here', 'memberpress-cryptopay') . '</a>')); ?></p>
                </div>
            <?php
        });
        return;
    }

    if ((class_exists(Loader::class) || class_exists(LiteLoader::class))) {
        if (class_exists(Loader::class)) {
            Helpers::registerIntegration('memberpress');
        }

        if (class_exists(LiteLoader::class)) {
            LiteHelpers::registerIntegration('memberpress');
        }

        add_filter('mepr-gateway-paths', 'addGatewayPathToMemberPress', 10, 1);
        add_filter('mepr-ctrls-paths', 'addGatewayPathToMemberPress', 99, 1);

        /**
         * @param array<mixed> $paths
         * @return array<mixed>
         */
        function addGatewayPathToMemberPress(array $paths): array
        {
            if (class_exists(Loader::class)) {
                array_push($paths, __DIR__ . '/classes/pro');
            }

            if (class_exists(LiteLoader::class)) {
                array_push($paths, __DIR__ . '/classes/lite');
            }

            return $paths;
        }

        if (class_exists(Loader::class)) {
            require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/pro/MeprCryptoPayCtrl.php';
            new MeprCryptoPayCtrl();
        }

        if (class_exists(LiteLoader::class)) {
            require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/lite/MeprCryptoPayLiteCtrl.php';
            new MeprCryptoPayLiteCtrl();
        }
    } else {
        add_action('admin_notices', function (): void {
            ?>
                <div class="notice notice-error">
                    <p><?php echo wp_kses_post(sprintf(__('CryptoPay Gateway for MemberPress: This plugin is an extra feature plugin so it cannot do anything on its own. It needs CryptoPay to work. You can buy CryptoPay by %s.', 'memberpress-cryptopay'), '<a href="https://beycanpress.com/product/cryptopay-all-in-one-cryptocurrency-payments-for-wordpress/?utm_source=wp_org_addons&utm_medium=memberpress" target="_blank">' . esc_html__('clicking here', 'memberpress-cryptopay') . '</a>')); ?></p>
                </div>
            <?php
        });
    }
});
