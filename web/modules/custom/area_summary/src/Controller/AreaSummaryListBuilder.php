<?php

namespace Drupal\area_summary\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a class to build a listing of area summary entities.
 *
 * @see \Drupal\nfafmis\Entity\AreaSummary
 */
class AreaSummaryListBuilder extends EntityListBuilder {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['management_unit'] = $this->t('Management unit');
    $header['cfr'] = $this->t('CFR');
    $header['area'] = $this->t('Area');
    $header['area_allocated'] = $this->t('Total area allocated (Ha)');
    $header['area_planted'] = $this->t('Total area planted (Ha)');
    $header['average_stems'] = $this->t('Average stems/ha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['management_unit'] = isset($entity->management_unit->entity) ? $entity->management_unit->entity->label() : NULL;
    $row['cfr'] = $entity->cfr->entity->label();
    $row['area'] = $entity->area->entity->label();
    $row['area_allocated'] = $entity->get('area_allocated')->value . ' ha';
    $row['area_planted'] = $entity->get('area_planted')->value . ' ha';
    $row['average_stems'] = $entity->get('average_stems')->value;
    return $row + parent::buildRow($entity);
  }

}
