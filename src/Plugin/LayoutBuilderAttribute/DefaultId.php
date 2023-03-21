<?php

namespace Drupal\layout_builder_attributes\Plugin\LayoutBuilderAttribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder_attributes\LayoutBuilderAttributeBase;
use Drupal\layout_builder_attributes\Annotation\LayoutBuilderAttribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure a default id plugin.
 *
 * @LayoutBuilderAttribute(
 *   id = "default_id",
 *   title = @Translation("Default ID"),
 *   description = @Translation("A simple textfield for editors to fill an arbitrary ID."),
 *   attributes = {
 *    "id",
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
class DefaultId extends LayoutBuilderAttributeBase {

  public function buildForm(array $form, FormStateInterface $form_state, array $context = []):array {
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => 'ID',
      '#description' => $this->t('An HTML identifier unique to the page.'),
      '#default_value' => $this->getConfiguration()['id'] ?? '',
    ];

    return $form;
  }

  public function render():string {
    return $this->getConfiguration()['id'] ?? '';
  }
}
