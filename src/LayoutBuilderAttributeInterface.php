<?php

namespace Drupal\layout_builder_attributes;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * An interface to define the expected operations of a Layout Builder Attribute.
 */
interface LayoutBuilderAttributeInterface extends PluginInspectionInterface, ConfigurableInterface, DependentPluginInterface {

  /**
   * Returns the title of the plugin.
   *
   * @return string
   *   A title.
   */
  public function getTitle():string;

  /**
   * Returns the description of the plugin.
   *
   * @return string
   *   A textual description of the purpose of the plugin.
   */
  public function getDescription():string;

  /**
   * The attributes for which the plugin applies: class, id, style, data-*.
   *
   * @return string[]
   *   A list of attributes.
   */
  public function getAttributes():array;

  /**
   * The elements for which the plugin applies: section, region, component_outer
   * component_title, component_outer.
   *
   * @return string[]
   *   A list of elements.
   */
  public function getElements():array;

  /**
   * Returns either the plugin applies or not given the context.
   *
   * @return bool
   *   The fact that is applies or not.
   */
  public function applies(array $context = []):bool;

  /**
   * Returns the rendered value to be added as attribute.
   *
   * @return string
   *   The value to be added as attribute.
   */
  public function render():string;

  /**
   * Returns the form to configure the plugin.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $context
   *   Additional information to build the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $context = []):array;

}
