<?php

namespace Drupal\yearonly\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a configurable entity datetime field.
 */
class YearOnlyFieldItemList extends FieldItemList {

  /**
   * Defines the default value as now.
   */
  const DEFAULT_VALUE_NOW = 'now';

  /**
   * Defines the default value as relative.
   */
  const DEFAULT_VALUE_RELATIVE = 'relative';

  /**
   * Defines the default value as a specific year.
   */
  const DEFAULT_VALUE_SPECIFIC = 'specific';

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      $default_value = $this->getFieldDefinition()->getDefaultValueLiteral();

      $element = [
        '#parents' => ['default_value_input'],
        'default_type' => [
          '#type' => 'select',
          '#title' => $this->t('Default year'),
          '#description' => $this->t('Choose a default value for the year.'),
          '#default_value' => isset($default_value[0]['default_type']) ? $default_value[0]['default_type'] : '',
          '#options' => [
            static::DEFAULT_VALUE_NOW => $this->t('Current year'),
            static::DEFAULT_VALUE_RELATIVE => $this->t('Relative year'),
            static::DEFAULT_VALUE_SPECIFIC => $this->t('Specific year'),
          ],
          '#empty_value' => '',
        ],
        'default_relative' => [
          '#type' => 'textfield',
          '#title' => $this->t('Relative default value'),
          '#description' => $this->t("Describe a time by reference to the current date, like '+2 years' (2 years from the day the field is created). See <a href=\"http://php.net/manual/function.strtotime.php\">strtotime</a> for more details."),
          '#default_value' => (isset($default_value[0]['default_type']) && $default_value[0]['default_type'] == static::DEFAULT_VALUE_RELATIVE) ? $default_value[0]['default_relative'] : '',
          '#states' => [
            'visible' => [
              ':input[id="edit-default-value-input-default-type"]' => ['value' => static::DEFAULT_VALUE_RELATIVE],
            ],
            'required' => [
              ':input[id="edit-default-value-input-default-type"]' => ['value' => static::DEFAULT_VALUE_RELATIVE],
            ],
          ],
        ],
        'default_specific' => [
          '#type' => 'textfield',
          '#title' => $this->t('Specific default year'),
          '#description' => $this->t("Enter a specific year for the default value (ex. 2020)."),
          '#default_value' => (isset($default_value[0]['default_type']) && $default_value[0]['default_type'] == static::DEFAULT_VALUE_SPECIFIC) ? $default_value[0]['default_specific'] : '',
          '#states' => [
            'visible' => [
              ':input[id="edit-default-value-input-default-type"]' => ['value' => static::DEFAULT_VALUE_SPECIFIC],
            ],
            'required' => [
              ':input[id="edit-default-value-input-default-type"]' => ['value' => static::DEFAULT_VALUE_SPECIFIC],
            ],
          ],
        ],
      ];

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    // Check for valid strtotime format.
    if ($form_state->getValue(['default_value_input', 'default_type']) == static::DEFAULT_VALUE_RELATIVE) {
      $is_strtotime = @strtotime($form_state->getValue(['default_value_input', 'default_relative']));
      if (!$is_strtotime) {
        $form_state->setErrorByName('default_value_input][default_relative', $this->t('The relative year value entered is invalid.'));
      }
    }

    if ($form_state->getValue(['default_value_input', 'default_type']) == static::DEFAULT_VALUE_SPECIFIC) {
      // Ensure a valid year (positive integer) was provided.
      $spec_year = $form_state->getValue(['default_value_input', 'default_specific']);
      if (!((int) $spec_year == $spec_year && (int) $spec_year > 0)) {
        $form_state->setErrorByName('default_value_input][default_specific', $this->t('Please provide a valid year.'));
      }
      // Ensure the specified year is within the min/max values specified in
      // field settings.
      else {
        $min_year = $form_state->getValue(['settings', 'yearonly_from']);
        $max_year = ($form_state->getValue(['settings', 'yearonly_to']) == 'now') ? date('Y') : $form_state->getValue(['settings', 'yearonly_to']);
        if (!($min_year <= $spec_year && $spec_year <= $max_year)) {
          $form_state->setErrorByName('default_value_input][default_specific', $this->t('The year is not within the range specified above.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['default_value_input', 'default_type'])) {
      if ($form_state->getValue(['default_value_input', 'default_type']) == static::DEFAULT_VALUE_NOW) {
        $form_state->setValueForElement($element['default_relative'], static::DEFAULT_VALUE_NOW);
      }
      return [$form_state->getValue('default_value_input')];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if (isset($default_value[0]['default_type'])) {
      switch ($default_value[0]['default_type']) {
        case static::DEFAULT_VALUE_NOW;
          $default_value = (int) date('Y');
          break;

        case static::DEFAULT_VALUE_RELATIVE;
          $default_value = (int) date('Y', strtotime($default_value[0]['default_relative']));
          break;

        case static::DEFAULT_VALUE_SPECIFIC;
          $default_value = (int) $default_value[0]['default_specific'];
          break;

      }
    }

    return $default_value;
  }

}
