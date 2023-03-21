<?php

namespace Drupal\layout_builder_attributes\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a layout builder attribute annotation object.
 *
 * @Annotation
 */
class LayoutBuilderAttribute extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the attribute type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description shown to users.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The type of attribute (id, class, style, data).
   *
   * @var string[]
   */
  public $attributes;

  /**
   * The type of element the plugin applies to (section, region, component_outer, component_title, component_inner)
   *
   * @var string[]
   */
  public $elements;
}
