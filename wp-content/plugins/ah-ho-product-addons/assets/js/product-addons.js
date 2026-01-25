/**
 * Ah Ho Product Add-ons - Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // ===== GIFT MESSAGE TOGGLE =====
    const $giftCheckbox = $('#ah_ho_is_gift');
    const $giftMessageRow = $('.ah-ho-gift-message-row');
    const $giftTextarea = $('#ah_ho_gift_message');

    // Toggle gift message field visibility
    $giftCheckbox.on('change', function() {
        if ($(this).is(':checked')) {
            $giftMessageRow.slideDown(300, function() {
                $giftTextarea.focus();
            });
        } else {
            $giftMessageRow.slideUp(300);
            $giftTextarea.val(''); // Clear message when unchecked
            updateCharCounter($giftTextarea);
        }
    });

    // Show gift message on page load if checkbox is checked (validation errors)
    if ($giftCheckbox.is(':checked')) {
        $giftMessageRow.show();
    }

    // ===== CHARACTER COUNTERS =====
    const $notesTextarea = $('#ah_ho_product_notes');

    // Update counters on input
    $notesTextarea.on('input', function() {
        updateCharCounter($(this));
    });

    $giftTextarea.on('input', function() {
        updateCharCounter($(this));
    });

    /**
     * Update character counter for a textarea
     */
    function updateCharCounter($textarea) {
        const $counter = $textarea.siblings('.ah-ho-char-counter');
        const current = $textarea.val().length;
        const max = parseInt($textarea.attr('maxlength')) || 300;
        const percent = (current / max) * 100;

        // Update counter text
        $counter.text(current + ' / ' + max + ' characters');

        // Add warning class when approaching limit (>90%)
        if (percent > 90) {
            $counter.addClass('warning');
        } else {
            $counter.removeClass('warning');
        }
    }

    // Initialize counters on page load
    updateCharCounter($notesTextarea);
    updateCharCounter($giftTextarea);

    // ===== VALIDATION ENHANCEMENT =====
    const $addToCartForm = $('form.cart');

    $addToCartForm.on('submit', function(e) {
        let errors = [];

        // Validate product notes if required
        if ($notesTextarea.length && $notesTextarea.prop('required')) {
            const notesValue = $notesTextarea.val().trim();
            if (notesValue === '') {
                errors.push('Please enter your special requests or preferences.');
                $notesTextarea.css('border-color', '#e74c3c');
            }
        }

        // Validate gift message if gift is checked and message is required
        if ($giftCheckbox.is(':checked') && $giftTextarea.attr('data-required') === 'true') {
            const giftValue = $giftTextarea.val().trim();
            if (giftValue === '') {
                errors.push('Please enter a gift message.');
                $giftTextarea.css('border-color', '#e74c3c');
            }
        }

        // Display errors
        if (errors.length > 0) {
            e.preventDefault();

            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.ah-ho-addons-wrapper').offset().top - 100
            }, 500);

            // Show error messages (if WooCommerce notices aren't available)
            alert(errors.join('\n'));

            return false;
        }
    });

    // Remove error styling on input
    $notesTextarea.on('input', function() {
        $(this).css('border-color', '');
    });

    $giftTextarea.on('input', function() {
        $(this).css('border-color', '');
    });

    // ===== ACCESSIBILITY ENHANCEMENTS =====

    // Add ARIA labels
    $giftCheckbox.attr('aria-label', 'Mark this product as a gift');
    $notesTextarea.attr('aria-label', 'Enter special requests or preferences');
    $giftTextarea.attr('aria-label', 'Enter gift message');

    // Add ARIA live region for character counters
    $('.ah-ho-char-counter').attr('aria-live', 'polite');
});
