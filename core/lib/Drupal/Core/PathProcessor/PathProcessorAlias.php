<?php

/**
 * @file
 * Contains \Drupal\Core\PathProcessor\PathProcessorAlias.
 */

namespace Drupal\Core\PathProcessor;

use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessorAlias implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a PathProcessorAlias object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager for looking up the system path.
   */
  public function __construct(AliasManagerInterface $alias_manager) {
    $this->aliasManager = $alias_manager;
  }

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processInbound().
   */
  public function processInbound($path, Request $request) {
    $path = $this->aliasManager->getSystemPath($path);
    return $path;
  }

  /**
   * Implements Drupal\Core\PathProcessor\OutboundPathProcessorInterface::processOutbound().
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    $langcode = isset($options['language']) ? $options['language']->langcode : NULL;
    $path = $this->aliasManager->getPathAlias($path, $langcode);
    return $path;
  }
}
