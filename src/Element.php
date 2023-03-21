<?php

namespace Drupal\layout_builder_attributes;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Defines a service for Text Editor's render elements.
 */
class Element implements TrustedCallbackInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new Element object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderLayoutBuilder'];
  }

  /**
   * Additional #pre_render callback for 'text_format' elements.
   */
  public function preRenderLayoutBuilder(array $element) {
    // Filter the element to keep only the section elements.
    $keys = array_filter(array_keys($element['layout_builder']), 'is_int');

    foreach ($keys as $key) {
      // Don't process the "Add section" sections.
      if (!isset($element['layout_builder'][$key]['layout-builder__section'])) {
        continue;
      }

      // If there is no applicable plugins for this section, we don't add the
      // "Configure attributes" link.
      // @TODO: Check also the applicable plugins for the regions.
      $data = ['element' => 'section'] + $element['layout_builder'][$key]['configure']['#url']->getRouteParameters();
      $plugins = \Drupal::service('plugin.manager.layout_builder_attributes')->getApplicablePlugins($data);
      if (empty($plugins)) {
        continue;
      }

      // Add a "Configure attributes" link.
      $element['layout_builder'][$key]['configure_attributes'] = [
        '#type' => 'link',
        '#title' => t('Configure attributes'),
        '#url' => Url::fromRoute('layout_builder_attributes.manage_section_attributes', $element['layout_builder'][$key]['configure']['#url']->getRouteParameters()),
        '#access' => $this->currentUser->hasPermission('manage layout builder section attributes'),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'layout-builder__link',
            'layout-builder__link--attributes',
          ],
          'style' => 'margin: 0 0 0 0.5rem;',
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
        ],
        '#weight' => -998,
      ];

      // Alter the "Remove" and "Configure" links weights to keep existing
      // ordering after adding the new "Configure attributes" link.
      $element['layout_builder'][$key]['remove']['#weight'] = -1000;
      $element['layout_builder'][$key]['configure']['#weight'] = -999;
    }

    return $element;
  }
}
