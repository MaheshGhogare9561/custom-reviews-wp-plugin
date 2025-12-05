<?php
/**
 * Plugin Name:       Custom Testimonials Carousel
 * Description:       Adds a testimonial carousel widget and "Testimonials" post type.
 * Version:           1.8 (SEO Fix)
 * Author:            Mahesh
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// === 1. REGISTER CUSTOM POST TYPE (NOW SEO-FRIENDLY) ===
function custom_register_testimonial_cpt() {
    $labels = [
        'name'               => 'Testimonials',
        'singular_name'      => 'Testimonial',
        'menu_name'          => 'Testimonials',
        'add_new_item'       => 'Add New Testimonial',
        'edit_item'          => 'Edit Testimonial',
        'new_item'           => 'New Testimonial',
        'view_item'          => 'View Testimonial',
        'search_items'       => 'Search Testimonials',
        'not_found'          => 'No testimonials found',
        'not_found_in_trash' => 'No testimonials found in Trash',
    ];
    
    // --- EDITED: These args make the post type "internal" ---
    $args = [
        'labels'        => $labels,
        'public'        => false,  // This is the main fix. Makes it non-public.
        'show_ui'       => true,   // KEEPS it visible in the admin menu.
        'show_in_menu'  => true,   // KEEPS it in the menu.
        'publicly_queryable'  => false,  // REMOVES the slug and public page.
        'exclude_from_search' => true,   // REMOVES it from WordPress site search.
        'show_in_rest'  => false,  // Hides from REST API (stops most SEO/block editor clutter).
        'menu_icon'     => 'dashicons-format-quote',
        'supports'      => ['title', 'editor', 'thumbnail'],
        'rewrite'       => false, // No slug needed.
    ];
    // --- END EDIT ---

    register_post_type('testimonial', $args);
}
add_action('init', 'custom_register_testimonial_cpt');


// === 1.1 ADD TAXONOMY (NOW SEO-FRIENDLY) ===
function custom_register_testimonial_taxonomy() {
    $labels = [
        'name'              => 'Testimonial Categories',
        'singular_name'     => 'Testimonial Category',
        'search_items'      => 'Search Categories',
        'all_items'         => 'All Categories',
        'parent_item'       => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item'         => 'Edit Category',
        'update_item'       => 'Update Category',
        'add_new_item'      => 'Add New Category',
        'new_item_name'     => 'New Category Name',
        'menu_name'         => 'Categories',
    ];
    
    // --- EDITED: We make the taxonomy internal-only too ---
    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => false, // Not publicly queryable
        'rewrite'           => false, // No slug
    ];
    // --- END EDIT ---
    
    register_taxonomy('testimonial_category', ['testimonial'], $args);
}
add_action('init', 'custom_register_testimonial_taxonomy');


// === 2. ADD "STARS" META BOX ===
function custom_add_stars_metabox() {
    add_meta_box(
        'custom_testimonial_stars', // ID
        'Star Rating',              // Title
        'custom_stars_metabox_html', // Callback function
        'testimonial',              // Post type
        'side'                      // Context (side, normal)
    );
}
add_action('add_meta_boxes', 'custom_add_stars_metabox');

function custom_stars_metabox_html($post) {
    $value = get_post_meta($post->ID, '_testimonial_stars', true);
    wp_nonce_field('custom_save_stars', 'custom_stars_nonce');
    ?>
    <label for="testimonial_stars">Rating:</label>
    <select name="testimonial_stars" id="testimonial_stars" style="width:100%;">
        <option value="">Select a rating...</option>
        <option value="5" <?php selected($value, '5'); ?>>5 Stars</option>
        <option value="4.5" <?php selected($value, '4.5'); ?>>4.5 Stars</option>
        <option value="4" <?php selected($value, '4'); ?>>4 Stars</option>
        <option value="3.5" <?php selected($value, '3.5'); ?>>3.5 Stars</option>
        <option value="3" <?php selected($value, '3'); ?>>3 Stars</option>
        <option value="2.5" <?php selected($value, '2.5'); ?>>2.5 Stars</option>
        <option value="2" <?php selected($value, '2'); ?>>2 Stars</option>
        <option value="1.5" <?php selected($value, '1.5'); ?>>1.5 Stars</option>
        <option value="1" <?php selected($value, '1'); ?>>1 Star</option>
    </select>
    <?php
}

function custom_save_stars_metabox($post_id) {
    if (!isset($_POST['custom_stars_nonce']) || !wp_verify_nonce($_POST['custom_stars_nonce'], 'custom_save_stars')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['testimonial_stars'])) {
        update_post_meta($post_id, '_testimonial_stars', sanitize_text_field($_POST['testimonial_stars']));
    }
}
add_action('save_post', 'custom_save_stars_metabox');


// === 3. REGISTER SCRIPTS ===
function custom_register_scripts() {
    wp_register_style('custom-carousel-css', plugin_dir_url(__FILE__) . 'carousel.css', [], '1.8'); // Version bump
    wp_register_script('custom-carousel-js', plugin_dir_url(__FILE__) . 'carousel.js', [], '1.2', true);
}
add_action('wp_enqueue_scripts', 'custom_register_scripts');
add_action('elementor/preview/enqueue_styles', 'custom_register_scripts');


// === 4. REGISTER THE WIDGET (MODIFIED) ===
class Custom_Testimonial_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'custom_testimonial_widget',
            'Testimonial Carousel',
            ['description' => 'Displays a carousel of testimonials.']
        );
    }

    // Widget Admin Form
    public function form($instance) {
        // Get existing values or set defaults
        $title = !empty($instance['title']) ? $instance['title'] : 'What our clients say';
        $category_id = !empty($instance['category_id']) ? $instance['category_id'] : '';
        $rating_text = !empty($instance['rating_text']) ? $instance['rating_text'] : 'GOOD';
        $rating_stars = !empty($instance['rating_stars']) ? $instance['rating_stars'] : '4.5';
        $review_count_text = !empty($instance['review_count_text']) ? $instance['review_count_text'] : 'Based on 321 reviews';

        $categories = get_terms(['taxonomy' => 'testimonial_category', 'hide_empty' => false]);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Widget Title (optional, not displayed):</label>
            <input 
                class="widefat" 
                id="<?php echo $this->get_field_id('title'); ?>" 
                name="<?php echo $this->get_field_name('title'); ?>" 
                type="text" 
                value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('category_id'); ?>">Show reviews from category:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('category_id'); ?>" name="<?php echo $this->get_field_name('category_id'); ?>">
                <option value="" <?php selected($category_id, ''); ?>>All Categories</option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($category_id, $category->term_id); ?>>
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <hr>
        <p><b>Left Panel Settings</b></p>
         <p>
            <label for="<?php echo $this->get_field_id('rating_text'); ?>">Rating Text:</label>
            <input 
                class="widefat" 
                id="<?php echo $this->get_field_id('rating_text'); ?>" 
                name="<?php echo $this->get_field_name('rating_text'); ?>" 
                type="text" 
                value="<?php echo esc_attr($rating_text); ?>">
        </p>
         <p>
            <label for="<?php echo $this->get_field_id('rating_stars'); ?>">Rating (e.g., 4, 4.5, 5):</label>
            <input 
                class="widefat" 
                id="<?php echo $this->get_field_id('rating_stars'); ?>" 
                name="<?php echo $this->get_field_name('rating_stars'); ?>" 
                type="number" 
                step="0.5"
                min="0"
                max="5"
                value="<?php echo esc_attr($rating_stars); ?>">
        </p>
         <p>
            <label for="<?php echo $this->get_field_id('review_count_text'); ?>">Review Count Text:</label>
            <input 
                class="widefat" 
                id="<?php echo $this->get_field_id('review_count_text'); ?>" 
                name="<?php echo $this->get_field_name('review_count_text'); ?>" 
                type="text" 
                value="<?php echo esc_attr($review_count_text); ?>">
        </p>
        <?php
    }

    // Widget Save
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['category_id'] = (!empty($new_instance['category_id'])) ? strip_tags($new_instance['category_id']) : '';
        $instance['rating_text'] = (!empty($new_instance['rating_text'])) ? strip_tags($new_instance['rating_text']) : 'GOOD';
        $instance['rating_stars'] = (!empty($new_instance['rating_stars'])) ? strip_tags($new_instance['rating_stars']) : '4.5';
        $instance['review_count_text'] = (!empty($new_instance['review_count_text'])) ? strip_tags($new_instance['review_count_text']) : 'Based on 321 reviews';
        return $instance;
    }

    // Widget Display
    public function widget($args, $instance) {
        wp_enqueue_style('custom-carousel-css');
        wp_enqueue_script('custom-carousel-js');

        echo $args['before_widget'];
        
        // Get new settings
        $rating_text = !empty($instance['rating_text']) ? $instance['rating_text'] : 'GOOD';
        $rating_stars = !empty($instance['rating_stars']) ? (float)$instance['rating_stars'] : 4.5;
        $review_count_text = !empty($instance['review_count_text']) ? $instance['review_count_text'] : 'Based on 321 reviews';

        ?>
        
        <svg width="0" height="0" style="display:none;">
            <symbol id="icon-star" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"/></symbol>
            <symbol id="icon-star-half" viewBox="0 0 24 24">
                 <defs><clipPath id="halfClip"><rect x="0" y="0" width="12" height="24"/></clipPath></defs>
                 <g clip-path="url(#halfClip)"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="#FBC02D"/></g>
                 <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="none" stroke="#E0E0E0" stroke-width="0.6"/>
            </symbol>
            <symbol id="icon-star-empty" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="none" stroke="#E0E0E0" stroke-width="0.6"/>
            </symbol>
            <symbol id="icon-check" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.25 17.292l-4.5-4.364 1.857-1.858 2.643 2.566 5.643-5.784 1.857 1.857-7.5 7.623z"/></symbol>
            <symbol id="icon-arrow-left" viewBox="0 0 24 24"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12l4.58-4.59z"/></symbol>
            <symbol id="icon-arrow-right" viewBox="0 0 24 24"><path fill="currentColor" d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12l-4.58 4.59z"/></symbol>
        </svg>

        <section class="testimonial-section">

            <div class="rating-summary">
                <h3><?php echo esc_html($rating_text); ?></h3>
                <div class="stars">
                    <?php
                    // Logic to display main rating stars
                    for ($i = 1; $i <= 5; $i++) {
                        if ($rating_stars >= $i) {
                            echo '<svg><use href="#icon-star"></use></svg>'; // Full star
                        } else if ($rating_stars == ($i - 0.5)) {
                            echo '<svg><use href="#icon-star-half"></use></svg>'; // Half star
                        } else {
                            echo '<svg><use href="#icon-star-empty"></use></svg>'; // Empty star
                        }
                    }
                    ?>
                </div>
                <p class="review-count"><?php echo esc_html($review_count_text); ?></p>
                <div class="google-logo-large">
                    <span class="g-1">G</span><span class="g-2">o</span><span class="g-3">o</span><span class="g-4">g</span><span class="g-5">l</span><span class="g-6">e</span>
                </div>
            </div>

            <div class="reviews-carousel">
                
                <button class="custom-carousel-arrow custom-scroll-left" aria-label="Previous review">
                    <svg><use href="#icon-arrow-left"></use></svg>
                </button>

                <div class="reviews-container">

                    <?php
                    // Setup query args
                    $category_id = !empty($instance['category_id']) ? $instance['category_id'] : '';
                    $query_args = [
                        'post_type'      => 'testimonial',
                        'posts_per_page' => -1,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ];
                    
                    if (!empty($category_id)) {
                        $query_args['tax_query'] = [
                            [
                                'taxonomy' => 'testimonial_category',
                                'field'    => 'term_id',
                                'terms'    => $category_id,
                            ],
                        ];
                    }

                    $testimonial_query = new WP_Query($query_args);

                    if ($testimonial_query->have_posts()) :
                        while ($testimonial_query->have_posts()) : $testimonial_query->the_post();
                            
                            $reviewer_name = get_the_title();
                            $stars = (float) get_post_meta(get_the_ID(), '_testimonial_stars', true);
                            
                    ?>
                            <div class="review-card">
                                <div class="review-card-header">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('thumbnail', ['class' => 'profile-pic', 'alt' => $reviewer_name]); ?>
                                    <?php else : ?>
                                        <div class="profile-pic-placeholder" style="background-color: #E91E63; color: white;">
                                            <?php echo esc_html(substr($reviewer_name, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="user-info">
                                        <span class="user-name"><?php echo esc_html($reviewer_name); ?></span>
                                    </div>
                                    
                                    <img class="google-logo-small" src="<?php echo plugin_dir_url(__FILE__) . 'images/Logo-google-icon-PNG.png'; ?>" alt="Google logo">
                                </div>
                                <div class="review-card-body">
                                    <div class="stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($stars >= $i) {
                                                echo '<svg class="star-icon"><use href="#icon-star"></use></svg>';
                                            } else if ($stars == ($i - 0.5)) {
                                                echo '<svg class="star-icon"><use href="#icon-star-half"></use></svg>';
                                            } else {
                                                echo '<svg class="star-icon"><use href="#icon-star-empty"></use></svg>';
                                            }
                                        }
                                        ?>
                                        <svg class="check-icon"><use href="#icon-check"></use></svg>
                                    </div>
                                    
                                    <p class="review-text">
                                        <?php echo wp_kses_post(get_the_content()); ?>
                                    </p>
                                    <a class="read-more">Read more</a>
                                </div>
                            </div>
                    
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p>No testimonials found for this category.</p>';
                    endif;
                    ?>

                </div>
                
                <button class="custom-carousel-arrow custom-scroll-right" aria-label="Next review">
                    <svg><use href="#icon-arrow-right"></use></svg>
                </button>

            </div>
        </section>
        <?php
        
        echo $args['after_widget'];
    }
}

// Register the widget
function custom_register_testimonial_widget() {
    register_widget('Custom_Testimonial_Widget');
}
add_action('widgets_init', 'custom_register_testimonial_widget');


// === 5. ADD ELEMENTOR PREVIEW FIX ===
function custom_elementor_preview_scripts() {
    wp_enqueue_style('custom-carousel-css');
    wp_enqueue_script('custom-carousel-js');
}
add_action('elementor/preview/enqueue_styles', 'custom_elementor_preview_scripts');
?>