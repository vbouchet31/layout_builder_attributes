<?php

namespace Drupal\layout_builder_attributes;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class to define standard operations of a layout builder attribute.
 */
abstract class LayoutBuilderAttributeBase extends PluginBase implements LayoutBuilderAttributeInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration():array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration):void {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration():array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle():string {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription():string {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes():array {
    return $this->pluginDefinition['attributes'];
  }

  /**
   * {@inheritdoc}
   */
  public function getElements():array {
    return $this->pluginDefinition['elements'];
  }

  /**
   * {@inheritdoc}
   */
  public function render():string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $context = []):bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies():array {
    return [];
  }
}
