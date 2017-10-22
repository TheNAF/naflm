<?php

$db_name   = 'obblmdb';
$db_user   = 'obblm';
$db_passwd = 'passwd';
$db_host   = 'localhost';

$db_version = 101;

$db_upgrade_options = array(
    'lrb' => '6'    // other options: '5' or '6x'. Used when upgrading your NAFLM from 0.75.
);

$settings['site_name'] = 'BB portal';
$settings['default_visitor_league'] = 1;
$settings['default_leagues'] = array(1);
$settings['hide_ES_extensions'] = false;

$rules['bank_threshold'] = 0;
$rules['force_IR'] = false;

$hrs = array();
