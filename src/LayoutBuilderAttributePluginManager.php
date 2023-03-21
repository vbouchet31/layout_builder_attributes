<?php

namespace Drupal\layout_builder_attributes;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The manager for Layout Builder Attribute plugins.
 */
class LayoutBuilderAttributePluginManager extends DefaultPluginManager {

  /**
   * Constructs a new LayoutBuilderAttributePluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct(
      'Plugin/LayoutBuilderAttribute',
      $namespaces,
      $module_handler,
      'Drupal\layout_builder_attributes\LayoutBuilderAttributeInterface',
      'Drupal\layout_builder_attributes\Annotation\LayoutBuilderAttribute'
    );
    $this->alterInfo('layout_builder_attribute_info');
    $this->setCacheBackend($cache_backend, 'layout_builder_attribute');

    $this->configFactory = $config_factory;
  }

  /**
   * Browse all the defined plugins to find applicable ones.
   *
   * @param array $context
   *   An arbitrary array of data that is used to determine if the plugins
   *   apply or not.
   * @param boolean $filter_config
   *   Filter the applicable plugins based on the module configuration.
   * @param boolean $filter_apply
   *   Filter the applicable plugins based on the applies() method.
   *
   * @return array
   *   A list of applicable plugin ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getApplicablePlugins(array $context = [], bool $filter_config = TRUE, bool $filter_apply = TRUE) {
    $config = $this->configFactory->get('layout_builder_attributes.settings')->get();

    $attribute = $context['attribute'] ?? FALSE;
    $element = $context['element'] ?? FALSE;

    $plugins = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      // Skip the plugin if it does not apply to this attribute type.
      if ($attribute && !in_array($attribute, $definition['attributes'])) {
        continue;
      }
      // Skip the plugin if it does not apply to this element type.
      if ($element && !empty($definition['elements']) && !in_array($element, $definition['elements'])) {
        continue;
      }

      // Skip the plugin if it is disabled for this element/type/attribute
      // combination.
      if ($filter_config) {
        if (isset($config[$element][$attribute][$plugin_id]) && !$config[$element][$attribute][$plugin_id]) {
          continue;
        }
      }

      // Skip the plugin if the plugin itself say so.
      if ($filter_apply) {
        $instance = $this->createInstance($plugin_id);
        if (!$instance->applies($context)) {
          continue;
        }
      }

      $plugins[$plugin_id] = $definition;
    }
    asort($plugins);

    return $plugins;
  }
}
