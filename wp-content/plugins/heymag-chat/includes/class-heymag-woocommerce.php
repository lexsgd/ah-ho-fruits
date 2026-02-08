<?php
/**
 * HeyMag WooCommerce Integration
 *
 * @package HeyMag_Chat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce integration class
 */
class HeyMag_WooCommerce {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * API instance
     */
    private $api;

    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->api = new HeyMag_API();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        $settings = HeyMag_Chat::get_settings();

        // Only register hooks if WooCommerce sync is enabled
        if (empty($settings['woocommerce_sync_enabled'])) {
            return;
        }

        // Product hooks
        add_action('woocommerce_new_product', array($this, 'on_product_created'), 10, 2);
        add_action('woocommerce_update_product', array($this, 'on_product_updated'), 10, 2);
        add_action('before_delete_post', array($this, 'on_product_deleted'));
        add_action('wp_trash_post', array($this, 'on_product_trashed'));
        add_action('untrash_post', array($this, 'on_product_restored'));

        // Inventory hooks
        add_action('woocommerce_product_set_stock', array($this, 'on_stock_changed'));
        add_action('woocommerce_variation_set_stock', array($this, 'on_stock_changed'));

        // Category hooks
        add_action('created_product_cat', array($this, 'on_category_changed'));
        add_action('edited_product_cat', array($this, 'on_category_changed'));
        add_action('delete_product_cat', array($this, 'on_category_changed'));

        // Order hooks — push order events to HeyMag
        add_action('woocommerce_new_order', array($this, 'on_order_created'), 10, 2);
        add_action('woocommerce_order_status_changed', array($this, 'on_order_status_changed'), 10, 4);
        add_action('woocommerce_order_refunded', array($this, 'on_order_refunded'), 10, 2);
    }

    /**
     * Product created
     *
     * @param int $product_id Product ID
     * @param WC_Product $product Product object
     */
    public function on_product_created($product_id, $product = null) {
        if (!$product) {
            $product = wc_get_product($product_id);
        }

        if (!$this->should_sync_product($product)) {
            return;
        }

        $this->sync_product($product, 'product.created');
    }

    /**
     * Product updated
     *
     * @param int $product_id Product ID
     * @param WC_Product $product Product object
     */
    public function on_product_updated($product_id, $product = null) {
        if (!$product) {
            $product = wc_get_product($product_id);
        }

        if (!$this->should_sync_product($product)) {
            return;
        }

        $this->sync_product($product, 'product.updated');
    }

    /**
     * Product deleted
     *
     * @param int $post_id Post ID
     */
    public function on_product_deleted($post_id) {
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        $this->api->send_webhook('product.deleted', array(
            'id' => $post_id,
            'source' => 'woocommerce',
        ));
    }

    /**
     * Product trashed
     *
     * @param int $post_id Post ID
     */
    public function on_product_trashed($post_id) {
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        $this->api->send_webhook('product.deleted', array(
            'id' => $post_id,
            'source' => 'woocommerce',
            'trashed' => true,
        ));
    }

    /**
     * Product restored from trash
     *
     * @param int $post_id Post ID
     */
    public function on_product_restored($post_id) {
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        $product = wc_get_product($post_id);
        if ($product && $this->should_sync_product($product)) {
            $this->sync_product($product, 'product.restored');
        }
    }

    /**
     * Stock changed
     *
     * @param WC_Product $product Product object
     */
    public function on_stock_changed($product) {
        $settings = HeyMag_Chat::get_settings();

        if (empty($settings['woocommerce_sync_inventory'])) {
            return;
        }

        if (!$this->should_sync_product($product)) {
            return;
        }

        // Send lightweight inventory update
        $this->api->send_webhook('product.updated', array(
            'id' => $product->get_id(),
            'source' => 'woocommerce',
            'inventory_only' => true,
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'manage_stock' => $product->get_manage_stock(),
        ));
    }

    /**
     * Category changed
     *
     * @param int $term_id Term ID
     */
    public function on_category_changed($term_id) {
        // Queue full sync when categories change
        if (!wp_next_scheduled('heymag_sync_products')) {
            wp_schedule_single_event(time() + 60, 'heymag_sync_products');
        }
    }

    /**
     * Check if product should be synced
     *
     * @param WC_Product $product Product object
     * @return bool
     */
    private function should_sync_product($product) {
        if (!$product instanceof WC_Product) {
            return false;
        }

        $settings = HeyMag_Chat::get_settings();

        // Check if we should sync drafts
        if ($product->get_status() !== 'publish') {
            return !empty($settings['woocommerce_sync_drafts']);
        }

        // Check category filter
        $sync_categories = $settings['woocommerce_sync_categories'] ?? 'all';

        if ($sync_categories !== 'all' && is_array($sync_categories)) {
            $product_categories = $product->get_category_ids();
            $intersect = array_intersect($product_categories, $sync_categories);
            if (empty($intersect)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sync single product
     *
     * @param WC_Product $product Product object
     * @param string $topic Webhook topic
     */
    private function sync_product($product, $topic) {
        $data = $this->format_product($product);
        $this->api->send_webhook($topic, $data);
    }

    /**
     * Format product data for API
     *
     * @param WC_Product $product Product object
     * @return array
     */
    private function format_product($product) {
        $settings = HeyMag_Chat::get_settings();

        $data = array(
            'id' => $product->get_id(),
            'source' => 'woocommerce',
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'currency' => get_woocommerce_currency(),
            'url' => $product->get_permalink(),
            'categories' => $this->get_category_names($product),
            'tags' => $this->get_tag_names($product),
            'attributes' => $this->get_attribute_data($product),
            'updated_at' => $product->get_date_modified()
                ? $product->get_date_modified()->date('c')
                : current_time('c'),
        );

        // Include description if enabled
        if (!empty($settings['woocommerce_sync_descriptions'])) {
            $data['description'] = $product->get_description();
            $data['short_description'] = $product->get_short_description();
        }

        // Include images if enabled
        if (!empty($settings['woocommerce_sync_images'])) {
            $data['images'] = $this->get_product_images($product);
        }

        // Include inventory if enabled
        if (!empty($settings['woocommerce_sync_inventory'])) {
            $data['stock_quantity'] = $product->get_stock_quantity();
            $data['stock_status'] = $product->get_stock_status();
            $data['manage_stock'] = $product->get_manage_stock();
            $data['backorders_allowed'] = $product->backorders_allowed();
        }

        // Include variations for variable products
        if ($product->is_type('variable')) {
            $data['variations'] = $this->get_variations($product);
        }

        return $data;
    }

    /**
     * Get category names
     *
     * @param WC_Product $product Product object
     * @return array
     */
    private function get_category_names($product) {
        $category_ids = $product->get_category_ids();
        $categories = array();

        foreach ($category_ids as $cat_id) {
            $term = get_term($cat_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $categories[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }

        return $categories;
    }

    /**
     * Get tag names
     *
     * @param WC_Product $product Product object
     * @return array
     */
    private function get_tag_names($product) {
        $tag_ids = $product->get_tag_ids();
        $tags = array();

        foreach ($tag_ids as $tag_id) {
            $term = get_term($tag_id, 'product_tag');
            if ($term && !is_wp_error($term)) {
                $tags[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }

        return $tags;
    }

    /**
     * Get attribute data
     *
     * @param WC_Product $product Product object
     * @return array
     */
    private function get_attribute_data($product) {
        $attributes = array();
        $product_attributes = $product->get_attributes();

        foreach ($product_attributes as $attribute) {
            if ($attribute instanceof WC_Product_Attribute) {
                $attributes[] = array(
                    'name' => $attribute->get_name(),
                    'options' => $attribute->get_options(),
                    'visible' => $attribute->get_visible(),
                    'variation' => $attribute->get_variation(),
                );
            }
        }

        return $attributes;
    }

    /**
     * Get product images
     *
     * @param WC_Product $product Product object
     * @return array
     */
    private function get_product_images($product) {
        $images = array();

        // Main image
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $images[] = array(
                'id' => $main_image_id,
                'url' => wp_get_attachment_url($main_image_id),
                'alt' => get_post_meta($main_image_id, '_wp_attachment_image_alt', true),
                'position' => 0,
            );
        }

        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        $position = 1;
        foreach ($gallery_ids as $image_id) {
            $images[] = array(
                'id' => $image_id,
                'url' => wp_get_attachment_url($image_id),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                'position' => $position++,
            );
        }

        return $images;
    }

    /**
     * Get variations for variable product
     *
     * @param WC_Product $product Variable product
     * @return array
     */
    private function get_variations($product) {
        if (!$product->is_type('variable')) {
            return array();
        }

        $variations = array();
        $variation_ids = $product->get_children();

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }

            $variations[] = array(
                'id' => $variation_id,
                'sku' => $variation->get_sku(),
                'price' => $variation->get_price(),
                'regular_price' => $variation->get_regular_price(),
                'sale_price' => $variation->get_sale_price(),
                'stock_quantity' => $variation->get_stock_quantity(),
                'stock_status' => $variation->get_stock_status(),
                'attributes' => $variation->get_variation_attributes(),
                'image' => $variation->get_image_id()
                    ? wp_get_attachment_url($variation->get_image_id())
                    : null,
            );
        }

        return $variations;
    }

    // =====================================================
    // Order Event Handlers
    // =====================================================

    /**
     * Order created
     *
     * @param int $order_id Order ID
     * @param WC_Order $order Order object
     */
    public function on_order_created($order_id, $order = null) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }

        $this->push_order_event('order.created', $order);
    }

    /**
     * Order status changed
     *
     * @param int $order_id Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @param WC_Order $order Order object
     */
    public function on_order_status_changed($order_id, $old_status, $new_status, $order = null) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }

        $this->push_order_event('order.status_changed', $order, array(
            'old_status' => $old_status,
            'new_status' => $new_status,
        ));
    }

    /**
     * Order refunded
     *
     * @param int $order_id Order ID
     * @param int $refund_id Refund ID
     */
    public function on_order_refunded($order_id, $refund_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->push_order_event('order.refunded', $order, array(
            'refund_id' => $refund_id,
        ));
    }

    /**
     * Push order event to HeyMag events endpoint
     *
     * @param string $event Event type
     * @param WC_Order $order Order object
     * @param array $extra Extra data
     */
    private function push_order_event($event, $order, $extra = array()) {
        $settings = HeyMag_Chat::get_settings();

        if (empty($settings['business_id'])) {
            return;
        }

        $data = array_merge(array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'billing_phone' => $order->get_billing_phone(),
            'billing_email' => $order->get_billing_email(),
            'billing_first_name' => $order->get_billing_first_name(),
            'billing_last_name' => $order->get_billing_last_name(),
            'customer_id' => $order->get_customer_id(),
        ), $extra);

        // Send to HeyMag events endpoint
        $api_url = HEYMAG_API_URL . '/integrations/woocommerce/events';
        $payload = array(
            'event' => $event,
            'store_url' => get_site_url(),
            'business_id' => sanitize_text_field($settings['business_id']),
            'data' => $data,
            'timestamp' => current_time('c'),
        );

        wp_remote_post($api_url, array(
            'timeout' => 10,
            'blocking' => false, // Non-blocking — don't slow down WooCommerce
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        ));
    }

    /**
     * Sync all products
     *
     * @param bool $force Force sync even if recently synced
     * @return array Sync result
     */
    public function sync_all_products($force = false) {
        $settings = HeyMag_Chat::get_settings();

        if (empty($settings['woocommerce_sync_enabled'])) {
            return array(
                'success' => false,
                'message' => 'WooCommerce sync is disabled',
            );
        }

        // Check last sync time
        $last_sync = get_option('heymag_last_product_sync');
        if (!$force && $last_sync && (time() - $last_sync) < 300) {
            return array(
                'success' => false,
                'message' => 'Recently synced. Try again in a few minutes.',
            );
        }

        // Get products to sync
        $args = array(
            'status' => empty($settings['woocommerce_sync_drafts']) ? 'publish' : array('publish', 'draft'),
            'limit' => -1,
            'return' => 'objects',
        );

        // Filter by category if specified
        if (!empty($settings['woocommerce_sync_categories']) &&
            $settings['woocommerce_sync_categories'] !== 'all') {
            $args['category'] = $settings['woocommerce_sync_categories'];
        }

        $products = wc_get_products($args);
        $synced = 0;
        $failed = 0;
        $errors = array();

        foreach ($products as $product) {
            if (!$this->should_sync_product($product)) {
                continue;
            }

            $result = $this->api->send_webhook('product.updated', $this->format_product($product));

            if (is_wp_error($result)) {
                $failed++;
                $errors[] = $product->get_name() . ': ' . $result->get_error_message();
            } else {
                $synced++;
            }

            // Rate limiting
            usleep(100000); // 100ms delay between requests
        }

        // Update last sync time
        update_option('heymag_last_product_sync', time());

        HeyMag_Core::log("Product sync complete: {$synced} synced, {$failed} failed", 'info');

        return array(
            'success' => $failed === 0,
            'synced' => $synced,
            'failed' => $failed,
            'total' => count($products),
            'errors' => $errors,
        );
    }

    /**
     * Get product count by status
     *
     * @return array
     */
    public function get_product_counts() {
        $counts = wp_count_posts('product');

        return array(
            'publish' => isset($counts->publish) ? (int) $counts->publish : 0,
            'draft' => isset($counts->draft) ? (int) $counts->draft : 0,
            'pending' => isset($counts->pending) ? (int) $counts->pending : 0,
            'private' => isset($counts->private) ? (int) $counts->private : 0,
            'total' => array_sum((array) $counts),
        );
    }
}
