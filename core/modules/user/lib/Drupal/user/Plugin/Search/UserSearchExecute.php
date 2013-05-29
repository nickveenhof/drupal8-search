<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Search\UserSearchExecute.
 */

namespace Drupal\user\Plugin\Search;

use Drupal\search\SearchExecuteInterface;
use Drupal\search\Annotation\SearchExecutePlugin;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchExecutePlugin(
 *   id = "user_search_execute",
 *   title = "Users",
 *   path = "user",
 *   module = "user",
 *   context = {
 *     "plugin.manager.entity" = {
 *       "class" = "\Drupal\Core\Entity\EntityManager"
 *     },
 *     "database" = {
 *       "class" = "\Drupal\Core\Database\Connection"
 *     },
 *     "module_handler" = {
 *       "class" = "\Drupal\Core\Extension\ModuleHandlerInterface"
 *     }
 *   }
 * )
 */
class UserSearchExecute extends ContextAwarePluginBase implements SearchExecuteInterface {


  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    if (empty($configuration['context']['plugin.manager.entity'])) {
      $configuration['context']['plugin.manager.entity'] = $container->get('plugin.manager.entity');
    }
    if (empty($configuration['context']['database'])) {
      $configuration['context']['database'] = $container->get('database');
    }
    if (empty($configuration['context']['module_handler'])) {
      $configuration['context']['module_handler'] = $container->get('module_handler');
    }
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    return !empty($this->configuration['keywords']);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    $keys = $this->configuration['keywords'];
    $find = array();
    // Replace wildcards with MySQL/PostgreSQL wildcards.
    $keys = preg_replace('!\*+!', '%', $keys);
    $query = db_select('users')
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
    $accounts = user_load_multiple($uids);

    foreach ($accounts as $account) {
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