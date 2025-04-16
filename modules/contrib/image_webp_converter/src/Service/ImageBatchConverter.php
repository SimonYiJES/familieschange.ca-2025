<?php

namespace Drupal\image_webp_converter\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WebPConvert\WebPConvert;

/**
 * Service class which contains all methods required for WebP conversion.
 */
final class ImageBatchConverter {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * EntityTypeManager to perform entity queries and loading entity ids.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityFieldManager that helps us obtain field definitions of entities.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * FileSystemInterface instance used for getting the real path of files.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * FileUsageInterface instance used for finding the usage of files.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Logger used for logging info.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * MessengerInterface instance to show messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * File url generator object.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Construct new ImageBatchConverter object for converting images.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Helps to perform entity queries and loads entity ids.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Helps in obtaining field definitions of fields present in entities.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   Used for getting the real path of files.
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   Used for finding the entities where files are used.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Used for logging messages as required.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   Used for translation of messages.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Used for showing messages to the page.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection object.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $urlGenerator
   *   File url generator object for generating relative file urls.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, FileSystemInterface $fileSystem, FileUsageInterface $fileUsage, LoggerChannelFactoryInterface $loggerFactory, TranslationInterface $stringTranslation, MessengerInterface $messenger, Connection $database, FileUrlGeneratorInterface $urlGenerator, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->fileSystem = $fileSystem;
    $this->fileUsage = $fileUsage;
    $this->logger = $loggerFactory->get('image_webp_converter');
    $this->messenger = $messenger;
    $this->stringTranslation = $stringTranslation;
    $this->database = $database;
    $this->urlGenerator = $urlGenerator;
    $this->configFactory = $configFactory;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class.
   * The factory should pass any needed dependencies into the constructor of
   * this class, but not the container itself.
   * Every call to this method must return a new instance of this class;
   * that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('file_system'),
      $container->get('file.usage'),
      $container->get('logger.factory'),
      $container->get('string_translation'),
      $container->get('messenger'),
      $container->get('database'),
      $container->get('file_url_generator'),
      $container->get('config.factory'),

    );
  }

  /**
   * Initiates and starts the batch process.
   */
  public function startBatch() {
    $required_mime_types = ['image/jpeg', 'image/png'];
    $query = $this->entityTypeManager->getStorage('file')->getQuery();
    $file_ids = $query->condition('filemime', $required_mime_types, 'IN')
      ->accessCheck(FALSE)
      ->execute();
    // Breaks the files list into chunks.
    $chunks = array_chunk($file_ids, 30);
    $batch = [
      'title' => $this->t('Image WebP Converter Batch'),
      'init_message' => $this->t('Initializing...'),
      'error_message' => $this->t('Batch has encountered an error.'),
      'operations' => [],
      'finished' => [$this, 'batchFinished'],
    ];
    foreach ($chunks as $chunk) {
      $batch['operations'][] = [[$this, 'processImage'], [$chunk]];
    }
    batch_set($batch);
  }

  /**
   * Converts the image files to WebP format.
   *
   * @param array $file_ids
   *   The files to be converted.
   * @param array $context
   *   The context array of the current page.
   */
  public function processImage(array $file_ids, &$context) {
    $config = $this->configFactory->get('image_webp_converter.settings');
    $selected_converter = $config->get('selected_converter');
    $image_quality = $config->get('quality');
    $lossless = $config->get('lossless');

    // Ensures that the selected converter is a valid string.
    if (empty($selected_converter) || !in_array($selected_converter, ['cwebp', 'gd', 'imagick'])) {
      $this->logger->warning('Invalid or missing converter selected. Using default converter (cwebp).');
      $selected_converter = 'cwebp';
    }
    // Validates the selected converter.
    $valid_converters = ['cwebp', 'gd', 'imagick'];
    if (!in_array($selected_converter, $valid_converters)) {
      $this->logger->warning('Invalid or missing converter selected. Using default converter (gd).');
      $selected_converter = 'gd';
    }
    $error_message = '';
    switch ($selected_converter) {
      case 'cwebp':
        break;

      case 'gd':
        if (!extension_loaded('gd')) {
          $error_message = $this->t('The "gd" converter requires the GD PHP extension, which is not installed or enabled on this server.');
        }
        break;

      case 'imagick':
        if (!extension_loaded('imagick')) {
          $error_message = $this->t('The "imagick" converter requires the Imagick PHP extension, which is not installed or enabled on this server.');
        }
        break;
    }
    // If an error occurred, log it and show a user-friendly message.
    if (!empty($error_message)) {
      $this->logger->error($error_message);
      $context['message'] = $this->t('Failed to convert file: @message', [
        '@message' => $error_message,
      ]);
      $this->messenger->addMessage($error_message, 'error');
    }
    // Setting conversion options.
    $options = [
      'converters' => [$selected_converter],
    ];
    $options['png'] = [
      'encoding' => 'lossy',
      'near lossless' => 60,
    ];
    $options['jpeg'] = [
      'encoding' => 'lossy',
      'auto-limit' => TRUE,
    ];
    // Appling quality or lossless settings based on configuration.
    if ($lossless) {
      $options['png']['encoding'] = 'lossless';
    }
    $options['quality'] = $image_quality ??= 85;

    foreach ($file_ids as $file_id) {
      $file = $this->entityTypeManager->getStorage('file')->load($file_id);

      if ($file instanceof File) {
        $uri = $file->getFileUri();
        $path = $this->fileSystem->realpath($uri);

        // Path for the WebP version.
        $webp_uri = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $uri);
        $webp_path = $this->fileSystem->realpath($webp_uri);
        try {
          // Checks if the file actually exists.
          if (!file_exists($path)) {
            throw new \Exception('File not found on the filesystem.');
          }
          // Starts conversion using WebPConvert.
          WebPConvert::convert($path, $webp_path, $options);

          // Updates file and references after successful conversion.
          $file->setFileUri($webp_uri);
          $file->save();
          $this->updateContentReferences($file, $uri, $webp_uri);
          $this->updateFileMetadata($file, $webp_uri);
          $context['message'] = $this->t('Converted to WebP: @file', ['@file' => $uri]);
          $this->logger->notice('Image converted to WebP successfully: @file', ['@file' => $webp_path]);
        }
        catch (\Exception $e) {
          // Handles exceptions and log issues.
          $this->logger->error('Failed to process file @file: @message', [
            '@file' => $file->getFileUri(),
            '@message' => $e->getMessage(),
          ]);
          $context['message'] = $this->t('Failed to convert file @file: @message', [
            '@file' => $uri,
            '@message' => $e->getMessage(),
          ]);
          // Skip this file and proceed with the next.
          continue;
        }
      }
      else {
        $this->logger->warning('Invalid file entity with ID @id. Skipping...', ['@id' => $file_id]);
        $context['message'] = $this->t('Invalid file entity with ID @id. Skipping...', ['@id' => $file_id]);
        continue;
      }
    }
  }

  /**
   * Updates references to the image in content entities.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file that has been converted to webp.
   * @param string $original_uri
   *   The uri of the original file.
   * @param string $webp_uri
   *   The uri of the converted file.
   */
  public function updateContentReferences(File $file, string $original_uri, string $webp_uri) {
    // Replace original file URI with WebP URI in content.
    $usage = $this->fileUsage->listUsage($file);
    foreach ($usage as $module => $arr) {
      foreach ($arr as $entity_type => $entity_ids) {
        foreach ($entity_ids as $entity_id => $count) {
          // Load the entites where the file is used.
          $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
          if ($entity) {
            foreach ($this->entityFieldManager->getFieldDefinitions($entity_type, $entity->bundle()) as $field_name => $field_definition) {
              // Execute this block if the image is used in ckeditor.
              if ($module == 'editor') {
                if ($field_definition->getType() == "text_with_summary") {
                  $field = $entity->get($field_name);
                  if (!$field->isEmpty()) {
                    foreach ($field as $item) {
                      $body = $item->value;
                      // Get relative path of the file and then the new one.
                      $og_path = $this->urlGenerator->generate($original_uri)->toString();
                      $webp_path = $this->urlGenerator->generate($webp_uri)->toString();
                      $pattern = '/' . preg_quote($og_path, '/') . '/';
                      // Replaces the img src path in the html with the new one.
                      $updated_body = preg_replace($pattern, $webp_path, $body);
                      // Updates the field with the updated html value.
                      $entity->set($field_name, [
                        'value' => $updated_body,
                        'format' => $item->format,
                      ]);
                    }
                  }
                }
              }
              else {
                // Search for a field that is of type image.
                if ($field_definition->getType() === 'image') {
                  $field = $entity->get($field_name);
                  if (!$field->isEmpty()) {
                    foreach ($field as $item) {
                      $image_file = $item->entity;
                      if ($image_file && $image_file->getFileUri() === $original_uri) {
                        // Update the parent entity field item.
                        $item->setValue([
                          'target_id' => $image_file->id(),
                          'alt' => $item->alt,
                        ]);
                      }
                    }
                  }
                }
              }
            }
            // Saving the parent entity after updates.
            $entity->save();
          }
        }
      }
    }
  }

  /**
   * Update file entities with the new WebP filename and mime type.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity being updated.
   * @param string $new_uri
   *   The new URI of the file after conversion.
   */
  public function updateFileMetadata(File $file, string $new_uri) {
    // Extracts the new filename from the URI.
    $new_filename = basename($new_uri);
    // Updates the file's filename property.
    $file->set('filename', $new_filename);
    // Saving the updated file entity.
    $file->save();
    // Update the file mime type in the database.
    $this->database->update('file_managed')
      ->fields(['filemime' => 'image/webp'])
      ->condition('uri', $new_uri)
      ->execute();

    // If this file belongs to a media entity, update the media name.
    $query = $this->entityTypeManager->getStorage('media')->getQuery();
    $media_ids = $query->condition('field_media_image.target_id', $file->id())
      ->accessCheck(FALSE)
      ->execute();
    foreach ($media_ids as $media_id) {
      $media = $this->entityTypeManager->getStorage('media')->load($media_id);
      if ($media instanceof Media) {
        $this->updateMediaName($media, $new_filename);
      }
    }
    $this->logger->notice('Updated file metadata for @id: new filename @name', [
      '@id' => $file->id(),
      '@name' => $new_filename,
    ]);
  }

  /**
   * Updates the media name after file conversion to WebP.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media entity that requires the name change.
   * @param string $new_filename
   *   The new filename of the converted image.
   */
  public function updateMediaName(Media $media, string $new_filename) {
    // Set the new name on the media entity.
    $media->setName($new_filename);
    // Save the updated media entity.
    $media->save();
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Boolean value indicating if the batch process was successfully finished.
   */
  public function batchFinished(bool $success) {
    if ($success) {
      $this->messenger->addMessage($this->t('All images have been converted to WebP format.'));
    }
    else {
      $this->messenger->addMessage($this->t('The batch process completed with some errors.'), 'warning');
    }
  }

}
