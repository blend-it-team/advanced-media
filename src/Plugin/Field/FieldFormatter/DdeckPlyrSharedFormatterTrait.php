<?php

namespace Drupal\advanced_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Shared trait for Plyr formatters.
 */
trait DdeckPlyrSharedFormatterTrait {

  use StringTranslationTrait;

  /**
   * Attach the correct libraries for this field instance.
   *
   * @param array $element
   *   The element array.
   * @param int $delta
   *   The delta.
   */
  public function attachPlyrLibraries(array &$element, int $delta): void {
    $element[$delta]['#plyr_settings'] = $this->buildPlyrDrupalSettings();
  }

  /**
   * Build Plyr drupalSettings.
   *
   * Build the settings array that is passed to the Twig template
   * to build up the data-attributes per player instance.
   *
   * @return array
   *   Return the Plyr settings array.
   */
  public function buildPlyrDrupalSettings() {
    $decoded = $this->getDecodedSettings() ?? [];
    $settings = [];
    foreach ($decoded as $settingName => $settingValue) {
      if ($settingName === 'controls') {
        $controls = [];
        if (is_array($settingValue)) {
          foreach ($settingValue as $controlName => $controlValue) {
            if (((bool) $controlValue)) {
              $controls[] = $controlName;
            }
          }
        }
        $settings['controls'] = $controls;
      }
      elseif ($settingName === 'youtube') {
        $youTube = new \stdClass();
        if (is_array($settingValue)) {
          foreach ($settingValue as $controlName => $controlValue) {
            if (((bool) $controlValue)) {
              $youTube->{$controlName} = (bool) $controlValue;
            }
          }
        }
        $settings['youtube'] = $youTube;
      }
      elseif ((bool) $settingValue) {
        $settings[$settingName] = TRUE;
      }
    }
    return $settings;
  }

  /**
   * Returns the decoded settings array from the JSON "settings" field.
   *
   * Supports backward compatibility: if "settings" is empty and the stored
   * config still has the old keys (e.g. autoplay, controls), those are used.
   *
   * @return array|null
   *   Decoded options array, or NULL if the stored JSON is invalid.
   */
  protected function getDecodedSettings(): ?array {
    $saved = $this->getSettings();
    $raw = $saved['settings'] ?? '';

    if (is_string($raw) && $raw !== '') {
      $decoded = json_decode($raw, TRUE);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
      }
      return NULL;
    }

    // Backward compatibility: old config had flat keys (autoplay, loop, controls, youtube).
    if (isset($saved['autoplay'])) {
      return $saved;
    }

    return [];
  }

  /**
   * Default settings structure (array) used for JSON default value.
   *
   * @return array
   *   The default Plyr options as an array.
   */
  protected static function defaultSettingsStructure(): array {
    return [
      'autoplay' => FALSE,
      'loop' => FALSE,
      'resetOnEnd' => TRUE,
      'hideControls' => TRUE,
      'controls' => [
        'play-large' => FALSE,
        'restart' => FALSE,
        'rewind' => FALSE,
        'play' => TRUE,
        'fast-forward' => FALSE,
        'progress' => TRUE,
        'current-time' => TRUE,
        'duration' => TRUE,
        'mute' => TRUE,
        'volume' => TRUE,
        'captions' => FALSE,
        'settings' => TRUE,
        'pip' => FALSE,
        'airplay' => FALSE,
        'fullscreen' => TRUE,
      ],
      'youtube' => [
        'noCookie' => TRUE,
      ],
    ];
  }

  /**
   * Default settings for the formatter.
   *
   * @return array
   *   Return the default settings array (single key "settings" with JSON string).
   */
  public static function defaultSettings() {
    return [
      'settings' => json_encode(static::defaultSettingsStructure(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ];
  }

  /**
   * The settings form for the formatter.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Return settings form array.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $stored = $this->getSettings()['settings'] ?? '';

    $form['settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Plyr Settings'),
      '#default_value' => $stored !== '' ? $stored : json_encode(static::defaultSettingsStructure(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
      '#description' => $this->t('Paste the JSON settings here.'),
    ];

    return $form;
  }

  /**
   * Summary of the form widget settings.
   *
   * @return array
   *   The summary of the settings.
   */
  public function settingsSummary() {
    $settings = $this->getDecodedSettings();

    if ($settings === NULL) {
      return [$this->t('Invalid or empty JSON')];
    }

    if ($settings === []) {
      return [$this->t('No settings')];
    }

    $generalSettings = [];
    if (TRUE === (bool) ($settings['autoplay'] ?? FALSE)) {
      $generalSettings[] = $this->t('Autoplaying');
    }
    if (TRUE === (bool) ($settings['loop'] ?? FALSE)) {
      $generalSettings[] = $this->t('Looping');
    }
    if (TRUE === (bool) ($settings['resetOnEnd'] ?? FALSE)) {
      $generalSettings[] = $this->t('Reset on end');
    }
    if (TRUE === (bool) ($settings['hideControls'] ?? FALSE)) {
      $generalSettings[] = $this->t('Hide controls automatically');
    }

    $controlsSettings = [];
    if (is_array($settings['controls'] ?? NULL)) {
      foreach ($settings['controls'] as $name => $value) {
        if (TRUE === (bool) $value) {
          $controlsSettings[] = $this->t('@control', ['@control' => ucfirst(str_replace('-', ' ', $name))]);
        }
      }
    }

    $summary = [];
    $summary[] = $this->t('General: @general', ['@general' => implode(', ', $generalSettings) ?: $this->t('None')]);
    $summary[] = $this->t('Control: @controls', ['@controls' => implode(', ', $controlsSettings) ?: $this->t('None')]);
    return $summary;
  }

}
