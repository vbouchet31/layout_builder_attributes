<?php

namespace Drupal\layout_builder_attributes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Plugins settings form.
 */
class PluginsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_attributes_plugins_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['layout_builder_attributes.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('layout_builder_attributes.settings')->get();

    $form['intro'] = [
      '#markup' => $this->t('<p>Control which attribute plugins are made available to content editors below:</p>'),
    ];

    // Deal with the section and region form elements first.
    foreach (['section', 'region'] as $element) {
      $form[$element] = [
        '#type' => 'details',
        '#title' => $this->t('%element attributes', ['%element' => ucfirst($element)]),
        '#tree' => TRUE,
      ];

      foreach(['id', 'class', 'style', 'data'] as $attribute) {
        $plugins = \Drupal::service('plugin.manager.layout_builder_attributes')->getApplicablePlugins([
          'element' => $element,
          'attribute' => $attribute,
        ], FALSE, FALSE);

        if (empty($plugins)) {
          $form[$element][$attribute] = [
            '#type' => 'item',
            'details' => [
              '#markup' => $this->t('No %attribute plugin available for the %element element.', ['%attribute' => $attribute, '%element' => $element]),
            ],
          ];
          continue;
        }

        $form[$element][$attribute] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Allowed %attribute attribute plugins:', ['%attribute' => $attribute]),
        ];

        $options = [];
        $default_values = [];
        foreach ($plugins as $plugin_id => $definition) {
          $options[$plugin_id] = $definition['title'];
          $form[$element][$attribute][$plugin_id]['#description'] = $definition['description'];

          // By default, we consider enabled all the plugins which are not
          // explicitly disabled.
          if (!isset($config[$element][$attribute][$plugin_id]) || $config[$element][$attribute][$plugin_id]) {
            $default_values[] = $plugin_id;
          }
        }

        $form[$element][$attribute]['#options'] = $options;
        $form[$element][$attribute]['#default_value'] = $default_values;
      }
    }

    // Deal with the component. Adapt the wording as
    // Layout Builder mentions "block" on the frontend but "component" in the
    // code.
    $form['component'] = [
      '#type' => 'details',
      '#title' => $this->t('<em>Block</em> attributes'),
      '#tree' => TRUE,
    ];

    foreach (['component_outer', 'component_title', 'component_inner'] as $element) {
      $form['component'][$element] = [
        '#type' => 'details',
        '#title' => $this->t('Block %element wrapper attributes', ['%element' => str_replace('component_', '', $element)]),
      ];

      foreach(['id', 'class', 'style', 'data'] as $attribute) {
        $plugins = \Drupal::service('plugin.manager.layout_builder_attributes')->getApplicablePlugins([
          'element' => $element,
          'attribute' => $attribute,
        ], FALSE, FALSE);

        if (empty($plugins)) {
          $form['component'][$element][$attribute] = [
            '#type' => 'item',
            'details' => [
              '#markup' => $this->t('No %attribute plugin available for the block %element wrapper.', ['%attribute' => $attribute, '%element' => str_replace('component_', '', $element)]),
            ],
          ];
          continue;
        }

        $form['component'][$element][$attribute] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Allowed %attribute attribute plugins:', ['%attribute' => $attribute]),
        ];

        $options = [];
        $default_values = [];
        foreach ($plugins as $plugin_id => $definition) {
          $options[$plugin_id] = $definition['title'];
          $form['component'][$element][$attribute][$plugin_id]['#description'] = $definition['description'];

          // By default, we consider enabled all the plugins which are not
          // explicitly disabled.
          if (!isset($config[$element][$attribute][$plugin_id]) || $config[$element][$attribute][$plugin_id]) {
            $default_values[] = $plugin_id;
          }
        }

        $form['component'][$element][$attribute]['#options'] = $options;
        $form['component'][$element][$attribute]['#default_value'] = $default_values;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('layout_builder_attributes.settings');

    foreach (['section', 'region', ['component', 'component_outer'], ['component', 'component_title'], ['component', 'component_inner']] as $element) {
      $values = $form_state->getValue($element);

      $element_config = [];
      foreach (['id', 'class', 'style', 'data'] as $attribute) {
        if (!is_array($values[$attribute])) {
          $element_config[$attribute] = [];
          continue;
        }

        // We store both enabled and disabled plugins so it is not necessary to
        // explicitly enable a plugin to make it available.
        foreach ($values[$attribute] as $plugin_id => $status) {
          $element_config[$attribute][$plugin_id] = (bool) $status;
        }
      }

      $config_key = is_array($element) ? $element[1] : $element;
      $config->set($config_key, $element_config);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
