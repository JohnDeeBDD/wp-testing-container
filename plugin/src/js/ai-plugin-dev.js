(function () {
  'use strict';

  /**
   * Hammer Action Feature
   * Handles the hammer button click action
   */ function setupHammerAction() {
      window.hammerAction = function() {
          // Find the comment textarea (Cacbot comment field)
          const commentField = document.querySelector('textarea[name="comment"]') || document.querySelector('#comment') || document.querySelector('.cacbot-comment-field') || document.querySelector('textarea');
          if (!commentField) {
              console.error('Comment field not found');
              return;
          }
          // Append the special action string to the comment field
          const actionString = '[ai-plugin-dev-build-plugin-action-button-pressed]';
          commentField.value = (commentField.value || '') + actionString;
          // Find the submit button by id and click it
          const submitButton = document.getElementById('submit');
          if (submitButton) {
              submitButton.click();
          } else {
              // Fallback: try to find and submit the form directly
              const form = commentField.closest('form');
              if (form) {
                  form.submit();
              } else {
                  console.error('Submit button and comment form not found');
              }
          }
      };
  }

  document.addEventListener("DOMContentLoaded", ()=>{
      setupHammerAction();
  });

})();
//# sourceMappingURL=ai-plugin-dev.js.map
