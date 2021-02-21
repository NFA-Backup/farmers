<?php

/**
 * @file
 * Post update functions.
 */

use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;

/**
 * Copy values from old Sub area planted field to new decimal field.
 */
function nfafmis_post_update_sub_area_planted(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
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
      $old_value = $node->get('field_sub_area_planted')->value;
      if (!empty($old_value)) {
        $node->field_subarea_planted->value = $old_value;
        $node->setNewRevision(FALSE);
        $node->save();
      }

      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max Sub area planted values.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Copy values from old Overall area allocated field to new decimal field.
 */
function nfafmis_post_update_offer_license_area_allocated(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
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
      $old_value = $node->get('field_overall_area_allocated')->value;
      if (!empty($old_value)) {
        $node->field_overall_area->value = $old_value;
        $node->setNewRevision(FALSE);
        $node->save();
      }

      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max Overall area allocated values.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Copy values from old Account area fields to new decimal fields.
 */
function nfafmis_post_update_account_area_fields(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'accounts_detail')
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
      $old_allocated = $node->get('field_area_allocated')->value;
      if (!empty($old_allocated)) {
        $node->field_account_area_allocated->value = $old_value;
      }
      $old_planted = $node->get('field_area_planted')->value;
      if (!empty($old_planted)) {
        $node->field_account_area_planted->value = $old_value;
      }
      if (!empty($old_allocated) || !empty($old_planted)) {
        $node->setNewRevision(FALSE);
        $node->save();
      }
      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Copied @progress of @max Account details area values.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}