<?php

/**
 * @file
 * Contains \Drupal\search\SearchPluginManager.
 */

namespace Drupal\search;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SearchExecute plugin manager.
 */
class SearchPluginManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param ContainerInterface $container A ContainerInterface instance.
   */
  public function __construct(\Traversable $namespaces) {
    $annotation_namespaces = array('Drupal\search\Annotation' => $namespaces['Drupal\search']);
    $this->discovery = new AnnotatedClassDiscovery('Search', $namespaces, $annotation_namespaces, 'Drupal\search\Annotation\SearchPlugin');
    $this->discovery = new AlterDecorator($this->discovery, 'search_info');
    $this->discovery = new CacheDecorator($this->discovery, 'search');

    // By using ContainerFactory, we call a static create() method on each
    // plugin.
    $this->factory = new ContainerFactory($this->discovery);
  }
}
