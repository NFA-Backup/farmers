/**
 * @file
 * Contains local JS for showing/hiding license data.
 */

(function ($) {
  Drupal.behaviors.nfamis = {
    attach: function (context, settings) {

      /*
            var row = $( ".view-id-offers_licences").children('div..views-row').hide();
            console.log(row);
            // $('.view-id-offers_licences.view-content .views-row').hide();
            $('#66').parentsUntil("div.views-row").show();

            $('#tabs li a').click(function () {
              var t = $(this).attr('id');
              if ($(this).hasClass('inactive')) {
                $('#tabs li a').addClass('inactive');
                $(this).removeClass('inactive');
                $('.tabcontent').hide();
                $('#data-' + t).fadeIn('slow');
              }
            });
      */

      $.fn.tabsMapper = function () {
        $(this).find('div.views-row').each(function (i) {
          $(this).find('a').click(function (e) {
            e.preventDefault();

            $(".offers-licences-wrapper").find('.views-row').hide();
            $target = $(".offers-licences-wrapper").find('.elem-' + i);
            $target.show();

            $('html, body').animate({
              scrollTop: $target.offset().top
            }, 100);
          });
        });
      }

      $(".offers-licences-wrapper").find('div.views-row').each(function (i) {
        $(this).addClass('elem-' + i);
        $(this).hide();
      });

      $(".offers-letter-dates-tabs").tabsMapper();
      $(".licence-number-tabs").tabsMapper();

    }
  };
}(jQuery));
