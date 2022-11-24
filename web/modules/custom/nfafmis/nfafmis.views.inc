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

  // A map showing the farmer's land allocations.
  $data['views']['nfafmis_map'] = [
    'title' => t('Farmer Map'),
    'help' => t('A map showing the farmer\'s land allocations'),
    'field' => [
      'id' => 'nfafmis_farmer_map',
    ],
  ];

  // Filter to limit lists of terms to those that are in the user's assigned
  // management unit.
  $data['taxonomy_term_field_data']['user_management_unit'] = array(
    'title' => t('Limit to current user\'s Management Unit'),
    'help' => t('Filter the view to the currently logged in user\'s assigned management unit.'),
    'group' => t('Taxonomy term'),
    'filter' => [
      'id' => 'nfafmis_user_management_unit',
      'type' => 'yes-no',
    ],

  );
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
 *
 * @param \Drupal\views\ViewExecutable $view
 */
function nfafmis_views_pre_render(ViewExecutable $view) {
  if ($view->id() == 'farmer_main_tab') {
    $view->element['#attached']['library'][] = 'nfafmis/nfafmis';
  }

  // On first load of Farmer Filter the farmer name is not set and the most
  // recent farmer is returned. We don't want that, so don't show farmer details
  // if the name filter was not set.
  if ($view->id() == 'farmer_filter' || $view->id() == 'farmer_main_tab') {
    if (empty($view->getExposedInput()['title'])) {
      $result = $view->result;
      foreach ($result as $i => $row) {
        $view->result[$i]->_entity = NULL;
      }
    }
  }

  // @todo Refactor this as a custom views filter.
  if ($view->id() == 'fmis_reports' && ($view->current_display == 'farmers_full' || $view->current_display == 'farmers_full_export')) {
    // The farmer report view has a relationship to content with an entity
    // reference to the farmer node, i.e. content types with field_farmer_name_ref
    // field. There are multiple content types with that field but we only want to
    // include results where the referencing node is an Offer/License. We cannot
    // simply add a filter to the view to filter by content type because
    // doing that limits the results to farmers that have Offer/License nodes.
    // We need to show all farmers, those with Offer/License and those without.
    // So here we remove results where the related entity is not an Offer/License.
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
