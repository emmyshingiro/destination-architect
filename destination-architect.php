<?php
/**
 * Plugin Name: Destination Architect
 * Description: Create reusable tourism destination pages with safari, attraction, and blog layouts.
 * Version: 1.0.0
 * Author: Emmy Shingiro
 * Author URI: https://unsplash.com/@eshingiro
 * Organization: Isange Digital
 * Organization URI: https://isangedigital.com/
 * Text Domain: destination-architect
 */

if (!defined('ABSPATH')) {
    exit;
}

final class WTA_Destination_Builder {
    const POST_TYPE = 'wta_destination';
    const NONCE_ACTION = 'wta_save_destination';
    const NONCE_NAME = 'wta_destination_nonce';

    private static $instance;

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_destination'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('the_content', array($this, 'render_destination_content'), 20);
        add_shortcode('wta_destination', array($this, 'destination_shortcode'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => __('Destinations', 'destination-architect'),
                'singular_name' => __('Destination', 'destination-architect'),
                'add_new' => __('Add New Destination', 'destination-architect'),
                'add_new_item' => __('Add New Destination', 'destination-architect'),
                'edit_item' => __('Edit Destination', 'destination-architect'),
                'new_item' => __('New Destination', 'destination-architect'),
                'view_item' => __('View Destination', 'destination-architect'),
                'search_items' => __('Search Destinations', 'destination-architect'),
                'not_found' => __('No destinations found.', 'destination-architect'),
                'menu_name' => __('Destinations', 'destination-architect'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-location-alt',
            'supports' => array('title', 'thumbnail', 'excerpt'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'destinations'),
        ));
    }

    public function add_meta_boxes() {
        add_meta_box(
            'wta-destination-details',
            __('Destination Page Content', 'destination-architect'),
            array($this, 'render_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        if (!$screen || self::POST_TYPE !== $screen->post_type || !in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'wta-destination-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        wp_enqueue_style(
            'wta-destination-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            array(),
            '1.0.0'
        );
    }

    public function enqueue_frontend_assets() {
        if (!is_singular(self::POST_TYPE)) {
            return;
        }

        wp_enqueue_style(
            'wta-destination-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap',
            array(),
            null
        );
        wp_enqueue_style(
            'wta-destination',
            plugin_dir_url(__FILE__) . 'assets/frontend.css',
            array('wta-destination-fonts'),
            '1.0.0'
        );
        wp_enqueue_script(
            'wta-destination',
            plugin_dir_url(__FILE__) . 'assets/frontend.js',
            array(),
            '1.0.0',
            true
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        $data = $this->get_destination_data($post->ID);
        ?>
        <div class="wta-admin">
            <p class="description"><?php esc_html_e('Add the content for this destination. Use the image picker to choose images from your Media Library.', 'destination-architect'); ?></p>
            <h2><?php esc_html_e('Hero', 'destination-architect'); ?></h2>
            <?php $this->text_field('hero_label', __('Small hero label', 'destination-architect'), $data['hero_label']); ?>
            <?php $this->text_field('hero_title', __('Hero title', 'destination-architect'), $data['hero_title'], __('Leave empty to use “Explore the Beauty of [Destination]”.', 'destination-architect')); ?>
            <?php $this->textarea_field('hero_intro', __('Hero introduction', 'destination-architect'), $data['hero_intro']); ?>
            <?php $this->repeater('hero_slides', __('Hero images', 'destination-architect'), $data['hero_slides'], array(
                'image' => __('Image', 'destination-architect'),
                'alt' => __('Image description (alt text)', 'destination-architect'),
            ), 'image'); ?>

            <h2><?php esc_html_e('Destination Overview', 'destination-architect'); ?></h2>
            <?php $this->text_field('overview_label', __('Small heading label', 'destination-architect'), $data['overview_label']); ?>
            <?php $this->text_field('overview_title', __('Overview heading', 'destination-architect'), $data['overview_title']); ?>
            <?php $this->textarea_field('overview_text', __('Overview text', 'destination-architect'), $data['overview_text'], __('Basic paragraph and link HTML is allowed.', 'destination-architect'), 8); ?>
            <?php $this->text_field('highlights', __('Highlights (separate with commas)', 'destination-architect'), implode(', ', $data['highlights'])); ?>

            <h2><?php esc_html_e('Tours', 'destination-architect'); ?></h2>
            <?php $this->text_field('tours_title', __('Tours section heading', 'destination-architect'), $data['tours_title']); ?>
            <?php $this->textarea_field('tours_intro', __('Tours section introduction', 'destination-architect'), $data['tours_intro']); ?>
            <?php $this->repeater('tours', __('Tour cards', 'destination-architect'), $data['tours'], array(
                'title' => __('Tour name', 'destination-architect'),
                'image' => __('Image', 'destination-architect'),
                'description' => __('Short description', 'destination-architect'),
                'duration' => __('Duration', 'destination-architect'),
                'url' => __('Tour link', 'destination-architect'),
            ), 'title'); ?>

            <h2><?php esc_html_e('Experiences & Attractions', 'destination-architect'); ?></h2>
            <?php $this->text_field('attractions_title', __('Section heading', 'destination-architect'), $data['attractions_title']); ?>
            <?php $this->textarea_field('attractions_intro', __('Section introduction', 'destination-architect'), $data['attractions_intro']); ?>
            <?php $this->repeater('attractions', __('Experience / attraction cards', 'destination-architect'), $data['attractions'], array(
                'title' => __('Name', 'destination-architect'),
                'image' => __('Image', 'destination-architect'),
                'description' => __('Short description', 'destination-architect'),
                'details' => __('More details (optional)', 'destination-architect'),
            ), 'title'); ?>

            <h2><?php esc_html_e('Destination Blog', 'destination-architect'); ?></h2>
            <p><?php esc_html_e('Select one or more existing post categories. The latest six published posts from those categories will appear on this page.', 'destination-architect'); ?></p>
            <?php $this->render_category_checkboxes($data['blog_categories']); ?>
        </div>
        <?php
    }

    private function text_field($key, $label, $value, $help = '') {
        ?>
        <p class="wta-field">
            <label for="wta-<?php echo esc_attr($key); ?>"><strong><?php echo esc_html($label); ?></strong></label>
            <input type="text" id="wta-<?php echo esc_attr($key); ?>" name="wta_meta[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" class="widefat">
            <?php if ($help) : ?><span class="description"><?php echo esc_html($help); ?></span><?php endif; ?>
        </p>
        <?php
    }

    private function textarea_field($key, $label, $value, $help = '', $rows = 4) {
        ?>
        <p class="wta-field">
            <label for="wta-<?php echo esc_attr($key); ?>"><strong><?php echo esc_html($label); ?></strong></label>
            <textarea id="wta-<?php echo esc_attr($key); ?>" name="wta_meta[<?php echo esc_attr($key); ?>]" rows="<?php echo esc_attr($rows); ?>" class="widefat"><?php echo esc_textarea($value); ?></textarea>
            <?php if ($help) : ?><span class="description"><?php echo esc_html($help); ?></span><?php endif; ?>
        </p>
        <?php
    }

    private function repeater($key, $label, $items, $fields, $title_key) {
        ?>
        <div class="wta-repeater" data-repeater="<?php echo esc_attr($key); ?>">
            <h3><?php echo esc_html($label); ?></h3>
            <div class="wta-repeater-items">
                <?php foreach ($items as $index => $item) : ?>
                    <?php $this->repeater_item($key, $index, $item, $fields, $title_key); ?>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button wta-add-item"><?php esc_html_e('Add item', 'destination-architect'); ?></button>
            <script type="text/html" class="wta-repeater-template"><?php
                $this->repeater_item($key, '__INDEX__', array(), $fields, $title_key);
            ?></script>
        </div>
        <?php
    }

    private function repeater_item($group, $index, $item, $fields, $title_key) {
        $item = is_array($item) ? $item : array();
        $title = isset($item[$title_key]) ? $item[$title_key] : '';
        if ('image' === $title_key) {
            $title = __('Hero image', 'destination-architect');
        }
        ?>
        <div class="wta-repeater-item">
            <div class="wta-repeater-heading">
                <strong><?php echo esc_html($title ? $title : __('New item', 'destination-architect')); ?></strong>
                <button type="button" class="button-link-delete wta-remove-item"><?php esc_html_e('Remove', 'destination-architect'); ?></button>
            </div>
            <?php foreach ($fields as $field => $label) :
                $value = isset($item[$field]) ? $item[$field] : '';
                $name = 'wta_meta[' . $group . '][' . $index . '][' . $field . ']';
                ?>
                <p class="wta-field">
                    <label><strong><?php echo esc_html($label); ?></strong></label>
                    <?php if ('image' === $field) :
                        $image_id = absint($value);
                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                        ?>
                        <input type="hidden" class="wta-image-id" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($image_id); ?>">
                        <span class="wta-image-preview"><?php if ($image_url) : ?><img src="<?php echo esc_url($image_url); ?>" alt=""><?php endif; ?></span>
                        <button type="button" class="button wta-select-image"><?php esc_html_e('Choose image', 'destination-architect'); ?></button>
                        <button type="button" class="button wta-clear-image"><?php esc_html_e('Clear', 'destination-architect'); ?></button>
                    <?php elseif (in_array($field, array('description', 'details'), true)) : ?>
                        <textarea name="<?php echo esc_attr($name); ?>" rows="3" class="widefat"><?php echo esc_textarea($value); ?></textarea>
                    <?php else : ?>
                        <input type="<?php echo 'url' === $field ? 'url' : 'text'; ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="widefat">
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_category_checkboxes($selected) {
        $categories = array(
            'rwanda-safaris' => 'Rwanda Safaris',
            'uganda-safaris' => 'Uganda Safaris',
            'tanzania-safaris' => 'Tanzania Safaris',
            'kenya-safaris' => 'Kenya Safaris',
        );

        foreach ($categories as $slug => $label) {
            $term = get_category_by_slug($slug);
            ?>
            <label class="wta-category-option">
                <input type="checkbox" name="wta_meta[blog_categories][]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $selected, true)); ?> <?php disabled(!$term); ?>>
                <?php echo esc_html($label); ?>
                <?php if (!$term) : ?><span class="description"><?php esc_html_e('(category not found on this WordPress site)', 'destination-architect'); ?></span><?php endif; ?>
            </label>
            <?php
        }
    }

    public function save_destination($post_id) {
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (!isset($_POST['wta_meta']) || !is_array($_POST['wta_meta'])) {
            return;
        }

        $raw = wp_unslash($_POST['wta_meta']);
        $text_keys = array('hero_label', 'hero_title', 'overview_label', 'overview_title', 'tours_title', 'attractions_title');
        foreach ($text_keys as $key) {
            $value = isset($raw[$key]) && is_scalar($raw[$key]) ? sanitize_text_field((string) $raw[$key]) : '';
            update_post_meta($post_id, '_wta_dest_' . $key, $value);
        }

        $textarea_keys = array('hero_intro', 'overview_text', 'tours_intro', 'attractions_intro');
        foreach ($textarea_keys as $key) {
            $value = isset($raw[$key]) && is_scalar($raw[$key]) ? (string) $raw[$key] : '';
            $value = 'overview_text' === $key ? wp_kses_post($value) : sanitize_textarea_field($value);
            update_post_meta($post_id, '_wta_dest_' . $key, $value);
        }

        $highlights = isset($raw['highlights']) && is_scalar($raw['highlights']) ? sanitize_text_field((string) $raw['highlights']) : '';
        update_post_meta($post_id, '_wta_dest_highlights', array_values(array_filter(array_map('trim', explode(',', $highlights)))));
        update_post_meta($post_id, '_wta_dest_blog_categories', isset($raw['blog_categories']) && is_array($raw['blog_categories'])
            ? array_values(array_intersect(array_map(function ($slug) {
                return is_scalar($slug) ? sanitize_title((string) $slug) : '';
            }, $raw['blog_categories']), array('rwanda-safaris', 'uganda-safaris', 'tanzania-safaris', 'kenya-safaris')))
            : array());

        foreach (array('hero_slides', 'tours', 'attractions') as $group) {
            $items = isset($raw[$group]) && is_array($raw[$group]) ? $raw[$group] : array();
            update_post_meta($post_id, '_wta_dest_' . $group, $this->sanitize_repeater($group, $items));
        }
    }

    private function sanitize_repeater($group, $items) {
        $allowed_fields = array(
            'hero_slides' => array('image', 'alt'),
            'tours' => array('title', 'image', 'description', 'duration', 'url'),
            'attractions' => array('title', 'image', 'description', 'details'),
        );
        $clean_items = array();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $clean = array();
            foreach ($allowed_fields[$group] as $field) {
                $value = isset($item[$field]) ? $item[$field] : '';
                if ('image' === $field) {
                    $clean[$field] = is_scalar($value) ? absint($value) : 0;
                } elseif ('url' === $field) {
                    $clean[$field] = is_scalar($value) ? esc_url_raw((string) $value) : '';
                } else {
                    $clean[$field] = is_scalar($value) ? sanitize_textarea_field((string) $value) : '';
                }
            }
            if (array_filter($clean)) {
                $clean_items[] = $clean;
            }
        }

        return $clean_items;
    }

    private function get_destination_data($post_id) {
        $defaults = array(
            'hero_label' => '',
            'hero_title' => '',
            'hero_intro' => '',
            'hero_slides' => array(),
            'overview_label' => '',
            'overview_title' => '',
            'overview_text' => '',
            'highlights' => array(),
            'tours_title' => '',
            'tours_intro' => '',
            'tours' => array(),
            'attractions_title' => '',
            'attractions_intro' => '',
            'attractions' => array(),
            'blog_categories' => array(),
        );

        foreach ($defaults as $key => $default) {
            $value = get_post_meta($post_id, '_wta_dest_' . $key, true);
            if ('' !== $value && false !== $value) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    public function render_destination_content($content) {
        if (!is_singular(self::POST_TYPE) || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        return $this->render_destination(get_the_ID());
    }

    public function destination_shortcode($attributes = array()) {
        $post_id = get_the_ID();
        if (!$post_id || self::POST_TYPE !== get_post_type($post_id)) {
            return '';
        }

        return $this->render_destination($post_id);
    }

    private function render_destination($post_id) {
        $data = $this->get_destination_data($post_id);
        $destination = get_the_title($post_id);
        $slides = $this->valid_items($data['hero_slides'], array('image'));
        $tours = $this->valid_items($data['tours'], array('title'));
        $attractions = $this->valid_items($data['attractions'], array('title'));
        $highlights = is_array($data['highlights']) ? $data['highlights'] : array();
        $categories = is_array($data['blog_categories']) ? $data['blog_categories'] : array();

        ob_start();
        ?>
        <div class="wra-page">
            <?php if ($slides) : ?>
                <section class="wra-hero" aria-label="<?php echo esc_attr(sprintf(__('Discover %s', 'destination-architect'), $destination)); ?>">
                    <?php foreach ($slides as $index => $slide) :
                        $image = wp_get_attachment_image_url(absint($slide['image']), 'full');
                        if (!$image) {
                            continue;
                        }
                        ?>
                        <div class="wra-hero-slide<?php echo 0 === $index ? ' active' : ''; ?>">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(isset($slide['alt']) ? $slide['alt'] : $destination); ?>" <?php echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
                        </div>
                    <?php endforeach; ?>
                    <div class="wra-hero-content">
                        <span class="wra-hero-label"><?php echo esc_html($data['hero_label'] ? $data['hero_label'] : sprintf(__('Discover %s', 'destination-architect'), $destination)); ?></span>
                        <h1><?php echo $data['hero_title'] ? esc_html($data['hero_title']) : esc_html(sprintf(__('Explore the Beauty of %s', 'destination-architect'), $destination)); ?></h1>
                        <?php if ($data['hero_intro']) : ?><p><?php echo esc_html($data['hero_intro']); ?></p><?php endif; ?>
                        <div class="wra-hero-actions">
                            <?php if ($tours) : ?><a href="#wra-destination-tours" class="wra-btn wra-btn-light"><?php esc_html_e('Explore Safaris', 'destination-architect'); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
                            <?php if ($attractions) : ?><a href="#wra-destination-attractions" class="wra-btn wra-btn-outline"><?php esc_html_e('Discover Experiences', 'destination-architect'); ?></a><?php endif; ?>
                        </div>
                    </div>
                    <div class="wra-hero-controls">
                        <button class="wra-hero-arrow wra-prev" type="button" aria-label="<?php esc_attr_e('Previous slide', 'destination-architect'); ?>">←</button>
                        <button class="wra-hero-arrow wra-next" type="button" aria-label="<?php esc_attr_e('Next slide', 'destination-architect'); ?>">→</button>
                        <div class="wra-hero-dots" aria-label="<?php esc_attr_e('Choose a slide', 'destination-architect'); ?>"></div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($data['overview_text'] || $highlights) : ?>
                <section class="wra-overview">
                    <div class="wra-container">
                        <div class="wra-overview-grid">
                            <div class="wra-overview-side">
                                <?php if ($data['overview_label']) : ?><span class="wra-eyebrow"><?php echo esc_html($data['overview_label']); ?></span><?php endif; ?>
                                <h2 class="wra-heading"><?php echo esc_html($data['overview_title'] ? $data['overview_title'] : sprintf(__('A remarkable destination for %s', 'destination-architect'), $destination)); ?></h2>
                            </div>
                            <div class="wra-overview-copy">
                                <?php echo wp_kses_post(wpautop($data['overview_text'])); ?>
                                <?php if ($highlights) : ?>
                                    <div class="wra-overview-highlights">
                                        <?php foreach ($highlights as $highlight) : ?><span class="wra-highlight"><span aria-hidden="true">✦</span> <?php echo esc_html($highlight); ?></span><?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($tours) : ?>
                <section class="wra-tours" id="wra-destination-tours">
                    <div class="wra-container">
                        <div class="wra-section-top">
                            <span class="wra-eyebrow"><?php esc_html_e('Find your next adventure', 'destination-architect'); ?></span>
                            <h2 class="wra-heading"><?php echo esc_html($data['tours_title'] ? $data['tours_title'] : sprintf(__('%s Safaris & Tours', 'destination-architect'), $destination)); ?></h2>
                            <?php if ($data['tours_intro']) : ?><p class="wra-intro"><?php echo esc_html($data['tours_intro']); ?></p><?php endif; ?>
                        </div>
                        <div class="wra-tour-grid">
                            <?php foreach ($tours as $tour) : $image = wp_get_attachment_image_url(absint($tour['image']), 'large'); ?>
                                <article class="wra-tour-card">
                                    <?php if ($image) : ?><div class="wra-tour-image"><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($tour['title']); ?>" loading="lazy"></div><?php endif; ?>
                                    <div class="wra-tour-body">
                                        <?php if ($tour['duration']) : ?><div class="wra-tour-meta"><span>◷ <?php echo esc_html($tour['duration']); ?></span></div><?php endif; ?>
                                        <h3><?php echo esc_html($tour['title']); ?></h3>
                                        <?php if ($tour['description']) : ?><p><?php echo esc_html($tour['description']); ?></p><?php endif; ?>
                                        <div class="wra-tour-footer">
                                            <?php if ($tour['duration']) : ?><span class="wra-tour-price"><?php esc_html_e('Duration', 'destination-architect'); ?><strong><?php echo esc_html($tour['duration']); ?></strong></span><?php endif; ?>
                                            <?php if (!empty($tour['url'])) : ?><a href="<?php echo esc_url($tour['url']); ?>" class="wra-tour-link"><?php esc_html_e('Explore Tour', 'destination-architect'); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($attractions) : ?>
                <section class="wra-attractions" id="wra-destination-attractions">
                    <div class="wra-container">
                        <div class="wra-section-top">
                            <span class="wra-eyebrow"><?php esc_html_e('Explore the destination', 'destination-architect'); ?></span>
                            <h2 class="wra-heading"><?php echo esc_html($data['attractions_title'] ? $data['attractions_title'] : sprintf(__('Experiences & Things to Do in %s', 'destination-architect'), $destination)); ?></h2>
                            <?php if ($data['attractions_intro']) : ?><p class="wra-intro"><?php echo esc_html($data['attractions_intro']); ?></p><?php endif; ?>
                        </div>
                        <div class="wra-attraction-grid">
                            <?php foreach ($attractions as $attraction) : $image = wp_get_attachment_image_url(absint($attraction['image']), 'large'); ?>
                                <article class="wra-attraction-card">
                                    <?php if ($image) : ?><div class="wra-attraction-image"><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($attraction['title']); ?>" loading="lazy"></div><?php endif; ?>
                                    <div class="wra-attraction-body">
                                        <h3><?php echo esc_html($attraction['title']); ?></h3>
                                        <?php if ($attraction['description']) : ?><p><?php echo esc_html($attraction['description']); ?></p><?php endif; ?>
                                        <?php if (!empty($attraction['details'])) : ?>
                                            <div class="wra-attraction-more"><div class="wra-attraction-more-inner"><p><?php echo esc_html($attraction['details']); ?></p></div></div>
                                            <button class="wra-attraction-toggle" type="button" aria-expanded="false"><?php esc_html_e('Discover attraction', 'destination-architect'); ?> <span aria-hidden="true">+</span></button>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php $this->render_blog_section($categories, $destination); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function valid_items($items, $required_fields) {
        if (!is_array($items)) {
            return array();
        }

        return array_values(array_filter($items, function ($item) use ($required_fields) {
            if (!is_array($item)) {
                return false;
            }
            foreach ($required_fields as $field) {
                if (empty($item[$field])) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function render_blog_section($slugs, $destination) {
        if (!$slugs) {
            return;
        }

        $terms = array();
        foreach ($slugs as $slug) {
            $term = get_category_by_slug($slug);
            if ($term) {
                $terms[] = $term;
            }
        }
        if (!$terms) {
            return;
        }

        $category_ids = wp_list_pluck($terms, 'term_id');
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            'category__in' => array_map('absint', $category_ids),
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ));
        $first_link = get_category_link($terms[0]->term_id);
        $category_names = wp_list_pluck($terms, 'name');
        $heading = implode(' & ', array_map('sanitize_text_field', $category_names));
        ?>
        <section class="wra-blog" id="wra-destination-blog">
            <div class="wra-container">
                <div class="wra-blog-header">
                    <div>
                        <span class="wra-eyebrow"><?php esc_html_e('Travel inspiration', 'destination-architect'); ?></span>
                        <h2 class="wra-heading"><?php echo esc_html($heading ? $heading . ' Blog' : sprintf(__('%s Safari Blog', 'destination-architect'), $destination)); ?></h2>
                        <p class="wra-intro"><?php esc_html_e('Discover travel guides, safari inspiration, destination highlights and helpful tips.', 'destination-architect'); ?></p>
                    </div>
                    <a href="<?php echo esc_url($first_link); ?>" class="wra-btn"><?php esc_html_e('View All Articles', 'destination-architect'); ?> <span aria-hidden="true">→</span></a>
                </div>
                <div class="wra-blog-grid">
                    <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); ?>
                        <article class="wra-blog-card">
                            <div class="wra-blog-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('large', array('loading' => 'lazy', 'alt' => the_title_attribute(array('echo' => false)))); ?>
                                <?php endif; ?>
                            </div>
                            <div class="wra-blog-body">
                                <div class="wra-blog-date"><?php echo esc_html(get_the_date()); ?></div>
                                <h3><?php echo esc_html(get_the_title()); ?></h3>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?></p>
                                <a class="wra-blog-link" href="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('Read Article', 'destination-architect'); ?> <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    <?php endwhile; wp_reset_postdata(); else : ?>
                        <div class="wra-blog-status"><?php esc_html_e('No articles were found in the selected categories yet.', 'destination-architect'); ?> <a href="<?php echo esc_url($first_link); ?>"><?php esc_html_e('Browse the category', 'destination-architect'); ?> →</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
    }
}

WTA_Destination_Builder::instance();
