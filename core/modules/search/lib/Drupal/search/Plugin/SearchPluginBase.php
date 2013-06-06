<?php
/**
 * @file
 * Definition of Drupal\search\Plugin\SearchPluginBase
 */

use Drupal\Component\Plugin\PluginBase;

namespace Drupal\search\Plugin;

/**
 * Base class for plugins wishing to support search.
 */
abstract class SearchPluginBase extends PluginBase implements SearchInterface {

  /**
   * The keywords to use in a search.
   *
   * @var string
   */
  protected $keywords;

  /**
   * Array of parameters from the query string from the request.
   *
   * @var array
   */
  protected $searchParams;

  /**
   * Array of attributies - usually from the request object.
   *
   * @var array
   */
  protected $searchAttributes;

  /**
   * Called by the ContainerFactory in the plugin manager to create an instance.
   * 
   * @param \Drupal\search\Plugin\ContainerInterface $container
   * @param array $configuration
   * @param type $plugin_id
   * @param array $plugin_definition
   * @return new instance of the plugin
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    // Default implemntation doesn't use any dependencies from the container,
    // but most plugins will want a database connection, entity manager,
    // or other dependencies.
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function setSearch($keywords, array $params, array $attributes) {
    $this->keywords = (string) $keywords;
    $this->searchParams = $params;
    $this->searchAttributes = $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchKeywords() {
    return $this->keywords;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchParams() {
    return $this->searchParams;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchAttributes() {
    return $this->searchAttributes;
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    // Default implmnetation suitable for plugins that only use keywords.
    return !empty($this->keywords);
  }

  /**
   * {@inheritdoc}
   */
  public function buildResult() {
    $results = $this->execute();
    return array(
      '#theme' => 'search_results',
      '#results' => $results,
      '#module' => $this->pluginDefinition['module'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    // Empty default implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function resetIndex() {
    // Empty default implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    // No-op default implementation
    return array('remaining' => 0, 'total' => 0);
  }

  /**
   * {@inheritdoc}
   */
  public function addToAdminForm(array &$form, array &$form_state) {
    // Empty default implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitAdminForm(array &$form, array &$form_state) {
    // Empty default implementation.
  }
}
