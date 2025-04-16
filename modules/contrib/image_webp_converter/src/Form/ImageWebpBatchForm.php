<?php

namespace Drupal\image_webp_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image_webp_converter\Service\ImageBatchConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to start the WebP conversion batch process.
 */
final class ImageWebpBatchForm extends FormBase {
  /**
   * Custom service that converts images to WebP.
   *
   * @var \Drupal\image_webp_converter\Service\ImageBatchConverter
   */
  protected $imageBatchConverter;

  /**
   * Initializes the ImageWebpBatchForm class instance.
   *
   * @param \Drupal\image_webp_converter\Service\ImageBatchConverter $imageBatchConverter
   *   The custom service used for converting images to webp.
   */
  public function __construct(ImageBatchConverter $imageBatchConverter) {
    $this->imageBatchConverter = $imageBatchConverter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('image_webp_converter.service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_webp_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="form-description"><p>',
      '#markup' => $this->t('Start the batch process to convert all images to WebP format.'),
      '#suffix' => '</p></div>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Conversion'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->imageBatchConverter->startBatch();
  }

}
