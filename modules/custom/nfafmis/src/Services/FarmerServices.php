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
  public function getLandRentAndOtherData($farmer_id, $offer_licence_id) {
    $area = $this->entityTypeManager->getStorage('node')->load($offer_licence_id);
    $field_cfr = $area->get('field_central_forest_reserve')->target_id;
    $overall_area_allocated = (int) $area->get('field_overall_area_allocated')->value;
    $rent_charges = $this->getRentSubTotal($offer_licence_id);
    $starting_amount = $this->getStartingAmountData($offer_licence_id);;
    $rent_sub_total = isset($rent_charges['sub_total']) ? $rent_charges['sub_total'] : 0;
    $rent_charges_data = isset($rent_charges['data']) ? $rent_charges['data'] : [];
    $other_subtotal = $this->getChargesSubTotal($offer_licence_id);

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
      'starting_amount' => $starting_amount['data'],
      'farmer_id' => $farmer_id,
      'offer_licence_id' => $offer_licence_id,
    ];
    $renderable = [
      '#theme' => 'tab__accounts__land_rent_other_data',
      '#data' => $data,
    ];
    $rendered = $this->renderer->render($renderable);
    return $rendered;
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
      foreach ($starting_amounts as $key => $starting_amount) {
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
   * Get annual charges from Land rent rates.
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
  public function getAnnualCharges($cfr, $overall_area_allocated, $for_year = NULL) {
    // field_central_forest_reserve taxonomy term id.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('type', 'land_rent_rates');
    $query->condition('field_central_forest_reserve.target_id', $cfr);
    if ($for_year) {
      $query->condition('field_rate_year.value', $for_year);
    }
    $land_rent_rates_nid = $query->execute();
    $land_rent_rates_table = [];
    $land_rent_rates_table['sub_total'] = 0;
    if (!empty($land_rent_rates_nid)) {
      $nids = array_values($land_rent_rates_nid);
      $land_rent_rates = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      foreach ($land_rent_rates as $key => $land_rent_rate) {
        // Make sure overall area is not zero.
        // land_rent_rate x overall_area_allocated.
        $rent_rate = $land_rent_rate->get('field_rate')->value;
        if ($overall_area_allocated) {
          $land_rent = $rent_rate * $overall_area_allocated;
          $land_rent_rates_table['sub_total'] += $land_rent;
          if ($for_year) {
            $land_rent_rates_table['data'][$key]['field_rate'] = $land_rent;
          }
          else {
            $land_rent_rates_table['data'][$key]['field_rate'] = number_format($land_rent, 0, '.', ',');
          }
        }
        else {
          $land_rent_rates_table['sub_total'] += $rent_rate;
          if ($for_year) {
            $land_rent_rates_table['data'][$key]['field_rate'] = $land_rent;
          }
          else {
            $land_rent_rates_table['data'][$key]['field_rate'] = number_format($land_rent, 0, '.', ',');
          }
        }
        $land_rent_rates_table['data'][$key]['field_rate_year'] = $land_rent_rate->get('field_rate_year')->value;
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

      foreach ($charges as $key => $charge) {
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
      foreach ($land_rent_rates as $key => $land_rent_rate) {
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
      foreach ($sub_areas as $key => $area) {
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
        foreach ($value['data'] as $key => $value) {
          $total_starting_amount[$key] += $value['amount'];
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
    // @TODO get dynamic data.
    $data = [
      'farmer_id' => $farmer_id,
      'balance' => [
        'overall' => 0,
        'land_rent' => 0,
        'other_fee' => 0,
      ],
      'payments' => [
        'total' => 0,
        'land_rent' => 0,
        'other' => 0,
      ],
    ];
    $renderable = [
      '#theme' => 'tab__accounts__payments_data',
      '#data' => $data,
    ];
    return $this->renderer->render($renderable);;
  }

}
