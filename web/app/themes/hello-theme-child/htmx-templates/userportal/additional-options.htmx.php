<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');
include_once 'init.php';

// Check if nonce is valid.
if (!isset($_SERVER['HTTP_X_WP_NONCE']) || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'hxwp_nonce')) {
    hxwp_die('Insufficient permissions.');
}
$result = false;
if (isset($hxvals['action']) && $hxvals['action'] === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = donationmanager\users\save_user_additional_options();
}


$form_data = donationmanager\users\get_additional_options_form_data();
?>
<form id="org-options" name="user-options" hx-post="/wp-htmx/v1/userportal/additional-options?action=edit"
      hx-target="#org-options" hx-swap="outerHTML"
      class="b-additional-options-form">
    <?php
    foreach ($form_data['elements'] as $element) {
        ?>
        <fieldset class="b-default-options">
            <legend class="b-default-options__legend"><?= esc_html($element['label']); ?></legend>
            <?php
            foreach ($element['options'] as $id => $option) {
                ?>
                <div class="b-default-options__item">
                    <input type="checkbox" id="t-<?php echo esc_attr($id); ?>" value="<?= esc_attr($option['term']->term_id); ?>"
                           name="<?= esc_attr($element['name']); ?>[]"
                           class="b-default-options__checkbox" <?= $option['checked'] ? 'checked' : '' ?>>
                    <label for="t-<?= esc_attr($id); ?>"
                           class="b-default-options__label"><?= esc_html($option['term']->name); ?></label>
                    <p class="b-default-options__description"><?= esc_html($option['term']->description); ?></p>
                </div>
                <?php
            }
            ?>
        </fieldset>
        <?php
    }
    ?>
    <button type="submit">Submit</button>
</form>