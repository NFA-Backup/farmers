<?php

/**
 * @file
 * Views hooks for nfafmis.
 */

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
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

  // A map showing the farmer's land allocations.
  $data['views']['nfafmis_map'] = [
    'title' => t('Farmer Map'),
    'help' => t("A map showing the farmer's land allocations"),
    'field' => [
      'id' => 'nfafmis_farmer_map',
    ],
  ];

  // Filter to limit lists of terms to those that are in the user's assigned
  // management unit.
  $data['taxonomy_term_field_data']['user_management_unit'] = [
    'title' => t("Limit to current user's Range or Sector"),
    'help' => t("Filter the view to the currently logged in user's assigned range or sector."),
    'group' => t('Taxonomy term'),
    'filter' => [
      'id' => 'nfafmis_user_range_sector',
      'type' => 'yes-no',
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

/**
 * Implements hook_views_pre_render().
 */
function nfafmis_views_pre_render(ViewExecutable $view) {
  // @todo Refactor this as a custom views filter.
  if ($view->id() == 'fmis_reports' && ($view->current_display == 'farmers_full' || $view->current_display == 'farmers_full_export')) {
    // The farmer report view has a relationship to content with an entity
    // reference to the farmer node, i.e. content types with
    // field_farmer_name_ref field. There are multiple content types with that
    // field, but we only want to include results where the referencing node is
    // an Offer/License. We cannot simply add a filter to the view to filter by
    // content type because doing that limits the results to farmers that have
    // Offer/License nodes. We need to show all farmers, those with
    // Offer/License and those without.
    // Here we remove results where the related entity is not an Offer/License.
    foreach ($view->result as $key => $row) {
      if (!empty($row->_relationship_entities)) {
        if (isset($row->_relationship_entities['reverse__node__field_farmer_name_ref']) && $row->_relationship_entities['reverse__node__field_farmer_name_ref']->bundle() != 'offer_license') {
          unset($view->result[$key]);
        }
        if (isset($row->_relationship_entities['reverse__node__field_areas_id']) && $row->_relationship_entities['reverse__node__field_areas_id']->bundle() != 'sub_area') {
          unset($view->result[$key]);
        }
      }
    }
  }
}

/**
 * Implements hook_views_data_export_row_alter().
 */
function nfafmis_views_data_export_row_alter(&$row, ResultRow $result, ViewExecutable $view) {
  if ($view->id() === 'accounts_tab_payments_report' && $view->current_display === 'data_export_1' && !empty($row['copy_of_receipt'])) {
    $row['copy_of_receipt'] = \Drupal::request()->getSchemeAndHttpHost() . $row['copy_of_receipt'];
  }
  if ($view->id() === 'fmis_reports' && $view->current_display === 'data_export_4' && !empty($row['copy_of_receipt'])) {
    $row['copy_of_receipt'] = \Drupal::request()->getSchemeAndHttpHost() . $row['copy_of_receipt'];
  }
}
