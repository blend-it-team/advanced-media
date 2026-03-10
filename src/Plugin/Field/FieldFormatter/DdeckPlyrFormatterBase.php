<?php

namespace Drupal\advanced_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileMediaFormatterBase;
use Drupal\file\Plugin\Field\FieldFormatter\FileMediaFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Plyr formatters.
 */
abstract class DdeckPlyrFormatterBase extends FileMediaFormatterBase implements FileMediaFormatterInterface {

  use DdeckPlyrSharedFormatterTrait;

  /**
   * The Plyr settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an PlyrStreamFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config_factory, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config_factory->get('plyr.settings');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if (!empty($elements)) {
      $elements['#attached']['library'][] = 'advanced_media/plyr-player';
      foreach ($items as $delta => $item) {

        /** @var \Drupal\Core\Template\Attribute $attributes */
        $attributes = $elements[$delta]['#attributes'];
        $attributes->addClass(['plyr', 'plyr-player']);

        $elements[$delta]['#plyr_settings'] = $this->buildPlyrDrupalSettings();
      }
    }

    return $elements;
  }

}
