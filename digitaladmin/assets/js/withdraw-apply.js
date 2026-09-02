/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }
  // Variable declaration for table
  var dt_user_table = $('.datatables-withdrawapply');

  // Users datatable
  if (dt_user_table.length) {
    var dt_user = dt_user_table.DataTable({
      destroy: true,
      order: [],
      columnDefs: [
        {
          targets: 'no-sort',  // Apply to columns with the 'no-sort' class
          orderable: false      // Disable sorting for those columns
        }
      ],
      dom:
        '<"row"' +
        '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<"me-4"f>>>' +
        '>t' +
        '<"row p-5"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: 'Show _MENU_',
        search: '',
        searchPlaceholder: 'Search User',
        paginate: {
          next: '<i class="ri-arrow-right-s-line"></i>',
          previous: '<i class="ri-arrow-left-s-line"></i>'
        }
      },
      initComplete: function () {
          $(dt_user_table).find('th').removeClass('sorting sorting_asc sorting_desc');
      }
    });
    // Remove sorting classes dynamically after DataTable is initialized
    
  }
});
