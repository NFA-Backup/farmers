<?php

namespace Drupal\nfafmis\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Displays a map showing the farmers land allocations.
 *
 * @ViewsField("nfafmis_farmer_map")
 */
class FarmerMap extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['show_subareas'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['show_subareas'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Show sub areas on map"),
      '#default_value' => $this->options['show_subareas'],
    ];

    parent::buildOptionsForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return \Drupal::service('plugin.manager.block')
      ->createInstance('farmer_map_block', ['subareas' => $this->options['show_subareas']])
      ->build();
  }

}
