<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

$curl['services']['demo'] = array(
    'host' => '',
    'timeout' => 1,
    'connect_timeout' => 1,
    'format' => 'json',
);

$config['curl'] = $curl;
