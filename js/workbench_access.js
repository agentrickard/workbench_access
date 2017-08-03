
(function ($) {

  'use strict';

  Drupal.behaviors.workbenchAccess = {
    attach: function (context, settings) {
      $('#edit-add', context).once('field-switch').each(function () {
        // We hide mass assign.
        $('.form-item-editors-add-mass').hide();
        $(this).find('.switch').click(function(e) {
          $('.form-item-editors-add').toggle();
          $('.form-item-editors-add-mass').toggle();
        })
      });
    }
  };

})(jQuery, drupalSettings);
