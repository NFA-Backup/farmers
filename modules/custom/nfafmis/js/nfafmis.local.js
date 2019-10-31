/**
 * @file
 * Contains local JS for showing/hiding license data.
 */

(function($) {
  Drupal.behaviors.nfamis = {
    attach: function(context, settings) {

      $(context).find("td.views-field").once("nfamis").each(function() {
        let editLink = $(this).find('a');
        if (editLink.length) {
          editLink.addClass('use-ajax');
          editLink.attr("data-dialog-options", '{"width":800}');
          editLink.attr("data-dialog-type", "modal");
        }
      });

      $.fn.tabsMapper = function() {
        $(this).find('div.views-row').each(function(i) {
          $(this).find('a').click(function(e) {
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

      $(".offers-licences-wrapper").find('div.views-row').each(function(i) {
        $(this).addClass('elem-' + i);
        $(this).hide();
      });

      $(".offers-letter-dates-tabs").tabsMapper();
      $(".licence-number-tabs").tabsMapper();

    }
  };
}(jQuery));
