<?php

/**
 * @file
 * Definition of Drupal\nfafmis\Plugin\views\filter\NfaUserManagementUnit.
 */

namespace Drupal\nfafmis\Plugin\views\filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Filters the current user's assigned management unit.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("nfafmis_user_management_unit")
 */
class NfaUserManagementUnit extends BooleanOperator implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a NfaUserManagementUnit object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    $this->currentUser = $current_user;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!empty($this->value)) {
      // If the user does not have permission to access all content in their
      // assigned management unit, limit them to their assigned range.
      if (!$this->currentUser->hasPermission('access all management unit content') && !$this->currentUser->hasPermission('edi all management unit content')) {
        $account = \Drupal::entityTypeManager()
          ->getStorage('user')
          ->load($this->currentUser->id());
        $range = $account->management_unit->entity;

        if ($range) {
          // Add a join to the management unit data table.
          $definition = [
            'table' => 'taxonomy_term__management_unit',
            'type' => 'INNER',
            'field' => 'entity_id',
            'left_table' => 'taxonomy_term_field_data',
            'left_field' => 'tid',
          ];

          $join = \Drupal::service('plugin.manager.views.join')
            ->createInstance('standard', $definition);
          $this->query->addRelationship('taxonomy_term__management_unit', $join, 'person');

          // Limit terms to those that are in the user's management unit.
          $this->query->addWhere('conditions', 'taxonomy_term__management_unit.management_unit_target_id ', $range->id(), '=');
        }
      }
    }
  }

}
