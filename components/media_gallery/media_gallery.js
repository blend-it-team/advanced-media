(function(Drupal) {

  Drupal.behaviors.mediaGallery = {
    attach(context) {
      const placeholders = once('advanced-media-gallery', '[data-gallery-media]', context);

      // #region agent log
      fetch('http://127.0.0.1:7710/ingest/9b1ba2db-177e-41ab-abc6-45d374897ea2',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'238b3d'},body:JSON.stringify({sessionId:'238b3d',runId:'pre-debug',hypothesisId:'Hjs1',location:'media_gallery.js:attach',message:'attach called',data:{placeholdersCount:placeholders.length,advancedMediaGalleryAnchorsInContext:context.querySelectorAll?.('.advanced-media-gallery a').length || 0,advancedMediaGalleryAnchorsInDoc:document.querySelectorAll('.advanced-media-gallery a').length},timestamp:Date.now()})}).catch(()=>{});
      // #endregion

      if (!placeholders.length) {
        // Fallback: if markup is already rendered with `.advanced-media-gallery`, init directly.
        const hasGallery = (context.querySelector?.('.advanced-media-gallery a') || document.querySelector('.advanced-media-gallery a'));
        if (hasGallery) {
          initPhotoSwipeOnce();
        }
        return;
      }

      placeholders.forEach((element) => {
        const idsAttr = element.getAttribute('data-gallery-media') || '';
        const ids = idsAttr
          .split(',')
          .map((id) => parseInt(id, 10))
          .filter((id) => id);

        if (!ids.length) {
          return;
        }

        const url = Drupal.url('advanced-media/gallery/' + ids.join(','));

        fetch(url, {
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error('Failed to load gallery images.');
            }
            return response.json();
          })
          .then((items) => {
            if (!Array.isArray(items) || !items.length) {
              return;
            }

            const gallery = document.createElement('div');
            gallery.classList.add('advanced-media-gallery');

            items.forEach((item) => {
              const link = document.createElement('a');
              link.href = item.original;

              const img = document.createElement('img');
              img.src = item.thumb;
              img.loading = 'lazy';
              img.alt = '';

              link.appendChild(img);
              gallery.appendChild(link);
            });

            element.replaceWith(gallery);

            initPhotoSwipeOnce();
          })
          .catch(() => {
            // Fail silently, degrade gracefully.
          });
      });
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
