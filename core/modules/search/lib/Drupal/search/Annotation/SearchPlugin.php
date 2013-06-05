<?php

/**
 * @file
 * Contains \Drupal\search\Annotation\SearchPlugin.
 */

namespace Drupal\search\Annotation;

/**
 * Defines an SearchPagePlugin type annotation object.
 *
 * @Annotation
 */
class SearchPlugin extends \Drupal\Component\Annotation\Plugin {

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
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public $title;
}
