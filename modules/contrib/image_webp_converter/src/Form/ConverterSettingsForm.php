<?php

namespace Drupal\image_webp_converter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConverterSettingsForm.
 *
 * Provides a configuration form for selecting a WebP converter.
 */
class ConverterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['image_webp_converter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_webp_converter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('image_webp_converter.settings');

    // Add a radio selection for converters.
    $form['selected_converter'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select WebP Converter'),
      '#options' => [
        'gd' => $this->t('GD'),
        'cwebp' => $this->t('cwebp'),
        'imagick' => $this->t('Imagick'),
      ],
      '#default_value' => $config->get('selected_converter') ?? 'gd',
      '#description' => $this->t('Note: Please ensure the required extension for the selected converter is installed and enabled on your server.'),
    ];

    // Adding a field to set the quality value.
    $form['quality'] = [
      '#type' => 'number',
      '#title' => $this->t('Image Quality'),
      '#description' => $this->t('Set the quality of the WebP images (0-100). Lower values mean smaller file size but lower quality.'),
      '#default_value' => $config->get('quality') ?? 80,
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    // Adding a checkbox for lossless conversion.
    $form['lossless'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Lossless Conversion'),
      '#description' => $this->t('If enabled, images will be converted to WebP in a lossless format. This applies only to lossless image formats (ie. png). Lossy image formats (ie. jpg) image conversion remains unaffected.'),
      '#default_value' => $config->get('lossless') ?? FALSE,
    ];

    // Adding a checkbox to enable/disable per-node image conversion.
    $form['per_node_conversion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable per-node WebP Conversion'),
      '#description' => $this->t('If enabled, a checkbox will appear on each node form to allow conversion of images to WebP.'),
      '#default_value' => $config->get('per_node_conversion') ?? FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('image_webp_converter.settings')
      ->set('selected_converter', $form_state->getValue('selected_converter'))
      ->set('quality', $form_state->getValue('quality'))
      ->set('lossless', $form_state->getValue('lossless'))
      ->set('per_node_conversion', $form_state->getValue('per_node_conversion'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
