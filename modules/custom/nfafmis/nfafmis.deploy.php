<?php

/**
 * @file
 * Deploy functions run after drush config:import
 */

use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\taxonomy\Entity\Term;

/**
 * Run migrations to import Ranges, CFRs and Blocks.
 */
function nfafmis_deploy_001() {
  $migrations = [
    'nfafmis_migrate_management_units',
    'nfafmis_migrate_cfrs',
    'nfafmis_migrate_blocks',
  ];

  foreach ($migrations as $id) {
    $migration = Drupal::service('plugin.manager.migration')->createInstance($id);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }
}

/**
 * Migrate block ids from list field to Block term reference field.
 */
function nfafmis_deploy_002(&$sandbox = NULL) {
  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = Drupal::entityQuery('node')
      ->condition('type', 'offer_license')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($nids as $result) {
      $sandbox['nodes'][] = $result;
    }
    if (!empty($sandbox['nodes'])) {
      $sandbox['max'] = count($sandbox['nodes']);
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 50);
  if (!empty($sandbox['nodes'])) {
    // Handle nodes in batches.
    $nids = array_slice($sandbox['nodes'], $sandbox['progress'], $batch_size);

    foreach ($nids as $id) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = Node::load($id);
      // Get the block id value from the select list.
      if ($key = $node->field_block_id->value) {
        $value = $node->field_block_id->getSetting('allowed_values')[$key];
        if (!empty($value)) {
          // Get the equivalent Block term id and set the new term ref field.
          $term = Drupal::entityTypeManager()->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $value, 'vid' => 'block']);
          if (!empty($term)) {
            $term = reset($term);
            $term_id = $term->id();

            $node->field_block_ref->target_id = $term_id;
            $node->setNewRevision(FALSE);
            $node->save();
          }
        }
      }
      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max block ids.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Update url aliases of Management unit, CFR and Block terms.
 */
function nfafmis_deploy_003(&$sandbox = NULL) {
  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    $vocabs = ['management_unit', 'central_forest_reserve', 'block'];
    // Load the term ids.
    $tids = Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabs, 'IN')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($tids as $result) {
      $sandbox['terms'][] = $result;
    }
    if (!empty($sandbox['terms'])) {
      $sandbox['max'] = count($sandbox['terms']);
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 50);
  if (!empty($sandbox['terms'])) {
    // Handle terms in batches.
    $tids = array_slice($sandbox['terms'], $sandbox['progress'], $batch_size);

    foreach ($tids as $id) {
      Drupal::service('pathauto.generator')->createEntityAlias(Term::load($id), 'insert');
    }
  }
}

/**
 * Migrate Sub-area date planted values to year planted.
 */
function nfafmis_deploy_004(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = Drupal::entityQuery('node')
      ->condition('type', 'sub_area')
      ->accessCheck(FALSE)
      ->execute();

    foreach ($nids as $result) {
      $sandbox['nodes'][] = $result;
    }
    if (!empty($sandbox['nodes'])) {
      $sandbox['max'] = count($sandbox['nodes']);
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 50);
  if (!empty($sandbox['nodes'])) {
    // Handle nodes in batches.
    $nids = array_slice($sandbox['nodes'], $sandbox['progress'], $batch_size);

    foreach ($nids as $id) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = Node::load($id);
      // Get the date planted value.
      if ($date = $node->field_date_planted->value) {
        $year = substr($date, 0, 4);
        $node->field_year_planted = $year;
        $node->setNewRevision(FALSE);
        $node->save();
      }
      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max date planted values.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}
