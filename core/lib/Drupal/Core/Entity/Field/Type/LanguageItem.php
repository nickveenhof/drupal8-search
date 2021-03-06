<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\LanguageItem.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldItemBase;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Defines the 'language_field' entity field item.
 */
class LanguageItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see LanguageItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['value'] = array(
        'type' => 'string',
        'label' => t('Language code'),
      );
      static::$propertyDefinitions['language'] = array(
        'type' => 'language',
        'label' => t('Language object'),
        // The language object is retrieved via the language code.
        'computed' => TRUE,
        'read-only' => FALSE,
        'settings' => array('langcode source' => 'value'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * Overrides \Drupal\Core\Entity\Field\FieldItemBase::get().
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the language property, if no array
    // is given.
    if (isset($values) && !is_array($values)) {
      $values = array('language' => $values);
    }
    // Make sure that the 'language' property gets set as 'value'.
    if (isset($values['value']) && !isset($values['language'])) {
      $values['language'] = $values['value'];
    }
    parent::setValue($values, $notify);
  }
}
