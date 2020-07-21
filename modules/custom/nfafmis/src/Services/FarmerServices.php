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
  public function getLandRentAndFeesData($farmer_id, $offer_licence_id) {
    $rent_charges = $this->getRentSubTotal($offer_licence_id);
    $starting_amount = $this->getStartingAmountData($offer_licence_id);
    $rent_sub_total = isset($rent_charges['sub_total']) ? $rent_charges['sub_total'] : 0;
    $rent_charges_data = isset($rent_charges['data']) ? $rent_charges['data'] : [];
    $other_subtotal = $this->getChargesSubTotal($offer_licence_id);

    // Get fees data.
    $fees_data = $this->getFeesData($offer_licence_id);

    // Get land rent data.
    $land_rent_data = $this->getLandRentData($offer_licence_id);

    $land_rent_starting_amount = 0;
    $other_charges_starting_amount = 0;

    // Calculate starting amount for land rent.
    if (isset($starting_amount['data']['land_rent']['amount'])) {
      $land_rent_starting_amount = $starting_amount['data']['land_rent']['amount'];
      $rent_sub_total += $land_rent_starting_amount;
      $land_rent_starting_amount_format = number_format($land_rent_starting_amount, 0, '.', ',');
      $starting_amount['data']['land_rent']['amount'] = $land_rent_starting_amount_format;
    }

    // Calculate starting amount for other charges amount.
    // @INFO: this is useless now can be remove
    if (isset($starting_amount['data']['other_charges']['amount'])) {
      $other_charges_starting_amount = $starting_amount['data']['other_charges']['amount'];
      $other_subtotal += $other_charges_starting_amount;
      $other_charges_starting_amount_format = number_format($other_charges_starting_amount, 0, '.', ',');
      $starting_amount['data']['other_charges']['amount'] = $other_charges_starting_amount_format;
    }

    $total = $rent_sub_total + $other_subtotal;
    $data = [
      'total' => number_format($total, 0, '.', ','),
      'rent_sub_total' => number_format($rent_sub_total, 0, '.', ','),
      'other_sub_total' => number_format($other_subtotal, 0, '.', ','),
      'rent_charges' => $rent_charges_data,
      'starting_amount' => isset($starting_amount['data']) ? $starting_amount['data'] : [],
      'farmer_id' => $farmer_id,
      'offer_licence_id' => $offer_licence_id,
      'fees' => $fees_data,
      'land_rent' => $land_rent_data,
    ];
    $renderable = [
      '#theme' => 'tab__accounts__land_rent_fees_data',
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
      ->condition('field_starting_amount_type.value', 'land_rent')
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
   * Get Starting amound data for both land rent and other charges.
   */
  protected function getStartingAmountData($offer_licence_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $starting_amount_nids = $query->condition('type', 'starting_amount')
      ->condition('field_areas_id.target_id', $offer_licence_id)
      ->execute();
    $starting_amount_data = [];
    if (!empty($starting_amount_nids)) {
      $starting_amounts = $this->entityTypeManager->getStorage('node')->loadMultiple($starting_amount_nids);
      foreach ($starting_amounts as $starting_amount) {
        $sa_type = $starting_amount->get('field_starting_amount_type')->value;
        $starting_amount_data['data'][$sa_type]['nid'] = $starting_amount->get('nid')->value;
        $starting_amount_data['data'][$sa_type]['amount'] = $starting_amount->get('field_amount')->value;
      }
    }
    return $starting_amount_data;
  }

  /**
   * Get rent-total from annual charges.
   *
   * @param string $offer_licence_id
   *   The area ID.
   *
   * @return array
   *   The rent sub total.
   */
  protected function getRentSubTotal($offer_licence_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $annual_charges_nids = $query->condition('type', 'annual_charges')
      ->condition('field_licence_id_ref.target_id', $offer_licence_id)
      ->execute();
    $annual_charges_table = [];
    $annual_charges_table['sub_total'] = 0;
    if (!empty($annual_charges_nids)) {
      $annual_charges = $this->entityTypeManager->getStorage('node')->loadMultiple($annual_charges_nids);
      foreach ($annual_charges as $key => $annual_charge) {
        $field_annual_charges = $annual_charge->get('field_annual_charges')->value;
        $annual_charges_table['sub_total'] += $field_annual_charges;
        $annual_charges_table['data'][$key]['field_rate'] = number_format($field_annual_charges, 0, '.', ',');
        $annual_charges_table['data'][$key]['field_rate_year'] = $annual_charge->get('field_rate_year')->value;
      }
    }
    return $annual_charges_table;
  }

  /**
   * Calculate annual charges for area.
   *
   * @param string $cfr
   *   The central forest reseve ID.
   * @param string $overall_area_allocated
   *   The overall area alocated for the area.
   * @param string $for_year
   *   Annual charges for the year.
   *
   * @return array
   *   The rent sub total.
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
   * Get sub-total from charges.
   *
   * @param string $offer_licence_id
   *   The area ID.
   *
   * @return array
   *   The charges sub total.
   */
  protected function getChargesSubTotal($offer_licence_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $charge_nids = $query->condition('type', 'charge')
      ->condition('field_areas_id.target_id', $offer_licence_id)
      ->execute();
    $field_amount = 0;
    if (!empty($charge_nids)) {
      $nids = array_values($charge_nids);
      $charges = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($charges as $charge) {
        $field_amount += $charge->get('field_amount')->value;
      }
    }
    return $field_amount;
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
   * Get total rent charges of all area for farmer year wise.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return array
   *   The rent total year wise.
   */
  public function getRentTotalYearWise($farmer_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $offer_license_nids = $query->condition('type', 'offer_license')
      ->condition('field_farmer_name_ref.target_id', $farmer_id)
      ->execute();

    // Load each areas and calculate its changes for each year.
    foreach ($offer_license_nids as $offer_licence_id) {
      $area = $this->entityTypeManager->getStorage('node')->load($offer_licence_id);
      $field_cfr = $area->get('field_central_forest_reserve')->target_id;
      $overall_area_allocated = (int) $area->get('field_overall_area_allocated')->value;
      $rent_charges[] = $this->getTotalYearWise($field_cfr, $overall_area_allocated);
    }
    $total_rent_charges = [];
    $total_rent_charges['sub_total'] = 0;
    foreach ($rent_charges as $rent_charge) {
      foreach ($rent_charge as $key => $value) {
        if (array_key_exists($key, $total_rent_charges)) {
          $total_rent_charges[$key] += $value;
        }
        else {
          $total_rent_charges[$key] = $value;
        }
      }
    }
    // Add sub total charges.
    if (!empty($total_rent_charges)) {
      foreach ($total_rent_charges as $key => $value) {
        $total_rent_charges['sub_total'] += $value;
        $total_rent_charges[$key] = number_format($value, 0, '.', ',');
      }
    }
    return $total_rent_charges;
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
   * Get total other charges of all area for farmer year wise.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return array
   *   The other charges year wise.
   */
  public function getOtherChargesYearWise($farmer_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $offer_license_nids = $query->condition('type', 'offer_license')
      ->condition('field_farmer_name_ref.target_id', $farmer_id)
      ->execute();

    // Load each areas and calculate its changes for each year.
    foreach ($offer_license_nids as $offer_licence_id) {
      $other_amounts[] = $this->getOtherTotalYearWise($offer_licence_id);
    }
    // Add sub total charges.
    $other_amounts_by_year = [];
    $other_amounts_by_year['sub_total'] = 0;
    if (!empty($other_amounts)) {
      foreach ($other_amounts as $other_amount) {
        foreach ($other_amount as $key => $value) {
          if (array_key_exists($key, $other_amounts_by_year)) {
            $other_amounts_by_year[$key] += $value;
          }
          else {
            $other_amounts_by_year[$key] = $value;
          }
        }
      }
    }
    foreach ($other_amounts_by_year as $key => $value) {
      $other_amounts_by_year['sub_total'] += $value;
      $other_amounts_by_year[$key] = number_format($value, 0, '.', ',');
    }
    return $other_amounts_by_year;
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
    // Get starting amount total for all area.
    $starting_amount_data = $this->getStartingAmountTotalData($farmer_id);
    $total_starting_amount = [];
    if (!empty($starting_amount_data)) {
      foreach ($starting_amount_data as $value) {
        if (isset($value['data'])) {
          foreach ($value['data'] as $key => $value) {
            $total_starting_amount[$key] = isset($total_starting_amount[$key]) ? $total_starting_amount[$key] : 0;
            $total_starting_amount[$key] += $value['amount'];
          }
        }
      }
    }

    $total_starting_amount_land_rent = 0;
    if (isset($total_starting_amount['land_rent'])) {
      $total_starting_amount_land_rent = $total_starting_amount['land_rent'];
    }

    $total_starting_amount_other_charges = 0;
    if (isset($total_starting_amount['other_charges'])) {
      $total_starting_amount_other_charges = $total_starting_amount['other_charges'];
    }

    // Get total rent charges of all area for farmer year wise.
    $rent_total_data = $this->getRentTotalYearWise($farmer_id);
    $rent_sub_total = 0;
    if (isset($rent_total_data['sub_total'])) {
      $rent_sub_total = $rent_total_data['sub_total'] + $total_starting_amount_land_rent;
      unset($rent_total_data['sub_total']);
    }
    // Get total other charges of all area for farmer year wise.
    $other_total_data = $this->getOtherChargesYearWise($farmer_id);
    $other_sub_total = 0;
    if (isset($other_total_data['sub_total'])) {
      $other_sub_total = $other_total_data['sub_total'] + $total_starting_amount_other_charges;
      unset($other_total_data['sub_total']);
    }

    $overall = $rent_sub_total + $other_sub_total;
    $total = $rent_sub_total + $other_sub_total;

    $data = [
      'farmer_id' => $farmer_id,
      'balance' => [
        'overall' => number_format($overall, 0, '.', ','),
        'land_rent' => number_format($rent_sub_total, 0, '.', ','),
        'other_fee' => number_format($other_sub_total, 0, '.', ','),
      ],
      'charges' => [
        'total' => number_format($total, 0, '.', ','),
        'land_rent' => number_format($rent_sub_total, 0, '.', ','),
        'other' => number_format($other_sub_total, 0, '.', ','),
      ],
      'land_rent' => [
        'data' => $rent_total_data,
        'total_starting_amount' => number_format($total_starting_amount_land_rent, 0, '.', ','),
        'sub_total' => number_format($rent_sub_total, 0, '.', ','),
      ],
      'land_rent_areares' => [
        'sub_total' => 0,
      ],
      'other_fees' => [
        'data' => $other_total_data,
        'total_starting_amount' => number_format($total_starting_amount_other_charges, 0, '.', ','),
        'sub_total' => number_format($other_sub_total, 0, '.', ','),
      ],
    ];
    $renderable = [
      '#theme' => 'tab__accounts__summary_charges_data',
      '#data' => $data,
    ];
    return $this->renderer->render($renderable);;
  }

  /**
   * Get total starting amount for all area of a farmer.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return array
   *   The other charges year wise.
   */
  public function getStartingAmountTotalData($farmer_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $offer_license_nids = $query->condition('type', 'offer_license')
      ->condition('field_farmer_name_ref.target_id', $farmer_id)
      ->execute();

    // Load each areas and calculate its changes for each year.
    foreach ($offer_license_nids as $offer_licence_id) {
      $other_amounts[] = $this->getStartingAmountData($offer_licence_id);
    }
    return $other_amounts ?? [];
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
    $payment_data = $this->getPaymentLandRentOtherFees($farmer_id);
    $other_fees = $land_rent = 0;
    foreach ($payment_data as $value) {
      $other_fees += $value['other_fees']['raw_sub_total'] ?? 0;
      $land_rent += $value['land_rent']['raw_sub_total'] ?? 0;
    }
    $overall = $other_fees + $land_rent;
    $other_fees = number_format($other_fees, 0, '.', ',');
    $land_rent = number_format($land_rent, 0, '.', ',');
    $overall = number_format($overall, 0, '.', ',');
    $data = [
      'farmer_id' => $farmer_id,
      'balance' => [
        'overall' => $overall,
        'land_rent' => $land_rent,
        'other_fee' => $other_fees,
      ],
      'payments' => [
        'total' => $overall,
        'land_rent' => $land_rent,
        'other_fee' => $other_fees,
      ],
      'payments_details' => $payment_data,
    ];
    $renderable = [
      '#theme' => 'tab__accounts__payments_data',
      '#data' => $data,
    ];
    return $this->renderer->render($renderable);;
  }

  /**
   * Get total starting amount for all area of a farmer.
   *
   * @param string $farmer_id
   *   The farmer ID.
   *
   * @return array
   *   The other charges year wise.
   */
  public function getPaymentLandRentOtherFees($farmer_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $payments_nids = $query->condition('type', 'payment')
      ->condition('field_farmer_name_ref.target_id', $farmer_id)
      ->condition('status', '1')
      ->execute();

    // Load each payment and calculate its changes for each year.
    $data = [];
    foreach ($payments_nids as $payments_id) {
      $payment = $this->entityTypeManager->getStorage('node')->load($payments_id);
      $invoice = $payment->get('field_invoice')->referencedEntities()[0];
      $area = $invoice->get('field_areas_id')->referencedEntities()[0];
      $payment_date = $payment->get('field_date_paid')->value;
      $year = explode('-', $payment_date)[0];

      // Get description as area name.
      if (!empty($area)) {
        $field_area_id = $area->get('field_area_id')->value;
      }

      // Check if invoce is there with payment.
      if (!empty($invoice)) {
        $field_invoice_details = $invoice->get('field_invoice_details')->value;
        $field_amount = $invoice->get('field_amount')->value;
        $data[$year]['raw_total'] = isset($data[$year]['raw_total']) ? $data[$year]['raw_total'] : 0;

        // Data for other fees.
        if ($field_invoice_details === '1') {
          // Initialize variables.
          $data[$year]['other_fees']['raw_sub_total'] = isset($data[$year]['other_fees']['raw_sub_total']) ? $data[$year]['other_fees']['raw_sub_total'] : 0;
          $data[$year]['other_fees']['sub_total'] = isset($data[$year]['other_fees']['sub_total']) ? $data[$year]['other_fees']['sub_total'] : 0;

          $data[$year]['raw_total'] += $field_amount;
          $data[$year]['other_fees']['data'][] = [
            'amount' => number_format($field_amount, 0, '.', ','),
            'date' => $payment_date,
            'field_invoice_number' => $invoice->get('field_invoice_number')->value,
            'field_receipt_number' => $payment->get('field_receipt_number')->value,
            'field_voucher_number' => $payment->get('field_receipt_scan')->value,
            'details' => $field_area_id ?? '',
          ];
          $data[$year]['other_fees']['raw_sub_total'] += $field_amount;
          $data[$year]['other_fees']['sub_total'] = number_format($data[$year]['other_fees']['raw_sub_total'], 0, '.', ',');;
        }
        // Data for land rent.
        if ($field_invoice_details === '2') {
          // Initialize variables.
          $data[$year]['land_rent']['raw_sub_total'] = isset($data[$year]['land_rent']['raw_sub_total']) ? $data[$year]['land_rent']['raw_sub_total'] : 0;
          $data[$year]['land_rent']['sub_total'] = isset($data[$year]['land_rent']['sub_total']) ? $data[$year]['land_rent']['sub_total'] : 0;

          $data[$year]['raw_total'] += $field_amount;
          $data[$year]['land_rent']['data'][] = [
            'amount' => number_format($field_amount, 0, '.', ','),
            'date' => $payment_date,
            'field_invoice_number' => $invoice->get('field_invoice_number')->value,
            'field_receipt_number' => $payment->get('field_receipt_number')->value,
            'field_voucher_number' => $payment->get('field_receipt_scan')->value,
            'details' => $field_area_id ?? '',
          ];
          $data[$year]['land_rent']['raw_sub_total'] += $field_amount;
          $data[$year]['land_rent']['sub_total'] = number_format($data[$year]['land_rent']['raw_sub_total'], 0, '.', ',');
        }
      }
      $data[$year]['total'] = number_format($data[$year]['raw_total'], 0, '.', ',');
    }
    return $data ?? [];
  }

}
