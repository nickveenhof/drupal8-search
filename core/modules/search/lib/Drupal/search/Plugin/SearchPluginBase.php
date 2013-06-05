<?php
/**
 * @file
 * Definition of Drupal\search\Plugin\SearchPluginBase
 */

use Drupal\Component\Plugin\PluginBase;

namespace Drupal\search\Plugin;

/**
 * Base class for plugins wishing to support search.
 */
abstract class SearchPluginBase extends PluginBase implements SearchInterface {

  /**
   * The keywords to use in a search.
   *
   * @var string
   */
  protected $keywords;

  /**
   * Array of parameters from the query string from the request.
   *
   * @var array
   */
  protected $searchParams;

  /**
   * Array of attributies - usually from the request object.
   *
   * @var array
   */
  protected $searchAttributes;

  /**
   * Implements Drupal\search\Plugin\SearchInterface::setSearch().
   */
  public function setSearch($keywords, array $params, array $attributes) {
    $this->keywords = $keywords;
    $this->searchParams = $params;
    $this->searchAttributes = $attributes;
  }

  /**
   * Implements Drupal\search\Plugin\SearchInterface::getSearchKeywords().
   */
  public function getSearchKeywords() {
    return $this->keywords;
  }

  /**
   * Implements Drupal\search\Plugin\SearchInterface::getSearchParams().
   */
  public function getSearchParams() {
    return $this->searchParams;
  }

  /**
   * Implements Drupal\search\Plugin\SearchInterface::getSearchAttributes().
   */
  public function getSearchAttributes() {
    return $this->searchAttributes;
  }
  // Note: Plugin configuration is optional so its left to the plugin type to
  // require a getter as part of its interface.
}
