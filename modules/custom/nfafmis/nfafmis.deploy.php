<?php

/**
 * @file
 * Deploy functions run after drush config:import
 */

use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;

/**
 * Migrate Sub-area date planted values to year planted.
 */
function nfafmis_deploy_001(&$sandbox = NULL) {

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
