/**
 * @file
 * Contains local JS for showing/hiding license data.
 */

(function($) {
  Drupal.behaviors.nfamis = {
    attach: function(context, settings) {

      $(".sub-areas-planting", context).once("nfamis").each(function() {

        // A area ID filter on sub-area, inventory tab.
        let filterElem = '<div class="form-inline clearfix"><span class="control-label">Area ID:</span>';
        filterElem+= '<div class="input-group"><input class="form-control ui-autocomplete-input" id="sub-area-filter"><span class="input-group-addon">';
        filterElem+= '<span class="icon glyphicon glyphicon glyphicon-arrow-down"></span></span></div></div>';
        $(this).find('.center-container .area-filter-section').append(filterElem);

        // Add sub-area ID filter on inventroy tab.
        let filterSubElem = '<div class="form-inline clearfix"><span class="control-label">Sub-area ID:</span>';
        filterSubElem+= '<div class="input-group"><input class="form-control ui-autocomplete-input" id="filter-sub-area"><span class="input-group-addon">';
        filterSubElem+= '<span class="icon glyphicon glyphicon glyphicon-arrow-down"></span></span></div></div>';
        $(this).find('.center-container .area-filter-sub-area').append(filterSubElem);


        // Event handler for down arrow.
        $(this).find('span.input-group-addon').click(function() {
          let filterVal = $('#sub-area-filter').val().trim();
          $(".sub-areas-planting .views-row").hide();
          $('.view-id-sub_areas_planting_status .views-row').hide();
          let filtered_name = filterVal.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          let filterClass = $('#'+filtered_name).attr('class');
          $(".sub-areas-planting .views-row").find('span.'+filterClass).parents('.views-row').fadeIn('slow');
          $('.view-id-sub_areas_planting_status .views-row').find('span.'+filterClass).parents('.views-row').fadeIn('slow');
        });

        $(this).find(".views-row").hide();
        $('.view-id-sub_areas_planting_status .views-row').hide();
        $(this).find(".views-row").each(function(index) {
          let filterVal = $(this).find('span').attr('id');
          let filtered_name = filterVal.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          $(this).find('span').attr('id', filtered_name);
          let filterClass = $(this).find('span').attr('class');
          if (index == 0) {
            $('#sub-area-filter').val(filterVal);
            $(this).find('span').parents('.views-row').fadeIn('slow');
            $('.view-id-sub_areas_planting_status .views-row').find('span.'+filterClass).parents('.views-row').fadeIn('slow');
          }
        });
      });

      $(".account-tab-view", context).once("nfamis").each(function() {
        // Default hide all content, then show first as default.
        $('.account-tab-view .view-content .views-row .field-content .views-row').hide();
        // Create tabs under account tab.
        let ulist = $("#account-sub-tabs").append('<ul></ul>').find('ul');
            ulist.addClass('nav nav-tabs farmer-tabs');
        // Loop through existing li to create a new one and append them in ul.
        $(context).find(".account-list-tabs").each(function(i,e) {
          let anchorElem = $(this).find('a');

          let name = anchorElem.text();
          let filtered_name = name.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          let listElem = $('<li/>');
          listElem.addClass('account-list-subtabs');
          let tempElem = $('<a href />');
          tempElem.addClass('tabs-item');
          $(this).attr('id',filtered_name);
          // tempElem.attr('style', 'margin-left: 50px;');
          tempElem.attr('href', '#'+filtered_name);
          tempElem.text(name);
          tempElem.appendTo(listElem);
          listElem.appendTo(ulist);
          // Set active class for first tab by default.
          if (i === 0) {
            tempElem.addClass('active');
            window.location = window.location+'#'+filtered_name;
            $('#'+filtered_name).parents('.views-row').fadeIn('slow');
          }
          // Bind click event for anchor tag.
          $(tempElem).click(function(){
            if (!$(this).hasClass('active')) {
              $('.account-tab-view .view-content .views-row .field-content .views-row').hide();
              let elemToHide = $(this).text().replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
              $('#'+elemToHide).parents('.views-row').fadeIn('slow')
              $('.account-list-subtabs a').removeClass('active');
              $(this).addClass('active');
            }
          })
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
