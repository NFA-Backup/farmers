<?php

/**
 * DB.
 */
$databases['default']['default'] = [
  'host' => $_SERVER['DB_HOST'],
  'port' => $_SERVER['DB_PORT'],
  'database' => $_SERVER['DB_NAME'],
  'username' => $_SERVER['DB_USER'],
  'password' => $_SERVER['DB_PASS'],
  'prefix' => $_SERVER['DB_PREFIX'] ?? '',
  'driver' => $_SERVER['DB_DRIVER'],
  'namespace' => sprintf('Drupal\Core\Database\Driver\%s', $_SERVER['DB_DRIVER']),
  'charset' => 'utf8mb4',
  'collation' => 'utf8mb4_general_ci',
];

/**
 * Hash Salt if not defined.
 */
if (!isset($settings['hash_salt']) || $settings['hash_salt'] == '') {
  $settings['hash_salt'] = '1zcjv7hM7q1hI1GSYBWg_0A_xfjR5pjji-m_gIT9l1kcsHQSB_s2e7-pyRLGkDAmUcvCmgObGA';
}

/**
 * Trusted hosts.
 */
$settings['trusted_host_patterns'] = [
  sprintf('^%s$', str_replace('.', '\.', $_SERVER['APP_DOMAIN'])),
  sprintf('^.+\.%s$', str_replace('.', '\.', $_SERVER['APP_DOMAIN'])),
];

$trusted_hosts = $_SERVER['TRUSTED_HOSTS'] ?? '';
$trusted_hosts = explode(',', $trusted_hosts);
$trusted_hosts = array_filter($trusted_hosts);
foreach ($trusted_hosts as $host) {
  $settings['trusted_host_patterns'][] = sprintf('^%s$', str_replace('.', '\.', $host));
}

/**
 * Paths.
 */
$settings['file_chmod_directory'] = 02775;

$docroot_base = realpath(DRUPAL_ROOT . '/..');

$settings['file_public_path'] = "sites/default/files";
$settings['file_private_path'] = $docroot_base . '/private';
$settings['file_temp_path'] = $docroot_base . '/tmp';

/**
 * Mapbox.
 */
$config['farm_map_mapbox.settings']['api_key'] = 'pk.eyJ1IjoibmF0LWZvci1hdXRoLXVnIiwiYSI6ImNsZGlveTl0bDFqZDYzdm82bjBvdzRxN3EifQ.2TBv1-oxpDs9TY7s0seLGg';

/**
 * Environment indicator.
 */
if ($_SERVER['APP_DOMAIN'] == 'farmers.nfa.go.ug') {
  $config['environment_indicator.indicator']['bg_color'] = '#EF5621';
  $config['environment_indicator.indicator']['fg_color'] = '#FFFFFF';
  $config['environment_indicator.indicator']['name'] = 'Production';
}
elseif ($_SERVER['APP_DOMAIN'] == 'farmers.stg.envs.utils.nfa.go.ug') {
  $config['environment_indicator.indicator']['bg_color'] = '#F8A519';
  $config['environment_indicator.indicator']['fg_color'] = '#FFFFFF';
  $config['environment_indicator.indicator']['name'] = 'Staging';
}
