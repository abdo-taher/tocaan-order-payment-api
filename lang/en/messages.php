<?php

return [

    // ─── Auth ───────────────────────────────────────────────────────────
    'auth' => [
        'registered' => 'User registered successfully.',
        'login_success' => 'Login successful.',
        'invalid_credentials' => 'Invalid credentials.',
        'logged_out' => 'Successfully logged out.',
        'profile_retrieved' => 'User profile retrieved.',
    ],

    // ─── Orders ─────────────────────────────────────────────────────────
    'orders' => [
        'retrieved' => 'Orders retrieved.',
        'created' => 'Order created successfully.',
        'shown' => 'Order retrieved.',
        'updated' => 'Order updated successfully.',
        'status_updated' => 'Order status updated successfully.',
        'deleted' => 'Order deleted successfully.',
        'not_found' => 'Order not found.',
        'only_pending_modify' => 'Only pending orders can be modified.',
        'has_payments' => 'Orders with payments cannot be deleted.',
        'invalid_transition' => "Cannot transition from ':from' to ':to'.",
    ],

    // ─── Payments ───────────────────────────────────────────────────────
    'payments' => [
        'processed' => 'Payment processed successfully.',
        'failed' => 'Payment processing failed.',
        'retrieved' => 'Payment retrieved.',
        'not_found' => 'No payment found for this order.',
        'order_not_found' => 'Order not found.',
        'only_confirmed' => 'Payment can only be processed for confirmed orders.',
        'already_paid' => 'This order already has a successful payment.',
        'amount_mismatch' => 'Payment amount must match the order total of :total.',
    ],

    // ─── General ────────────────────────────────────────────────────────
    'success' => 'Success.',
    'not_found' => 'Resource not found.',
    'unauthorized' => 'Unauthorized.',
    'error' => 'An error occurred.',
    'deleted' => 'Resource deleted successfully.',
    'created' => 'Resource created successfully.',

    // ─── Notifications ──────────────────────────────────────────────────
    'notifications' => [
        'payment_subject' => 'Payment Confirmation',
        'payment_greeting' => 'Hello :name,',
        'payment_body' => 'Your payment for Order #:order_id has been processed successfully. Amount: $:amount via :method.',
        'payment_transaction' => 'Transaction ID: :transaction_id',
        'payment_thanks' => 'Thank you for your purchase!',
    ],

];
