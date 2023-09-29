
(function ($, Drupal) {
  Drupal.behaviors.siteHistoryTableRow = {
    attach: function attach(context) {
      $('.site-revision a').on('click', function() {
        $(this).parents('tr').unbind('click');
      })

      $('.site-revision-row').on('click', function() {
          $(this).toggleClass('expanded').next().toggleClass('expanded').toggle();
      })

      $('.site-revision-details').on('click', function() {
          console.log($(this).prev());
          $(this).toggle().prev().toggleClass('expanded');
      })
    },
  }
})(jQuery, Drupal);
