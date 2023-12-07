<?php

/**
 * Plugin Name: MemberPress - CryptoPay Gateway
 * Version:     1.0.1
 * Plugin URI:  https://beycanpress.com/cryptopay/
 * Description: Adds Cryptocurrency payment gateway (CryptoPay) for MemberPress.
 * Author:      BeycanPress LLC
 * Author URI:  https://beycanpress.com
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: memberpress-cryptopay
 * Tags: Cryptopay, Cryptocurrency, WooCommerce, WordPress, MetaMask, Trust, Binance, Wallet, Ethereum, Bitcoin, Binance smart chain, Payment, Plugin, Gateway, Moralis, Converter, API, coin market cap, CMC
 * Requires at least: 5.0
 * Tested up to: 6.4.2
 * Requires PHP: 7.4
*/

use \BeycanPress\CryptoPay\Loader;
use \BeycanPress\CryptoPay\Services;
use \BeycanPress\CryptoPay\PluginHero\Hook;
use \BeycanPress\CryptoPayLite\Loader as LiteLoader;
use \BeycanPress\CryptoPayLite\Services as LiteServices;
use \BeycanPress\CryptoPayLite\PluginHero\Hook as LiteHook;

define('MEMBERPRESS_CRYPTOPAY_FILE', __FILE__);
define('MEMBERPRESS_CRYPTOPAY_VERSION', '1.0.1');
define('MEMBERPRESS_CRYPTOPAY_URL', plugin_dir_url(__FILE__));
define('MEMBERPRESS_CRYPTOPAY_DIR', plugin_dir_path(__FILE__));

register_activation_hook(MEMBERPRESS_CRYPTOPAY_FILE, function() {
	if (class_exists(Loader::class)) {
		require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/pro/Models/MemberPressCrpyoPayModel.php';
		(new MemberPressCrpyoPayModel())->createTable();
	}
	if (class_exists(LiteLoader::class)) {
		require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/lite/Models/MemberPressCrpyoPayLiteModel.php';
		(new MemberPressCrpyoPayLiteModel())->createTable();
	}
});

function memberpress_cryptopay_addModels() {
	if (class_exists(Loader::class)) {
		require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/pro/Models/MemberPressCrpyoPayModel.php';
		Hook::addFilter('models', function($models) {
			return array_merge($models, [
				'memberpress' => new MemberPressCrpyoPayModel()
			]);
		});
	}

	if (class_exists(LiteLoader::class)) {
		require_once MEMBERPRESS_CRYPTOPAY_DIR . 'classes/lite/Models/MemberPressCrpyoPayLiteModel.php';
		LiteHook::addFilter('models', function($models) {
			return array_merge($models, [
				'memberpress_lite' => new MemberPressCrpyoPayLiteModel()
			]);
		});
	}
}

memberpress_cryptopay_addModels();

add_action('plugins_loaded', function() {

	memberpress_cryptopay_addModels();

	load_plugin_textdomain('memberpress-cryptopay', false, basename(__DIR__) . '/languages');

	if (defined('MEPR_VERSION') && (class_exists(Loader::class) || class_exists(LiteLoader::class))) {

		if (class_exists(Loader::class)) {
			Services::registerAddon('memberpress');
		}

		if (class_exists(LiteLoader::class)) {
			LiteServices::registerAddon('memberpress_lite');
		}

		add_filter('mepr-gateway-paths', 'addGatewayPathToMemberPress', 10, 1);
		add_filter('mepr-ctrls-paths', 'addGatewayPathToMemberPress', 99, 1);
		function addGatewayPathToMemberPress($paths) {
			
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
		add_action('admin_notices', function () {
			?>
				<div class="notice notice-error">
					<p><?php echo sprintf(esc_html__('MemberPress - CryptoPay Gateway: This plugin is an extra feature plugin so it cannot do anything on its own. It needs CryptoPay to work. You can buy CryptoPay by %s.', 'memberpress-cryptopay'), '<a href="https://beycanpress.com/product/cryptopay-all-in-one-cryptocurrency-payments-for-wordpress/?utm_source=wp_org_addons&utm_medium=memberpress" target="_blank">'.esc_html__('clicking here', 'memberpress-cryptopay').'</a>'); ?></p>
				</div>
			<?php
		});
	}
});