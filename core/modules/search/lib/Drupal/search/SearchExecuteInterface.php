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
  public function __construct($keywords, array $query_parameters, array $request_attributes);
  public function isSearchExecutable();
  public function execute();

}