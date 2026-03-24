<?php

declare(strict_types=1);

namespace Drupal\advanced_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'PhotoSwipe Media Gallery' formatter.
 */
#[FieldFormatter(
  id: 'photoswipe_media_gallery',
  label: new TranslatableMarkup('PhotoSwipe Media Gallery'),
  field_types: ['image'],
)]
class PhotoswipeMediaGalleryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'thumb_image_style' => '',
      'pswp_image_style' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    // Add library photoswipe
    $elements['#attached']['library'][] = 'advanced_media/photoswipe';

    $thumbStyleId = $this->getSetting('thumb_image_style') ?: '';
    $pswpStyleId = $this->getSetting('pswp_image_style') ?: '';

    $thumbStyle = $thumbStyleId ? ImageStyle::load($thumbStyleId) : NULL;
    $pswpStyle = $pswpStyleId ? ImageStyle::load($pswpStyleId) : NULL;

    $files = [];
    foreach ($items as $item) {
      // Twig `photoswipe-media-gallery.html.twig` expects an associative array
      // with `url`, `width`, `height`, `alt`.
      $fileEntity = $item->entity ?? NULL;

      if (!$fileEntity && isset($item->target_id) && is_numeric($item->target_id)) {
        try {
          $fileEntity = \Drupal::entityTypeManager()->getStorage('file')->load((int) $item->target_id);
        }
        catch (\Throwable $e) {
          $fileEntity = NULL;
        }
      }

      $url = NULL;
      $thumbUrl = NULL;

      $uri = NULL;
      if ($fileEntity && method_exists($fileEntity, 'getFileUri')) {
        $uri = $fileEntity->getFileUri();
      }

      if (is_string($uri) && $uri !== '') {
        // Full URL for PhotoSwipe.
        if ($pswpStyle) {
          $url = $pswpStyle->buildUrl($uri);
        }
        else {
          $url = method_exists($fileEntity, 'createFileUrl') ? $fileEntity->createFileUrl() : NULL;
        }

        // Thumbnail URL for the grid/list.
        if ($thumbStyle) {
          $thumbUrl = $thumbStyle->buildUrl($uri);
        }
        else {
          $thumbUrl = $url;
        }
      }

      // Best-effort dimensions/alt.
      $width = $item->width ?? NULL;
      $height = $item->height ?? NULL;
      $alt = $item->alt ?? NULL;

      if ($alt === NULL && method_exists($item, 'get')) {
        try {
          $altValue = $item->get('alt');
          if ($altValue && !$altValue->isEmpty()) {
            $alt = (string) $altValue->value;
          }
        }
        catch (\Throwable $e) {
          // ignore
        }
      }

      // If a PhotoSwipe image style is configured, update width/height accordingly.
      if ($pswpStyle && is_numeric($width) && is_numeric($height) && is_string($uri) && $uri !== '') {
        $dimensions = [
          'width' => (int) $width,
          'height' => (int) $height,
        ];
        $pswpStyle->transformDimensions($dimensions, $uri);
        $width = $dimensions['width'] ?? $width;
        $height = $dimensions['height'] ?? $height;
      }

      $normalized = [
        'url' => $url,
        'thumb_url' => $thumbUrl,
        'width' => $width,
        'height' => $height,
        'alt' => $alt,
      ];

      $files[] = $normalized;
    }

    // Render a single gallery container for the whole field (not one per delta),
    // so grid + Photoswipe can work with multiple images.
    if (!empty($files)) {
      $elements[0] = [
        '#theme' => 'photoswipe_media_gallery',
        '#attributes' => [],
        '#files' => [
          'values' => $files,
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $imageStyles = \Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple();
    $options = ['' => $this->t('Original')];
    foreach ($imageStyles as $style) {
      $options[$style->id()] = $style->label();
    }

    $form['thumb_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Thumbnail image style (grid preview)'),
      '#options' => $options,
      '#default_value' => $this->getSetting('thumb_image_style'),
    ];
    $form['pswp_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('PhotoSwipe image style (opened image)'),
      '#options' => $options,
      '#default_value' => $this->getSetting('pswp_image_style'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $thumb = $this->getSetting('thumb_image_style');
    $pswp = $this->getSetting('pswp_image_style');

    $summary = [];
    $summary[] = $this->t('Thumbnail style: @s', [
      '@s' => $thumb ? (ImageStyle::load($thumb)?->label() ?? $thumb) : $this->t('Original'),
    ]);
    $summary[] = $this->t('PhotoSwipe style: @s', [
      '@s' => $pswp ? (ImageStyle::load($pswp)?->label() ?? $pswp) : $this->t('Original'),
    ]);

    return $summary;
  }

  public static function getMediaType()
  {
    // TODO: Implement getMediaType() method.
  }
}
