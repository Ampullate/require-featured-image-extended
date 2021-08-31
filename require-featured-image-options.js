jQuery(document).ready(function($) {
  function togglePostTypeDimensions(typeCheckbox) {
    var $postCheckbox = jQuery(typeCheckbox);
    var $sameDimsCheckbox = jQuery("input#rfi_post_types_same_dimensions");
    jQuery("fieldset.dimensions").css("display", "none");
    if ($sameDimsCheckbox.prop("checked")) {
      jQuery("fieldset#dimensions-for-rfi_post_type_all").css("display", "");
    } else {
      jQuery("fieldset#dimensions-for-rfi_post_type_all").css("display", "none");
      jQuery("input.post-type").each(function(e) {
        $postCheckbox = jQuery(this);
        jQuery("fieldset#dimensions-for-" + $postCheckbox.attr("id")).css("display", $postCheckbox.prop("checked") ? "" : "none");
      });
    }
  }
  
  jQuery("input.post-type").each(function(e) {
    togglePostTypeDimensions(this);
  });
  
  jQuery("input.post-type").change(function(e) {
    togglePostTypeDimensions(this);
  });
  jQuery("input#rfi_post_types_same_dimensions").change(function(e) {
    togglePostTypeDimensions(this);
  });
});
