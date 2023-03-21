<?php

namespace Drupal\layout_builder_attributes\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for managing component attributes.
 */
class ManageComponentAttributesForm extends FormBase {

  use AjaxFormHelperTrait;
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
    return 'layout_builder_attributes_manage_component_attributes_form';
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
   * @param string $uuid
   *   The UUID of the block being updated.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL) {
    $parameters = array_slice(func_get_args(), 2);
    foreach ($parameters as $parameter) {
      if (is_null($parameter)) {
        throw new \InvalidArgumentException('ManageComponentAttributesForm requires all parameters.');
      }
    }

    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;

    $section = $section_storage->getSection($delta);
    $component = $section->getComponent($uuid);
    $component_attributes = $component->get('component_attributes');

    // These are the arbitrary additional data to be forwarded to the plugins
    // to help them determine if they should apply or not.
    $data = [
      'section_storage' => $section_storage,
      'delta' => $delta,
      'uuid' => $uuid
    ];

    foreach (['component_outer', 'component_title', 'component_inner'] as $element) {
      $data['element'] = $element;

      foreach (['class', 'id', 'style', 'data'] as $attribute) {
        $data['attribute'] = $attribute;

        $plugins = \Drupal::service('plugin.manager.layout_builder_attributes')->getApplicablePlugins($data);
        foreach (array_keys($plugins) as $plugin_id) {
          $form[$element][$attribute][$plugin_id] = [];
          $instance = \Drupal::service('plugin.manager.layout_builder_attributes')->createInstance(
            $plugin_id,
            $component_attributes[$element][$attribute][$plugin_id] ?? []
          );

          // @TODO: Check if it is really needed.
          $subform_state = SubformState::createForSubForm(
            $form[$element][$attribute][$plugin_id],
          $form,
          $form_state);
          $form[$element][$attribute][$plugin_id] = $instance->buildForm($form[$element][$attribute][$plugin_id], $subform_state, $data);
        }
      }

      if (isset($form[$element])) {
        $form[$element]['#type'] = 'details';
        $form[$element]['#title'] = $this->t('Block %element wrapper attributes', ['%element' => str_replace('component_', '', $element)]);

        $form[$element]['intro'] = [
          '#markup' => $this->t('<p>Manage attributes on the block %element wrapper element</p>', ['%element' => str_replace('component_', '', $element)]),
          '#weight' => -1000,
        ];
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delta = $this->getSelectedDelta($form_state);
    $section = $this->sectionStorage->getSection($delta);

    $additional_settings = [];
    foreach (['component_outer', 'component_title', 'component_inner'] as $element) {
      $values = $form_state->getValue($element);

      // The type may be enabled but if no attributes are available for it,
      // the form element won't be created.
      if (empty($values)) {
        continue;
      }

      foreach (['class', 'id', 'style', 'data'] as $attribute) {
        // The attribute may be enabled for this type but if no plugin is
        // available for it, the form element won't be created.
        if (empty($values[$attribute])) {
          continue;
        }

        $additional_settings[$element][$attribute] = $values[$attribute];
      }
    }

    // Store configuration in layout_builder.component.additional.
    // Switch to third-party settings when
    // https://www.drupal.org/project/drupal/issues/3015152 is committed.
    $section->getComponent($this->uuid)->set('component_attributes', $additional_settings);

    $this->layoutTempstore->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
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
}
