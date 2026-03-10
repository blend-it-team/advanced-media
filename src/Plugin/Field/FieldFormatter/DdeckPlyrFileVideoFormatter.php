<?php

declare(strict_types=1);

namespace Drupal\advanced_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'DDECK Plyr for video files' formatter.
 */
#[FieldFormatter(
  id: 'advanced_media_file_video',
  label: new TranslatableMarkup('DDECK Plyr for video files'),
  field_types: ['file'],
)]
class DdeckPlyrFileVideoFormatter extends DdeckPlyrFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'video';
  }

}
