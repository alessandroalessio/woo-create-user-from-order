<?php
/*
Plugin Name: A2 Create User from Woocommerce Order
Plugin URI: https://www.a2area.it
Description: Create a new user from Woocommerce Guest Order
Version: 1.1
Author: Alessandro Alessio
Author URI: https://www.a2area.it
Text Domain: a2woo_cuford
Domain Path: /lang
*/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

function generateRandomPassword($lunghezza = 12) {
    $caratteriPermessi = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+{}[]|;:,.<>?';
    $lunghezzaCaratteri = strlen($caratteriPermessi);
    $password = '';

    for ($i = 0; $i < $lunghezza; $i++) {
        $carattereCasuale = $caratteriPermessi[rand(0, $lunghezzaCaratteri - 1)];
        $password .= $carattereCasuale;
    }

    return $password;
}

add_action('woocommerce_after_register_post_type',  function() {

    $mail = new PHPMailer(true);

    // Get last 10 order
    $query = new WC_Order_Query( array(
        'limit' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
        'customer_id' => 0,
        // 'return' => 'ids'
    ) );
    $orders = $query->get_orders();

    // Loop through orders
    foreach ( $orders AS $order ) {

        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $email = $order->get_billing_email();
        $password = generateRandomPassword();

        // Create User (or get ID if exist)
        $user_id = email_exists($email);
        if ( !$user_id ) {
            $user_id = wp_create_user($email, $password, $email);
        }
        
        // Join Order with User
        update_post_meta( $order->get_id(), '_customer_user', $user_id );

        die(); 

        // Send Email
        if ( $_ENV['SEND_WITH_BREVO']!='' && $_ENV['BREVO_USER']!='' && $_ENV['BREVO_PASS']!='' ) :
            try {
                //Server settings
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->isSMTP();
                $mail->Host       = $_ENV['BREVO_SMTP'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['BREVO_USER'];
                $mail->Password   = $_ENV['BREVO_PASS'];
                // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = $_ENV['BREVO_PORT'];

                //Recipients
                $blogname = get_option('blogname');

                $mail->setFrom($_ENV['BREVO_USER'], $blogname);
                $mail->addAddress($email);

                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->Subject = 'Here is the subject';
                $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                $mail->send();
                echo 'Message has been sent';
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
            die();
            echo '<pre style="background: #1f1f1f; padding:1em; border:1px solid #ddd;font-size: 10px;color: #1ed44e;">'; var_dump($user_id); echo '</pre>';
        endif;
    }
});