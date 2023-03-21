<?php

namespace Drupal\layout_builder_attributes\Plugin\LayoutBuilderAttribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder_attributes\LayoutBuilderAttributeBase;
use Drupal\layout_builder_attributes\Annotation\LayoutBuilderAttribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure a default style plugin.
 *
 * @LayoutBuilderAttribute(
 *   id = "default_style",
 *   title = @Translation("Default style"),
 *   description = @Translation("A simple textfield for editors to fill arbitrary inline styles."),
 *   attributes = {
 *    "style",
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
class DefaultStyle extends LayoutBuilderAttributeBase {

  public function buildForm(array $form, FormStateInterface $form_state, array $context = []):array {
    $form['style'] = [
      '#type' => 'textfield',
      '#title' => 'Style',
      '#description' => $this->t('Inline CSS styles. <em>In general, inline CSS styles should be avoided.</em>'),
      '#default_value' => $this->getConfiguration()['style'] ?? '',
    ];

    return $form;
  }

  public function render():string {
    return $this->getConfiguration()['style'] ?? '';
  }
}
