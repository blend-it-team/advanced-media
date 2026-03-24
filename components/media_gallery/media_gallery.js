(function(Drupal) {

  Drupal.behaviors.mediaGallery = {
    attach(context) {
      const placeholders = once('advanced-media-gallery', '[data-gallery-media]', context);

      if (!placeholders.length) {
        const hasGallery = (context.querySelector?.('.advanced-media-gallery a') || document.querySelector('.advanced-media-gallery a'));
        if (hasGallery) {
          initPhotoSwipeOnce();
        }
        return;
      }
    },
  };

  function initPhotoSwipeOnce() {
    if (document.body.dataset.advancedMediaGalleryPswpInitialized === '1') {
      return;
    }
    document.body.dataset.advancedMediaGalleryPswpInitialized = '1';

    if (typeof PhotoSwipeLightbox === 'undefined' || typeof PhotoSwipe === 'undefined') {
      return;
    }

    const lightbox = new PhotoSwipeLightbox({
      gallery: '.advanced-media-gallery',
      children: 'a',
      pswpModule: PhotoSwipe,
    });

    lightbox.init();
  }

})(Drupal);
