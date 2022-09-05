<?php

/**
 * @file
 * Derive settings based on the domain / environment.
 */

use Drush\Drush;

// In Drush >= 9, $_SERVER has no HTTP_HOST key.
// We want to add it since some code relies on it.
if (function_exists('drush_main') && DRUSH_MAJOR_VERSION >= 9) {
  $uri = Drush::bootstrapManager()->getUri();
  $_SERVER['HTTP_HOST'] = substr($uri, strpos($uri, '://') + 3);
}

[$host, $port] = explode(':', $_SERVER['HTTP_HOST'] . ':');
$matches = [];
preg_match('/farmers\.([\w\d]*)\.(.*)$/', $host, $matches);
$env = $matches[1] ?? 'pro';

if ($env === 'pro') {
  // Load production services.
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
}
else {
  if ($env !== 'ddev') {
    $env = 'stage';
  }

  // Disable production config on staging/development environments.
  $config['config_split.config_split.pro']['status'] = FALSE;
  // Enable staging/development settings.
  if ($env === 'ddev') {
    $config['config_split.config_split.dev']['status'] = TRUE;
  }
  else {
    $config['config_split.config_split.stage']['status'] = TRUE;
  }

  $config['farm_map_mapbox.settings']['api_key'] = 'pk.eyJ1IjoibWlyaWFtLWNhbWJyaWNvIiwiYSI6ImNrcW1kaXN5cTBhdjcydW1yaHJ3bmVrM3QifQ.MB0fF_anVMxXl8-YPN4rRA';
}

// Salt for one-time login links, cancel links, form tokens, etc.
$settings['hash_salt'] = $env . 'mqKjfUpmdjUbAorfMBPNiBtbMPsSiLdcNHUpHqSvHWJHpgsPdMziHCROUGAyYeXr';

// The directory relative to drupal root where custom settings can be exported
// and re-imported for deployment. Do not use a trailing slash.
$settings['custom_translations_directory'] = '../translations';
