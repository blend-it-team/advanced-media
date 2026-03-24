<?php

namespace Drupal\advanced_media\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns image URLs for a list of media IDs.
 */
class GalleryController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GalleryController object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns JSON describing the gallery images.
   *
   * @param string $ids
   *   Comma-separated media IDs.
   */
  public function images(string $ids): JsonResponse {
    $id_list = array_filter(array_map('intval', explode(',', $ids)));

    if (empty($id_list)) {
      return new JsonResponse([]);
    }

    $storage = $this->entityTypeManager->getStorage('media');
    $media_entities = $storage->loadMultiple($id_list);

    $data = [];

    foreach ($id_list as $id) {
      if (empty($media_entities[$id])) {
        continue;
      }
      $media = $media_entities[$id];

      if ($media->bundle() !== 'image') {
        continue;
      }

      $source = $media->getSource();
      $source_field_definition = $source->getSourceFieldDefinition($media->bundle());
      if (!$source_field_definition) {
        continue;
      }
      $source_field_name = $source_field_definition->getName();

      if (!$media->hasField($source_field_name) || $media->get($source_field_name)->isEmpty()) {
        continue;
      }

      $file = $media->get($source_field_name)->entity;
      if (!$file) {
        continue;
      }

      $original_uri = $file->getFileUri();
      $original_url = file_create_url($original_uri);

      $thumb_style_id = 'medium';
      $thumb_url = $original_url;
      if ($image_style = ImageStyle::load($thumb_style_id)) {
        $thumb_url = $image_style->buildUrl($original_uri);
      }

      $data[] = [
        'id' => $id,
        'original' => $original_url,
        'thumb' => $thumb_url,
      ];
    }

    return new JsonResponse($data);
  }

}
