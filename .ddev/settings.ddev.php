<?php

/**
 * @file
 * #ddev-generated: Automatically generated Drupal settings file.
 * ddev manages this file and may delete or overwrite the file unless this
 * comment is removed.  It is recommended that you leave this file alone.
 */

$host = "db";
$port = 3306;

// If DDEV_PHP_VERSION is not set but IS_DDEV_PROJECT *is*, it means we're running (drush) on the host,
// so use the host-side bind port on docker IP
if (empty(getenv('DDEV_PHP_VERSION') && getenv('IS_DDEV_PROJECT') == 'true')) {
  $host = "127.0.0.1";
  $port = 49207;
}

$databases['default']['default'] = array(
  'database' => "db",
  'username' => "db",
  'password' => "db",
  'host' => $host,
  'driver' => "mysql",
  'port' => $port,
  'prefix' => "",
);

$settings['hash_salt'] = 'mqKjfUpmdjUbAorfMBPNiBtbMPsSiLdcNHUpHqSvHWJHpgsPdMziHCROUGAyYeXr';

// Don't use Symfony's APCLoader. ddev includes APCu; Composer's APCu loader has
// better performance.
$settings['class_loader_auto_detect'] = FALSE;

$settings['file_private_path'] = '../private';

// Override drupal/symfony_mailer default config to use Mailpit.
$config['symfony_mailer.settings']['default_transport'] = 'sendmail';
$config['symfony_mailer.mailer_transport.sendmail']['plugin'] = 'smtp';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['user'] = '';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['pass'] = '';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['host'] = 'localhost';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['port'] = '1025';
