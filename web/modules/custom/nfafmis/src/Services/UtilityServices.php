<?php

namespace Drupal\nfafmis\Services;

use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class UtilityServices.
 */
class UtilityServices {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $requestStack;

  /**
   * Constructs a new UtilityServices object.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   */
  public function __construct(AccountProxy $current_user, RequestStack $request) {
    $this->currentUser = $current_user;
    $this->requestStack = $request;
  }

  /**
   * Get query parameters.
   *
   * @return mixed
   *   The array of Query params.
   */
  public function getQueryParams() {
    $response = $this->getReferrerAndQueryParams();
    if (!empty($response)) {
      if (isset($response['query_params'])) {
        return $response['query_params'];
      }
    }
    return NULL;
  }

  /**
   * Get referrer and query params.
   *
   * @return array
   *   The array of Query params.
   */
  public function getReferrerAndQueryParams() {
    $currentRequest = $this->requestStack->getCurrentRequest();
    $referer = $currentRequest->headers->get('referer');
    $baseUrl = $currentRequest->createFromGlobals()->getSchemeAndHttpHost();
    $alias = substr($referer, strlen($baseUrl));

    // All query params.
    $queryParams = $currentRequest->query->all();

    $refParam['query_params'] = !empty($queryParams) ? $queryParams : NULL;
    $refParam['alias'] = $alias ? $alias : NULL;

    return $refParam;
  }

  /**
   * Get the Range/Management Unit of the logged-in user.
   *
   * @return mixed
   *  Returns based on user role:
   *    - the assigned Management Unit term if the user has role Range User or
   *      Range Power User.
   *    - NULL if the user has role Range User or Range Power User but does not
   *      have an assigned Management Unit or if the user does not have role
   *      Range User or Range Power User.
   */
  public function getUserManagementUnit() {
    // If the user does not have permission to access all management units,
    // retrieve the range that the user has been assigned to.
    if (!$this->currentUser->hasPermission('access all management unit content') && !$this->currentUser->hasPermission('edit all management unit content')) {
      $account = \Drupal::entityTypeManager()->getStorage('user')->load($this->currentUser->id());
      return $account->management_unit->entity;
    }
    else
      return NULL;
  }

  /**
   * Get the Sector Unit of the logged-in user.
   *
   * @return mixed
   *  Returns based on user role:
   *    - the assigned Sector term if the user has role Sector User or Sector
   *      Power User.
   *    - NULL if the user has role Sector User or Sector Power User but does not
   *      have an assigned Sector or if the user does not have role
   *      Sector User or Sector Power User.
   */
  public function getUserSector() {
    // If the user does not have permission to access all sectors, retrieve the
    // sector that the user has been assigned to.
    if (!$this->currentUser->hasPermission('access all management unit content') && !$this->currentUser->hasPermission('edit all management unit content')) {
      $account = \Drupal::entityTypeManager()->getStorage('user')->load($this->currentUser->id());
      return $account->sector->entity;
    }
    else
      return NULL;
  }
}
