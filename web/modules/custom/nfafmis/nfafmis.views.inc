<?php

/**
 * @file
 * Views hooks for nfafmis.
 */

/**
 * Implements hook_views_data_alter().
 */
function nfafmis_views_data_alter(array &$data) {
  // Add our custom "title query param" field to the global table so it's
  // always available.
  $data['views']['nfafmis_title_query_param'] = [
    'title' => t('NFAFMIS - "title" param from query string'),
    'help' => t('The "title" parameter from the current query string, or an empty string if not present.'),
    'field' => [
      'id' => 'nfafmis_title_query_param',
    ],
  ];
}
