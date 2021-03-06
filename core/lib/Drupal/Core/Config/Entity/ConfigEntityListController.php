<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityListController.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListController;

/**
 * Defines the default list controller for ConfigEntity objects.
 */
class ConfigEntityListController extends EntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::load().
   */
  public function load() {
    $entities = parent::load();
    uasort($entities, array($this->entityInfo['class'], 'sort'));
    return $entities;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityListController::getOperations();
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    if (isset($this->entityInfo['entity_keys']['status'])) {
      if (!$entity->status()) {
        $operations['enable'] = array(
          'title' => t('Enable'),
          'href' => $uri['path'] . '/enable',
          'options' => $uri['options'],
          'weight' => -10,
        );
      }
      else {
        $operations['disable'] = array(
          'title' => t('Disable'),
          'href' => $uri['path'] . '/disable',
          'options' => $uri['options'],
          'weight' => 20,
        );
      }
    }

    return $operations;
  }

}
