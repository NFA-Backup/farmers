<?php 

/**
 * DB
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
 * Hash Salt if not defined
 */
if (!isset($settings['hash_salt']) || $settings['hash_salt'] == '') {
  $settings['hash_salt'] = '1zcjv7hM7q1hI1GSYBWg_0A_xfjR5pjji-m_gIT9l1kcsHQSB_s2e7-pyRLGkDAmUcvCmgObGA';
}

/**
 * Trusted hosts
 */
$settings['trusted_host_patterns'] = [
  sprintf('^%s$', str_replace('.', '\.', $_SERVER['PROJECT_BASE_URL'])),
  sprintf('^.+\.%s$', str_replace('.', '\.', $_SERVER['PROJECT_BASE_URL'])),
];

$trusted_hosts = $_SERVER['TRUSTED_HOSTS'] ?? '';
$trusted_hosts = explode(',', $trusted_hosts);
$trusted_hosts = array_filter($trusted_hosts);
foreach ($trusted_hosts as $host) {
  $settings['trusted_host_patterns'][] = sprintf('^%s$', str_replace('.', '\.', $host));
}


/**
 * Paths
 */
$settings['file_chmod_directory'] = 02775;

$docroot_base = realpath(DRUPAL_ROOT . '/..');

$settings['file_public_path'] = "sites/default/files";
$settings['file_private_path'] = $docroot_base . '/private';
$settings['file_temp_path'] = $docroot_base . '/tmp';