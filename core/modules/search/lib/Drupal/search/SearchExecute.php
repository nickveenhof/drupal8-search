<?php

namespace Drupal\search;

class SearchExecute implements SearchExecuteInterface {
  public function getConditions();
  public function setConditions();
  public function execute();
}