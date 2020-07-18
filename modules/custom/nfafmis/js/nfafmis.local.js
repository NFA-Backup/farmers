/**
 * @file
 * Contains local JS for showing/hiding license data.
 */

(function($) {
  Drupal.behaviors.nfamis = {
    attach: function(context, settings) {

      $(".sub-areas-planting", context).once("nfamis").each(function() {

        let filterSubAreaTab = $(this).find('.center-container .area-filter-sub-area-tab');
        let filterInventoryTab = $(this).find('.center-container .area-filter-inventory-tab');
        let filterHarvestTab = $(this).find('.center-container .area-filter-harvest-tab');

        // Add area ID filter on sub-area and inventory tab.
        let filterElem = `
        <div class="form-inline views-field">
          <span class="control-label" style="margin-right: 110px;">Area ID:</span>
          <div class="input-group">
            <select id="filter-sub-area" class="form-control ui-autocomplete-input"></select>
          </div>
        </div>`;
        filterSubAreaTab.append(filterElem);
        filterInventoryTab.append(filterElem);
        filterHarvestTab.append(filterElem);

        // Add sub-area ID filter on inventory tab.
        let filterSubElem = `
        <div class="form-inline views-field">
          <span class="control-label" style="margin-right: 82px;">Sub-area ID:</span>
          <div class="input-group">
            <select id="filter-sub-area-id" class="form-control ui-autocomplete-input"></select>
          </div>
        </div>`;
        filterInventoryTab.append(filterSubElem);
        filterHarvestTab.append(filterSubElem);

        // Add inventory link.
        let addInventroyElem = `<a class="btn btn-info btn-xs"
        data-dialog-options="{&quot;width&quot;:800}" data-dialog-type="modal"
        id="add-invenotry-btn">Add Inventory</a>`;
        filterInventoryTab.append(addInventroyElem);

        // Add Harvest link.
        let addHarvestElem = `<a class="btn btn-info btn-xs"
        data-dialog-options="{&quot;width&quot;:800}" data-dialog-type="modal"
        id="add-harvest-btn">Add Harvest</a>`;
        filterHarvestTab.append(addHarvestElem);

        // Event handler for area select list.
        $(this).find('#filter-sub-area').change(function() {
          let filterVal = $(this).children("option:selected").val();

          $(".sub-areas-planting .views-row").hide();
          $('.view-id-sub_areas_planting_status .views-row').hide();

          let filtered_name = filterVal.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          let filterClass = $('#' + filtered_name).attr('class');

          $(".sub-areas-planting .views-row").
          find('span.' + filterClass).parents('.views-row').fadeIn('slow');
          $('.view-id-sub_areas_planting_status .views-row')
            .find('span.' + filterClass).parents('.views-row').fadeIn('slow');

          // Remove all option and create new based on area selection.
          $('#filter-sub-area-id option').remove();
          $('.view-id-sub_areas_planting_status .views-row')
            .find('span.' + filterClass).parents('.views-col').each(function(index) {
              let subAreaId = $(this).find('span.' + filterClass).attr('data-sub-area-id');

              // Append option in sub-area-id select list.
              if (subAreaId !== undefined) {
                $('.view-id-sub_areas_planting_status .views-row')
                  .find('span.sub-area-id-' + subAreaId).parents('.views-row').fadeIn('slow');
                $('#filter-sub-area-id').append('<option value="' + subAreaId + '">' + subAreaId + '</option>');
                if (index == 0) {
                  $('#filter-sub-area-id').val(subAreaId).change();
                }
              }
              // If there is no option availabe in sub-area-id.
              let length = $('#filter-sub-area-id > option').length;
              if (length === 0) {
                $('#add-invenotry-btn').attr('href', '/node/add/inventory?destination=/tree-farmer-overview/inventory');
                $('#add-harvest-btn').attr('href', '/node/add/thinning_harvest_details?destination=/tree-farmer-overview/harvest');
              }
            });
        });

        // Change handler for sub-area-ids.
        $('#filter-sub-area-id').change(function() {
          let filterClass = $(this).val();
          if (filterClass !== undefined) {
            $('#add-invenotry-btn').attr('href', '/node/add/inventory?destination=/tree-farmer-overview/inventory&sub_area_id=' + filterClass);
            $('#add-harvest-btn').attr('href', '/node/add/thinning_harvest_details?destination=/tree-farmer-overview/harvest&sub_area_id=' + filterClass);
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
        let ulist = $("#account-sub-tabs").append('<ul></ul>').find('ul');
        ulist.addClass('nav nav-tabs farmer-tabs');
        // Loop through existing li to create a new one and append them in ul.
        $(context).find(".account-list-tabs").each(function(i, e) {
          let anchorElem = $(this).find('a');

          let name = anchorElem.text();
          let filtered_name = name.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
          let listElem = $('<li/>');
          listElem.addClass('account-list-subtabs');
          let tempElem = $('<a href />');
          tempElem.addClass('tabs-item');
          $(this).attr('id', filtered_name);
          tempElem.attr('href', '#' + filtered_name);
          tempElem.text(name);
          tempElem.appendTo(listElem);
          listElem.appendTo(ulist);

          // Set active class for first tab by default.
          if (!window.location.hash) {
            tempElem.addClass('active');
            window.location = window.location + '#' + filtered_name;
            $('#' + filtered_name).parents('.views-row').fadeIn('slow');
            $('.account-list-subtab-fees a').addClass('active');
            $('section.fees').fadeIn('slow');
          }
          // Set active tab based on hash value, like: payment, summary-charges.
          else {
            let filtered_id = window.location.hash;
            if (filtered_name === filtered_id.slice(1)) {
              hideElement();
              $(filtered_id).parents('.views-row').fadeIn('slow');
              $(filtered_id).parents('.view-display-id-block_2').fadeIn('slow');
              $('.views-field-field-itemise-charges').fadeIn('slow');
              $('.account-list-subtabs a').removeClass('active');
              $('.account-list-subtab-fees a').addClass('active');
              $('section.fees').fadeIn('slow');
              tempElem.addClass('active');
            }
          }

          // Bind click event for anchor tag.
          $(tempElem).click(function() {
            if (!$(this).hasClass('active')) {
              hideElement();
              let elemToHide = $(this).text().replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-').toLowerCase();
              $('#' + elemToHide).parents('.views-row').fadeIn('slow');
              $('#' + elemToHide).parents('.view-display-id-block_2').fadeIn('slow');
              $('.views-field-field-itemise-charges').fadeIn('slow');
              $('.account-list-subtabs a').removeClass('active');
              $('.account-list-subtab-fees a').addClass('active');
              $('section.fees').fadeIn('slow');
              $(this).addClass('active');
            }
          })

          // Bind click event for anchor tag fees.
          $('.account-list-subtab-fees a').click(function() {
            if (!$(this).hasClass('active')) {
              $('section.land-rent').hide();
              $('.account-list-subtab-land-rent a').removeClass('active');
              $(this).addClass('active');
              $('section.fees').fadeIn('slow');
              $('.views-field-field-itemise-charges').fadeIn('slow');
            }
          });
          // Bind click event for anchor tag land-rent.
          $('.account-list-subtab-land-rent a').click(function() {
            if (!$(this).hasClass('active')) {
              $('section.fees').hide();
              $('.account-list-subtab-fees a').removeClass('active');
              $('.views-field-field-itemise-charges').hide();
              $(this).addClass('active');
              $('section.land-rent').fadeIn('slow');
            }
          });

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

  // Default hide all content, then show accordingly.
  function hideElement() {
    $('.account-tab-view .view-content .views-row .field-content .views-row').hide();
    $('.account-tab-view .view-content .views-row .field-content .view-display-id-block_2').hide();
    $('.views-field-field-itemise-charges').hide();
    $('section.fees').hide();
    $('section.land-rent').hide();
  }
}(jQuery));
