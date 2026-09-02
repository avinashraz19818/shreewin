/**
 * UI Navbar
 */
'use strict';

(function () {
  // If layout is RTL add .dropdown-menu-end class to .dropdown-menu
  if (isRtl) {
    Helpers._addClass('dropdown-menu-end', document.querySelectorAll('.dropdown-menu'));
  }

  // dkwin26 dropdown
  const dkwin26Dropdown = document.querySelectorAll('.nav-link.dkwin26-dropdown');
  if (dkwin26Dropdown) {
    dkwin26Dropdown.forEach(e => {
      new dkwin26Dropdown(e);
    });
  }
})();
