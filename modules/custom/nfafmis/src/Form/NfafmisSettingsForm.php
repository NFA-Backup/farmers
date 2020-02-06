<?php

namespace Drupal\nfafmis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NfafmisSettingsForm.
 */
class NfafmisSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nfafmis_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nfafmis.settings',
    ];
  }

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('nfafmis.settings');

    $form['item-check'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Anual charges date & late fees settings'),
      '#description' => $this->t('Add anual charge date on which calculation will be happen applying the late fees.'),
    ];
    $form['item-check']['anual_charge_date'] = [
      '#type' => 'date',
      '#required' => TRUE,
      '#title' => $this->t('Date'),
      '#default_value' => $config->get('anual_charge_date'),
    ];
    $form['item-check']['late_fees'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => '12',
      '#field_suffix' => '%',
      '#title' => $this->t('Late fees'),
      '#default_value' => $config->get('late_fees'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $anual_charge_date = $form_state->getValue('anual_charge_date');
    $late_fees = $form_state->getValue('late_fees');

    // Retrieve the configuration.
    $this->config->getEditable('nfafmis.settings')
      // Set the submitted configuration setting.
      ->set('anual_charge_date', $anual_charge_date)
      ->set('late_fees', $late_fees)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
