<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\EntityReferenceTaxonomyTermRssFormatter.
 */

namespace Drupal\taxonomy\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference taxonomy term RSS' formatter.
 *
 * @todo: Have a way to indicate this formatter applies only to taxonomy terms.
 *
 * @Plugin(
 *   id = "entity_reference_rss_category",
 *   module = "taxonomy",
 *   label = @Translation("RSS category"),
 *   description = @Translation("Display reference to taxonomy term in RSS."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceTaxonomyTermRssFormatter extends EntityReferenceFormatterBase {

  /**
   * Overrides Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $entity->rss_elements[] = array(
        'key' => 'category',
        'value' => $item['entity']->label(),
        'attributes' => array(
          'domain' => $item['target_id'] ? url('taxonomy/term/' . $item['target_id'], array('absolute' => TRUE)) : '',
        ),
      );
    }

    return $elements;
  }
}
