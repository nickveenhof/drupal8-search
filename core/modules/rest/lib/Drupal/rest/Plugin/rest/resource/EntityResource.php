<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\rest\resource\EntityResource.
 */

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents entities as resources.
 *
 * @Plugin(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   serialization_class = "Drupal\Core\Entity\Entity",
 *   derivative = "Drupal\rest\Plugin\Derivative\EntityDerivative"
 * )
 */
class EntityResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   *
   * @param mixed $id
   *   The entity ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the loaded entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($id) {
    $definition = $this->getDefinition();
    $entity = entity_load($definition['entity_type'], $id);
    if ($entity) {
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      foreach ($entity as $field_name => $field) {
        if (!$field->access('view')) {
          unset($entity->{$field_name});
        }
      }
      return new ResourceResponse($entity);
    }
    throw new NotFoundHttpException(t('Entity with ID @id not found', array('@id' => $id)));
  }

  /**
   * Responds to entity POST requests and saves the new entity.
   *
   * @param mixed $id
   *   Ignored. A new entity is created with a new ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function post($id, EntityInterface $entity) {
    if (!$entity->access('create')) {
      throw new AccessDeniedHttpException();
    }
    $definition = $this->getDefinition();
    // Verify that the deserialized entity is of the type that we expect to
    // prevent security issues.
    if ($entity->entityType() != $definition['entity_type']) {
      throw new BadRequestHttpException(t('Invalid entity type'));
    }
    // POSTed entities must not have an ID set, because we always want to create
    // new entities here.
    if (!$entity->isNew()) {
      throw new BadRequestHttpException(t('Only new entities can be created'));
    }
    foreach ($entity as $field_name => $field) {
      if (!$field->access('create')) {
        throw new AccessDeniedHttpException(t('Access denied on creating field @field.', array('@field' => $field_name)));
      }
    }
    try {
      $entity->save();
      watchdog('rest', 'Created entity %type with ID %id.', array('%type' => $entity->entityType(), '%id' => $entity->id()));

      $url = url(strtr($this->pluginId, ':', '/') . '/' . $entity->id(), array('absolute' => TRUE));
      // 201 Created responses have an empty body.
      return new ResourceResponse(NULL, 201, array('Location' => $url));
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, t('Internal Server Error'), $e);
    }
  }

  /**
   * Responds to entity PATCH requests.
   *
   * @param mixed $id
   *   The entity ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function patch($id, EntityInterface $entity) {
    if (empty($id)) {
      throw new NotFoundHttpException();
    }
    $definition = $this->getDefinition();
    if ($entity->entityType() != $definition['entity_type']) {
      throw new BadRequestHttpException(t('Invalid entity type'));
    }
    $original_entity = entity_load($definition['entity_type'], $id);
    // We don't support creating entities with PATCH, so we throw an error if
    // there is no existing entity.
    if ($original_entity == FALSE) {
      throw new NotFoundHttpException();
    }
    if (!$original_entity->access('update')) {
      throw new AccessDeniedHttpException();
    }
    $info = $original_entity->entityInfo();
    // Make sure that the entity ID is the one provided in the URL.
    $entity->{$info['entity_keys']['id']} = $id;

    // Overwrite the received properties.
    foreach ($entity as $field_name => $field) {
      if (isset($entity->{$field_name})) {
        if (empty($entity->{$field_name})) {
          if (!$original_entity->{$field_name}->access('delete')) {
            throw new AccessDeniedHttpException(t('Access denied on deleting field @field.', array('@field' => $field_name)));
          }
        }
        else {
          if (!$original_entity->{$field_name}->access('update')) {
            throw new AccessDeniedHttpException(t('Access denied on updating field @field.', array('@field' => $field_name)));
          }
        }
        $original_entity->{$field_name} = $field;
      }
    }
    try {
      $original_entity->save();
      watchdog('rest', 'Updated entity %type with ID %id.', array('%type' => $entity->entityType(), '%id' => $entity->id()));

      // Update responses have an empty body.
      return new ResourceResponse(NULL, 204);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, t('Internal Server Error'), $e);
    }
  }

  /**
   * Responds to entity DELETE requests.
   *
   * @param mixed $id
   *   The entity ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete($id) {
    $definition = $this->getDefinition();
    $entity = entity_load($definition['entity_type'], $id);
    if ($entity) {
      if (!$entity->access('delete')) {
        throw new AccessDeniedHttpException();
      }
      try {
        $entity->delete();
        watchdog('rest', 'Deleted entity %type with ID %id.', array('%type' => $entity->entityType(), '%id' => $entity->id()));

        // Delete responses have an empty body.
        return new ResourceResponse(NULL, 204);
      }
      catch (EntityStorageException $e) {
        throw new HttpException(500, t('Internal Server Error'), $e);
      }
    }
    throw new NotFoundHttpException(t('Entity with ID @id not found', array('@id' => $id)));
  }
}
