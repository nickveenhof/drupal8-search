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
   *   Optional query parameters for the given search. Could be used to refine
   *   the scope of the search.
   *
   * @param array $request_attributes
   *   All the attributes that belong to executed request
   *
   */
  public function __construct($keywords, array $query_parameters, array $request_attributes);

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
