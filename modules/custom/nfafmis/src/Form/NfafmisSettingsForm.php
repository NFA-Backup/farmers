<?php

namespace Drupal\nfafmis\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\nfafmis\Services\FarmerServices;
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
   * Drupal\Core\Entity\EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\nfafmis\Services\FarmerServices.
   *
   * @var \Drupal\nfafmis\Services\FarmerServices
   */
  protected $farmerService;

  /**
   * Class constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager,
    FarmerServices $farmer_service) {
    $this->config = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->farmerService = $farmer_service;

    // Year for which annual charges has to be calculated.
    $this->year = date("Y") - 1;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('nfafmis_service.farmer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('nfafmis.settings');

    $form['item-check'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Annual charges date & late fees settings'),
      '#description' => $this->t('Add a date on which annual charges calculation will be happen for each areas against each farmer applying the late fees.'),
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
    $form['item-charges'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('By clicking the button you can generate annual charges for the year %year.  note annual charges will be calculated for last the year only.', ['%year' => $this->year]),
    ];
    $form['item-charges']['calculate_annual_charges'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Calculate annual charges for year %year', ['%year' => $this->year]),
      '#submit' => ['::calculateAnnualChargesHandler'],
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

  /**
   * Custom submission handler for calculateAnnualChargesHandler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function calculateAnnualChargesHandler(array &$form, FormStateInterface $form_state) {
    // Create node of annual charges programmatically for last year.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $offer_license_ids = $query->condition('type', 'offer_license')
      ->condition('status', 1)
      ->execute();
    $areas_object = $this->entityTypeManager->getStorage('node')->loadMultiple($offer_license_ids);
    foreach ($areas_object as $area) {
      $area_allocated = $area->get('field_overall_area_allocated')->value;
      $cfr = $area->get('field_central_forest_reserve')->target_id;
      if ($cfr && $area_allocated) {
        $this->createAnnualCharges($area, $cfr, $area_allocated);
      }
    }
  }

  /**
   * Create node of annual charges programmatically for last year.
   */
  public function createAnnualCharges($area, $cfr, $area_allocated) {
    $annual_charges = $this->farmerService->getAnnualCharges($cfr, $area_allocated, $this->year);
    $title = $area->get('title')->value;
    if (!empty($annual_charges['data'])) {
      $field_annual_charges = array_values($annual_charges['data'])[0]['field_rate'];
      $node = Node::create([
        'type' => 'annual_charges',
        'field_annual_charges' => $field_annual_charges,
        'field_rate_year' => $this->year,
        'field_licence_id_ref' => $area,
      ]);
      $node->save();
      $this->messenger()->addMessage($this->t('Annual charges added against area :area for the year :year', [
        ':area' => $title,
        ':year' => $this->year,
      ]));
    }
  }

}
