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
   * Constructs a new SearchExecute object.
   *
   * @param string $keywords
   *   The keywords to search for.
   *
   * @param array $query_parameters
   *   The query parameters in the URL for the given search. Could be used to refine
   *   the scope of the search.
   *
   * @param array $request_attributes
   *   All the attributes on the current request. Could be used to refine
   *   the scope of the search.
   */
  public function __construct($keywords, array $query_parameters = array(), array $request_attributes = array());

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
