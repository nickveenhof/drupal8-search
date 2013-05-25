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
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $annotation_namespaces = array('Drupal\search\Annotation' => $namespaces['Drupal\search']);
    $this->discovery = new AnnotatedClassDiscovery('Search', $namespaces, $annotation_namespaces, 'Drupal\search\Annotation\SearchPagePlugin');
    $this->discovery = new AlterDecorator($this->discovery, 'search_info');
    $this->discovery = new CacheDecorator($this->discovery, 'search');

    $this->factory = new DefaultFactory($this->discovery);
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
