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
  elseif ($view->storage->get('id') === 'cfr_lists') {
    // Get the current user's assigned management unit.
    $nfafmis_service_utility = \Drupal::service('nfafmis_service.utility');
    $range = $nfafmis_service_utility->getUserManagementUnit();

    if ($range) {
      // Add a join to the management unit data table.
      $definition = [
        'table' => 'taxonomy_term__management_unit',
        'type' => 'INNER',
        'field' => 'entity_id',
        'left_table' => 'taxonomy_term_field_data',
        'left_field' => 'tid',
      ];

      $join = Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);
      $query->addRelationship('taxonomy_term__management_unit', $join, 'person');

      // Limit CFRs to those that are in the user's management unit.
      $query->addWhere('conditions', 'taxonomy_term__management_unit.management_unit_target_id ', $range->id(), '=');
    }
  }
}

/**
 * Implements hook_views_pre_render().
 *
 * @param \Drupal\views\ViewExecutable $view
 */
function nfafmis_views_pre_render(ViewExecutable $view) {
  $view->element['#attached']['library'][] = 'nfafmis/nfafmis';

  // On first load of Farmer Filter the farmer name is not set and the most
  // recent farmer is returned. We don't want that, so don't show farmer details
  // if the name filter was not set.
  if ($view->id() == 'farmer_filter') {
    if (empty($view->getExposedInput()['title'])) {
      $result = $view->result;
      foreach ($result as $i => $row) {
        $view->result[$i]->_entity = NULL;
      }
    }
  }

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
