<?php

namespace Drupal\layout_builder_attributes\Plugin\LayoutBuilderAttribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder_attributes\LayoutBuilderAttributeBase;
use Drupal\layout_builder_attributes\Annotation\LayoutBuilderAttribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure a default data plugin.
 *
 * @LayoutBuilderAttribute(
 *   id = "default_data",
 *   title = @Translation("Default data"),
 *   description = @Translation("A simple textarea for editors to fill arbitrary data-* attributes."),
 *   attributes = {
 *    "data",
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
class DefaultData extends LayoutBuilderAttributeBase {

  public function buildForm(array $form, FormStateInterface $form_state, array $context = []):array {
    $form['data'] = [
      '#type' => 'textarea',
      '#title' => 'Data-* attributes',
      '#description' => $this->t('Custom attributes, which are available to both CSS and JS.<br><br>Each attribute should be entered on its own line with a pipe (|) separating its name and its optional value:<br>data-test|example-value<br>data-attribute-with-no-value'),
      '#default_value' => $this->getConfiguration()['data'] ?? '',
    ];

    return $form;
  }

  public function render():string {
    return $this->getConfiguration()['data'] ?? '';
  }
}
