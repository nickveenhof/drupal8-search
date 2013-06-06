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
