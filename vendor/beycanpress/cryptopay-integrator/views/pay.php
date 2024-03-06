<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html__('CryptoPay Single Page Payment') ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                background-color: #f0f0f0;
                color: #333;
            }

            .container {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-around;
                align-items: flex-start;
                padding: 20px;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin: 20px auto;
                max-width: 800px;
            }

            .payment-info {
                flex: 1;
                max-width: 400px;
                margin-right: 20px;
            }

            .payment-info h2 {
                margin-top: 0;
            }

            .payment-form {
                flex: 1;
                max-width: 400px;
            }

            .payment-info img {
                max-width: 100%;
                height: auto;
                border-radius: 5px;
            }

            @media screen and (max-width: 768px) {
                .container {
                    flex-direction: column;
                }

                .payment-info,
                .payment-form {
                    margin-right: 0;
                    margin-bottom: 20px;
                    max-width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="payment-info">
                <h2><?php echo esc_html__('Payment Information') ?></h2>
                <?php if (isset($order['id'])): ?>
                    <p><strong><?php echo esc_html__('Order ID') ?>:</strong> <?php echo esc_html($order['id']) ?></p>
                <?php endif; ?>
                <?php if (isset($order['amount'])): ?>
                    <p><strong><?php echo esc_html__('Amount') ?>:</strong> <?php echo esc_html($order['amount']) ?> <?php echo esc_html($order['currency']) ?></p>
                <?php endif; ?>
                <?php if (isset($addon)): ?>
                    <p><strong><?php echo esc_html__('Addon') ?>:</strong> <?php echo esc_html($addon) ?></p>
                <?php endif; ?>
                <p>
                <?php echo esc_html__('This page is the single payment page, after you make your payment on this page you will be returned to the addon\'s page that redirected you to this page.') ?>
                </p>
            </div>
            <div class="payment-form">
                <?php echo $cryptopay; ?>
            </div>
        </div>
        <?php do_action('wp_print_footer_scripts'); ?>
    </body>
</html>