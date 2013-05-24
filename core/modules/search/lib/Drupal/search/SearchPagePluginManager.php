<?php

/**
 * Contains \Drupal\search\SearchPagePluginManager.
 */

namespace Drupal\search;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * SearchPage plugin manager.
 */
class SearchPagePluginManager extends PluginManagerBase {

  /**
   * Constructs a ArchiverManager object.
   *
   * @param array $namespaces
   *   An array of paths keyed by its corresponding namespaces.
   */
  public function __construct(array $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('Search', $namespaces);
    $this->discovery = new AlterDecorator($this->discovery, 'search_info');
    $this->discovery = new CacheDecorator($this->discovery, 'search_info');
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // Normalize the data
    $configuration + array(
      'keywords' => '',
      'query_parameters' => array(),
      'request_attributes' => array(),
    );
    return new $plugin_class($configuration['keywords'], $configuration['query_parameters'], $configuration['request_attributes']);
  }

  /**
   * Implements \Drupal\Core\PluginManagerInterface::getInstance().
   *
   * Finds an instance based on the module that owns the plugin
   */
  public function getInstance(array $options = array()) {
    $module = $options['module'];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if ($definition['module'] == $module && !empty($options['configuration'])) {
        // Return the instance of the searchExecutor where the annotation
        // has mentioned it belongs to a certain module. Eg.: node
        return $this->createInstance($plugin_id, $options['configuration']);
      }
    }
  }
}