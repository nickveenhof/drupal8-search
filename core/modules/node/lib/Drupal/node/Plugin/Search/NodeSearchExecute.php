<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Search\NodeSearchExecute.
 */

namespace Drupal\node\Plugin\Search;

use Drupal\Component\Plugin\ContextAwarePluginBase;
use Drupal\search\SearchExecuteInterface;
use Drupal\search\Annotation\SearchExecutePlugin;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchExecutePlugin(
 *   id = "node_search_execute",
 *   title = "Content",
 *   path = "node",
 *   module = "node"
 *   context = {
 *     "plugin.manager.entity" = {
 *       "class" = "\Drupal\Core\Entity\EntityManager"
 *     }
 *     "database" = {
 *       "class" = "\Drupal\Core\Database\Connection"
 *     }
 *     "module_handler" = {
 *       "class" = "\Drupal\Core\Extension\ModuleHandlerInterface"
 *     }
 *   }
 * )
 * )
 */
class NodeSearchExecute extends ContextAwarePluginBase implements SearchExecuteInterface {

  /**
   * The keywords to search for.
   *
   * @var string
   */
  protected $keywords;

  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    if (empty($configuration['context']['plugin.manager.entity'])) {
      $configuration['context']['plugin.manager.entity'] = $container->get('plugin.manager.entity');
    }
    if (empty($configuration['context']['database'])) {
      $configuration['context']['database'] = $container->get('database');
    }
    if (empty($configuration['context']['module_handler'])) {
      $configuration['context']['module_handler'] = $container->get('module_handler');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    return (bool) $this->keywords;
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
    $query = $this->getContext('database')->select('search_index', 'i', array('target' => 'slave'))
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

    $entity_manger = $this->getContext('plugin.manager.entity');
    $node_storage = $entity_manger->getStorageController('node');
    $node_render = $entity_manger->getRenderController('node');
    $module_handler = $this->getContext('module_handler');

    foreach ($find as $item) {
      // Render the node.
      $entities = $node_storage->load(array($item->sid));
      $node = $entities[$item->sid];
      $build = $node_render->view($node, 'search_result', $item->langcode);
      unset($build['#theme']);
      $node->rendered = drupal_render($build);

      // Fetch comments for snippet.
      $node->rendered .= ' ' . $module_handler->invoke('comment', 'node_update_index', $node, $item->langcode);

      $extra = $module_handler->invokeAll('node_search_result', $node, $item->langcode);

      $language = $module_handler->invoke('language', 'load', $item->langcode);
      $uri = $node->uri();
      $results[] = array(
        'link' => url($uri['path'], array_merge($uri['options'], array('absolute' => TRUE, 'language' => $language))),
        'type' => check_plain($module_handler->invoke('node', 'get_type_label', $node)),
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
    if ($ranking = $this->getContext('module_handler')->invokeAll('ranking')) {
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
