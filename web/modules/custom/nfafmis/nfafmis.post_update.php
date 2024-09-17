<?php

/**
 * @file
 * Post update functions run before drush config:import.
 */

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

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

/**
 * Rename incorrectly named CFRs.
 */
function nfafmis_post_update_b() {
  // CFR terms to be renamed. Commented names will be added by the migration.
  $cfrs = [
    //'Abera',
    58 => 'Aduku (North)',
    //'Aduku (South)',
    78 => 'Ayer (1959 eucalyptus)',
    79 => 'Ayer (Bala Road)',
    80 => 'Ayer (Lira Road)',
    82 => 'Bala (North)',
    83 => 'Bala (South)',
    88 => 'Gung-Gung',
    101 => 'Lela-Olok',
    154 => 'Kigulya Hill',
    170 => 'Nsekuro Hill',
    239 => 'Moroto',
    108 => 'Nangolibwel',
    110 => 'Nyangea-Napore',
    152 => 'Katuugo',
    //'Achomai',
    204 => 'Alungamosimosi',
    211 => 'Bugondo Hill',
    215 => 'Bululu Hill',
    235 => 'Lubani',
    242 => 'Nagongera (East)',
    243 => 'Nagongera (West)',
    248 => 'Namasiga-Kidimbuli',
    251 => 'Namazingiri',
    255 => 'Ochomil',
    258 => 'Ogata-Akimenga',
    259 => 'Ogera Hill',
    320 => 'Kuzito',
    322 => 'Kyansonzi',
    345 => 'Nakawa Forestry Research',
    348 => 'Nakiza',
    356 => 'Namakupa',
    355 => 'Namawanyi & Namananga',
    //'Nambale (Kasa South)',
    362 => 'Ngogwe (Bwema Island)',
    378 => 'Wantagalala',
    497 => 'Mafuga',
    498 => 'Mbarara',
    384 => 'Bumude-Nchwanga',
    393 => 'Kabugeza (Kasanda)',
    425 => 'North Rwenzori',
    282 => 'Bunjazi',
    311 => 'Kisasa',
    325 => 'Lajabwa',
    336 => 'Manwa (South East)',
    338 => 'Mulega',
    370 => 'Tero (East)',
    371 => 'Tero (West)',
    496 => 'Kyantuhe',
    535 => 'Lul Kayonga',
    536 => 'Lul Oming',
    537 => 'Lul Opio',
    538 => 'Mt. Kei',
    542 => 'Otzi (East)',
    543 => 'Otzi (West)',
    141 => 'Kagogo (Budongo System)',
    157 => 'Kitonya (Budongo System)',
    164 => 'Mbale (Katuugo Plantations)',
    165 => 'Mpanga (Budongo System)',
    321 => 'Kyampisi (Lakeshore)',
  ];

  // Rename the CFR terms.
  foreach ($cfrs as $tid => $name) {
    Term::load($tid)->setName($name)->save();
  }
}

/**
 * Reset the Date harvested date for sub areas that have not been harvested.
 */
function nfafmis_post_update_c(&$sandbox = NULL) {

  // Use the sandbox to update nodes in batches.
  if (!isset($sandbox['progress'])) {
    // This is the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;

    // Load Sub area nodes ids.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'sub_area')
      ->condition('field_area_harvested', FALSE)
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
      $node->field_date_harvested->value = 0;
      $node->setNewRevision(FALSE);
      $node->save();

      $sandbox['progress']++;
    }

    // Tell Drupal what percentage of the batch is completed.
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    \Drupal::logger('NFA-FMIS')
      ->debug(
        'Reset date harvested of @progress of @max Sub areas.',
        [
          '@progress' => $sandbox['progress'],
          '@max' => $sandbox['max'],
        ]
      );
  }
}

/**
 * Rebuild node access permissions.
 */
function nfafmis_post_update_006_rebuild_node_permissions(&$sandbox = NULL) {
  // Rebuild node access permissions.
  node_access_rebuild(TRUE);
}
