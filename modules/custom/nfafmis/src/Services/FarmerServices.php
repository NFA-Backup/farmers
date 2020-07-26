<?php

namespace Drupal\nfafmis\Services;

use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class FarmerServices.
 */
class FarmerServices {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new FarmerServices object.
   */
  public function __construct(
    AccountProxy $current_user,
    EntityTypeManager $entity_type_manager,
    Renderer $renderer
  ) {
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Update view field result with get_land_rent_and_other_data.
   *
   * @param string $farmer_id
   *   The farmer ID.
   * @param string $offer_licence_id
   *   The area ID.
   *
   * @return array
   *   The rendered array.
   */
  public function getAreaLandRentAndFeesData($farmer_id, $offer_licence_id) {
    $starting_amount = $this->getStartingAmountData($offer_licence_id);
    // Get fees & land rent data.
    $fees_data = $this->getFeesData($offer_licence_id);
    $land_rent_data = $this->getLandRentData($offer_licence_id);
    $data = [
      'starting_amount' => $starting_amount,
      'farmer_id' => $farmer_id,
      'offer_licence_id' => $offer_licence_id,
      'fees' => $fees_data,
      'land_rent' => $land_rent_data,
    ];
    $renderable = [
      '#theme' => 'tab__accounts__area__land_rent_fees_data',
      '#data' => $data,
    ];
    $rendered = $this->renderer->render($renderable);
    return $rendered;
  }

  /**
   * Get fees data for particular area.
   *
   * @param string $offer_licence_id
   *   The area ID.
   *
   * @return array
   *   The fee data.
   */
  public function getFeesData($offer_licence_id) {
    $fees_data = [
      'balance' => 0,
      'charges' => 0,
      'payments' => 0,
    ];
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $charge_nids = $query->condition('type', 'charge')
      ->condition('field_areas_id.target_id', $offer_licence_id)
      ->execute();
    if (!empty($charge_nids)) {
      $nids = array_values($charge_nids);
      $charges = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($charges as $charge) {
        // Get invoice from charges.
        $invoice = $charge->get('field_payment_advice')->referencedEntities()[0];
        $invoice_has_payment = $this->invoiceHasPayment($invoice->id());
        if (!empty($invoice_has_payment)) {
          $fees_data['payments'] += $charge->get('field_amount')->value;
        }
        else {
          $fees_data['balance'] += $charge->get('field_amount')->value;
        }
        $fees_data['charges'] += $charge->get('field_amount')->value;
      }
    }
    // Format the data.
    $fees_data['balance'] = number_format($fees_data['balance'], 0, '.', ',');
    $fees_data['payments'] = number_format($fees_data['payments'], 0, '.', ',');
    $fees_data['charges'] = number_format($fees_data['charges'], 0, '.', ',');
    return $fees_data;
  }

  /**
   * Get payment id against each invoice (Payment advice).
   *
   * @param string $invoice_id
   *   The invoice ID.
   *
   * @return array
   *   The array of payment id.
   */
  public function invoiceHasPayment($invoice_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $payment_nid = $query->condition('type', 'payment')
      ->condition('field_invoice.target_id', $invoice_id)
      ->execute();
    return !empty($payment_nid) ? $payment_nid : [];
  }

  /**
   * Get land rent data for particular area.
   *
   * @param string $offer_licence_id
   *   The area ID.
   *
   * @return array
   *   The land rent data.
   */
  public function getLandRentData($offer_licence_id) {
    $land_rent_data = [
      'balance' => 0,
      'charges' => 0,
      'payments' => 0,
      'data' => [],
    ];
    $this->getLandRentStartingAmountData($offer_licence_id, $land_rent_data);
    $this->getLandRentAnnulChargesData($offer_licence_id, $land_rent_data);
    return $land_rent_data;
  }

  /**
   * Get land rent starting amount for particular area.
   *
   * @param string $offer_licence_id
   *   The area ID.
   * @param array $land_rent_data
   *   The $land_rent_data.
   */
  protected function getLandRentStartingAmountData($offer_licence_id, &$land_rent_data) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $starting_amount_nids = $query->condition('type', 'starting_amount')
      ->condition('field_areas_id.target_id', $offer_licence_id)
      ->execute();
    $data_array = [];
    if (!empty($starting_amount_nids)) {
      $starting_amounts = $this->entityTypeManager->getStorage('node')->loadMultiple($starting_amount_nids);
      foreach ($starting_amounts as $starting_amount) {
        $amount = $starting_amount->get('field_amount')->value;
        $data_array += ['date' => 'Starting amount'];
        $data_array += ['nid' => $starting_amount->get('nid')->value];
        $land_rent_data['charges'] += $amount;
        $data_array += ['land_rent_due' => $amount];
        $data_array += ['previous_arrears' => 0];
        $data_array += ['late_fee_due' => 0];
        $data_array += ['total_due' => $amount];
        $invoice = $starting_amount->get('field_payment_advice')->referencedEntities()[0];
        // Get incove for staring amount.
        if ($invoice) {
          $data_array += ['payment_advc_no' => $invoice->get('field_invoice_number')->value];
          $data_array += ['payment_advc_nid' => $invoice->id()];
          $invoice_payment_id = $this->invoiceHasPayment($invoice->id());
          // Get payment data for invoice.
          if (!empty($invoice_payment_id)) {
            $land_rent_data['payments'] += $amount;
            $payment_id = reset($invoice_payment_id);
            $payment = $this->entityTypeManager->getStorage('node')->load($payment_id);
            $data_array += ['payment_nid' => $payment->id()];
            $data_array += ['payment_date' => $payment->get('field_date_paid')->value];
            $data_array += ['receipt_number' => $payment->get('field_receipt_number')->value];
          }
          else {
            $land_rent_data['balance'] += $amount;
          }
        }
      }
      $land_rent_data['data']['sa'] = $data_array;
    }
  }

  /**
   * Check for existing annual charges for the particular year and area.
   *
   * @param object $area
   *   The area object.
   * @param string $for_year
   *   The year.
   */
  public function chekForExistingAnnualCharges($area, $for_year) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $annual_charges_nid = $query->condition('type', 'annual_charges')
      ->condition('field_licence_id_ref.target_id', $area->id())
      ->condition('field_rate_year.value', $for_year)
      ->condition('field_annual_charges_type', '1')
      ->execute();
    return $annual_charges_nid ?? [];
  }

  /**
   * Get land rent annual charges for particular area.
   *
   * @param string $offer_licence_id
   *   The area ID.
   * @param array $land_rent_data
   *   The $land_rent_data.
   */
  protected function getLandRentAnnulChargesData($offer_licence_id, &$land_rent_data) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $annual_charges_nids = $query->condition('type', 'annual_charges')
      ->condition('field_licence_id_ref.target_id', $offer_licence_id)
      ->execute();
    $data_array = [];
    if (!empty($annual_charges_nids)) {
      $annual_charges = $this->entityTypeManager->getStorage('node')->loadMultiple($annual_charges_nids);
      foreach ($annual_charges as $key => $annual_charge) {
        $amount = $annual_charge->get('field_annual_charges')->value;
        $year = $annual_charge->get('field_rate_year')->value;
        $arrears = $annual_charge->get('field_arrears')->value;
        $charge_type = $annual_charge->get('field_annual_charges_type')->value;
        $land_rent_data['charges'] += $amount;
        $data_array[$key]['date'] = '01-01-' . $year;
        if ($charge_type == '1') {
          $data_array[$key]['land_rent_due'] = $amount;
          $data_array[$key]['late_fee_due'] = 0;
        }
        else {
          $data_array[$key]['land_rent_due'] = 0;
          $data_array[$key]['late_fee_due'] = $amount;
        }
        $data_array[$key]['previous_arrears'] = $arrears;
        $data_array[$key]['total_due'] = $amount;
        $invoice = $annual_charge->get('field_payment_advice')->referencedEntities()[0];
        // Get incove for annual charges.
        if ($invoice) {
          $data_array[$key]['payment_advc_no'] = $invoice->get('field_invoice_number')->value;
          $data_array[$key]['payment_advc_nid'] = $invoice->id();
          $invoice_payment_id = $this->invoiceHasPayment($invoice->id());
          // Get payment data for annual charges.
          if (!empty($invoice_payment_id)) {
            $land_rent_data['payments'] += $amount;
            $payment_id = reset($invoice_payment_id);
            $payment = $this->entityTypeManager->getStorage('node')->load($payment_id);
            $data_array[$key]['payment_nid'] = $payment->id();
            $data_array[$key]['payment_date'] = $payment->get('field_date_paid')->value;
            $data_array[$key]['receipt_number'] = $payment->get('field_receipt_number')->value;
          }
          else {
            $land_rent_data['balance'] += $amount;
          }
        }
      }
      // Sort data according to the date.
      krsort($data_array);
      $land_rent_data['data'] = array_merge($data_array, $land_rent_data['data']);
    }
  }

  /**
   * Get Starting amound data for particular area.
   */
  protected function getStartingAmountData($offer_licence_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $starting_amount_nids = $query->condition('type', 'starting_amount')
      ->condition('field_areas_id.target_id', $offer_licence_id)
      ->execute();
    $starting_amount_data = [];
    if (!empty($starting_amount_nids)) {
      $starting_amount_nid = reset($starting_amount_nids);
      $starting_amount = $this->entityTypeManager->getStorage('node')->load($starting_amount_nid);
      $starting_amount_data['nid'] = $starting_amount->get('nid')->value;
      $starting_amount_data['amount'] = $starting_amount->get('field_amount')->value;
    }
    return $starting_amount_data;
  }

  /**
   * Calculate annual charges for area.
   *
   * @param string $cfr
   *   The central forest reseve ID.
   * @param string $overall_area_allocated
   *   The overall unit alocated for the area.
   * @param string $for_year
   *   Annual charges for the year.
   *
   * @return array
   *   The annual land rent.
   */
  public function calculateAnnualCharges($cfr, $overall_area_allocated, $for_year) {
    // field_central_forest_reserve taxonomy term id.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('type', 'land_rent_rates');
    $query->condition('field_central_forest_reserve.target_id', $cfr);
    $query->condition('field_rate_year.value', $for_year);
    $land_rent_rates_nid = $query->execute();
    $land_rent_annual_charge = [];

    if (!empty($land_rent_rates_nid)) {
      $nid = reset($land_rent_rates_nid);
      $land_rent_rate_node = $this->entityTypeManager->getStorage('node')->load($nid);
      // Make sure overall area is not zero.
      // land_rent_rate x overall_area_allocated.
      $rent_rate = $land_rent_rate_node->get('field_rate')->value;
      $land_rent = $rent_rate * $overall_area_allocated;
      $land_rent_annual_charge = $land_rent;
      return $land_rent_annual_charge;
    }
    return 0;
  }

  /**
   * Get previous year unpaid land rent for paticular area.
   *
   * @param object $area
   *   The area object.
   * @param string $for_year
   *   Annual charges for the year.
   *
   * @return array
   *   The land rent total.
   */
  public function getPreviousYearLandRentDue($area, $year) {
    $year = $year - 1;
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $annual_charges_nids = $query->condition('type', 'annual_charges')
      ->condition('field_licence_id_ref.target_id', $area->id())
      ->condition('field_rate_year.value', $year)
      ->condition('field_annual_charges_type', '1')
      ->execute();

    $land_rent_annual_charge = [];
    if (!empty($annual_charges_nids)) {
      $annual_charges_nid = reset($annual_charges_nids);
      $annual_charges = $this->entityTypeManager->getStorage('node')->load($annual_charges_nid);
      $land_rent_annual_charge['amount'] = $annual_charges->get('field_annual_charges')->value;
      $land_rent_annual_charge['charges_due'] = TRUE;
      $invoice = $annual_charges->get('field_payment_advice')->referencedEntities()[0];
      // Get incove for annual charges.
      if ($invoice) {
        $invoice_payment_id = $this->invoiceHasPayment($invoice->id());
        // Get payment data for annual charges.
        if (!empty($invoice_payment_id)) {
          $land_rent_annual_charge['charges_due'] = FALSE;
        }
      }
    }
    return $land_rent_annual_charge;
  }

  /**
   * Get offer license IDs based on farmer id.
   *
   * @param string $offer_licence_ids
   *   The area ID.
   *
   * @return mixed
   *   The list of sub aread ids.
   */
  public function getSubAreasIds($offer_licence_ids) {
    $offer_licence_ids = explode(',', $offer_licence_ids);
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $sub_area_nids = $query->condition('type', 'sub_area')
      ->condition('field_areas_id.target_id', $offer_licence_ids, 'IN')
      ->execute();
    if (!empty($sub_area_nids)) {
      $sub_area_nids = array_values($sub_area_nids);
      return implode(',', $sub_area_nids);
    }
    return NULL;
  }

  /**
   * Get total charges of area year wise.
   *
   * @param string $cfr
   *   The central forest reseve ID.
   * @param string $overall_area_allocated
   *   The overall area alocated for the area.
   *
   * @return array
   *   The chages total year wise.
   */
  protected function getTotalYearWise($cfr, $overall_area_allocated) {
    // field_central_forest_reserve taxonomy term id.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $land_rent_rates_nid = $query->condition('type', 'land_rent_rates')
      ->condition('field_central_forest_reserve.target_id', $cfr)
      ->execute();
    $land_rent_rates_table = [];
    if (!empty($land_rent_rates_nid)) {
      $nids = array_values($land_rent_rates_nid);
      $land_rent_rates = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      foreach ($land_rent_rates as $land_rent_rate) {
        // Make sure overall area is not zero.
        // land_rent_rate x overall_area_allocated.
        $rent_rate = $land_rent_rate->get('field_rate')->value;
        $for_year = $land_rent_rate->get('field_rate_year')->value;
        if ($overall_area_allocated) {
          $land_rent = $rent_rate * $overall_area_allocated;
          if (array_key_exists($for_year, $land_rent_rates_table)) {
            $land_rent_rates_table[$for_year] += $land_rent;
          }
          else {
            $land_rent_rates_table[$for_year] = $land_rent;
          }
        }
        else {
          if (array_key_exists($for_year, $land_rent_rates_table)) {
            $land_rent_rates_table[$for_year] += $land_rent;
          }
          else {
            $land_rent_rates_table[$for_year] = $land_rent;
          }
        }
      }
    }
    return $land_rent_rates_table;
  }

  /**
   * Get sub-total from charges.
   *
   * @param string $offer_licence_id
   *   The area ID.
   *
   * @return array
   *   The other charges total year wise.
   */
  public function getOtherTotalYearWise($offer_licence_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $charge_nids = $query->condition('type', 'charge')
      ->condition('field_areas_id.target_id', $offer_licence_id)
      ->execute();
    $field_amount = [];
    if (!empty($charge_nids)) {
      $nids = array_values($charge_nids);
      $charges = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($charges as $key => $charge) {
        $field_charge_date = $charge->get('field_charge_date')->value;
        if ($field_charge_date) {
          $field_charge_date = explode('-', $field_charge_date);
          $key = $field_charge_date[0];
          if (array_key_exists($key, $field_amount)) {
            $field_amount[$key] += $charge->get('field_amount')->value;
          }
          else {
            $field_amount[$key] = $charge->get('field_amount')->value;
          }
        }
      }
    }
    return $field_amount;
  }

  /**
   * Get invoice details.
   *
   * @param string $field_invoice_id
   *   The invoice id.
   *
   * @return array
   *   The result array.
   */
  public function getInvoiceDetails($field_invoice_id) {
    $invoice = $this->entityTypeManager->getStorage('node')->load($field_invoice_id);
    $field_areas_id = $invoice->get('field_areas_id')->target_id;
    $field_invoice_for = $invoice->get('field_invoice_details')->value;
    $other_charges['field_invoice_details'] = $field_invoice_for;
    $other_charges = $this->getOtherCharges($field_areas_id, $field_invoice_for);
    return $other_charges;
  }

  /**
   * Get other charges or land rent amount for specific area.
   *
   * @param string $offer_licence_id
   *   The area id for which amount has to be fetched.
   * @param string $charge_type
   *   Indicate charges type other or land rent.
   * @param bool $unformatted
   *   Weather to return formated amount.
   *
   * @return array
   *   The detail array result.
   */
  public function getOtherCharges($offer_licence_id, $charge_type, $unformatted = FALSE) {

    $area = $this->entityTypeManager->getStorage('node')->load($offer_licence_id);
    $charges_data['title'] = $area->getTitle();
    $charges_total = 0;
    $charges_data['data'] = [];

    // Charges type '1' represents other fees.
    if ($charge_type === '1') {
      $charges_data['other_fees'] = TRUE;
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $charge_nids = $query->condition('type', 'charge')
        ->condition('field_areas_id.target_id', $offer_licence_id)
        ->execute();
      if (!empty($charge_nids)) {
        $nids = array_values($charge_nids);
        $charges = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

        foreach ($charges as $charge) {
          $field_amount = $charge->get('field_amount')->value;
          $charges_total += $field_amount;
          $charges_data['data'][] = [
            'desc' => $charge->get('field_charge_description')->value,
            'date' => $charge->get('field_charge_date')->value,
            'amount' => number_format($field_amount, 0, '.', ','),
          ];
        }
      }
    }
    // Charges type '2' represents land rent.
    if ($charge_type === '2') {
      $charges_data['land_rent'] = TRUE;
      $charges_date = $this->getRentSubTotal($offer_licence_id);
      $charges_total = $charges_date['sub_total'];
    }

    if ($unformatted) {
      $charges_data['total'] = $charges_total;
    }
    else {
      $charges_data['total'] = number_format($charges_total, 0, '.', ',');
    }
    return $charges_data;
  }

  /**
   * Get offer license IDs based on farmer id.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return mixed
   *   The area ids.
   */
  public function getOfferLicenseIds($farmer_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $nids = $query->condition('type', 'offer_license')
      ->condition('field_farmer_name_ref.target_id', $farmer_id)
      ->execute();
    if (!empty($nids)) {
      $nids = array_values($nids);
      return implode(',', $nids);
    }
    return NULL;
  }

  /**
   * Get get_area_planted_un_planted_value value  based on area ID.
   *
   * @param string $offer_license_id
   *   The area ID.
   *
   * @return array
   *   The area planted unplanted count.
   */
  public function getAreaPlantedUnPlantedValue($offer_license_id) {
    // Get sub-area entity Ids based on area ID.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $nids = $query->condition('type', 'sub_area')
      ->condition('field_areas_id.target_id', $offer_license_id)
      ->execute();
    if (!empty($nids)) {
      $nids = array_values($nids);
      $field_sub_area_planted = 0;
      $sub_areas = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      foreach ($sub_areas as $area) {
        $field_sub_area_planted += $area->get('field_sub_area_planted')->value;
      }
      return $field_sub_area_planted;
    }
    return 0;
  }

  /**
   * Update view field result with get_summary_charges_data.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return array
   *   The rendered summary charges.
   */
  public function getSummaryChargesData($farmer_id) {
    $summary_charges['balance'] = [
      'overall' => 0,
      'land_rent' => 0,
      'fees' => 0,
    ];
    $summary_charges['fees'] = [
      'charges' => 0,
    ];
    $summary_charges['land_rent'] = [
      'charges' => 0,
      'late_fee' => 0,
      'due' => 0,
    ];
    $area_ids = $this->getFarmerAreaIds($farmer_id);
    if (!empty($area_ids)) {
      // Get overall oustanding fees & data.
      $this->getOverallOutstandingFees($area_ids, $summary_charges);
      // Get overall oustanding Land rent along with starting amount.
      $this->getOverallOutstandingLandRent($area_ids, $summary_charges);
    }

    $data = [
      'farmer_id' => $farmer_id,
      'summary_charges' => $summary_charges,
    ];
    $renderable = [
      '#theme' => 'tab__accounts__summary_charges_data',
      '#data' => $data,
    ];
    return $this->renderer->render($renderable);;
  }

  /**
   * Get overall outstanding fee for all area of particular farmer.
   *
   * @param string $area_ids
   *   The area ID.
   * @param string $summary_charges
   *   The summary_charges array.
   */
  public function getOverallOutstandingFees($area_ids, &$summary_charges) {
    // Calculate outstanding fees.
    $invoice_nids = $this->getInvoiceIds($area_ids);
    foreach ($invoice_nids as $invoice_id) {
      $payment = $this->invoiceHasPayment($invoice_id);
      if (empty($payment)) {
        $invoice = $this->entityTypeManager->getStorage('node')->load($invoice_id);
        $amount = $invoice->get('field_amount')->value;
        $summary_charges['fees']['charges'] += $amount;
        $summary_charges['balance']['overall'] += $amount;
        $summary_charges['balance']['fees'] += $amount;

        // Build data array.
        $data_array = [];
        $area = $invoice->get('field_areas_id')->referencedEntities()[0];
        if (!empty($area)) {
          $data_array['area'] = $area->getTitle();
        }
        $charge = $this->getChargeFromInvoice($invoice_id);
        if (!empty($charge)) {
          $data_array['date'] = $charge->get('field_charge_date')->value;
          $data_array['desc'] = $charge->get('field_charge_description')->value;
          $data_array['total_due'] = $charge->get('field_amount')->value;
          $data_array['payment_advc_no'] = $invoice->get('field_invoice_number')->value;
          $data_array['payment_advc_nid'] = $invoice->id();
        }
        $summary_charges['fees']['data'][] = $data_array;
      }
    }
    krsort($summary_charges['fees']['data']);
  }

  /**
   * Get chage for particular invoice.
   *
   * @param string $invoice_id
   *   The invoice ID.
   *
   * @return object
   *   The charge object.
   */
  public function getChargeFromInvoice($invoice_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $charge_nids = $query->condition('type', 'charge')
      ->condition('field_payment_advice.target_id', $invoice_id)
      ->execute();
    $charge_nid = reset($charge_nids);
    if (!empty($charge_nid)) {
      $charge = $this->entityTypeManager->getStorage('node')->load($charge_nid);
      return $charge;
    }
    return [];
  }

  /**
   * Get annual charges for particular invoice.
   *
   * @param string $invoice_id
   *   The invoice ID.
   *
   * @return object
   *   The charge object.
   */
  public function getAnnualChargeFromInvoice($invoice_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $annual_charges_nids = $query->condition('type', 'annual_charges')
      ->condition('field_payment_advice.target_id', $invoice_id)
      ->execute();
    $annual_charges_nid = reset($annual_charges_nids);
    if (!empty($annual_charges_nid)) {
      $annual_charges = $this->entityTypeManager->getStorage('node')->load($annual_charges_nid);
      return $annual_charges;
    }
    return [];
  }

  /**
   * Get overall outstanding land rent for all area of particular farmer.
   *
   * @param string $area_ids
   *   The area ID.
   * @param string $summary_charges
   *   The summary_charges array.
   */
  public function getOverallOutstandingLandRent($area_ids, &$summary_charges) {
    // Calculate outstanding land rent.
    $invoice_nids = $this->getInvoiceIds($area_ids, '2');
    foreach ($invoice_nids as $invoice_id) {
      $payment = $this->invoiceHasPayment($invoice_id);
      if (empty($payment)) {
        $invoice = $this->entityTypeManager->getStorage('node')->load($invoice_id);
        $amount = $invoice->get('field_amount')->value;
        $summary_charges['land_rent']['charges'] += $amount;
        $summary_charges['balance']['overall'] += $amount;
        $summary_charges['balance']['land_rent'] += $amount;

        // Calculate land rent late_fee/due.
        $annual_charges = $this->getAnnualChargeFromInvoice($invoice_id);
        $data_array = [];
        // Build data array.
        if ($annual_charges) {
          $charge_type = $annual_charges->get('field_annual_charges_type')->value;
          if ($charge_type == '1') {
            $summary_charges['land_rent']['due'] += $amount;
            $data_array['due'] = $amount;
          }
          else {
            $summary_charges['land_rent']['late_fee'] += $amount;
            $data_array['late_fee_due'] = $amount;
            $data_array['previous_arrears'] = $annual_charges->get('field_arrears')->value;
          }
          $year = $annual_charges->get('field_rate_year')->value;
          $data_array['date'] = '01-01-' . $year;
          $data_array['total_due'] = $amount;
          $data_array['payment_advc_no'] = $invoice->get('field_invoice_number')->value;
          $data_array['payment_advc_nid'] = $invoice->id();
        }
        $area = $invoice->get('field_areas_id')->referencedEntities()[0];
        if (!empty($area)) {
          $data_array['area'] = $area->getTitle();
        }
        $summary_charges['land_rent']['data'][] = $data_array;
      }
    }
    krsort($summary_charges['land_rent']['data']);
    // Calculate outstanding starting amount as part of land rent.
    $invoice_nids = $this->getInvoiceIds($area_ids, '3');
    foreach ($invoice_nids as $invoice_id) {
      $payment = $this->invoiceHasPayment($invoice_id);
      if (empty($payment)) {
        $invoice = $this->entityTypeManager->getStorage('node')->load($invoice_id);
        $amount = $invoice->get('field_amount')->value;
        $summary_charges['land_rent']['charges'] += $amount;
        $summary_charges['balance']['overall'] += $amount;
        $summary_charges['balance']['land_rent'] += $amount;
      }
    }
  }

  /**
   * Get all Invoice of particular type.
   *
   * @param array $area_ids
   *   The area ids.
   * @param string $type
   *   - 1|Fees
   *   - 2|Land rent
   *   - 3|Starting amount
   *   The type of invoice field_invoice_details.
   *
   * @return array
   *   The array of area ids.
   */
  public function getInvoiceIds($area_ids, $type = '1') {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $invoice_nids = $query->condition('type', 'invoice')
      ->condition('field_areas_id.target_id', $area_ids, 'IN')
      ->condition('field_invoice_details', $type)
      ->condition('status', '1')
      ->execute();
    $invoice_nids = array_values($invoice_nids);
    return $invoice_nids ?? [];
  }

  /**
   * Get all area of particular farmer.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return array
   *   The array of area ids.
   */
  public function getFarmerAreaIds($farmer_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $area_nids = $query->condition('type', 'offer_license')
      ->condition('field_farmer_name_ref.target_id', $farmer_id)
      ->execute();
    $area_nids = array_values($area_nids);
    return $area_nids ?? [];
  }

  /**
   * Update view field result with get_payments_data.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return array
   *   The rendered payment data.
   */
  public function getPaymentsData($farmer_id) {
    $payments = [
      'fees' => [
        'balance' => 0,
        'payments' => 0,
        'charges' => 0,
        'data' => [],
      ],
      'land_rent' => [
        'balance' => 0,
        'payments' => 0,
        'charges' => 0,
        'data' => [],
      ],
      'due_starting_amount' => 0,
    ];
    $area_ids = $this->getFarmerAreaIds($farmer_id);
    if (!empty($area_ids)) {
      $this->getAllFeesData($area_ids, $payments);
      // Get all Land rent along with starting amount.
      $this->getAllLandRentData($area_ids, $payments);
    }
    // dump($payments);die;
    $renderable = [
      '#theme' => 'tab__accounts__payments_data',
      '#data' => ['payments' => $payments],
    ];
    return $this->renderer->render($renderable);;
  }

  /**
   * Get fee for all area of particular farmer.
   *
   * @param string $area_ids
   *   The area ID.
   * @param string $payment
   *   The $payment array.
   */
  public function getAllFeesData($area_ids, &$payment) {
    $invoice_nids = $this->getInvoiceIds($area_ids);
    foreach ($invoice_nids as $invoice_id) {
      $payment_nids = $this->invoiceHasPayment($invoice_id);
      $invoice = $this->entityTypeManager->getStorage('node')->load($invoice_id);
      $amount = $invoice->get('field_amount')->value;
      $payment['fees']['charges'] += $amount;
      $data_array = [];
      if (!empty($payment_nids)) {
        $payment_nid = reset($payment_nids);
        $payment_node = $this->entityTypeManager->getStorage('node')->load($payment_nid);
        $payment['fees']['payments'] += $amount;
        $data_array['payment_date'] = $payment_node->get('field_date_paid')->value;
        $data_array['payment_receipt_number'] = $payment_node->get('field_receipt_number')->value;
      }
      else {
        $payment['fees']['balance'] += $amount;
      }
      $area = $invoice->get('field_areas_id')->referencedEntities()[0];
      if (!empty($area)) {
        $data_array['area'] = $area->getTitle();
      }
      $charge = $this->getChargeFromInvoice($invoice_id);
      if (!empty($charge)) {
        $data_array['date'] = $charge->get('field_charge_date')->value;
        $data_array['desc'] = $charge->get('field_charge_description')->value;
        $data_array['amount'] = $charge->get('field_amount')->value;
        $data_array['payment_advc_no'] = $invoice->get('field_invoice_number')->value;
        $data_array['payment_advc_nid'] = $invoice->id();
      }
      $payment['fees']['data'][] = $data_array;
    }
    krsort($payment['fees']['data']);
  }

  /**
   * Get land rent for all area of particular farmer.
   *
   * @param string $area_ids
   *   The area ID.
   * @param string $payments
   *   The $payments array.
   */
  public function getAllLandRentData($area_ids, &$payment) {
    $invoice_nids = $this->getInvoiceIds($area_ids, '2');
    foreach ($invoice_nids as $invoice_id) {
      $payment_nids = $this->invoiceHasPayment($invoice_id);
      $invoice = $this->entityTypeManager->getStorage('node')->load($invoice_id);
      $amount = $invoice->get('field_amount')->value;
      $payment['land_rent']['charges'] += $amount;
      $data_array = [];
      if (!empty($payment_nids)) {
        $payment_nid = reset($payment_nids);
        $payment_node = $this->entityTypeManager->getStorage('node')->load($payment_nid);
        $payment['land_rent']['payments'] += $amount;
        $data_array['payment_date'] = $payment_node->get('field_date_paid')->value;
        $data_array['payment_receipt_number'] = $payment_node->get('field_receipt_number')->value;
        $data_array['payment_nid'] = $payment_nid;
      }
      else {
        $payment['land_rent']['balance'] += $amount;
      }
      // Calculate land rent late_fee/due.
      $annual_charges = $this->getAnnualChargeFromInvoice($invoice_id);
      if ($annual_charges) {
        $charge_type = $annual_charges->get('field_annual_charges_type')->value;
        if ($charge_type == '1') {
          $data_array['due'] = $amount;
        }
        else {
          $data_array['late_fee_due'] = $amount;
          $data_array['previous_arrears'] = $annual_charges->get('field_arrears')->value;
        }
        $year = $annual_charges->get('field_rate_year')->value;
        $data_array['date'] = '01-01-' . $year;
        $data_array['total_due'] = $amount;
        $data_array['payment_advc_no'] = $invoice->get('field_invoice_number')->value;
        $data_array['payment_advc_nid'] = $invoice->id();
      }
      $area = $invoice->get('field_areas_id')->referencedEntities()[0];
      if (!empty($area)) {
        $data_array['area'] = $area->getTitle();
      }
      $payment['land_rent']['data'][] = $data_array;
    }
    krsort($payment['land_rent']['data']);

    // Starting amount as part of land rent.
    $sm_invoice_nids = $this->getInvoiceIds($area_ids, '3');
    $sm_data_array = [];
    foreach ($sm_invoice_nids as $invoice_id) {
      $invoice = $this->entityTypeManager->getStorage('node')->load($invoice_id);
      $amount = $invoice->get('field_amount')->value;
      $sm_data_array['date'] = 'Starting amount';
      $sm_data_array['due'] = $amount;
      $sm_data_array['late_fee_due'] = 0;
      $sm_data_array['previous_arrears'] = 0;
      $sm_data_array['total_due'] = $amount;
      $sm_data_array['payment_advc_no'] = $invoice->get('field_invoice_number')->value;
      $sm_data_array['payment_advc_nid'] = $invoice->id();
      $area = $invoice->get('field_areas_id')->referencedEntities()[0];
      if (!empty($area)) {
        $sm_data_array['area'] = $area->getTitle();
      }
      $payment_nids = $this->invoiceHasPayment($invoice_id);
      if (!empty($payment_nids)) {
        $payment_nid = reset($payment_nids);
        $payment_node = $this->entityTypeManager->getStorage('node')->load($payment_nid);
        $sm_data_array['payment_date'] = $payment_node->get('field_date_paid')->value;
        $sm_data_array['payment_receipt_number'] = $payment_node->get('field_receipt_number')->value;
        $sm_data_array['payment_nid'] = $payment_nid;
      }
      else {
        $payment['due_starting_amount'] += $amount;
        $sm_data_array['payment_date'] = NULL;
        $sm_data_array['payment_receipt_number'] = NULL;
        $sm_data_array['payment_nid'] = NULL;
      }
      $payment['land_rent']['data'][] = $sm_data_array;
    }

  }

}
