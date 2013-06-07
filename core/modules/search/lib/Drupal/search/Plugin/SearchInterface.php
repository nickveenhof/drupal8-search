<?php

/**
 * @file
 * Contains \Drupal\aearch\Plugin\SearchInterface.
 */

namespace Drupal\search\Plugin;

/**
 * Defines a common interface for all SearchExecute objects.
 */
interface SearchInterface {
  /**
   * Set the keywords, params, and attributes to be used by execute().
   *
   * @return string
   *   The keywords.
   */
  public function setSearch($keywords, array $params, array $attributes);

  /**
   * Returns the currently set keywords of the plugin instance.
   *
   * @return string
   *   The keywords.
   */
  public function getSearchKeywords();

  /**
   * Returns the currently set params (from query string).
   *
   * @return array
   *   The params.
   */
  public function getSearchParams();

  /**
   * Returns the currently set attributes (from the request).
   *
   * @return array
   *   The attributes.
   */
  public function getSearchAttributes();

  /**
   * Verifies if the values set via setSearch() are valid to execute a search for.
   *
   * @return boolean
   *   A true or false depending on the implementation.
   */
  public function isSearchExecutable();

  /**
   * Execute the search.
   *
   * @return array $results
   *   A structured list of search results
   */
  public function execute();

  /**
   * Execute the search and build a render array.
   *
   * @return array $render
   *   The search results in a renderable array.
   */
  public function buildResults();

  /**
   * Update the search index for this plugin.
   *
   * This method is called every cron run if the plugin has been set as
   * an active search module on the Search settings page
   * (admin/config/search/settings). It allows your module to add items to the
   * built-in search index using search_index(), or to add them to your module's
   * own indexing mechanism.
   *
   * When implementing this method, your module should index content items that
   * were modified or added since the last run. PHP has a time limit
   * for cron, though, so it is advisable to limit how many items you index
   * per run using config('search.settings')->get('index.cron_limit') (see
   * example below). Also, since the cron run could time out and abort in the
   * middle of your run, you should update your module's internal bookkeeping on
   * when items have last been indexed as you go rather than waiting to the end
   * of indexing.
   *
   * @ingroup search
   */
  public function updateIndex();

  /**
   * Take action when the search index is going to be rebuilt.
   *
   * Modules that use updateIndex() should update their indexing
   * bookkeeping so that it starts from scratch the next time updateIndex()
   * is called.
   *
   * @ingroup search
   */
  public function resetIndex();

  /**
   * Report the status of indexing.
   *
   * The core search module only invokes this method on active module plugins.
   * Implementing modules do not need to check whether they are active when
   * calculating their return values.
   *
   * @return
   *  An associative array with the key-value pairs:
   *  - remaining: The number of items left to index.
   *  - total: The total number of items to index.
   */
  public function indexStatus();

  /**
   * Add elements to the search settings form.
   * 
   * The core search module only invokes this method on active module plugins.
   *
   * @param $form
   *   Nested array of form elements that comprise the form.
   * @param $form_state
   *   A keyed array containing the current state of the form. The arguments
   *   that drupal_get_form() was originally called with are available in the
   *   array $form_state['build_info']['args'].
   */
  public function addToAdminForm(array &$form, array &$form_state);

  /**
   * Handle any submission for elements on the search settings form.
   * 
   * The core search module only invokes this method on active module plugins.
   *
   * @param $form
   *   Nested array of form elements that comprise the form.
   * @param $form_state
   *   A keyed array containing the current state of the form. The arguments
   *   that drupal_get_form() was originally called with are available in the
   *   array $form_state['build_info']['args'].
   */
  public function submitAdminForm(array &$form, array &$form_state);
}
