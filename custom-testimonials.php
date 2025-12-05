<?php
/**
 * Plugin Name:       Custom Testimonials Carousel
 * Description:       Adds a testimonial carousel widget with support for CPT or Manual Entry (Unlimited).
 * Version:           3.2 (Repeater & UI Fix)
 * Author:            Mahesh
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// === 0. ADMIN ASSETS FOR MEDIA UPLOADER & STYLES ===
function custom_testimonial_admin_scripts() {
    wp_enqueue_media(); 
}
add_action('admin_enqueue_scripts', 'custom_testimonial_admin_scripts');

function custom_testimonial_admin_footer_scripts() {
    ?>
    <style>
        /* --- Admin Widget Styles (Transparent/Elementor Match) --- */
        .widget-content { background: transparent !important; border: none !important; box-shadow: none !important; }
        
        .ct-control-label { display: block; margin-bottom: 5px; font-family: Roboto, Arial, Helvetica, sans-serif; font-size: 11px; font-weight: 500; text-transform: uppercase; color: #a4afb7; letter-spacing: 0.5px; }
        
        .ct-widget-input, .ct-widget-textarea, .ct-widget-select { 
            background-color: #404349 !important; 
            border: none !important; 
            border-radius: 3px !important; 
            color: #d5d8dc !important; 
            width: 100%; 
            padding: 6px 10px; 
            font-size: 12px; 
            margin-bottom: 15px; 
            box-shadow: none !important; 
            height: auto !important;
        }
        .ct-widget-input:focus, .ct-widget-textarea:focus { background-color: #45494e !important; outline: 1px solid #666 !important; }
        
        .ct-separator { border-top: 1px solid #404349; margin: 15px -15px; padding-top: 15px; }
        .ct-section-title { color: #fff; font-size: 13px; font-weight: 600; margin-bottom: 10px; display: block; }
        
        /* REPEATER STYLES */
        .ct-repeater-container { margin-top: 10px; }
        
        .ct-widget-card { 
            background: rgba(255,255,255,0.02); 
            border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 3px;
            margin-bottom: 5px;
        }
        
        .ct-widget-summary { 
            padding: 10px; 
            cursor: pointer; 
            outline: none; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            color: #d5d8dc; 
            font-size: 12px; 
            font-weight: 600; 
            background: rgba(0,0,0,0.1);
        }
        .ct-widget-summary:hover { background: rgba(255,255,255,0.05); color: #fff; }
        
        /* Icons */
        .ct-icon-toggle { font-size: 12px; color: #a4afb7; transition: transform 0.2s; }
        details[open] .ct-icon-toggle { transform: rotate(180deg); }
        
        .ct-widget-content { padding: 15px; border-top: 1px solid rgba(255,255,255,0.05); }
        
        /* Image Box */
        .ct-image-upload-box { position: relative; background-color: #404349; border: 1px dashed #6d7882; border-radius: 3px; height: 80px; width: 100%; margin-bottom: 15px; cursor: pointer; overflow: hidden; display: flex; align-items: center; justify-content: center; transition: border-color 0.2s; }
        .ct-image-upload-box:hover { border-color: #a4afb7; }
        .ct-placeholder-icon { width: 40px; height: 30px; background-color: #6d7882; -webkit-mask: url('data:image/svg+xml;utf8,<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M1 18h22L12 6 1 18zm2.5-1.5L12 8.25 20.5 16.5H3.5z"/><circle cx="6.5" cy="9.5" r="1.5"/></svg>') no-repeat center; mask: url('data:image/svg+xml;utf8,<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>') no-repeat center; -webkit-mask-size: contain; mask-size: contain; }
        .ct-image-preview { width: 100%; height: 100%; object-fit: cover; display: block; }
        
        /* Actions */
        .ct-remove-overlay { position: absolute; top: 0; right: 0; padding: 5px; display: none; z-index: 10; }
        .ct-image-upload-box:hover .ct-remove-overlay { display: block; }
        .ct-remove-btn { color: #fff; background: rgba(0,0,0,0.5); border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; font-size: 14px; }
        
        .ct-delete-item { color: #ff6b6b; font-size: 11px; text-transform: uppercase; cursor: pointer; float: right; margin-top: -5px; }
        .ct-delete-item:hover { text-decoration: underline; }

        .ct-add-btn { width: 100%; background: #58D0F5; color: #fff; border: none; padding: 8px; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 12px; margin-top: 10px; }
        .ct-add-btn:hover { background: #41bce0; }
    </style>
    
    <script>
    jQuery(document).ready(function($){
        
        // --- 1. REPEATER LOGIC ---
        
        // Add New Item
        $(document).on('click', '.ct-add-btn', function(e){
            e.preventDefault();
            var wrapper = $(this).siblings('.ct-repeater-container');
            var template = wrapper.siblings('.ct-repeater-template').html();
            
            // Generate unique ID based on timestamp to avoid conflict
            var uniqueId = new Date().getTime(); 
            template = template.replace(/__INDEX__/g, uniqueId);
            
            wrapper.append(template);
            
            // Trigger change so Elementor enables the "Apply" button
            wrapper.find('input').first().trigger('change');
        });

        // Delete Item
        $(document).on('click', '.ct-delete-item', function(e){
            e.preventDefault();
            if(confirm('Are you sure you want to delete this testimonial?')) {
                var item = $(this).closest('.ct-widget-card');
                var container = item.closest('.ct-repeater-container');
                item.remove();
                // Trigger change
                container.closest('form').find('input').first().trigger('change'); 
            }
        });

        // Live Title Update (Accordion Header)
        $(document).on('keyup', '.ct-name-input', function(){
            var val = $(this).val();
            var title = val ? val : 'New Testimonial';
            $(this).closest('.ct-widget-card').find('.ct-item-title').text(title);
        });

        // --- 2. IMAGE UPLOAD LOGIC ---

        $(document).on('click', '.ct-image-upload-box', function(e){
            if($(e.target).closest('.ct-remove-btn').length > 0) return; // Ignore if clicking remove

            e.preventDefault();
            var box = $(this);
            var inputId = box.data('input-id');
            var inputField = $('#'+inputId);
            
            var custom_uploader = wp.media({
                title: 'Select Image',
                button: { text: 'Insert Media' },
                multiple: false
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                inputField.val(attachment.url);
                box.find('.ct-image-preview').attr('src', attachment.url).show();
                box.find('.ct-placeholder-icon').hide();
                box.addClass('has-image');
                inputField.trigger('change'); 
            }).open();
        });

        $(document).on('click', '.ct-remove-btn', function(e){
            e.preventDefault();
            e.stopPropagation(); 
            var btn = $(this);
            var box = btn.closest('.ct-image-upload-box');
            var inputId = box.data('input-id');
            var inputField = $('#'+inputId);

            inputField.val(''); 
            box.find('.ct-image-preview').attr('src', '').hide(); 
            box.find('.ct-placeholder-icon').show(); 
            box.removeClass('has-image');
            inputField.trigger('change');
        });
    });
    </script>
    <?php
}
add_action('admin_print_footer_scripts', 'custom_testimonial_admin_footer_scripts');


// === 1. REGISTER CUSTOM POST TYPE ===
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
    
    $args = [
        'labels'        => $labels,
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'show_in_rest'  => false,
        'menu_icon'     => 'dashicons-format-quote',
        'supports'      => ['title', 'editor', 'thumbnail'],
        'rewrite'       => false,
    ];

    register_post_type('testimonial', $args);
}
add_action('init', 'custom_register_testimonial_cpt');


// === 1.1 ADD TAXONOMY ===
function custom_register_testimonial_taxonomy() {
    $labels = [
        'name'              => 'Testimonial Categories',
        'singular_name'     => 'Testimonial Category',
        'menu_name'         => 'Categories',
    ];
    
    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => false,
        'rewrite'           => false,
    ];
    
    register_taxonomy('testimonial_category', ['testimonial'], $args);
}
add_action('init', 'custom_register_testimonial_taxonomy');


// === 2. ADD "STARS" META BOX ===
function custom_add_stars_metabox() {
    add_meta_box('custom_testimonial_stars', 'Star Rating', 'custom_stars_metabox_html', 'testimonial', 'side');
}
add_action('add_meta_boxes', 'custom_add_stars_metabox');

function custom_stars_metabox_html($post) {
    $value = get_post_meta($post->ID, '_testimonial_stars', true);
    wp_nonce_field('custom_save_stars', 'custom_stars_nonce');
    ?>
    <label for="testimonial_stars">Rating:</label>
    <select name="testimonial_stars" id="testimonial_stars" style="width:100%;">
        <option value="5" <?php selected($value, '5'); ?>>5 Stars</option>
        <option value="4.5" <?php selected($value, '4.5'); ?>>4.5 Stars</option>
        <option value="4" <?php selected($value, '4'); ?>>4 Stars</option>
        <option value="3.5" <?php selected($value, '3.5'); ?>>3.5 Stars</option>
        <option value="3" <?php selected($value, '3'); ?>>3 Stars</option>
        <option value="2" <?php selected($value, '2'); ?>>2 Stars</option>
        <option value="1" <?php selected($value, '1'); ?>>1 Star</option>
    </select>
    <?php
}

function custom_save_stars_metabox($post_id) {
    if (!isset($_POST['custom_stars_nonce']) || !wp_verify_nonce($_POST['custom_stars_nonce'], 'custom_save_stars')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['testimonial_stars'])) {
        update_post_meta($post_id, '_testimonial_stars', sanitize_text_field($_POST['testimonial_stars']));
    }
}
add_action('save_post', 'custom_save_stars_metabox');


// === 3. REGISTER SCRIPTS ===
function custom_register_scripts() {
    wp_register_style('custom-carousel-css', plugin_dir_url(__FILE__) . 'carousel.css', [], '3.2');
    wp_register_script('custom-carousel-js', plugin_dir_url(__FILE__) . 'carousel.js', [], '3.2', true);
}
add_action('wp_enqueue_scripts', 'custom_register_scripts');
add_action('elementor/preview/enqueue_styles', 'custom_register_scripts');


// === 4. REGISTER THE WIDGET ===
class Custom_Testimonial_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'custom_testimonial_widget',
            'Testimonial Carousel (Hybrid)',
            ['description' => 'Displays testimonials from Categories OR Manual Entry.']
        );
    }

    // Widget Admin Form
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'What our clients say';
        $source_type = !empty($instance['source_type']) ? $instance['source_type'] : 'category'; 
        $category_id = !empty($instance['category_id']) ? $instance['category_id'] : '';
        
        $rating_text = !empty($instance['rating_text']) ? $instance['rating_text'] : 'GOOD';
        $rating_stars = !empty($instance['rating_stars']) ? $instance['rating_stars'] : '4.5';
        $review_count_text = !empty($instance['review_count_text']) ? $instance['review_count_text'] : 'Based on 321 reviews';

        // Get Repeater Items
        $manual_items = isset($instance['manual_items']) ? $instance['manual_items'] : [];

        $categories = get_terms(['taxonomy' => 'testimonial_category', 'hide_empty' => false]);
        ?>
        
        <!-- GENERAL SETTINGS -->
        <p>
            <label class="ct-control-label" for="<?php echo $this->get_field_id('title'); ?>">Widget Title (Hidden):</label>
            <input class="ct-widget-input" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>

        <!-- SOURCE SELECTION -->
        <p>
            <label class="ct-control-label" for="<?php echo $this->get_field_id('source_type'); ?>">Data Source:</label>
            <select class="ct-widget-select" id="<?php echo $this->get_field_id('source_type'); ?>" name="<?php echo $this->get_field_name('source_type'); ?>">
                <option value="category" <?php selected($source_type, 'category'); ?>>Use Post Categories (Global)</option>
                <option value="manual" <?php selected($source_type, 'manual'); ?>>Manual Entry (Page Specific)</option>
            </select>
        </p>

        <!-- CATEGORY SETTINGS -->
        <p>
            <label class="ct-control-label" for="<?php echo $this->get_field_id('category_id'); ?>">Select Category:</label>
            <select class="ct-widget-select" id="<?php echo $this->get_field_id('category_id'); ?>" name="<?php echo $this->get_field_name('category_id'); ?>">
                <option value="" <?php selected($category_id, ''); ?>>All Categories</option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($category_id, $category->term_id); ?>>
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <!-- LEFT PANEL SECTION -->
        <div class="ct-separator"></div>
        <span class="ct-section-title">Left Panel Settings</span>
        
        <label class="ct-control-label">Rating Text</label>
        <input class="ct-widget-input" name="<?php echo $this->get_field_name('rating_text'); ?>" type="text" value="<?php echo esc_attr($rating_text); ?>">
        
        <div style="display: flex; gap: 10px;">
            <div style="flex:1;">
                <label class="ct-control-label">Stars</label>
                <input class="ct-widget-input" name="<?php echo $this->get_field_name('rating_stars'); ?>" type="number" step="0.5" min="0" max="5" value="<?php echo esc_attr($rating_stars); ?>">
            </div>
            <div style="flex:1;">
                <label class="ct-control-label">Review Count</label>
                <input class="ct-widget-input" name="<?php echo $this->get_field_name('review_count_text'); ?>" type="text" value="<?php echo esc_attr($review_count_text); ?>">
            </div>
        </div>

        <!-- MANUAL REVIEWS SECTION (REPEATER) -->
        <div class="ct-separator"></div>
        <span class="ct-section-title">Manual Reviews</span>
        <p style="color:#888; font-size:11px; margin-top:-5px; margin-bottom:15px;">(Visible only when Data Source is 'Manual Entry')</p>

        <div class="ct-repeater-container">
            <?php 
            if(!empty($manual_items) && is_array($manual_items)) {
                foreach($manual_items as $index => $item) {
                    $this->render_repeater_item($index, $item);
                }
            }
            ?>
        </div>

        <button class="ct-add-btn">ADD TESTIMONIAL</button>

        <!-- Hidden Template for New Items (JS will use this) -->
        <div class="ct-repeater-template" style="display:none;">
            <?php $this->render_repeater_item('__INDEX__', [], true); ?>
        </div>

        <?php
    }

    // Helper to render a single repeater item
    private function render_repeater_item($index, $item = [], $is_template = false) {
        $prefix = $this->get_field_name('manual_items') . "[$index]";
        $id_prefix = $this->get_field_id('manual_items') . "-$index";
        
        $m_content = $item['content'] ?? '';
        $m_image   = $item['image'] ?? '';
        $m_name    = $item['name'] ?? '';
        $m_title   = $item['title'] ?? '';
        $m_link    = $item['link'] ?? '';
        $m_stars   = $item['stars'] ?? '5';
        
        $has_image = !empty($m_image);
        ?>
        <details class="ct-widget-card" <?php if($is_template) echo 'open'; ?>>
            <summary class="ct-widget-summary">
                <span class="ct-item-title"><?php echo $m_name ? esc_html($m_name) : 'New Testimonial'; ?></span>
                <span class="dashicons dashicons-arrow-down-alt2 ct-icon-toggle"></span>
            </summary>
            
            <div class="ct-widget-content">
                <span class="ct-delete-item">Delete</span>
                
                <label class="ct-control-label">Content</label>
                <textarea class="ct-widget-textarea" rows="4" placeholder="Enter review text..." name="<?php echo $prefix; ?>[content]"><?php echo esc_textarea($m_content); ?></textarea>

                <label class="ct-control-label">Choose Image</label>
                <input type="hidden" id="<?php echo $id_prefix; ?>-img" name="<?php echo $prefix; ?>[image]" value="<?php echo esc_attr($m_image); ?>">
                
                <div class="ct-image-upload-box <?php echo $has_image ? 'has-image' : ''; ?>" data-input-id="<?php echo $id_prefix; ?>-img">
                    <div class="ct-placeholder-icon" style="display: <?php echo $has_image ? 'none' : 'block'; ?>;"></div>
                    <img class="ct-image-preview" src="<?php echo esc_attr($m_image); ?>" style="display: <?php echo $has_image ? 'block' : 'none'; ?>;">
                    <div class="ct-remove-overlay">
                        <span class="ct-remove-btn dashicons dashicons-trash"></span>
                    </div>
                </div>

                <label class="ct-control-label">Name</label>
                <input class="ct-widget-input ct-name-input" type="text" placeholder="John Doe" name="<?php echo $prefix; ?>[name]" value="<?php echo esc_attr($m_name); ?>">
                
                <div style="display:flex; gap: 10px;">
                    <div style="flex:1;">
                        <label class="ct-control-label">Title</label>
                        <input class="ct-widget-input" type="text" placeholder="Designer" name="<?php echo $prefix; ?>[title]" value="<?php echo esc_attr($m_title); ?>">
                    </div>
                    <div style="flex:1;">
                        <label class="ct-control-label">Stars</label>
                        <select class="ct-widget-select" name="<?php echo $prefix; ?>[stars]">
                            <?php foreach(['5','4.5','4','3.5','3','2','1'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php selected($m_stars, $s); ?>><?php echo $s; ?> Stars</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="ct-control-label">Link</label>
                <input class="ct-widget-input" type="text" placeholder="https://example.com" name="<?php echo $prefix; ?>[link]" value="<?php echo esc_attr($m_link); ?>">
            </div>
        </details>
        <?php
    }

    // Widget Save
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['source_type'] = strip_tags($new_instance['source_type']);
        $instance['category_id'] = strip_tags($new_instance['category_id']);
        $instance['rating_text'] = strip_tags($new_instance['rating_text']);
        $instance['rating_stars'] = strip_tags($new_instance['rating_stars']);
        $instance['review_count_text'] = strip_tags($new_instance['review_count_text']);

        // Save Manual Repeater Items
        $instance['manual_items'] = [];
        if (isset($new_instance['manual_items']) && is_array($new_instance['manual_items'])) {
            foreach ($new_instance['manual_items'] as $item) {
                // Only save if name or content is set to avoid empty array issues
                if (!empty($item['name']) || !empty($item['content'])) {
                    $instance['manual_items'][] = [
                        'content' => wp_kses_post($item['content']),
                        'image'   => esc_url_raw($item['image']),
                        'name'    => strip_tags($item['name']),
                        'title'   => strip_tags($item['title']),
                        'link'    => esc_url_raw($item['link']),
                        'stars'   => strip_tags($item['stars']),
                    ];
                }
            }
        }

        return $instance;
    }

    // Widget Display
    public function widget($args, $instance) {
        wp_enqueue_style('custom-carousel-css');
        wp_enqueue_script('custom-carousel-js');

        echo $args['before_widget'];
        
        $source_type = !empty($instance['source_type']) ? $instance['source_type'] : 'category';
        $rating_text = !empty($instance['rating_text']) ? $instance['rating_text'] : 'GOOD';
        $rating_stars = !empty($instance['rating_stars']) ? (float)$instance['rating_stars'] : 4.5;
        $review_count_text = !empty($instance['review_count_text']) ? $instance['review_count_text'] : 'Based on 321 reviews';

        $testimonials_data = [];

        if ($source_type === 'manual') {
            // === MODIFIED: LOOP THROUGH REPEATER ARRAY ===
            $manual_items = isset($instance['manual_items']) ? $instance['manual_items'] : [];
            if(is_array($manual_items)) {
                foreach($manual_items as $item) {
                    $testimonials_data[] = [
                        'name'    => $item['name'] ?? '',
                        'title'   => $item['title'] ?? '',
                        'link'    => $item['link'] ?? '',
                        'content' => $item['content'] ?? '',
                        'stars'   => (float)($item['stars'] ?? 5),
                        'image'   => !empty($item['image']) ? $item['image'] : false
                    ];
                }
            }
        } else {
            // Category Mode (Unchanged)
            $category_id = !empty($instance['category_id']) ? $instance['category_id'] : '';
            $query_args = ['post_type' => 'testimonial', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC'];
            
            if (!empty($category_id)) {
                $query_args['tax_query'] = [['taxonomy' => 'testimonial_category', 'field' => 'term_id', 'terms' => $category_id]];
            }

            $testimonial_query = new WP_Query($query_args);
            if ($testimonial_query->have_posts()) {
                while ($testimonial_query->have_posts()) {
                    $testimonial_query->the_post();
                    $img_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') : false;
                    $testimonials_data[] = [
                        'name'    => get_the_title(),
                        'title'   => '',
                        'link'    => '',
                        'content' => get_the_content(),
                        'stars'   => (float) get_post_meta(get_the_ID(), '_testimonial_stars', true),
                        'image'   => $img_url
                    ];
                }
                wp_reset_postdata();
            }
        }
        ?>
        
        <!-- SVG SYMBOLS (Hidden) -->
        <svg width="0" height="0" style="display:none;">
            <symbol id="icon-star" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"/></symbol>
            <symbol id="icon-star-half" viewBox="0 0 24 24">
                 <defs><clipPath id="halfClip"><rect x="0" y="0" width="12" height="24"/></clipPath></defs>
                 <g clip-path="url(#halfClip)"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="#FBC02D"/></g>
                 <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="none" stroke="#E0E0E0" stroke-width="0.6"/>
            </symbol>
            <symbol id="icon-star-empty" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="none" stroke="#E0E0E0" stroke-width="0.6"/></symbol>
            <symbol id="icon-check" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.25 17.292l-4.5-4.364 1.857-1.858 2.643 2.566 5.643-5.784 1.857 1.857-7.5 7.623z"/></symbol>
            <symbol id="icon-arrow-left" viewBox="0 0 24 24"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12l4.58-4.59z"/></symbol>
            <symbol id="icon-arrow-right" viewBox="0 0 24 24"><path fill="currentColor" d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12l-4.58 4.59z"/></symbol>
        </svg>

        <section class="testimonial-section">

            <div class="rating-summary">
                <h3><?php echo esc_html($rating_text); ?></h3>
                <div class="stars">
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        if ($rating_stars >= $i) echo '<svg><use href="#icon-star"></use></svg>';
                        else if ($rating_stars == ($i - 0.5)) echo '<svg><use href="#icon-star-half"></use></svg>';
                        else echo '<svg><use href="#icon-star-empty"></use></svg>';
                    }
                    ?>
                </div>
                <p class="review-count"><?php echo esc_html($review_count_text); ?></p>
                <div class="google-logo-large">
                    <span class="g-1">G</span><span class="g-2">o</span><span class="g-3">o</span><span class="g-4">g</span><span class="g-5">l</span><span class="g-6">e</span>
                </div>
            </div>

            <div class="reviews-carousel">
                <button class="custom-carousel-arrow custom-scroll-left" aria-label="Previous review"><svg><use href="#icon-arrow-left"></use></svg></button>
                
                <div class="reviews-container">
                    <?php if (!empty($testimonials_data)) : ?>
                        <?php foreach ($testimonials_data as $data) : ?>
                            <div class="review-card">
                                <div class="review-card-header">
                                    <?php if ($data['image']) : ?>
                                        <img src="<?php echo esc_url($data['image']); ?>" class="profile-pic" alt="<?php echo esc_attr($data['name']); ?>">
                                    <?php else : ?>
                                        <div class="profile-pic-placeholder" style="background-color: #E91E63; color: white;">
                                            <?php echo esc_html(substr($data['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="user-info">
                                        <?php if (!empty($data['link'])) : ?>
                                            <a href="<?php echo esc_url($data['link']); ?>" target="_blank" class="user-name-link" style="text-decoration:none; color:inherit; font-weight:bold;">
                                        <?php endif; ?>
                                            <span class="user-name"><?php echo esc_html($data['name']); ?></span>
                                        <?php if (!empty($data['link'])) : ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($data['title'])) : ?>
                                            <div class="user-title" style="font-size: 0.8em; color: #777; margin-top:2px;"><?php echo esc_html($data['title']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <img class="google-logo-small" src="<?php echo plugin_dir_url(__FILE__) . 'images/Logo-google-icon-PNG.png'; ?>" alt="Google logo">
                                </div>
                                <div class="review-card-body">
                                    <div class="stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($data['stars'] >= $i) echo '<svg class="star-icon"><use href="#icon-star"></use></svg>';
                                            else if ($data['stars'] == ($i - 0.5)) echo '<svg class="star-icon"><use href="#icon-star-half"></use></svg>';
                                            else echo '<svg class="star-icon"><use href="#icon-star-empty"></use></svg>';
                                        }
                                        ?>
                                        <svg class="check-icon"><use href="#icon-check"></use></svg>
                                    </div>
                                    <p class="review-text"><?php echo wp_kses_post($data['content']); ?></p>
                                    <a class="read-more">Read more</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No testimonials found.</p>
                    <?php endif; ?>
                </div>
                <button class="custom-carousel-arrow custom-scroll-right" aria-label="Next review"><svg><use href="#icon-arrow-right"></use></svg></button>
            </div>
        </section>
        <?php
        echo $args['after_widget'];
    }
}

function custom_register_testimonial_widget() { register_widget('Custom_Testimonial_Widget'); }
add_action('widgets_init', 'custom_register_testimonial_widget');
function custom_elementor_preview_scripts() { wp_enqueue_style('custom-carousel-css'); wp_enqueue_script('custom-carousel-js'); }
add_action('elementor/preview/enqueue_styles', 'custom_elementor_preview_scripts');