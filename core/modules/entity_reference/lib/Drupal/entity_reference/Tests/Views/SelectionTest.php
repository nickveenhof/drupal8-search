<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\Views\SelectionTest.
 */

namespace Drupal\entity_reference\Tests\Views;

use Drupal\simpletest\WebTestBase;

/**
 * Tests entity reference selection handler.
 */
class SelectionTest extends WebTestBase {

  public static $modules = array('views', 'entity_reference', 'entity_reference_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity Reference: Selection handler',
      'description' => 'Tests entity reference selection handler provided by Views.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the selection handler.
   */
  public function testSelectionHandler() {
    // Create nodes.
    $type = $this->drupalCreateContentType()->type;
    $node1 = $this->drupalCreateNode(array('type' => $type));
    $node2 = $this->drupalCreateNode(array('type' => $type));
    $node3 = $this->drupalCreateNode();

    $nodes = array();
    foreach (array($node1, $node2, $node3) as $node) {
      $nodes[$node->type][$node->nid] = $node->label();
    }

    // Build a fake field instance.
    $field = array(
      'translatable' => FALSE,
      'entity_types' => array(),
      'settings' => array(
        'target_type' => 'node',
      ),
      'field_name' => 'test_field',
      'type' => 'entity_reference',
      'cardinality' => '1',
    );
    $instance = array(
      'settings' => array(
        'handler' => 'views',
        'handler_settings' => array(
          'target_bundles' => array(),
          'view' => array(
            'view_name' => 'test_entity_reference',
            'display_name' => 'entity_reference_1',
            'arguments' => array(),
          ),
        ),
      ),
    );

    // Get values from selection handler.
    $handler = entity_reference_get_selection_handler($field, $instance);
    $result = $handler->getReferencableEntities();

    $success = FALSE;
    foreach ($result as $node_type => $values) {
      foreach ($values as $nid => $label) {
        if (!$success = $nodes[$node_type][$nid] == trim(strip_tags($label))) {
          // There was some error, so break.
          break;
        }
      }
    }

    $this->assertTrue($success, 'Views selection handler returned expected values.');
  }
}
