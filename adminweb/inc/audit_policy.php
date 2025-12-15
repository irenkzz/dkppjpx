<?php
// Central audit logging policy
$auditPolicy = array(
    'always_actions' => array('LOGIN_OK', 'LOGIN_FAIL', 'LOGOUT', 'LOGIN_BRUTEFORCE', 'LOGIN_BLOCKED'),
    'critical_modules' => array('auth', 'users', 'identitas', 'menu', 'modul', 'templates'),
    'modules' => array(
        'auth' => array(
            'log_actions' => array('LOGIN_OK', 'LOGIN_FAIL', 'LOGOUT')
        ),
        'users' => array(
            'log_actions' => array('CREATE', 'UPDATE', 'DELETE', 'TOGGLE', 'BULK_UPDATE', 'BULK_DELETE')
        ),
        'identitas' => array(
            'log_actions' => array('UPDATE')
        ),
        'menu' => array(
            'log_actions' => array('CREATE', 'UPDATE', 'DELETE', 'SAVE_SORT', 'REORDER')
        ),
        'modul' => array(
            'log_actions' => array('CREATE', 'UPDATE', 'DELETE', 'TOGGLE')
        ),
        'templates' => array(
            'log_actions' => array('UPDATE', 'CREATE', 'DELETE')
        ),
    ),
    'default' => array(
        'log_actions' => array('CREATE', 'UPDATE', 'DELETE')
    ),
);
