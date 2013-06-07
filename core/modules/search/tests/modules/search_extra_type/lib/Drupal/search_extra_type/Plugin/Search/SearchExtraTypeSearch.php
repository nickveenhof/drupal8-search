<?php

/**
 * @file
 * Contains \Drupal\search_extra_type\Plugin\Search\SearchExtraTypeSearch.
 */

namespace Drupal\search_extra_type\Plugin\Search;

use Drupal\Core\Config\Config;
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
  protected $configSettings;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $container->get('config.factory')->get('search_extra_type.settings'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public function __construct(Config $config_settings, array $configuration, $plugin_id, array $plugin_definition) {
    $this->configSettings = $config_settings;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function setSearch($keywords, array $params, array $attributes) {
    if (empty($params['search_conditions'])) {
      $params['search_conditions'] = '';
    }
    parent::setSearch($keywords, $params, $attributes);
  }

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
        'snippet' => "Dummy search snippet to display. Keywords: {$this->keywords}\n\nConditions: " . print_r($this->searchParams['search_conditions'], TRUE),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();
    $output['prefix']['#markup'] = '<h2>Test page text is here</h2> <ol class="search-results">';

    foreach ($results as $entry) {
      $output[] = array(
        '#theme' => 'search_result',
        '#result' => $entry,
        '#module' => 'search_extra_type',
      );
    }
    $output['suffix']['#markup'] = '</ol>' . theme('pager');

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function addToAdminForm(array &$form, array &$form_state) {
    // Output form for defining rank factor weights.
    $form['extra_type_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Extra type settings'),
      '#tree' => TRUE,
    );

    $form['extra_type_settings']['boost'] = array(
      '#type' => 'select',
      '#title' => t('Boost method'),
      '#options' => array(
        'bi' => t('Bistromathic'),
        'ii' => t('Infinite Improbability'),
      ),
      '#default_value' => $this->configSettings->get('boost'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitAdminForm(array &$form, array &$form_state) {
    $this->configSettings
      ->set('boost', $form_state['values']['extra_type_settings']['boost'])
      ->save();
  }
}
