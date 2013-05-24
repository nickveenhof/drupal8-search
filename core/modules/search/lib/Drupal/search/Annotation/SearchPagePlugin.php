<?php

/**
 * @file
 * Contains \Drupal\search\Annotation\SearchPagePlugin.
 */

namespace Drupal\search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Entity type annotation object.
 *
 * @Annotation
 */
class SearchPagePlugin extends Plugin {
  /**
   * The ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module providing the type.
   *
   * @var string
   */
  public $module;

  /**
   * The path of the search page.
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
