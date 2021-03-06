<?php

/**
 * @file
 * Contains \Drupal\path\Form\DeleteForm.
 */

namespace Drupal\path\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Path\Path;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a path alias.
 */
class DeleteForm extends ConfirmFormBase implements ControllerInterface {

  /**
   * The path crud service.
   *
   * @var Path $path
   */
  protected $path;

  /**
   * The path alias being deleted.
   *
   * @var array $pathAlias
   */
  protected $pathAlias;

  /**
   * Constructs a \Drupal\Core\Path\Path object.
   *
   * @param \Drupal\Core\Path\Path $path
   *   The path crud service.
   */
  public function __construct(Path $path) {
    $this->path = $path;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.crud')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'path_alias_delete';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete path alias %title?', array('%title' => $this->pathAlias['alias']));
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getCancelPath().
   */
  protected function getCancelPath() {
    return 'admin/config/search/path';
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $pid = NULL) {
    $this->pathAlias = $this->path->load(array('pid' => $pid));

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->path->delete(array('pid' => $this->pathAlias['pid']));

    $form_state['redirect'] = 'admin/config/search/path';
  }
}
