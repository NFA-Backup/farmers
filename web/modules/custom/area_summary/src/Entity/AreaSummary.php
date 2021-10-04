<?php

namespace Drupal\area_summary\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the area summary entity class for storing summary data for forest reserves.
 *
 * @ContentEntityType(
 *   id = "area_summary",
 *   label = @Translation("Area summary data"),
 *   handlers = {
 *     "list_builder" = "Drupal\area_summary\Controller\AreaSummaryListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "area_summary",
 *   admin_permission = "administer area_summary entity",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "area" = "area",
 *     "cfr" = "cfr",
 *     "farmer" = "farmer",
 *   },
 *   links = {
 *     "canonical" = "/area_summary/{area_summary}",
 *     "collection" = "/admin/content/area_summary",
 *   },
 * )
 */
class AreaSummary extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the area summary entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the area summary entity.'))
      ->setReadOnly(TRUE);

    $fields['area'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Area'))
      ->setDescription(t('The area aka offer/license.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['offer_license' => 'offer_license']])
      ->setDisplayOptions('form', ['weight' => 0]);

    $fields['farmer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Farmer'))
      ->setDescription(t('The farmer.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['farmer_details' => 'farmer_details']])
      ->setDisplayOptions('form', ['weight' => 0]);

    $fields['management_unit'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Management unit'))
      ->setDescription(t('The management unit aka range.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['management_unit' => 'management_unit']])
      ->setDisplayOptions('form', ['weight' => 0]);

    $fields['cfr'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('CFR'))
      ->setDescription(t('The central forest reserve aka CFR.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['central_forest_reserve' => 'central_forest_reserve']])
      ->setDisplayOptions('form', ['weight' => 0]);

    $fields['area_allocated'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total area allocated'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', ['weight' => 0]);

    $fields['area_planted'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total area planted'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', ['weight' => 0]);

    $fields['average_stems'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Average stems/ha'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', ['weight' => 0]);

    return $fields;
  }

  /**
   * Sets the area planted value for the area.
   *
   * @param float $area_planted
   *   The commerce checkout flow plugin manager.
   *
   * @return $this
   */
  public function setAreaPlanted(float $area_planted) {
    $this->set('area_planted', $area_planted);
    return $this;
  }

}
