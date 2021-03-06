/**
 * @file
 * contentEditable-based in-place editor for plain text content.
 */
(function ($, _, Backbone, Drupal) {

"use strict";

Drupal.edit.editors.direct = Drupal.edit.EditorView.extend({

  // Stores the textual DOM element that is being in-place edited.
  $textElement: null,

  /**
   * {@inheritdoc}
   */
  initialize: function (options) {
    Drupal.edit.EditorView.prototype.initialize.call(this, options);

    var editorModel = this.model;
    var fieldModel = this.fieldModel;

    // Store the original value of this field. Necessary for reverting changes.
    var $textElement = this.$textElement = this.$el.find('.field-item:first');
    editorModel.set('originalValue', $.trim(this.$textElement.text()));

    // Sets the state to 'changed' whenever the value changes
    var previousText = editorModel.get('originalValue');
    $textElement.on('keyup paste', function (event) {
      var currentText = $.trim($textElement.text());
      if (previousText !== currentText) {
        previousText = currentText;
        editorModel.set('currentValue', currentText);
        fieldModel.set('state', 'changed');
      }
    });
  },

  /**
   * {@inheritdoc}
   */
  getEditedElement: function () {
    return this.$textElement;
  },

  /**
   * {@inheritdoc}
   */
  stateChange: function (fieldModel, state) {
    var from = fieldModel.previous('state');
    var to = state;
    switch (to) {
      case 'inactive':
        break;
      case 'candidate':
        if (from !== 'inactive') {
          this.$textElement.removeAttr('contentEditable');
        }
        if (from === 'invalid') {
          this.removeValidationErrors();
        }
        break;
      case 'highlighted':
        break;
      case 'activating':
        // Defer updating the field model until the current state change has
        // propagated, to not trigger a nested state change event.
        _.defer(function () {
          fieldModel.set('state', 'active');
        });
        break;
      case 'active':
        this.$textElement.attr('contentEditable', 'true');
        break;
      case 'changed':
        break;
      case 'saving':
        if (from === 'invalid') {
          this.removeValidationErrors();
        }
        this.save();
        break;
      case 'saved':
        break;
      case 'invalid':
        this.showValidationErrors();
        break;
    }
  },

  /**
   * {@inheritdoc}
   */
  getEditUISettings: function () {
    return { padding: true, unifiedToolbar: false, fullWidthToolbar: false };
  },

  /**
   * {@inheritdoc}
   */
  revert: function () {
    this.$textElement.html(this.model.get('originalValue'));
  }

});

})(jQuery, _, Backbone, Drupal);
