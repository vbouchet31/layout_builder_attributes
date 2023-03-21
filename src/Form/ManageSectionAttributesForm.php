<?php

namespace Drupal\layout_builder_attributes\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for managing section attributes.
 */
class ManageSectionAttributesForm extends FormBase {

  use AjaxFormHelperTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;
  use LayoutRebuildTrait;

  /**
   * The Layout Tempstore.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstore;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new ManageComponentAttributesForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration object factory.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, ConfigFactory $config_factory) {
    $this->layoutTempstore = $layout_tempstore_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_attributes_manage_section_attributes_form';
  }

  /**
   * Builds the attributes form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The original delta of the section.
   * @param string $plugin_id
   *   The plugin_id of the section.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $plugin_id = NULL) {
    $parameters = array_slice(func_get_args(), 2, 2);
    foreach ($parameters as $parameter) {
      if (is_null($parameter)) {
        throw new \InvalidArgumentException('ManageComponentAttributesForm requires all parameters.');
      }
    }

    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->isUpdate = is_null($plugin_id);
    $this->pluginId = $plugin_id;

    $layout_settings = $this->sectionStorage->getSection($delta)->getLayoutSettings();

    // These are the arbitrary additional data to be forwarded to the plugins
    // to help them determine if they should apply or not.
    $data = [
      'element' => 'section',
      'section_storage' => $this->sectionStorage,
      'delta' => $this->delta,
      'is_update' => $this->isUpdate,
      'plugin_id' => $this->pluginId,
    ];

    foreach (['class', 'id', 'style', 'data'] as $attribute) {
      $data['attribute'] = $attribute;
      $plugins = \Drupal::service('plugin.manager.layout_builder_attributes')->getApplicablePlugins($data);

      foreach ($plugins as $plugin_id => $definition) {
        $form['section'][$attribute][$plugin_id] = [];
        $instance = \Drupal::service('plugin.manager.layout_builder_attributes')->createInstance(
          $plugin_id,
          $layout_settings['section_attributes'][$attribute][$plugin_id] ?? []
        );

        // @TODO: Check if it is really needed.
        $subform_state = SubformState::createForSubForm(
          $form['section'][$attribute][$plugin_id],
          $form,
          $form_state);
        $form['section'][$attribute][$plugin_id] = $instance->buildForm($form['section'][$attribute][$plugin_id], $subform_state, $data);
      }
    }

    $data['element'] = 'region';
    foreach ($section_storage->getSection($delta)->getLayout()->getPluginDefinition()->getRegions() as $region_id => $region_definition) {
      $form['regions'][$region_id] = [
        '#type' => 'details',
        '#title' => $this->t('Region %region_name attributes', ['%region_name' => $region_definition['label']]),
      ];

      $form['regions'][$region_id]['intro'] = [
        '#type' => 'item',
        '#markup' => $this->t('Manage attributes on the region %region_name wrapper element.', ['%region_name' => $region_definition['label']]),
        '#weight' => -1000,
      ];

      foreach (['class', 'id', 'style', 'data'] as $attribute) {
        $data['attribute'] = $attribute;
        $plugins = \Drupal::service('plugin.manager.layout_builder_attributes')->getApplicablePlugins($data);

        foreach ($plugins as $plugin_id => $definition) {
          $form['regions'][$region_id][$attribute][$plugin_id] = [];
          $instance = \Drupal::service('plugin.manager.layout_builder_attributes')->createInstance(
            $plugin_id,
            $layout_settings['region_attributes'][$region_id][$attribute][$plugin_id] ?? []
          );

          // @TODO: Check if it is really needed.
          $subform_state = SubformState::createForSubForm(
            $form['regions'][$region_id][$attribute][$plugin_id],
            $form,
            $form_state);
          $form['regions'][$region_id][$attribute][$plugin_id] = $instance->buildForm($form['regions'][$region_id][$attribute][$plugin_id], $subform_state, $data);
        }
      }
    }

    $form['#tree'] = TRUE;

    // Workaround for core bug:
    // https://www.drupal.org/project/drupal/issues/2897377.
    $form['#id'] = Html::cleanCssIdentifier($this->getFormId());

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#button_type' => 'primary',
    ];

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
    }

    return $form;
  }

  /**
   * Gets the selected delta.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return int
   *   The section delta.
   */
  protected function getSelectedDelta(FormStateInterface $form_state) {
    if ($form_state->hasValue('region')) {
      return (int) explode(':', $form_state->getValue('region'))[0];
    }
    return (int) $this->delta;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delta = $this->getSelectedDelta($form_state);
    $layout_settings = $this->sectionStorage->getSection($delta)->getLayoutSettings();

    unset($layout_settings['section_attributes']);
    unset($layout_settings['regions_attributes']);

    $values = $form_state->getValue('section');
    foreach (['class', 'id', 'style', 'data'] as $attribute) {
      if (!isset($values[$attribute])) {
        continue;
      }

      $layout_settings['section_attributes'][$attribute] = $values[$attribute];
    }

    $values = $form_state->getValue('regions');
    foreach ($values as $region_name => $region_values) {
      foreach (['class', 'id', 'style', 'data'] as $attribute) {
        if (!isset($region_values[$attribute])) {
          continue;
        }

        $layout_settings['region_attributes'][$region_name][$attribute] = $region_values[$attribute];
      }
    }

    $this->sectionStorage->getSection($delta)->setLayoutSettings($layout_settings);

    $this->layoutTempstore->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }
}
