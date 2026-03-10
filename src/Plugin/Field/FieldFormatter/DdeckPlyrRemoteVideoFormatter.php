<?php

namespace Drupal\advanced_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'advanced_media_remote_video' formatter.
 *
 * @FieldFormatter(
 *   id = "advanced_media_remote_video",
 *   label = @Translation("DDECK Plyr for remote videos"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class DdeckPlyrRemoteVideoFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use DdeckPlyrSharedFormatterTrait;

  const VIMEO_ID_REGEX = "/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/";

  const YOUTUBE_ID_REGEX = '/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch\?(?:.*&)?v=|embed\/|v\/|vi\/|user\/|shorts\/)))([^\?&"\'<>#]+)/';

  /**
   * The media settings config.
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
   * The oEmbed url resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

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
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed url resolver service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config_factory, RendererInterface $renderer, UrlResolverInterface $url_resolver) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config_factory->get('plyr.settings');
    $this->renderer = $renderer;
    $this->urlResolver = $url_resolver;
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
      $container->get('renderer'),
      $container->get('media.oembed.url_resolver')
    );
  }

  /**
   * Extract provider from video url.
   *
   * @param string $url
   *   String of video url.
   *
   * @return string|false
   *   Return provider as a lower case string from video url or FALSE.
   *
   * @throws \Drupal\media\OEmbed\ProviderException
   * @throws \Drupal\media\OEmbed\ResourceException
   */
  private function extractProvider(string $url) {
    $provider_name = NULL;
    if ($provider = $this->urlResolver->getProviderByUrl($url)) {
      $provider_name = strtolower($provider->getName());
    }
    return in_array($provider_name, ['vimeo', 'youtube']) ? $provider_name : FALSE;
  }

  /**
   * Extract video ID of given provider from video url.
   *
   * @param string $url
   *   String of video url.
   * @param string $provider
   *   String of previously extracted provider.
   *
   * @return string|false
   *   Return embed ID of video url or FALSE.
   */
  private function extractEmbedId(string $url, string $provider) {
    switch ($provider) {
      case 'vimeo':
        preg_match(self::VIMEO_ID_REGEX, $url, $matches);
        if (isset($matches[5])) {
          $id = $matches[5];
        }
        break;

      case 'youtube':
        preg_match(self::YOUTUBE_ID_REGEX, $url, $matches);
        if (isset($matches[1])) {
          $id = $matches[1];
        }
        break;
    }

    if (isset($id)) {
      return $id;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $plyrSsettings = $this->buildPlyrDrupalSettings();

    foreach ($items as $delta => $item) {
      $main_property = $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
      $value = $item->{$main_property};

      if (empty($value)) {
        continue;
      }

      // Add Plyr player library.
      $elements['#attached']['library'][] = 'plyr/plyr-player';

      if (($provider = $this->extractProvider($value))
        && $embedId = $this->extractEmbedId($value, $provider)) {

        $elements[$delta] = [
          '#theme' => 'advanced_media_file_remote_video',
          '#attributes' => [
            'class' => [
              'plyr',
              'plyr-player',
            ],
          ],
          '#plyr_settings' => $plyrSsettings,
          '#video_provider' => $provider,
          '#video_embed_id' => $embedId,
        ];

        // Add cache dependencies of each item in the field.
        $this->renderer->addCacheableDependency($elements[$delta], $item);
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    if (parent::isApplicable($field_definition)) {
      $media_type = $field_definition->getTargetBundle();

      if ($media_type) {
        $media_type = MediaType::load($media_type);
        return $media_type && $media_type->getSource() instanceof OEmbedInterface;
      }
    }
    return FALSE;
  }

}
