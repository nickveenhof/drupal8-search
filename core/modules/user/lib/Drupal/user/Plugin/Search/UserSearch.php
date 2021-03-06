<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Search\UserSearch.
 */

namespace Drupal\user\Plugin\Search;


use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\Annotation\SearchPlugin;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchPlugin(
 *   id = "user_search",
 *   title = "Users",
 *   path = "user",
 *   module = "user"
 * )
 */
class UserSearch extends SearchPluginBase {
  protected $database;
  protected $entityManager;
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $container->get('database'),
      $container->get('plugin.manager.entity'),
      $container->get('module_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public function __construct(Connection $database, EntityManager $entity_manager, ModuleHandlerInterface $module_handler, array $configuration, $plugin_id, array $plugin_definition) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    $keys = $this->keywords;
    $find = array();
    // Replace wildcards with MySQL/PostgreSQL wildcards.
    $keys = preg_replace('!\*+!', '%', $keys);
    $query = $this->database
      ->select('users')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('users', array('uid'));
    if (user_access('administer users')) {
      // Administrators can also search in the otherwise private email field, and
      // they don't need to be restricted to only active users.
      $query->fields('users', array('mail'));
      $query->condition(db_or()->
        condition('name', '%' . db_like($keys) . '%', 'LIKE')->
        condition('mail', '%' . db_like($keys) . '%', 'LIKE'));
    }
    else {
      // Regular users can only search via usernames, and we do not show them
      // blocked accounts.
      $query->condition('name', '%' . db_like($keys) . '%', 'LIKE')
        ->condition('status', 1);
    }
    $uids = $query
      ->limit(15)
      ->execute()
      ->fetchCol();
    $accounts = $this->entityManager->getStorageController('user')->load($uids);

    foreach ($accounts as $account_ng) {
      $account = $account_ng->getBCEntity();
      $result = array(
        'title' => user_format_name($account),
        'link' => url('user/' . $account->uid, array('absolute' => TRUE)),
      );
      if (user_access('administer users')) {
        $result['title'] .= ' (' . $account->mail . ')';
      }
      $results[] = $result;
    }

    return $results;
  }
}