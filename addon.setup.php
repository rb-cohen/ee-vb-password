<?php

if (!defined('VB_PASSWORD_NAME')){
    define('VB_PASSWORD_NAME',         'VB Password Updater');
    define('VB_CLASS_NAME',   'vb_password');
    define('VB_PASSWORD_VERSION',      '0.0.1');
}

return array(
    'author'         => 'Arron Woods',
    'author_url'     => 'http://www.arronwoods.com/',
    'docs_url'       => 'http://www.arronwoods.com/',
    'name'           => VB_PASSWORD_NAME,
    'description'    => '',
    'version'        => VB_PASSWORD_VERSION,
    'namespace'      => 'AW\VbPassword',
    'settings_exist' => true,
);
