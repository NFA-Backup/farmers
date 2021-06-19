<?php

namespace Drupal\nfafmis\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a field to retrieve the "title" query param, if existing.
 *
 * @ViewsField("nfafmis_title_query_param")
 */
class NfaTitleQueryParam extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return (string) \Drupal::request()->query->get('title');
  }

}
