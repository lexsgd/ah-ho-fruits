<?php
/**
 * The header template
 *
 * @package Ah_Ho_Fruits
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'ah-ho-fruits'); ?></a>

    <header id="masthead" class="site-header">
        <div class="container">
            <div class="header-inner">
                <!-- Logo -->
                <div class="site-branding">
                    <?php if (has_custom_logo()) : ?>
                        <div class="site-logo">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else : ?>
                        <h1 class="site-title">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                                <?php bloginfo('name'); ?>
                            </a>
                        </h1>
                        <?php
                        $description = get_bloginfo('description', 'display');
                        if ($description) :
                            ?>
                            <p class="site-description"><?php echo $description; ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Navigation -->
                <nav id="site-navigation" class="main-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'fallback_cb'    => false,
                        'container'      => false,
                    ));
                    ?>
                </nav>

                <!-- Header Actions (Cart, Search, Account) -->
                <div class="header-actions">
                    <!-- Search Toggle -->
                    <button class="search-toggle" aria-label="<?php esc_attr_e('Search', 'ah-ho-fruits'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </button>

                    <?php if (function_exists('WC')) : ?>
                        <!-- Account Link -->
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="account-link" aria-label="<?php esc_attr_e('My Account', 'ah-ho-fruits'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>

                        <!-- Mini Cart -->
                        <?php ah_ho_mini_cart(); ?>
                    <?php endif; ?>

                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" aria-label="<?php esc_attr_e('Menu', 'ah-ho-fruits'); ?>">
                        <span class="hamburger">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Form (Hidden by default) -->
        <div class="header-search" aria-hidden="true">
            <div class="container">
                <?php get_search_form(); ?>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <nav id="mobile-navigation" class="mobile-navigation" aria-hidden="true">
        <div class="mobile-nav-inner">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'mobile',
                'menu_id'        => 'mobile-menu',
                'fallback_cb'    => function() {
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'mobile-menu-fallback',
                    ));
                },
                'container'      => false,
            ));
            ?>
        </div>
    </nav>

    <div id="content" class="site-content">
