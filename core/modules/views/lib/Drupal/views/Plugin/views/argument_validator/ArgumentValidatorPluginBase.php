<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase.
 */

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_argument_validate_plugins Views argument validate plugins
 * @{
 * Allow specialized methods of validating arguments.
 */

/**
 * Base argument validator plugin to provide basic functionality.
 */
abstract class ArgumentValidatorPluginBase extends PluginBase {

  /**
   * The argument handler instance associated with this plugin.
   *
   * @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase
   */
  protected $argument;

  /**
   * Sets the parent argument this plugin is associated with.
   *
   * @param \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument
   *   The parent argument to set.
   */
  public function setArgument(ArgumentPluginBase $argument) {
    $this->argument = $argument;
  }

  /**
   * Retrieve the options when this is a new access
   * control plugin
   */
  protected function defineOptions() { return array(); }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) { }

  /**
   * Provide the default form form for validating options
   */
  public function validateOptionsForm(&$form, &$form_state) { }

  /**
   * Provide the default form form for submitting options
   */
  public function submitOptionsForm(&$form, &$form_state, &$options = array()) { }

  /**
   * Determine if the administrator has the privileges to use this plugin
   */
  public function access() { return TRUE; }

  /**
   * If we don't have access to the form but are showing it anyway, ensure that
   * the form is safe and cannot be changed from user input.
   *
   * This is only called by child objects if specified in the buildOptionsForm(),
   * so it will not always be used.
   */
  protected function checkAccess(&$form, $option_name) {
    if (!$this->access()) {
      $form[$option_name]['#disabled'] = TRUE;
      $form[$option_name]['#value'] = $form[$this->option_name]['#default_value'];
      $form[$option_name]['#description'] .= ' <strong>' . t('Note: you do not have permission to modify this. If you change the default filter type, this setting will be lost and you will NOT be able to get it back.') . '</strong>';
    }
  }

  function validate_argument($arg) { return TRUE; }

  /**
   * Process the summary arguments for displaying.
   *
   * Some plugins alter the argument so it uses something else interal.
   * For example the user validation set's the argument to the uid,
   * for a faster query. But there are use cases where you want to use
   * the old value again, for example the summary.
   */
  function process_summary_arguments(&$args) { }

}

/**
 * @}
 */
