<?php

namespace Drupal\area_summary\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides the entity_test delete form.
 *
 * @internal
 */
class AreaSummaryDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

}
