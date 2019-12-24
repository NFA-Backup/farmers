/**
 * @file
 * Contains local JS for showing/hiding license data.
 */

(function($) {
  Drupal.behaviors.nfamis = {
    attach: function(context, settings) {

      $(".account-tab-view", context).once("nfamis").each(function() {
        var ulist = $("#account-sub-tabs").append('<ul class="nav nav-tabs farmer-tabs"></ul>').find('ul');
         $(context).find(".account-list-tabs").each(function() {
          console.log(this.children[0].innerText);

          if (this.children[0].innerText) {
            let name = this.children[0].innerText;
            let filtered_name = name.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
            let elem = '<a href="#'+filtered_name+'" class="tabs-item">'+name+'</a>';
            ulist.append('<li class="tabs-item">'+elem+'</li>');
          }
         });
      });

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
