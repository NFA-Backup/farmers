/**
 * @file
 * Contains local JS for showing/hiding license data.
 */

(function($) {
  Drupal.behaviors.nfamis = {
    attach: function(context, settings) {

      // Whenever a farmer log flag is triggered, make sure views whose content
      // might need to update are also refreshed.
      $('.view-flagged-farmer-log .flag a').on('click', function (e) {
        $(this)
          .closest('.view-farmer-main-tab')
          .find('.view-flagged-farmer-log')
          .trigger('RefreshView');
      });

      $(".sub-areas-planting", context).once("nfamis").each(function() {

        let filterSubAreaTab = $(this).find('.center-container .area-filter-sub-area-tab');
        let filterInventoryTab = $(this).find('.center-container .area-filter-inventory-tab');
        let filterHarvestTab = $(this).find('.center-container .area-filter-harvest-tab');

        // Add area ID filter on sub-area and inventory tab.
        let filterElem = `
        <div class="form-inline views-field">
          <strong>Area ID:</strong>
          <select id="filter-sub-area" class="form-select required form-element form-element--type-select"></select>
        </div>`;
        filterSubAreaTab.append(filterElem);
        filterInventoryTab.append(filterElem);
        filterHarvestTab.append(filterElem);

        // Add sub-area ID filter on inventory tab.
        let filterSubElem = `
        <div class="form-inline views-field">
          <strong>Sub-area ID:</strong>
          <select id="filter-sub-area-id" class="form-select required form-element form-element--type-select"></select>
        </div>`;
        filterInventoryTab.append(filterSubElem);
        filterHarvestTab.append(filterSubElem);

        // Add inventory link.
        let addInventoryElem = `<a class="use-ajax button"
        data-dialog-options="{&quot;width&quot;:800}" data-dialog-type="modal"
        id="add-inventory-btn">Add Inventory</a>`;
        filterInventoryTab.append(addInventoryElem);

        // Add Harvest link.
        let addHarvestElem = `<a class="use-ajax button"
        data-dialog-options="{&quot;width&quot;:800}" data-dialog-type="modal"
        id="add-harvest-btn">Add Harvest</a>`;
        filterHarvestTab.append(addHarvestElem);

        // Event handler for area select list.
        $(this).find('#filter-sub-area').change(function () {
          let filterVal = $(this).children("option:selected").val();

          // Hide the list of sub areas and map before determining which ones to show.
          $(".sub-areas-planting .views-row").hide();
          $('.view-id-sub_areas_planting_status tr').hide();
          $('.farm-map').hide();

          let filtered_name = filterVal.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          let filterClass = $('#' + filtered_name).attr('class');
          let $subareas = $('.view-id-sub_areas_planting_status tr td:first-child').find('span.' + filterClass).parents('tr');

          // Show the subareas and map if the area has subareas.
          $(".sub-areas-planting .views-row").find('span.' + filterClass).parents('.views-row').fadeIn('slow');
          if ($subareas.length !== 0) {
            $subareas.fadeIn('slow');
            $('.farm-map').fadeIn('slow');
          }

          // Remove all subarea select options and create new list based on area selection.
          $('#filter-sub-area-id option').remove();
          $('.view-id-sub_areas_planting_status .views-row')
            .find('span.' + filterClass).parents('.views-col').each(function (index) {
              let subAreaId = $(this).find('span.' + filterClass).attr('data-sub-area-id');

              // Append option in sub-area-id select list.
              if (subAreaId !== undefined) {
                $('.view-id-sub_areas_planting_status .views-row')
                  .find('span.sub-area-id-' + subAreaId).parents('.views-col').fadeIn('slow');
                $('#filter-sub-area-id').append('<option value="' + subAreaId + '">' + subAreaId + '</option>');
                if (index == 0) {
                  $('#filter-sub-area-id').val(subAreaId).change();
                }
              }
              // If there is no option available in sub-area-id.
              let length = $('#filter-sub-area-id > option').length;
              if (length === 0) {
                let inventory_href = '/node/add/inventory?destination=/tree-farmer-overview/inventory';
                let harvest_href = '/node/add/thinning_harvest_details?destination=/tree-farmer-overview/harvest';
                const url_params = new URLSearchParams(window.location.search);
                const title_param = url_params.get('title');
                if (title_param) {
                  inventory_href = inventory_href + '%3Ftitle%3D' + title_param;
                  harvest_href = harvest_href + '%3Ftitle%3D' + title_param;
                }
                $('#add-inventory-btn').attr('href', inventory_href);
                $('#add-harvest-btn').attr('href', harvest_href);
              }
            });
        });

        // Change handler for sub-area-ids.
        $('#filter-sub-area-id').change(function() {
          let filterClass = $(this).val();
          if (filterClass !== undefined) {
            let inventory_href = '/node/add/inventory?destination=/tree-farmer-overview/inventory';
            let harvest_href = '/node/add/thinning_harvest_details?destination=/tree-farmer-overview/harvest';
            const url_params = new URLSearchParams(window.location.search);
            const title_param = url_params.get('title');
            if (title_param) {
              inventory_href = inventory_href + '%3Ftitle%3D' + title_param;
              harvest_href = harvest_href + '%3Ftitle%3D' + title_param;
            }
            inventory_href = inventory_href + '&sub_area_id=' + filterClass;
            harvest_href = harvest_href + '&sub_area_id=' + filterClass;
            $('#add-inventory-btn').attr('href', inventory_href);
            $('#add-harvest-btn').attr('href', harvest_href);
          }
        });

        $(this).find(".views-row").hide();
        $('.view-id-sub_areas_planting_status .views-row').hide();

        // Process default functionality.
        $(this).find(".views-row").each(function(index) {
          let filterVal = $(this).find('span').attr('id');
          let filtered_name = filterVal.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          $(this).find('span').attr('id', filtered_name);
          let filterClass = $(this).find('span').attr('class');

          // Append option in select list.
          $('#filter-sub-area').append('<option value="' + filtered_name + '">' + filterVal + '</option>');

          // Show the first element value as default selected.
          if (index == 0) {
            $('#filter-sub-area').val(filtered_name).change();
            $(this).find('span').parents('.views-row').fadeIn('slow');
            $('.view-id-sub_areas_planting_status .views-row')
              .find('span.' + filterClass).parents('.views-row').fadeIn('slow');
          }
        });
      });

      // Account tab section start from here.
      $(".account-tab-view", context).once("nfamis").each(function() {
        hideElement();
        // Create tabs under account tab.
        let ulist = $("#account-sub-tabs").append('<div class="tabs-wrapper is-horizontal"><ul></ul></div>').find('ul');
        ulist.addClass('tabs tabs--secondary clearfix farmer-tabs');
        // Loop through existing li to create a new one and append them in ul.
        $(context).find(".account-list-tabs").each(function(i, e) {
          let anchorElem = $(this).find('a');

          let name = anchorElem.text();
          let filtered_name = name.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          let listElem = $('<li/>');
          listElem.addClass('tabs__tab account-list-subtabs');
          let tempElem = $('<a href />');
          tempElem.addClass('tabs__link tabs-item');
          $(this).attr('id', filtered_name);
          tempElem.attr('href', '#' + filtered_name);
          tempElem.text(name);
          tempElem.appendTo(listElem);
          listElem.appendTo(ulist);

          // Set active class for first tab by default.
          if (!window.location.hash) {
            tempElem.addClass('is-active');
            window.location = window.location + '#' + filtered_name;
            $('#' + filtered_name).parents('.views-row').fadeIn('slow');
            $('.account-list-subtab-land-rent a').removeClass('is-active');
            $('.account-list-subtab-payments a').removeClass('is-active');
            $('.account-list-subtab-fees a').addClass('is-active');
            $('section.fees').fadeIn('slow');
            $('.views-field-field-itemise-charges').fadeIn('slow');
          }
          // Set active tab based on hash value, like: payment, summary-charges.
          else {
            let filtered_id = window.location.hash;
            if (filtered_name === filtered_id.slice(1)) {
              hideElement();
              $(filtered_id).parents('.views-row').fadeIn('slow');
              $(filtered_id).parents('.view-display-id-block_2').fadeIn('slow');
              $('.account-list-subtabs a').removeClass('is-active');
              $('.account-list-subtab-land-rent a').removeClass('is-active');
              $('.account-list-subtab-payments a').removeClass('is-active');
              $('.account-list-subtab-fees a').addClass('is-active');
              tempElem.addClass('is-active');
            }
          }

          // Bind click event for anchor tag.
          $(tempElem).click(function() {
            if (!$(this).hasClass('is-active')) {
              hideElement();
              let elemToHide = $(this).text().replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
              $('#' + elemToHide).parents('.views-row').fadeIn('slow');
              $('#' + elemToHide).parents('.view-display-id-block_2').fadeIn('slow');
              $('.views-field-field-itemise-charges').hide();
              $('.account-list-subtab-land-rent a').removeClass('is-active');
              $('.account-list-subtabs a').removeClass('is-active');
              $('.account-list-subtab-payments a').removeClass('is-active');
              $('.account-list-subtab-fees a').addClass('is-active');
              $('section.fees').fadeIn('slow');
              $(this).addClass('is-active');
            }
          })

          // Bind click event for anchor tag payments.
          $('.account-list-subtab-payments a').click(function() {
            if (!$(this).hasClass('is-active')) {
              $('section.fees').hide();
              $('section.land-rent').hide();
              $('.account-list-subtab-fees a').removeClass('is-active');
              $('.account-list-subtab-land-rent a').removeClass('is-active');
              $('.views-field-field-itemise-charges').hide();
              $(this).addClass('is-active');
              $('section.payments').fadeIn('slow');
            }
          });
          // Bind click event for anchor tag fees.
          $('.account-list-subtab-fees a').click(function() {
            if (!$(this).hasClass('is-active')) {
              $('section.payments').hide();
              $('section.land-rent').hide();
              $('.account-list-subtab-payments a').removeClass('is-active');
              $('.account-list-subtab-land-rent a').removeClass('is-active');
              $(this).addClass('is-active');
              $('section.fees').fadeIn('slow');
              $('.views-field-field-itemise-charges').fadeIn('slow');
            }
          });
          // Bind click event for anchor tag land-rent.
          $('.account-list-subtab-land-rent a').click(function() {
            if (!$(this).hasClass('is-active')) {
              $('section.payments').hide();
              $('section.fees').hide();
              $('.account-list-subtab-payments a').removeClass('is-active');
              $('.account-list-subtab-fees a').removeClass('is-active');
              $('.views-field-field-itemise-charges').hide();
              $(this).addClass('is-active');
              $('section.land-rent').fadeIn('slow');
            }
          });

        });
      });

      $(context).find("td.views-field").once("nfamis").each(function() {
        let editLink = $(this).find('a');
        if (editLink.length && editLink.hasClass('use-modal')) {
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

      // Add green color if balance is zero.
      $(context).find('.center-container .row .views-field').once("nfamis").each(function() {
        $('div.field-content:contains("UGX 0")').removeClass('balance').addClass('payment');
      });

      $(".offers-letter-dates-tabs").tabsMapper();
      $(".licence-number-tabs").tabsMapper();
    }
  };

  // Default hide all content, then show accordingly.
  function hideElement() {
    $('.account-tab-view .view-content .views-row .field-content .views-row').hide();
    $('.account-tab-view .view-content .views-row .field-content .view-display-id-block_2').hide();
    $('.views-field-field-itemise-charges').hide();
    $('section.land-rent').hide();
    $('section.payments').hide();
  }
}(jQuery));
