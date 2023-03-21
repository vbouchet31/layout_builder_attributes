<?php

namespace Drupal\layout_builder_attributes\Plugin\LayoutBuilderAttribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder_attributes\LayoutBuilderAttributeBase;
use Drupal\layout_builder_attributes\Annotation\LayoutBuilderAttribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure a default class plugin.
 *
 * @LayoutBuilderAttribute(
 *   id = "default_class",
 *   title = @Translation("Default class"),
 *   description = @Translation("A simple textfield for editors to fill arbitrary classes."),
 *   attributes = {
 *     "class",
 *   },
 *   elements = {
 *     "section",
 *     "region",
 *     "component_outer",
 *     "component_title",
 *     "component_inner",
 *   }
 * )
 */
class DefaultClass extends LayoutBuilderAttributeBase {

  public function buildForm(array $form, FormStateInterface $form_state, array $context = []):array {
    $form['class'] = [
      '#type' => 'textfield',
      '#title' => 'Class(es)',
      '#description' => $this->t('Classes to be applied. Multiple classes should be separated by a space.'),
      '#default_value' => $this->getConfiguration()['class'] ?? '',
    ];

    return $form;
  }

  public function render():string {
    return $this->getConfiguration()['class'] ?? '';
  }
}
