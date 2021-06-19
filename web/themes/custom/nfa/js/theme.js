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
    }
  };

})(jQuery, Drupal);
