<?php

namespace Drupal\layout_builder_attributes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Display settings form.
 */
class DisplaySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_attributes_display_settings_form';
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
    $form['section'] = [
      '#type' => 'details',
      '#title' => $this->t('Section'),
      '#tree' => TRUE,
    ];

    $form['section']['section_form_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Section form'),
      '#options' => [
        'embedded' => $this->t('Embedded in the "Configure section" form'),
        'link' => $this->t('Additional link along the "Configure section" link'),
      ],
      '#attributes' => [
        'name' => 'field_section_form_display',
      ]
    ];
    $form['section']['field_form_display']['link']['#description'] = $this->t('If Drupal core is <a href="https://www.drupal.org/project/drupal/issues/3344037">patched</a> to display the "Remove" and "Configure" links as contextual links, the "Manage attributes" link will also be a contextual link.');

    $form['section']['section_link_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link label'),
      '#states' => [
        'visible' => [
          ':input[name="field_section_form_display"]' => ['value' => 'link'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }
}
