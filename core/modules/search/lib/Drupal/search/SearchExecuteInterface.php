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
  public function getConditions();
  public function setConditions();
  public function execute();

}