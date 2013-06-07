<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Search\NodeSearch.
 */

namespace Drupal\node\Plugin\Search;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class NodeSearch extends SearchPluginBase implements ContainerFactoryPluginInterface {
  protected $database;
  protected $entity_manager;
  protected $module_handler;
  protected $config_factory;
  protected $state;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    $database = $container->get('database');
    $entity_manager = $container->get('plugin.manager.entity');
    $module_handler = $container->get('module_handler');
    $config_factory = $container->get('config.factory');
    $state = $container->get('keyvalue')->get('state');
    return new static($database, $entity_manager, $module_handler, $config_factory, $state, $configuration, $plugin_id, $plugin_definition);
  }

  public function __construct(Connection $database, EntityManager $entity_manager, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory, KeyValueStoreInterface $state, array $configuration, $plugin_id, array $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->database = $database;
    $this->entity_manager = $entity_manager;
    $this->module_handler = $module_handler;
    $this->config_factory = $config_factory;
    $this->state = $state;
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

    $node_storage = $this->entity_manger->getStorageController('node');
    $node_render = $this->entity_manger->getRenderController('node');
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
        // @todo - move rank out of drupal variables.
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
  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    $limit = (int) $this->config_factory('search.settings')->get('index.cron_limit');

    $result = $this->database->queryRange("SELECT n.nid FROM {node} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE d.sid IS NULL OR d.reindex <> 0 ORDER BY d.reindex ASC, n.nid ASC", 0, $limit, array(), array('target' => 'slave'));
    $nids = $result->fetchCol();
    if (!$nids) {
      return;
    }

    // The indexing throttle should be aware of the number of language variants
    // of a node.
    $counter = 0;
    $node_storage = $this->entity_manger->getStorageController('node');
    foreach ($node_storage->load($nids) as $node) {
      // Determine when the maximum number of indexable items is reached.
      $counter += count($node->getTranslationLanguages());
      if ($counter > $limit) {
        break;
      }
      $this->indexNode($node);
    }
  }

  /**
   * Indexes a single node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node to index.
   */
  protected function indexNode(EntityInterface $node) {

    // Save the changed time of the most recent indexed node, for the search
    // results half-life calculation.
    $this->state->set('node.cron_last', $node->changed);

    $languages = $node->getTranslationLanguages();

    foreach ($languages as $language) {
      // Render the node.
      $build = $this->module_handler->invoke('node', 'view', array($node, 'search_index', $language->langcode));

      unset($build['#theme']);
      $node->rendered = drupal_render($build);

      $text = '<h1>' . check_plain($node->label($language->langcode)) . '</h1>' . $node->rendered;

      // Fetch extra data normally not visible.
      $extra = $this->module_handler->invokeAll('node_update_index', array($node, $language->langcode));
      foreach ($extra as $t) {
        $text .= $t;
      }

      // Update index.
      $this->module_handler->invoke('search', 'index', array($node->nid, 'node', $text, $language->langcode));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetIndex() {
    $this->database->update('search_dataset')
      ->fields(array('reindex' => REQUEST_TIME))
      ->condition('type', 'node')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $total = $this->database->query('SELECT COUNT(*) FROM {node}')->fetchField();
    $remaining = $this->database->query("SELECT COUNT(*) FROM {node} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE d.sid IS NULL OR d.reindex <> 0")->fetchField();
    return array('remaining' => $remaining, 'total' => $total);
  }

  /**
   * {@inheritdoc}
   */
  public function addToAdminForm(array &$form, array &$form_state) {
    // Output form for defining rank factor weights.
    $form['content_ranking'] = array(
      '#type' => 'details',
      '#title' => t('Content ranking'),
    );
    $form['content_ranking']['#theme'] = 'node_search_admin';
    $form['content_ranking']['info'] = array(
      '#value' => '<em>' . t('The following numbers control which properties the content search should favor when ordering the results. Higher numbers mean more influence, zero means the property is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '</em>'
    );

    // Note: reversed to reflect that higher number = higher ranking.
    $options = drupal_map_assoc(range(0, 10));
    foreach ($this->module_handler->invokeAll('ranking') as $var => $values) {
      $form['content_ranking']['factors']['node_rank_' . $var] = array(
        '#title' => $values['title'],
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => variable_get('node_rank_' . $var, 0),
      );
    }
  }
}
