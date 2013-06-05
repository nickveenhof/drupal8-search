<?php

/**
 * @file
 * Hooks provided by the Search module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define access to a custom search routine.
 *
 * This hook allows a module to define permissions for a search tab.
 *
 * @ingroup search
 */
function hook_search_access() {
  return user_access('access content');
}

/**
 * Add elements to the search settings form.
 *
 * @return
 *   Form array for the Search settings page at admin/config/search/settings.
 *
 * @ingroup search
 */
function hook_search_admin() {
  // Output form for defining rank factor weights.
  $form['content_ranking'] = array(
    '#type' => 'details',
    '#title' => t('Content ranking'),
  );
  $form['content_ranking']['#theme'] = 'node_search_admin';
  $form['content_ranking']['#tree'] = TRUE;
  $form['content_ranking']['info'] = array(
    '#value' => '<em>' . t('The following numbers control which properties the content search should favor when ordering the results. Higher numbers mean more influence, zero means the property is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '</em>'
  );

  // Note: reversed to reflect that higher number = higher ranking.
  $options = drupal_map_assoc(range(0, 10));
  $ranks = config('node.settings')->get('search_rank');
  foreach (module_invoke_all('ranking') as $var => $values) {
    $form['content_ranking']['factors'][$var] = array(
      '#title' => $values['title'],
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($ranks[$var]) ? $ranks[$var] : 0,
    );
  }

  $form['#submit'][] = 'node_search_admin_submit';

  return $form;
}

/**
 * Override the rendering of search results.
 *
 * A module that implements hook_search_info() to define a type of search may
 * implement this hook in order to override the default theming of its search
 * results, which is otherwise themed using theme('search_results').
 *
 * Note that by default, theme('search_results') and theme('search_result')
 * work together to create an ordered list (OL). So your hook_search_page()
 * implementation should probably do this as well.
 *
 * @param $results
 *   An array of search results.
 *
 * @return
 *   A renderable array, which will render the formatted search results with a
 *   pager included.
 *
 * @see search-result.tpl.php
 * @see search-results.tpl.php
 */
function hook_search_page($results) {
  $output['prefix']['#markup'] = '<ol class="search-results">';

  foreach ($results as $entry) {
    $output[] = array(
      '#theme' => 'search_result',
      '#result' => $entry,
      '#module' => 'my_module_name',
    );
  }
  $output['suffix']['#markup'] = '</ol>' . theme('pager');

  return $output;
}

/**
 * Preprocess text for search.
 *
 * This hook is called to preprocess both the text added to the search index
 * and the keywords users have submitted for searching.
 *
 * Possible uses:
 * - Adding spaces between words of Chinese or Japanese text.
 * - Stemming words down to their root words to allow matches between, for
 *   instance, walk, walked, walking, and walks in searching.
 * - Expanding abbreviations and acronymns that occur in text.
 *
 * @param $text
 *   The text to preprocess. This is a single piece of plain text extracted
 *   from between two HTML tags or from the search query. It will not contain
 *   any HTML entities or HTML tags.
 *
 * @param $langcode
 *   The language code of the entity that has been found.
 *
 * @return
 *   The text after preprocessing. Note that if your module decides not to
 *   alter the text, it should return the original text. Also, after
 *   preprocessing, words in the text should be separated by a space.
 *
 * @ingroup search
 */
function hook_search_preprocess($text, $langcode = NULL) {
  // If the langcode is set to 'en' then add variations of the word "testing"
  // which can also be found during English language searches.
  if (isset($langcode) && $langcode == 'en') {
    // Add the alternate verb forms for the word "testing".
    if ($text == 'we are testing') {
      $text .= ' test tested';
    }
  }

  return $text;
}
