<?php

/**
 * @file
 * Contains \Drupal\Modules\Search\SearchExecuteInterface.
 */

namespace Drupal\search;

/**
 * Defines a common interface for all SearchExecute objects.
 */
interface SearchExecuteInterface {
  /**
   * Verifies if the given parameters are valid enough to execute a search for.
   *
   * @return boolean
   *   A true or false depending on the implementation.
   */
  public function isSearchExecutable();

  /**
   * Execute the search
   *
   * @return array $results
   *   A structured list of search results
   */
  public function execute();
}
