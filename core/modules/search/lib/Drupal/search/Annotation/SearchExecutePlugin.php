<?php

/**
 * @file
 * Contains \Drupal\search\Annotation\SearchPagePlugin.
 */

namespace Drupal\search\Annotation;

/**
 * Defines an SearchPagePlugin type annotation object.
 *
 * @Annotation
 */
class SearchExecutePlugin extends \Drupal\Component\Annotation\Plugin {

  /**
   * The ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module providing the plugin.
   *
   * @var string
   */
  public $module;

  /**
   * The path fragment to be added to search/ for the search page.
   *
   * @var string
   */
  public $path;

  /**
   * The title for the search page tab.
   *
   * @var string
   */
  public $title;
}
