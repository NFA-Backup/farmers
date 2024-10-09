<?php

namespace Drupal\nfafmis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nfafmis\Services\FarmerServices;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Farmer Accounts tab.
 */
class FarmerAccountsPageController extends ControllerBase {

  /**
   * The farmer services.
   *
   * @var \Drupal\nfafmis\Services\FarmerServices
   */
  protected $farmerServices;

  public function __construct(FarmerServices $farmerIdService) {
    $this->farmerServices = $farmerIdService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nfafmis_service.farmer')
    );
  }

  /**
   * Returns a render array for the custom page.
   */
  public function content() {
    $farmer_id = $this->farmerServices->getFarmerIdFromTitle();

    // Load the first view by ID and display ID.
    $view1 = Views::getView('accounts_tab');
    if (is_object($view1)) {
      $view1->setDisplay('block_1');
      $view1->setArguments([$farmer_id]);
      $view1->preExecute();
      $view1->execute();
    }
    $view1_render = is_object($view1) ? $view1->render() : [];

    // Load the second view by ID and display ID.
    $view2 = Views::getView('accounts_tab_payments_report');
    if (is_object($view2)) {
      $view2->setDisplay('payments_report');
      $view2->setArguments([$farmer_id]);
      $view2->preExecute();
      $view2->execute();
      $view2_render = $view2->render();
    }
    $view2_render = is_object($view2) ? $view2->render() : [];

    return [
      '#type' => 'container',
      '#attributes' => ['class' => 'account-tab-view'],
      'header' => [
        '#type' => 'markup',
        '#markup' => '<div class="view-header" id="account-sub-tabs">
<h5 class="title">Land Allocations</h5>
</div>',
      ],
      'view1' => $view1_render,
      'view2' => $view2_render,
    ];
  }

}
