<?php
// Audit alert configuration
$auditAlertConfig = array(
    'enabled' => true,
    'webhook_url' => '', // set to your webhook endpoint (e.g., Slack/Discord gateway)
    'email_to' => 'irenkzz@gmail.com',    // optional fallback email
    'from_email' => 'dkppjayapura.site',  // optional From header
    'rate_limit_seconds_by_action' => array(
        'ACCESS_DENIED'  => 300,
        'LOGIN_BRUTEFORCE' => 300,
        'LOGIN_BLOCKED' => 300,
        'DELETE'         => 60,
        'BULK_DELETE'    => 60,
        'BULK_UPDATE'    => 120,
        'SAVE_SORT'      => 120,
        'REORDER'        => 120,
        'ROLE_CHANGE'    => 60,
        'DEACTIVATE'     => 60,
        'RESET_PASSWORD' => 60,
    ),
    'login_fail_threshold' => array(
        'max_fails' => 5,
        'window_seconds' => 600,
    ),
);
