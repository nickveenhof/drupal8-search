<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Search\NodeSearchExecute.
 */

namespace Drupal\node\Plugin\Search;

use Drupal\search\SearchExecuteInterface;
use Drupal\search\Annotation\SearchPagePlugin;

/**
 * Executes a keyword search aginst the search index.
 *
 * @SearchPagePlugin(
 *   id = "node_search_execute",
 *   title = "Content",
 *   path = "node",
 *   module = "node"
 * )
 */
class NodeSearchExecute implements SearchExecuteInterface {

  /**
   * The keywords to search for.
   *
   * @var string
   */
  protected $keywords;

  /**
   * {@inheritdoc}
   */
  public function __construct($keywords, array $query_parameters = array(), array $request_attributes = array()) {
    $this->keywords = $keywords;
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
    $query = db_select('search_index', 'i', array('target' => 'slave'))
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
    _node_rankings($query);

    // Load results.
    $find = $query
      // Add the language code of the indexed item to the result of the query,
      // since the node will be rendered using the respective language.
      ->fields('i', array('langcode'))
      ->limit(10)
      ->execute();

    foreach ($find as $item) {
      // Render the node.
      $node = node_load($item->sid);
      $build = node_view($node, 'search_result', $item->langcode);
      unset($build['#theme']);
      $node->rendered = drupal_render($build);

      // Fetch comments for snippet.
      $node->rendered .= ' ' . module_invoke('comment', 'node_update_index', $node, $item->langcode);

      $extra = module_invoke_all('node_search_result', $node, $item->langcode);

      $language = language_load($item->langcode);
      $uri = $node->uri();
      $results[] = array(
        'link' => url($uri['path'], array_merge($uri['options'], array('absolute' => TRUE, 'language' => $language))),
        'type' => check_plain(node_get_type_label($node)),
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
}
