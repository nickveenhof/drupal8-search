<?php

namespace Drupal\user\Plugin\Search;

use Drupal\search\SearchExecuteInterface;
use Drupal\search\Annotation\SearchPagePlugin;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchPagePlugin(
 *   id = "user_search_execute",
 *   title = "Users",
 *   path = "user",
 *   module = "user"
 * )
 */
class UserSearchExecute implements SearchExecuteInterface {
  protected $keywords;

  public function __construct($keywords, array $query_parameters, array $request_attributes) {
    $this->keywords = $keywords;
  }

  public function isSearchExecutable() {
    return (bool) $this->keywords;
  }

  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    $keys = $this->keywords;
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