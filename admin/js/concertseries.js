jQuery(function($){
  // Set all variables to be used in scope
  var frame,
      metaBox = $('#bc_flyer.postbox'), // Your meta box id here
      addImgLink = metaBox.find('.bc-upload'),
      delImgLink = metaBox.find('.bc-delete'),
      imgContainer = metaBox.find('.bc-prev-container'),
      imgIdInput = metaBox.find('.bc-flyer-id');

  // ADD IMAGE LINK
  addImgLink.click(function(event) {
    event.preventDefault();

    // If the media frame already exists, reopen it.
    if (frame) {
      frame.open();
      return;
    }

    // Create a new media frame
    frame = wp.media({
      title: 'Flyer hochladen oder auswählen',
      button: {
        text: 'Als Flyer auswählen'
      },
      multiple: false  // Set to true to allow multiple files to be selected
    });

    // When an image is selected in the media frame...
    frame.on('select', function() {
      // Get media attachment details from the frame state
      var attachment = frame.state().get('selection').first().toJSON();

      // Send the attachment URL to our custom image input field.
      imgContainer.append('<img src="'+attachment.sizes.full.url+'" alt="Flyer" style="max-width:100%;"/>');

      // Send the attachment id to our hidden input
      imgIdInput.val(attachment.id);

      // Hide the add image link
      addImgLink.addClass('hidden');

      // Unhide the remove image link
      delImgLink.removeClass('hidden');
    });

    // Finally, open the modal on click
    frame.open();
  });


  // DELETE IMAGE LINK
  delImgLink.click(function(event) {
    event.preventDefault();

    // Clear out the preview image
    imgContainer.html('');

    // Un-hide the add image link
    addImgLink.removeClass('hidden');

    // Hide the delete image link
    delImgLink.addClass('hidden');

    // Delete the image id from the hidden input
    imgIdInput.val('');
  });
});
