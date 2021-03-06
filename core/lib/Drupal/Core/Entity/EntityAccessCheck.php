<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessCheck.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessCheckInterface;

/**
 * Provides a generic access checker for entities.
 */
class EntityAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_entity_access', $route->getRequirements());
  }

  /**
   * Implements \Drupal\Core\Access\AccessCheckInterface::access().
   *
   * The value of the '_entity_access' key must be in the pattern
   * 'entity_type.operation.' The entity type must match the {entity_type}
   * parameter in the route pattern. This will check a node for 'update' access:
   * @code
   * pattern: '/foo/{node}/bar'
   * requirements:
   *   _entity_access: 'node.update'
   * @endcode
   * Available operations are 'view', 'update', 'create', and 'delete'.
   */
  public function access(Route $route, Request $request) {
    // Split the entity type and the operation.
    $requirement = $route->getRequirement('_entity_access');
    list($entity_type, $operation) = explode('.', $requirement);
    // If there is valid entity of the given entity type, check its access.
    if ($request->attributes->has($entity_type)) {
      $entity = $request->attributes->get($entity_type);
      if ($entity instanceof EntityInterface) {
        return $entity->access($operation);
      }
    }
    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return NULL;
  }

}
