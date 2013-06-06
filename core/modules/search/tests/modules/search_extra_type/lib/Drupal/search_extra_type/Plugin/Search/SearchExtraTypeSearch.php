<?php

/**
 * @file
 * Contains \Drupal\search_extra_type\Plugin\Search\SearchExtraTypeSearch.
 */

namespace Drupal\search_extra_type\Plugin\Search;

use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\Annotation\SearchPlugin;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchPlugin(
 *   id = "search_extra_type_search",
 *   title = "Dummy search type",
 *   path = "dummy_path",
 *   module = "search_extra_type"
 * )
 */
class SearchExtraTypeSearch extends SearchPluginBase {

  /**
   * Verifies if the given parameters are valid enough to execute a search for.
   *
   * @return boolean
   *   A true or false depending on the implementation.
   */
  public function isSearchExecutable() {
    return (bool) ($this->keywords || !empty($this->searchParams['search_conditions']));
  }

  /**
   * Execute the search
   *
   * This is a dummy search, so when search "executes", we just return a dummy
   * result containing the keywords and a list of conditions.
   *
   * @return array $results
   *   A structured list of search results
   */
  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    return array(
      array(
        'link' => url('node'),
        'type' => 'Dummy result type',
        'title' => 'Dummy title',
        'snippet' => "Dummy search snippet to display. Keywords: {$this->keywords}\n\nConditions: " . print_r($this->conditions, TRUE),
      ),
    );
  }
}
