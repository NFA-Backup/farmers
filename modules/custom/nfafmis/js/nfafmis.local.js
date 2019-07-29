/**
 * @file
 * Contains local JS for showing/hiding license data.
 */

(function ($) {
  Drupal.behaviors.nfamis = {
    attach: function (context, settings) {

      // var row =  $('#66').parentsUntil("div.views-row").hide();
      // Enable default tab.
      // $('#tabs li a:not(:first)').addClass('inactive');
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
    }
  };
}(jQuery));
