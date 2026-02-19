/**
 * Ah Ho Fruit - Main JavaScript
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        AhHoFruits.init();
    });

    // Main object
    var AhHoFruits = {
        init: function() {
            this.mobileMenu();
            this.searchToggle();
            this.stickyHeader();
            this.miniCartUpdate();
            this.smoothScroll();
            this.productGallery();
        },

        // Mobile Menu
        mobileMenu: function() {
            var $toggle = $('.mobile-menu-toggle');
            var $nav = $('.mobile-navigation');
            var $body = $('body');
            var $overlay = $('<div class="mobile-overlay"></div>');

            // Add overlay to body
            $body.append($overlay);

            // Toggle mobile menu
            $toggle.on('click', function(e) {
                e.preventDefault();
                $nav.toggleClass('active');
                $overlay.toggleClass('active');
                $body.toggleClass('menu-open');

                // Toggle hamburger animation
                $(this).find('.hamburger').toggleClass('active');
            });

            // Close on overlay click
            $overlay.on('click', function() {
                $nav.removeClass('active');
                $overlay.removeClass('active');
                $body.removeClass('menu-open');
                $toggle.find('.hamburger').removeClass('active');
            });

            // Close on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $nav.hasClass('active')) {
                    $nav.removeClass('active');
                    $overlay.removeClass('active');
                    $body.removeClass('menu-open');
                    $toggle.find('.hamburger').removeClass('active');
                }
            });
        },

        // Search Toggle
        searchToggle: function() {
            var $toggle = $('.search-toggle');
            var $search = $('.header-search');

            $toggle.on('click', function(e) {
                e.preventDefault();
                $search.toggleClass('active');

                if ($search.hasClass('active')) {
                    $search.find('input[type="search"]').focus();
                }
            });

            // Close on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $search.hasClass('active')) {
                    $search.removeClass('active');
                }
            });
        },

        // Sticky Header
        stickyHeader: function() {
            var $header = $('.site-header');
            var headerOffset = $header.offset().top;
            var lastScrollTop = 0;

            $(window).on('scroll', function() {
                var scrollTop = $(this).scrollTop();

                if (scrollTop > headerOffset) {
                    $header.addClass('sticky');

                    // Hide/show on scroll direction
                    if (scrollTop > lastScrollTop && scrollTop > 200) {
                        $header.addClass('hidden');
                    } else {
                        $header.removeClass('hidden');
                    }
                } else {
                    $header.removeClass('sticky hidden');
                }

                lastScrollTop = scrollTop;
            });
        },

        // Mini Cart Update (AJAX)
        miniCartUpdate: function() {
            $(document.body).on('added_to_cart removed_from_cart', function() {
                $.ajax({
                    url: ahHoAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ah_ho_update_cart_count',
                        nonce: ahHoAjax.nonce
                    },
                    success: function(response) {
                        $('.cart-count').text(response);

                        // Add animation
                        $('.cart-count').addClass('pulse');
                        setTimeout(function() {
                            $('.cart-count').removeClass('pulse');
                        }, 300);
                    }
                });
            });
        },

        // Smooth Scroll
        smoothScroll: function() {
            $('a[href*="#"]:not([href="#"])').on('click', function(e) {
                if (
                    location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') &&
                    location.hostname === this.hostname
                ) {
                    var target = $(this.hash);
                    target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');

                    if (target.length) {
                        e.preventDefault();
                        $('html, body').animate({
                            scrollTop: target.offset().top - 100
                        }, 800);
                    }
                }
            });
        },

        // Product Gallery (for single product)
        productGallery: function() {
            // Thumbnail click handler
            $('.woocommerce-product-gallery__image').on('click', function(e) {
                // WooCommerce handles this, but we can add custom behavior
            });
        }
    };

    // Export for potential extensions
    window.AhHoFruits = AhHoFruits;

})(jQuery);

// CSS Animation for cart count
var style = document.createElement('style');
style.textContent = `
    .cart-count.pulse {
        animation: pulse 0.3s ease;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }

    .site-header.sticky {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        animation: slideDown 0.3s ease;
    }

    .site-header.hidden {
        transform: translateY(-100%);
    }

    @keyframes slideDown {
        from { transform: translateY(-100%); }
        to { transform: translateY(0); }
    }

    body.menu-open {
        overflow: hidden;
    }
`;
document.head.appendChild(style);
