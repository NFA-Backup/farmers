<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/default/settings.local.php'. Then, go to the bottom of
 * 'sites/default/settings.php' and uncomment the commented lines that mention
 * 'settings.local.php'.
 *
 * If you are using a site name in the path, such as 'sites/example.com', copy
 * this file to 'sites/example.com/settings.local.php', and uncomment the lines
 * at the bottom of 'sites/example.com/settings.php'.
 */

/**
 * Assertions.
 *
 * The Drupal project primarily uses runtime assertions to enforce the
 * expectations of the API by failing when incorrect calls are made by code
 * under development.
 *
 * @see http://php.net/assert
 * @see https://www.drupal.org/node/2492225
 *
 * If you are using PHP 7.0 it is strongly recommended that you set
 * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
 * or runtime) on development machines and to 0 in production.
 *
 * @see https://wiki.php.net/rfc/expectations
 */
assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
$settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Remove 'field_prefix' for new fields.
 */
$config['field_ui.settings']['field_prefix'] = '';

$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/development.services.yml';

// https://www.drupal.org/node/2598914 -> 'Find cache bins'.
$cache_bins = [
  'bootstrap',
  'config',
  'corefix_static_no_serialize',
  'data',
  'default',
  'discovery',
  //  'discovery_migration',
  'dynamic_page_cache',
  'entity',
  //  'jsonapi_memory',
  //  'jsonapi_normalizations',
  //  'jsonapi_resource_types',
  //  'menu',
  //  'migrate',
  'page',
  'render',
  //  'rest',
  //  'static',
  //  'toolbar'
];

foreach ($cache_bins as $bin) {
  $settings['cache']['bins'][$bin] = 'cache.backend.null';
}

// This will ensure the site can only be accessed through the intended host
// names. Additional host patterns can be added for custom configurations.
$settings['trusted_host_patterns'] = ['.*'];

// Override swiftmailer configuration to works fine with mailhog.
$config['swiftmailer.transport']['transport'] = 'smtp';
$config['swiftmailer.transport']['smtp_host'] = 'localhost';
$config['swiftmailer.transport']['smtp_port'] = '1025';
$config['swiftmailer.transport']['smtp_encryption'] = '0';
$config['swiftmailer.transport']['smtp_credential_provider'] = 'swiftmailer';
$config['swiftmailer.transport']['smtp_credentials'] = [
  'swiftmailer' => [
    'username' => '',
    'password' => NULL,
  ],
];

/**
 * Environment indicator.
 */
$config['environment_indicator.indicator']['bg_color'] = '#3995D8';
$config['environment_indicator.indicator']['fg_color'] = '#FFFFFF';
$config['environment_indicator.indicator']['name'] = 'Development';

// Settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if (is_readable($ddev_settings) && getenv('IS_DDEV_PROJECT') == 'true') {
  require $ddev_settings;
}
