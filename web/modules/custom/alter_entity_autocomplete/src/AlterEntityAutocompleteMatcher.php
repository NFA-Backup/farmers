<?php

namespace Drupal\alter_entity_autocomplete;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher;

class AlterEntityAutocompleteMatcher extends EntityAutocompleteMatcher {

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {

    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {

          $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);

          if ($entity->bundle() == 'offer_license') {
            $label = $entity->get('field_area_number')->value;
          }

          // Update key and label as per field.
          $key = $label . ' (' . $entity_id . ')';
          $label = $label . ' (' . $entity_id . ')';

          // Strip things like starting/trailing white spaces, line breaks and tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));

          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
