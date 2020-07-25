<?php

namespace Drupal\nfafmis\Form;

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
    $this->current_year = date("Y");
    $this->last_year = date("Y") - 1;
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
      '#title' => $this->t('Late fees settings'),
      '#description' => $this->t("This will be use to calculate land rent late fee annually."),
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
      '#type' => 'details',
      '#title' => $this->t('Generate annual charges manually'),
      '#description' => $this->t("By clicking the button you can generate annual charges for the year %y1, %y2 accordingly.
       <br>Note: Annual charges are meant to be calculated automatically on 1st January of every year, though this button can be used to create charges anytime but make sure you understand the consequences. Until you are not sure about this don't click on these button.", ['%y1' => $this->last_year, '%y2' => $this->current_year]
      ),
    ];
    $form['item-charges']['manual-action'] = [
      '#type' => 'fieldset',
    ];
    $form['item-charges']['manual-action']['last_year'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#for_year' => $this->last_year,
      '#submit' => [[$this, 'calculateAnnualChargesHandler']],
      '#value' => $this->t("Calculate annual charges for $this->last_year"),
    ];
    $form['item-charges']['manual-action']['current_year'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#for_year' => $this->current_year,
      '#submit' => [[$this, 'calculateAnnualChargesHandler']],
      '#value' => $this->t("Calculate annual charges for $this->current_year"),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $late_fees = $form_state->getValue('late_fees');
    // Retrieve the configuration.
    $this->config->getEditable('nfafmis.settings')
      // Set the submitted configuration setting.
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
    $for_year = $form_state->getTriggeringElement()['#for_year'];
    if (!$for_year) {
      $for_year = $this->current_year;
    }
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
        $annual_charges = $this->farmerService->chekForExistingAnnualCharges($area, $for_year);
        // Prevent creating duplicate annual charges for the same year.
        if (empty($annual_charges)) {
          $this->createAnnualCharges($area, $cfr, $area_allocated, $for_year);
        }
        else {
          $this->messenger()->addMessage($this->t('Annual charges already added against area :area for the year :year', [
            ':area' => $area->getTitle(),
            ':year' => $for_year,
          ]), 'warning');
        }
      }
    }
  }

  /**
   * Create annual charges for all area (offer license) lend by a farmer,
   * it consists two part.
   *
   * - Land rent late fee.
   * - Annual land rent.
   */
  public function createAnnualCharges($area, $cfr, $area_allocated, $for_year) {
    $previous_year_land_rent = $this->farmerService->getPreviousYearLandRentDue($area, $for_year);
    if (!empty($previous_year_land_rent) && $previous_year_land_rent['charges_due']) {
      $config = $this->config('nfafmis.settings');
      $late_fees = $config->get('late_fees');
      // Create land rent late fee, this will only happen if there is annual
      // land rent unpaid for previous year.
      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'annual_charges',
        'field_annual_charges' => ($previous_year_land_rent['amount'] * $late_fees) / 100,
        'field_rate_year' => $for_year,
        'field_licence_id_ref' => $area,
        'field_arrears' => $previous_year_land_rent['amount'],
        'field_annual_charges_type' => '2',
      ]);
      $node->save();
      $this->messenger()->addMessage($this->t('Annual charges (late fee) added against area :area for the year :year', [
        ':area' => $area->getTitle(),
        ':year' => $for_year,
      ]));
    }
    // Create annual land rent, this will only happen if land_rent_rates is
    // already added against Central Forest Reserve ($cfr) added for the year
    // ($this->year).
    $annual_charges = $this->farmerService->calculateAnnualCharges($cfr, $area_allocated, $for_year);
    if ($annual_charges) {
      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'annual_charges',
        'field_annual_charges' => $annual_charges,
        'field_rate_year' => $for_year,
        'field_licence_id_ref' => $area,
        'field_annual_charges_type' => '1',
      ]);
      $node->save();
      $this->messenger()->addMessage($this->t('Annual charges (land rent) added against area :area for the year :year', [
        ':area' => $area->getTitle(),
        ':year' => $for_year,
      ]));
    }
  }

}
