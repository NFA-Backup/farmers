(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.nfaTheme = {
    attach: function (context) {

      // Show the selected subarea map.
      $('.view-sub-area-map .show-subarea-map').once('subarea-map-selected').click(function (event) {
        var mapId = $(this).attr("href");
        $('.subarea-map.active, .show-subarea-map.active').removeClass('active');
        $(mapId).addClass('active');
        event.preventDefault();
      });

      // Redraw a map that's in a tab when the tab becomes visible.
      $('a.map-tab').on('shown.bs.tab', function (e) {
        farmOS.map.instances.forEach(function (instance) {
          instance.map.updateSize();
        });
      })
    }
  };

})(jQuery, Drupal);
