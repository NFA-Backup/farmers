<?php

/**
 * @file
 * Views hooks for nfafmis.
 */

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

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

/**
 * Implements hook_views_query_alter().
 */
function nfafmis_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->storage->get('id') === 'farmer_filter') {

    foreach ($query->where as &$condition_group) {
      foreach ($condition_group['conditions'] as &$condition) {
        // Make the farmer name search case sensitive.
        if ($condition['field'] == 'node_field_data.title') {
          $condition['operator'] = 'LIKE BINARY';
        }
      }
    }
  }
}
