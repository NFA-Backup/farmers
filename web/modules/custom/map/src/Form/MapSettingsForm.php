<?php

namespace Drupal\nfa_map\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a nfa_map settings form.
 */
class MapSettingsForm extends ConfigFormbase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nfa_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

}
