jQuery(document).ready(function($) {
  var imageIsLoaded = [];
  var imageIsTooSmall = [];
  
  $('#postimagediv h2').append(' <sup style="color: red; font-weight: bold;">*</sup>');

  function isGutenberg() {
    return ($('.block-editor-writing-flow').length > 0);
  }

  function checkImageReturnWarningMessageOrEmpty() {
    if (isGutenberg()) {
      var $img = $('.editor-post-featured-image').find('img');
    } else {
      var $img = $('#postimagediv').find('img');
    }
    if ($img.length === 0) {
      imageIsLoaded = [];
      imageIsTooSmall = [];
      return passedFromServer.jsWarningHtml;
    }
    var imagePath = getImagePath($img);
    checkPassedImageIsTooSmall($img);
    if (imageIsLoaded[imagePath] && imageIsTooSmall[imagePath]) {
      return passedFromServer.jsSmallHtml;
    }
    return '';
  }
  
  function getImagePath($img) {
    return $img[0].src.replace(/-\d+[Xx]\d+\./g, ".");
  }

  function checkPassedImageIsTooSmall($img) {
    var imagePath = getImagePath($img);
    var featuredImage = new Image();
    featuredImage.src = imagePath;
    featuredImage.onload = function(e) {
      imageIsLoaded[imagePath] = true;
      imageIsTooSmall[imagePath] = featuredImage.width < passedFromServer.width || featuredImage.height < passedFromServer.height;
    }
  }

  function disablePublishAndWarn(message) {
    createMessageAreaIfNeeded();
    $('#nofeature-message').addClass("error").html('<p>' + message + '</p>');
    if ($('#postimagediv .inside .image-required').length==0) {
      $('#postimagediv .inside').prepend('<div class="image-required" style="color: red; font-weight: bold;">' + passedFromServer.jsImageRequired + '</div>');
    }
    if (isGutenberg()) {
      $('.editor-post-publish-panel__toggle').attr('disabled', 'disabled');
    } else {
      $('#publish').attr('disabled', 'disabled');
    }
  }

  function clearWarningAndEnablePublish() {
    $('#nofeature-message').remove();
    $('#postimagediv .inside .acf-required').remove();
    if (isGutenberg()) {
      $('.editor-post-publish-panel__toggle').removeAttr('disabled');
    } else {
      $('#publish').removeAttr('disabled');
    }
  }
  function createMessageAreaIfNeeded() {
    if ($('body').find("#nofeature-message").length === 0) {
      if (isGutenberg()) {
        $('.components-notice-list').append('<div id="nofeature-message"></div>');
      } else {
        $('#post').before('<div id="nofeature-message"></div>');
      }
    }
  }

  function detectWarnFeaturedImage() {
    if (checkImageReturnWarningMessageOrEmpty()) {
      disablePublishAndWarn(checkImageReturnWarningMessageOrEmpty());
    } else {
      clearWarningAndEnablePublish();
    }
  }

  detectWarnFeaturedImage();
  setInterval(detectWarnFeaturedImage, 800);
});
