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
require_once 'vendor/autoload.php';

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

        echo '<pre style="background: #1f1f1f; padding:1em; border:1px solid #ddd;font-size: 10px;color: #1ed44e;">'; var_dump($user_id); echo '</pre>';
    }
});