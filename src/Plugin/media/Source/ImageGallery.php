<?php

namespace Drupal\advanced_media\Plugin\media\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\MediaSourceBase;

/**
 * File entity media source.
 *
 * @see \Drupal\file\FileInterface
 */
#[MediaSource(
  id: "image_gallery",
  label: new TranslatableMarkup("Image Gallery"),
  description: new TranslatableMarkup("Use local files for reusable media."),
  allowed_field_types: ["image"],
)]
class ImageGallery extends MediaSourceBase {

  /**
   * Key for "Name" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_NAME = 'name';

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      static::METADATA_ATTRIBUTE_NAME => $this->t('Name')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->configuration['source_field'])->entity;
    // If the source field is not required, it may be empty.
    if (!$file) {
      return parent::getMetadata($media, $attribute_name);
    }
    switch ($attribute_name) {
      case static::METADATA_ATTRIBUTE_NAME:

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type);
  }

}
