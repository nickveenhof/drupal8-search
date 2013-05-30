<?php

/**
 * @file
 * Contains \Drupal\search_extra_type\Plugin\Search\SearchExtraSearchExecute.
 */

namespace Drupal\search_extra_type\Plugin\Search;

use Drupal\Component\Plugin\PluginBase;
use Drupal\search\SearchExecuteInterface;
use Drupal\search\Annotation\SearchExecutePlugin;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchExecutePlugin(
 *   id = "search_extra_type_search_execute",
 *   title = "Dummy search type",
 *   path = "dummy_path",
 *   module = "search_extra_type"
 * )
 */
class SearchExtraSearchExecute extends PluginBase implements SearchExecuteInterface {

  /**
   * The keywords to search for.
   *
   * @var string
   */
  protected $keywords;
  protected $conditions = array();

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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    if (!empty($this->configuration['query_parameters']['search_conditions'])) {
      $this->conditions['search_conditions'] = $this->configuration['query_parameters']['search_conditions'];
    }
    $this->keywords = (string) $this->configuration['keywords'];
  }

  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Verifies if the given parameters are valid enough to execute a search for.
   *
   * @return boolean
   *   A true or false depending on the implementation.
   */
  public function isSearchExecutable() {
    return (bool) ($this->keywords || $this->conditions);
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
