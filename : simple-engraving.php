<?php
/**
 * Plugin Name: חריטה אישית עם אימוג'ים
 * Description: תוסף פשוט להוספת טקסט אישי למוצרים
 * Version: 1.0
 * Author: שמך
 */

if (!defined('ABSPATH')) exit;

class Simple_Product_Engraving {
    
    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_settings'));
        add_action('woocommerce_process_product_meta', array($this, 'save_settings'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'show_field'));
        add_action('wp_enqueue_scripts', array($this, 'add_styles'));
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate'), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_to_cart'), 10, 2);
        add_filter('woocommerce_get_item_data', array($this, 'show_in_cart'), 10, 2);
        add_action('woocommerce_before_calculate_totals', array($this, 'update_price'));
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order'), 10, 4);
    }
    
    public function add_settings() {
        echo '<div class="options_group">';
        
        woocommerce_wp_checkbox(array(
            'id' => '_engraving_enable',
            'label' => 'אפשר חריטה',
            'description' => 'הפעל אפשרות לחריטה למוצר זה'
        ));
        
        woocommerce_wp_text_input(array(
            'id' => '_engraving_label',
            'label' => 'כותרת השדה',
            'placeholder' => 'טקסט לחריטה',
            'description' => 'הטקסט שיופיע מעל שדה הקלט'
        ));
        
        woocommerce_wp_text_input(array(
            'id' => '_engraving_price',
            'label' => 'מחיר נוסף (' . get_woocommerce_currency_symbol() . ')',
            'placeholder' => '0',
            'type' => 'number',
            'custom_attributes' => array('step' => '0.01', 'min' => '0')
        ));
        
        woocommerce_wp_text_input(array(
            'id' => '_engraving_max',
            'label' => 'מקסימום תווים',
            'placeholder' => '50',
            'type' => 'number',
            'custom_attributes' => array('min' => '1')
        ));
        
        woocommerce_wp_checkbox(array(
            'id' => '_engraving_required',
            'label' => 'שדה חובה',
            'description' => 'חייב למלא טקסט'
        ));
        
        echo '</div>';
    }
    
    public function save_settings($post_id) {
        update_post_meta($post_id, '_engraving_enable', isset($_POST['_engraving_enable']) ? 'yes' : 'no');
        update_post_meta($post_id, '_engraving_label', sanitize_text_field($_POST['_engraving_label'] ?? ''));
        update_post_meta($post_id, '_engraving_price', floatval($_POST['_engraving_price'] ?? 0));
        update_post_meta($post_id, '_engraving_max', intval($_POST['_engraving_max'] ?? 50));
        update_post_meta($post_id, '_engraving_required', isset($_POST['_engraving_required']) ? 'yes' : 'no');
    }
    
    public function show_field() {
        global $product;
        
        if (!$product || get_post_meta($product->get_id(), '_engraving_enable', true) !== 'yes') {
            return;
        }
        
        $label = get_post_meta($product->get_id(), '_engraving_label', true) ?: 'טקסט אישי';
        $required = get_post_meta($product->get_id(), '_engraving_required', true) === 'yes';
        $max = intval(get_post_meta($product->get_id(), '_engraving_max', true)) ?: 50;
        $price = floatval(get_post_meta($product->get_id(), '_engraving_price', true));
        
        ?>
        <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 10px;">
            <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 16px;">
                <?php echo esc_html($label); ?>
                <?php if ($required): ?>
                    <span style="color: red;">*</span>
                <?php endif; ?>
                <?php if ($price > 0): ?>
                    <span style="color: #27ae60;">(+ <?php echo wc_price($price); ?>)</span>
                <?php endif; ?>
            </label>
            
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <textarea 
                    name="engraving_text" 
                    id="engraving_text"
                    maxlength="<?php echo $max; ?>"
                    placeholder="הזן טקסט כאן... (אפשר גם אימוג'ים 😊)"
                    style="flex: 1; min-height: 80px; padding: 10px; font-size: 15px; border: 2px solid #ced4da; border-radius: 8px; direction: rtl;"
                    <?php echo $required ? 'required' : ''; ?>
                ></textarea>
                
                <button 
                    type="button" 
                    onclick="document.getElementById('engraving_text').value += prompt('הדבק אימוג\'י:', '😊') || ''"
                    style="padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 20px;"
                >
                    😊
                </button>
            </div>
            
            <div style="margin-top: 8px; font-size: 14px; color: #6c757d;">
                <span id="char_count">0</span> / <?php echo $max; ?> תווים
            </div>
        </div>
        
        <script>
        (function() {
            var textarea = document.getElementById('engraving_text');
            var counter = document.getElementById('char_count');
            
            if (!textarea || !counter) return;
            
            textarea.addEventListener('input', function() {
                var count = Array.from(textarea.value).length;
                counter.textContent = count;
                counter.style.color = count > <?php echo $max; ?> ? 'red' : '#6c757d';
            });
        })();
        </script>
        <?php
    }
    
    public function add_styles() {
        if (!is_product()) return;
        
        echo '<style>
        #engraving_text:focus {
            outline: none;
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        </style>';
    }
    
    public function validate($passed, $product_id, $quantity) {
        if (get_post_meta($product_id, '_engraving_enable', true) !== 'yes') {
            return $passed;
        }
        
        $required = get_post_meta($product_id, '_engraving_required', true) === 'yes';
        $text = isset($_POST['engraving_text']) ? trim($_POST['engraving_text']) : '';
        $max = intval(get_post_meta($product_id, '_engraving_max', true)) ?: 50;
        $count = mb_strlen($text, 'UTF-8');
        
        if ($required && empty($text)) {
            wc_add_notice('נא למלא את שדה החריטה', 'error');
            return false;
        }
        
        if ($count > $max) {
            wc_add_notice("הטקסט ארוך מדי (מקסימום $max תווים)", 'error');
            return false;
        }
        
        return $passed;
    }
    
    public function add_to_cart($cart_item_data, $product_id) {
        if (get_post_meta($product_id, '_engraving_enable', true) !== 'yes') {
            return $cart_item_data;
        }
        
        if (isset($_POST['engraving_text']) && !empty($_POST['engraving_text'])) {
            $cart_item_data['engraving_text'] = wp_kses_post(trim($_POST['engraving_text']));
            $cart_item_data['engraving_price'] = floatval(get_post_meta($product_id, '_engraving_price', true));
        }
        
        return $cart_item_data;
    }
    
    public function show_in_cart($item_data, $cart_item) {
        if (isset($cart_item['engraving_text'])) {
            $item_data[] = array(
                'key' => 'חריטה',
                'value' => esc_html($cart_item['engraving_text']),
                'display' => ''
            );
        }
        return $item_data;
    }
    
    public function update_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['engraving_text']) && isset($cart_item['engraving_price'])) {
                $price = $cart_item['data']->get_price() + $cart_item['engraving_price'];
                $cart_item['data']->set_price($price);
            }
        }
    }
    
    public function save_order($item, $cart_item_key, $values, $order) {
        if (isset($values['engraving_text'])) {
            $item->add_meta_data('חריטה', $values['engraving_text'], true);
        }
    }
}

new Simple_Product_Engraving();
