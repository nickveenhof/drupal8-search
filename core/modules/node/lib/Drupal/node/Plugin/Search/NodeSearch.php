<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Search\NodeSearch.
 */

namespace Drupal\node\Plugin\Search;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\Annotation\SearchPlugin;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchPlugin(
 *   id = "node_search",
 *   title = "Content",
 *   path = "node",
 *   module = "node"
 * )
 */
class NodeSearch extends SearchPluginBase {
  protected $database;
  protected $entity_manager;
  protected $module_handler;
  protected $keywords;

  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    $database = $container->get('database');
    $entity_manager = $container->get('plugin.manager.entity');
    $module_handler = $container->get('module_handler');
    return new static($database, $entity_manager, $module_handler, $configuration, $plugin_id, $plugin_definition);
  }

  public function __construct(Connection $database, EntityManager $entity_manager, ModuleHandlerInterface $module_handler, array $configuration, $plugin_id, array $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->database = $database;
    $this->entity_manager = $entity_manager;
    $this->module_handler = $module_handler;
    $this->keywords = (string) $this->configuration['keywords'];
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    return !empty($this->keywords);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    $keys = $this->keywords;
    // Build matching conditions
    $query = $this->database
      ->select('search_index', 'i', array('target' => 'slave'))
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('node_field_data', 'n', 'n.nid = i.sid');
    $query
      ->condition('n.status', 1)
      ->addTag('node_access')
      ->searchExpression($keys, 'node');

    // Insert special keywords.
    $query->setOption('type', 'n.type');
    $query->setOption('langcode', 'n.langcode');
    if ($query->setOption('term', 'ti.tid')) {
      $query->join('taxonomy_index', 'ti', 'n.nid = ti.nid');
    }
    // Only continue if the first pass query matches.
    if (!$query->executeFirstPass()) {
      return array();
    }

    // Add the ranking expressions.
    $this->addNodeRankings($query);

    // Load results.
    $find = $query
      // Add the language code of the indexed item to the result of the query,
      // since the node will be rendered using the respective language.
      ->fields('i', array('langcode'))
      ->limit(10)
      ->execute();

    $entity_manger = $this->entity_manager;
    $node_storage = $entity_manger->getStorageController('node');
    $node_render = $entity_manger->getRenderController('node');
    $module_handler = $this->module_handler;

    foreach ($find as $item) {
      // Render the node.
      $entities = $node_storage->load(array($item->sid));
      $node = $entities[$item->sid];
      $build = $node_render->view($node, 'search_result', $item->langcode);
      unset($build['#theme']);
      $node->rendered = drupal_render($build);

      // Fetch comments for snippet.
      $node->rendered .= ' ' . $module_handler->invoke('comment', 'node_update_index', array($node, $item->langcode));

      $extra = $module_handler->invokeAll('node_search_result', array($node, $item->langcode));

      $language = $module_handler->invoke('language', 'load', array($item->langcode));
      $uri = $node->uri();
      $results[] = array(
        'link' => url($uri['path'], array_merge($uri['options'], array('absolute' => TRUE, 'language' => $language))),
        'type' => check_plain($module_handler->invoke('node', 'get_type_label', array($node))),
        'title' => $node->label($item->langcode),
        'user' => theme('username', array('account' => $node)),
        'date' => $node->changed,
        'node' => $node,
        'extra' => $extra,
        'score' => $item->calculated_score,
        'snippet' => search_excerpt($keys, $node->rendered, $item->langcode),
        'langcode' => $node->langcode,
      );
    }
    return $results;
  }

  /**
   * Gathers the rankings from the the hook_ranking() implementations.
   *
   * @param $query
   *   A query object that has been extended with the Search DB Extender.
   */
  protected function addNodeRankings(SelectExtender $query) {
    if ($ranking = $this->module_handler->invokeAll('ranking')) {
      $tables = &$query->getTables();
      foreach ($ranking as $rank => $values) {
        if ($node_rank = variable_get('node_rank_' . $rank, 0)) {
          // If the table defined in the ranking isn't already joined, then add it.
          if (isset($values['join']) && !isset($tables[$values['join']['alias']])) {
            $query->addJoin($values['join']['type'], $values['join']['table'], $values['join']['alias'], $values['join']['on']);
          }
          $arguments = isset($values['arguments']) ? $values['arguments'] : array();
          $query->addScore($values['score'], $arguments, $node_rank);
        }
      }
    }
  }

}
