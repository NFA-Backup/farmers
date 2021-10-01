<?php

namespace Drupal\area_summary\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of area summary entities.
 *
 * @see \Drupal\nfafmis\Entity\AreaSummary
 */
class AreaSummaryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['management_unit'] = t('Management unit');
    $header['cfr'] = t('CFR');
    $header['area'] = t('Area');
    $header['area_allocated'] = t('Total area allocated (Ha)');
    $header['area_planted'] = t('Total area planted (Ha)');
    $header['average_stems'] = t('Average stems/ha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['management_unit'] = isset($entity->management_unit->entity) ? $entity->management_unit->entity->label() : NULL;
    $row['cfr'] = $entity->cfr->entity->label();
    $row['area'] = $entity->area->entity->label();
    $row['area_allocated'] = $entity->area_allocated->value . ' ha';
    $row['area_planted'] = $entity->area_planted->value . ' ha';
    $row['average_stems'] = $entity->average_stems->value;
    return $row + parent::buildRow($entity);
  }

}
