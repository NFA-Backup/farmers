<?php

namespace Drupal\nfafmis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Site\Settings;
use Drupal\nfafmis\Services\FarmerServices;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;
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
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\nfafmis\Services\FarmerServices $farmer_service
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager,
    FarmerServices $farmer_service) {
    $this->config = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->farmerService = $farmer_service;
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
      '#description' => $this->t('<strong>Note: Annual charges are meant to be calculated automatically on 1st January of every year. This button can be used to create charges anytime but make sure you understand the consequences. If you are not sure about this do not click on this button.</strong>'),
    ];
    $form['item-charges']['manual-action'] = [
      '#type' => 'fieldset',
    ];

    $options = array_combine(range(date('Y'), 2004), range(date('Y'), 2004));
    $form['item-charges']['manual-action']['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Select year'),
      '#options' => $options,
    ];
    $form['item-charges']['manual-action']['farmer']= [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Farmer'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['farmer_details'],
      ],
    ];
    $form['item-charges']['manual-action']['recalculate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recalculate and replace existing charges.'),
      '#description' => $this->t('If checked, existing charges will be deleted and replaced with recalculated values.'),
      '#return_value' => TRUE,
      '#default_value' => FALSE,
    ];
    $form['item-charges']['manual-action']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#submit' => [[$this, 'calculateAnnualChargesBatch']],
      '#value' => $this->t('Calculate annual charges'),
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
   * Set up batch process for calculating annual charges.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function calculateAnnualChargesBatch(array &$form, FormStateInterface $form_state) {
    $year = $form_state->getValue('year');
    $recalculate = $form_state->getValue('recalculate');
    $farmer = $form_state->getValue('farmer');

    if ($year) {
      $batch = [
        'operations' => [
          ['Drupal\nfafmis\Form\NfafmisSettingsForm::batchProcess', [$year, $recalculate, $farmer]],
        ],
        'finished' => 'Drupal\nfafmis\Form\NfafmisSettingsForm::batchFinished',
        'title' => t('Calculating annual land rent'),
        'progress_message' => t('Calculating annual land rent charges for @year', ['@year' => $year]),
      ];
      batch_set($batch);
    }
  }

  /**
   * Batch callback for calculating annual charges.
   */
  public static function batchProcess($year, $recalculate, $farmer, &$context) {
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');

    if (!isset($context['sandbox']['progress'])) {
      // This is the first run. Initialize the sandbox.
      $context['sandbox']['progress'] = 0;
      $context['results']['late'] = 0;
      $context['results']['annual'] = 0;

      // Load nids of the area nodes to be processed.
      $query = $storage_handler->getQuery()
        ->condition('type', 'offer_license')
        ->condition('status', 1)
        ->accessCheck();
      if ($farmer) {
        $query->condition('field_farmer_name_ref', $farmer);
      }
      $nids = $query->execute();

      foreach ($nids as $result) {
        $context['sandbox']['nodes'][] = $result;
      }
      if (!empty($context['sandbox']['nodes'])) {
        $context['sandbox']['max'] = count($context['sandbox']['nodes']);
      }
    }

    $batch_size = Settings::get('entity_update_batch_size', 50);
    if (!empty($context['sandbox']['nodes'])) {
      // Handle nodes in batches.
      $nids = array_slice($context['sandbox']['nodes'], $context['sandbox']['progress'], $batch_size);

      foreach ($nids as $id) {
        /** @var \Drupal\node\NodeInterface $node */
        $area = Node::load($id);
        $area_allocated = $area->get('field_overall_area')->value;
        $cfr = $area->get('field_central_forest_reserve')->target_id;
        if ($cfr && $area_allocated) {
          $annual_charges = \Drupal::service('nfafmis_service.farmer')->checkForExistingAnnualCharges($area, $year);
          // If charges already exist and the recalculate option is checked,
          // delete the existing charges before calculating.
          if ($annual_charges && $recalculate) {
            $entities = $storage_handler->loadMultiple($annual_charges);
            $storage_handler->delete($entities);
            $annual_charges = NULL;
            // Delete the payment advice associated with the annual charge.
            foreach ($entities as $entity) {
              $payment_advice = $storage_handler->load($entity->field_payment_advice->entity->id());
              $payment_advice->delete();
            }
          }
          if (empty($annual_charges)) {
            NfafmisSettingsForm::createAnnualCharges($area, $cfr, $area_allocated, $year, $context);
            $context['message'] = t('Processed @count of @total areas', [
              '@count' => $context['sandbox']['progress'] + 1,
              '@total' => $context['sandbox']['max'],
            ]);
          }
        }

        $context['sandbox']['progress']++;
      }

      // Tell Drupal what percentage of the batch is completed.
      $context['finished'] = empty($context['sandbox']['max']) ? 1 : ($context['sandbox']['progress'] / $context['sandbox']['max']);
    }
  }

  /**
   * Create annual charges for all areas (offer license) rented by a farmer.
   * There are two types of charge:.
   *
   * - Land rent late fee.
   * - Annual land rent.
   *
   * @param $area
   * @param $cfr
   * @param $area_allocated
   * @param $for_year
   * @param $context
   */
  public static function createAnnualCharges($area, $cfr, $area_allocated, $for_year, &$context) {
    $farmer_service = \Drupal::service('nfafmis_service.farmer');
    $entity_type_manager = \Drupal::entityTypeManager();

    $previous_year_land_rent = $farmer_service->getPreviousYearLandRentDue($area, $for_year);
    if (!empty($previous_year_land_rent) && $previous_year_land_rent['charges_due']) {
      $late_fees = $config = \Drupal::config('nfafmis.settings')->get('late_fees');
      // Create land rent late fee, this will only happen if there is annual
      // land rent unpaid for previous year.
      $node = $entity_type_manager->getStorage('node')->create([
        'type' => 'annual_charges',
        'field_annual_charges' => ($previous_year_land_rent['amount'] * $late_fees) / 100,
        'field_rate_year' => $for_year,
        'field_licence_id_ref' => $area,
        'field_arrears' => $previous_year_land_rent['amount'],
        'field_annual_charges_type' => '2',
      ]);
      $node->save();
      $context['results']['late']++;
    }
    // Create annual land rent if annual rates have been configured for the
    // given year and CFR.
    $annual_charges = $farmer_service->calculateAnnualCharges($cfr, $area_allocated, $for_year);
    if ($annual_charges) {
      $node = $entity_type_manager->getStorage('node')->create([
        'type' => 'annual_charges',
        'field_annual_charges' => $annual_charges,
        'field_rate_year' => $for_year,
        'field_licence_id_ref' => $area,
        'field_annual_charges_type' => '1',
        'field_overall_area' => $area_allocated,
      ]);
      $node->save();
      $context['results']['annual']++;
    }
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    $messenger = \Drupal::service('messenger');

    if ($success) {
      if ($results['late'] > 0 || $results['annual'] > 0) {
        if ($results['late'] > 0) {
          $messenger->addMessage(t('Calculated @count late rent charges.', ['@count' => $results['late']]));
        }
        if ($results['annual'] > 0) {
          $messenger->addMessage(t('Calculated @count annual rent charges.', ['@count' => $results['annual']]));
        }
      }
      else {
        $messenger->addMessage(t('No new rent charges were calculated.'));
      }
    }
    else {
      $messenger->addMessage(t('An error occurred while calculating annual charges.'));
    }
  }
}
