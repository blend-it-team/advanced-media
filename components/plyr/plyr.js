/* global Plyr */
(function (Drupal) {
  Drupal.behaviors.ddeckPlyrSetupPlayers = {
    attach(context, settings) {
      if (typeof Plyr === 'undefined') {
        Drupal.throwError('Plyr.io JS library is missing or not loaded.');
        return;
      }
      const players = Plyr.setup('.plyr-player', {
        i18n: {
          restart: Drupal.t('Restart'),
          rewind: Drupal.t('Rewind @seektimes', { '@seektime': '{seektime}' }),
          play: Drupal.t('Play'),
          pause: Drupal.t('Pause'),
          fastForward: Drupal.t('Forward @seektimes', {
            '@seektime': '{seektime}',
          }),
          seek: Drupal.t('Seek'),
          seekLabel: Drupal.t('@currentTime of {duration}', {
            '@currentTime': '{currentTime}',
            '@duration': '{duration}',
          }),
          played: Drupal.t('Played'),
          buffered: Drupal.t('Buffered'),
          currentTime: Drupal.t('Current time'),
          duration: Drupal.t('Duration'),
          volume: Drupal.t('Volume'),
          mute: Drupal.t('Mute'),
          unmute: Drupal.t('Unmute'),
          enableCaptions: Drupal.t('Enable captions'),
          disableCaptions: Drupal.t('Disable captions'),
          download: Drupal.t('Download'),
          enterFullscreen: Drupal.t('Enter fullscreen'),
          exitFullscreen: Drupal.t('Exit fullscreen'),
          frameTitle: Drupal.t('Player for @title', { '@title': '{title}' }),
          captions: Drupal.t('Captions'),
          settings: Drupal.t('Settings'),
          pip: Drupal.t('PIP'),
          menuBack: Drupal.t('Go back to previous menu'),
          speed: Drupal.t('Speed'),
          normal: Drupal.t('Normal'),
          quality: Drupal.t('Quality'),
          loop: Drupal.t('Loop'),
          start: Drupal.t('Start'),
          end: Drupal.t('End'),
          all: Drupal.t('All'),
          reset: Drupal.t('Reset'),
          disabled: Drupal.t('Disabled'),
          enabled: Drupal.t('Enabled'),
          advertisement: Drupal.t('Ad'),
          qualityBadge: {
            2160: Drupal.t('4K'),
            1440: Drupal.t('HD'),
            1080: Drupal.t('HD'),
            720: Drupal.t('HD'),
            576: Drupal.t('SD'),
            480: Drupal.t('SD'),
          },
        },
      });
    },
  };
})(Drupal);
