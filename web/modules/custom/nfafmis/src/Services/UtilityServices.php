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

}
