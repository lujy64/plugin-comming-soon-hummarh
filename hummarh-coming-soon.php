<?php
/**
 * Plugin Name: Hummarh Coming Soon
 * Plugin URI: https://github.com/
 * Description: Coming soon page for Hummarh with editable logos, text, links, and carousel images. Includes shortcode support for Elementor and WPBakery.
 * Version: 1.0.0
 * Author: The Panther Soft - Vaira Maria Lujan
 * Text Domain: hummarh-coming-soon
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HUMMARH_COMING_SOON_VERSION', '1.0.0');
define('HUMMARH_COMING_SOON_FILE', __FILE__);
define('HUMMARH_COMING_SOON_DIR', plugin_dir_path(__FILE__));
define('HUMMARH_COMING_SOON_URL', plugin_dir_url(__FILE__));
final class Hummarh_Coming_Soon_Plugin
{
    private const OPTION_NAME = 'hummarh_coming_soon_settings';

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_bar_menu', [$this, 'add_admin_bar_status'], 90);
        add_action('template_redirect', [$this, 'maybe_render_coming_soon'], 0);
        add_action('vc_before_init', [$this, 'register_wpbakery_element']);
        add_action('elementor/widgets/register', [$this, 'register_elementor_widget']);

        add_shortcode('hummarh_coming_soon', [$this, 'render_shortcode']);
    }

    public static function defaults(): array
    {
        $image_url = HUMMARH_COMING_SOON_URL . 'assets/images/';

        return [
            'enabled' => 0,
            'page_title' => 'Hummarh - Coming Soon',
            'eyebrow' => 'COMING SOON',
            'linkedin_label' => 'LINKEDIN',
            'linkedin_url' => 'https://www.linkedin.com/',
            'phone_label' => '+54 9 2975 371203',
            'phone_url' => 'tel:+5492975371203',
            'mail_label' => 'MAIL',
            'mail_url' => 'mailto:info@hummarh.com',
            'tagline' => "Gestion de Personas y\nDesarrollo Organizacional.",
            'wordmark_logo' => $image_url . 'logo-texto.png',
            'wordmark_logo_mobile' => $image_url . 'logo-texto.png',
            'badge_logo' => $image_url . 'logo-circular.png',
            'carousel_image_1' => $image_url . 'carousel-1.png',
            'carousel_image_2' => $image_url . 'carousel-2.png',
            'carousel_image_3' => $image_url . 'carousel-3.png',
        ];
    }

    public static function get_settings(): array
    {
        $saved = get_option(self::OPTION_NAME, []);

        if (!is_array($saved)) {
            $saved = [];
        }

        return wp_parse_args($saved, self::defaults());
    }

    public static function activate(): void
    {
        if (false === get_option(self::OPTION_NAME, false)) {
            add_option(self::OPTION_NAME, self::defaults());
        }
    }

    public function register_admin_page(): void
    {
        add_menu_page(
            __('Hummarh Coming Soon', 'hummarh-coming-soon'),
            __('Hummarh Coming Soon', 'hummarh-coming-soon'),
            'manage_options',
            'hummarh-coming-soon',
            [$this, 'render_admin_page'],
            'dashicons-clock',
            58
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ('toplevel_page_hummarh-coming-soon' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'hummarh-coming-soon-admin',
            HUMMARH_COMING_SOON_URL . 'assets/css/hummarh-admin.css',
            [],
            $this->asset_version('assets/css/hummarh-admin.css')
        );
        wp_enqueue_script(
            'hummarh-coming-soon-admin',
            HUMMARH_COMING_SOON_URL . 'assets/js/hummarh-admin.js',
            ['jquery'],
            $this->asset_version('assets/js/hummarh-admin.js'),
            true
        );
    }

    public function add_admin_bar_status(WP_Admin_Bar $wp_admin_bar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        $enabled = !empty($settings['enabled']);

        $wp_admin_bar->add_node([
            'id' => 'hummarh-coming-soon-status',
            'title' => $enabled ? __('Coming Soon: ON', 'hummarh-coming-soon') : __('Coming Soon: OFF', 'hummarh-coming-soon'),
            'href' => admin_url('admin.php?page=hummarh-coming-soon'),
            'meta' => [
                'class' => $enabled ? 'hcs-admin-bar-on' : 'hcs-admin-bar-off',
            ],
        ]);
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->maybe_save_settings();
        $settings = self::get_settings();
        $preview_url = add_query_arg('hcs-preview', '1', home_url('/'));
        ?>
        <div class="wrap hcs-admin-wrap">
            <h1><?php esc_html_e('Hummarh Coming Soon', 'hummarh-coming-soon'); ?></h1>
            <?php settings_errors('hummarh_coming_soon_messages'); ?>

            <form method="post" action="">
                <?php wp_nonce_field('hummarh_coming_soon_save', 'hummarh_coming_soon_nonce'); ?>

                <section class="hcs-admin-status">
                    <div>
                        <span class="hcs-admin-eyebrow"><?php esc_html_e('Estado del sitio', 'hummarh-coming-soon'); ?></span>
                        <h2><?php esc_html_e('Activar plantilla coming soon', 'hummarh-coming-soon'); ?></h2>
                        <p><?php esc_html_e('Cuando esta activo, los visitantes ven la plantilla y los administradores siguen viendo el sitio normal.', 'hummarh-coming-soon'); ?></p>
                    </div>
                    <label class="hcs-admin-switch">
                        <input type="checkbox" name="enabled" value="1" <?php checked(!empty($settings['enabled'])); ?>>
                        <span aria-hidden="true"></span>
                        <strong><?php echo !empty($settings['enabled']) ? esc_html__('Activo', 'hummarh-coming-soon') : esc_html__('Inactivo', 'hummarh-coming-soon'); ?></strong>
                    </label>
                </section>

                <div class="hcs-admin-grid">
                    <section class="hcs-admin-card">
                        <h2><?php esc_html_e('Textos principales', 'hummarh-coming-soon'); ?></h2>
                        <?php
                        $this->text_field('page_title', __('Titulo del navegador', 'hummarh-coming-soon'), $settings['page_title']);
                        $this->text_field('eyebrow', __('Texto superior', 'hummarh-coming-soon'), $settings['eyebrow']);
                        $this->textarea_field('tagline', __('Texto bajo el logo', 'hummarh-coming-soon'), $settings['tagline']);
                        ?>
                    </section>

                    <section class="hcs-admin-card">
                        <h2><?php esc_html_e('Botones y enlaces', 'hummarh-coming-soon'); ?></h2>
                        <?php
                        $this->text_field('linkedin_label', __('Texto boton LinkedIn', 'hummarh-coming-soon'), $settings['linkedin_label']);
                        $this->url_field('linkedin_url', __('URL LinkedIn', 'hummarh-coming-soon'), $settings['linkedin_url']);
                        $this->text_field('phone_label', __('Texto boton telefono', 'hummarh-coming-soon'), $settings['phone_label']);
                        $this->url_field('phone_url', __('URL telefono', 'hummarh-coming-soon'), $settings['phone_url']);
                        $this->text_field('mail_label', __('Texto boton mail', 'hummarh-coming-soon'), $settings['mail_label']);
                        $this->url_field('mail_url', __('URL mail', 'hummarh-coming-soon'), $settings['mail_url']);
                        ?>
                    </section>

                    <section class="hcs-admin-card">
                        <h2><?php esc_html_e('Logos', 'hummarh-coming-soon'); ?></h2>
                        <?php
                        $this->image_field('wordmark_logo', __('Logo letras desktop', 'hummarh-coming-soon'), $settings['wordmark_logo']);
                        $this->image_field('wordmark_logo_mobile', __('Logo letras mobile', 'hummarh-coming-soon'), $settings['wordmark_logo_mobile']);
                        $this->image_field('badge_logo', __('Logo circular', 'hummarh-coming-soon'), $settings['badge_logo']);
                        ?>
                    </section>

                    <section class="hcs-admin-card">
                        <h2><?php esc_html_e('Carrusel', 'hummarh-coming-soon'); ?></h2>
                        <p class="description"><?php esc_html_e('Usa tres imagenes. En escritorio se desplazan en vertical; en mobile se desplazan en horizontal.', 'hummarh-coming-soon'); ?></p>
                        <?php
                        $this->image_field('carousel_image_1', __('Imagen 1', 'hummarh-coming-soon'), $settings['carousel_image_1']);
                        $this->image_field('carousel_image_2', __('Imagen 2', 'hummarh-coming-soon'), $settings['carousel_image_2']);
                        $this->image_field('carousel_image_3', __('Imagen 3', 'hummarh-coming-soon'), $settings['carousel_image_3']);
                        ?>
                    </section>
                </div>

                <p class="submit hcs-admin-actions">
                    <button type="submit" class="button button-primary button-hero"><?php esc_html_e('Guardar cambios', 'hummarh-coming-soon'); ?></button>
                    <a class="button button-hero" href="<?php echo esc_url($preview_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver preview', 'hummarh-coming-soon'); ?></a>
                </p>
            </form>

            <section class="hcs-admin-help">
                <h2><?php esc_html_e('Uso en Elementor o WPBakery', 'hummarh-coming-soon'); ?></h2>
                <p><?php esc_html_e('Agrega el shortcode [hummarh_coming_soon] en un bloque Shortcode. Si WPBakery esta activo, tambien aparecera como elemento dentro de la categoria Hummarh.', 'hummarh-coming-soon'); ?></p>
            </section>
        </div>
        <?php
    }

    private function maybe_save_settings(): void
    {
        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
            return;
        }

        if (!isset($_POST['hummarh_coming_soon_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hummarh_coming_soon_nonce'])), 'hummarh_coming_soon_save')) {
            return;
        }

        $settings = [
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
            'page_title' => $this->sanitize_post_text('page_title'),
            'eyebrow' => $this->sanitize_post_text('eyebrow'),
            'linkedin_label' => $this->sanitize_post_text('linkedin_label'),
            'linkedin_url' => $this->sanitize_post_url('linkedin_url'),
            'phone_label' => $this->sanitize_post_text('phone_label'),
            'phone_url' => $this->sanitize_post_url('phone_url'),
            'mail_label' => $this->sanitize_post_text('mail_label'),
            'mail_url' => $this->sanitize_post_url('mail_url'),
            'tagline' => $this->sanitize_post_textarea('tagline'),
            'wordmark_logo' => $this->sanitize_post_url('wordmark_logo'),
            'wordmark_logo_mobile' => $this->sanitize_post_url('wordmark_logo_mobile'),
            'badge_logo' => $this->sanitize_post_url('badge_logo'),
            'carousel_image_1' => $this->sanitize_post_url('carousel_image_1'),
            'carousel_image_2' => $this->sanitize_post_url('carousel_image_2'),
            'carousel_image_3' => $this->sanitize_post_url('carousel_image_3'),
        ];

        update_option(self::OPTION_NAME, wp_parse_args($settings, self::defaults()));

        add_settings_error(
            'hummarh_coming_soon_messages',
            'hummarh_coming_soon_saved',
            __('Configuracion guardada.', 'hummarh-coming-soon'),
            'updated'
        );
    }

    private function sanitize_post_text(string $key): string
    {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
    }

    private function sanitize_post_textarea(string $key): string
    {
        return isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
    }

    private function sanitize_post_url(string $key): string
    {
        return isset($_POST[$key]) ? esc_url_raw(wp_unslash($_POST[$key])) : '';
    }

    private function text_field(string $name, string $label, string $value): void
    {
        ?>
        <label class="hcs-admin-field">
            <span><?php echo esc_html($label); ?></span>
            <input type="text" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
        </label>
        <?php
    }

    private function url_field(string $name, string $label, string $value): void
    {
        ?>
        <label class="hcs-admin-field">
            <span><?php echo esc_html($label); ?></span>
            <input type="url" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
        </label>
        <?php
    }

    private function textarea_field(string $name, string $label, string $value): void
    {
        ?>
        <label class="hcs-admin-field">
            <span><?php echo esc_html($label); ?></span>
            <textarea name="<?php echo esc_attr($name); ?>" rows="4"><?php echo esc_textarea($value); ?></textarea>
        </label>
        <?php
    }

    private function image_field(string $name, string $label, string $value): void
    {
        ?>
        <div class="hcs-admin-field hcs-admin-media-field">
            <span><?php echo esc_html($label); ?></span>
            <div class="hcs-admin-media-row">
                <input class="hcs-admin-media-url" type="url" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
                <button type="button" class="button hcs-admin-upload"><?php esc_html_e('Elegir', 'hummarh-coming-soon'); ?></button>
                <button type="button" class="button hcs-admin-clear"><?php esc_html_e('Quitar', 'hummarh-coming-soon'); ?></button>
            </div>
            <div class="hcs-admin-media-preview">
                <?php if (!empty($value)) : ?>
                    <img src="<?php echo esc_url($value); ?>" alt="">
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function maybe_render_coming_soon(): void
    {
        if (is_admin() || $this->is_login_request() || $this->is_system_request()) {
            return;
        }

        $settings = self::get_settings();
        $is_preview = isset($_GET['hcs-preview']) && current_user_can('manage_options');

        if (empty($settings['enabled']) && !$is_preview) {
            return;
        }

        if (!$is_preview && is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }

        $this->render_full_page($settings);
    }

    private function is_login_request(): bool
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? wp_basename(sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME']))) : '';

        return 'wp-login.php' === $script;
    }

    private function is_system_request(): bool
    {
        if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || (function_exists('wp_doing_cron') && wp_doing_cron())) {
            return true;
        }

        return defined('REST_REQUEST') && REST_REQUEST;
    }

    public function render_shortcode(): string
    {
        $this->enqueue_front_assets();

        return $this->render_template(self::get_settings(), false);
    }

    public function register_wpbakery_element(): void
    {
        if (!function_exists('vc_map')) {
            return;
        }

        vc_map([
            'name' => __('Hummarh Coming Soon', 'hummarh-coming-soon'),
            'base' => 'hummarh_coming_soon',
            'category' => __('Hummarh', 'hummarh-coming-soon'),
            'description' => __('Renderiza la plantilla coming soon editable.', 'hummarh-coming-soon'),
            'icon' => 'dashicons-clock',
            'params' => [],
        ]);
    }

    public function register_elementor_widget($widgets_manager): void
    {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        require_once HUMMARH_COMING_SOON_DIR . 'includes/elementor-widget.php';
        $widgets_manager->register(new Hummarh_Coming_Soon_Elementor_Widget());
    }

    private function render_full_page(array $settings): void
    {
        $this->enqueue_front_assets();
        status_header(200);
        nocache_headers();
        ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($settings['page_title']); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('hcs-body'); ?>>
            <?php
            if (function_exists('wp_body_open')) {
                wp_body_open();
            }
            echo $this->render_template($settings, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            wp_footer();
            ?>
        </body>
        </html>
        <?php
        exit;
    }

    private function enqueue_front_assets(): void
    {
        wp_enqueue_style(
            'hummarh-coming-soon',
            HUMMARH_COMING_SOON_URL . 'assets/css/hummarh-coming-soon.css',
            [],
            $this->asset_version('assets/css/hummarh-coming-soon.css')
        );
        wp_enqueue_script(
            'hummarh-coming-soon',
            HUMMARH_COMING_SOON_URL . 'assets/js/hummarh-coming-soon.js',
            [],
            $this->asset_version('assets/js/hummarh-coming-soon.js'),
            true
        );
    }

    private function asset_version(string $relative_path): string
    {
        $file_path = HUMMARH_COMING_SOON_DIR . ltrim($relative_path, '/\\');

        if (file_exists($file_path)) {
            return (string) filemtime($file_path);
        }

        return HUMMARH_COMING_SOON_VERSION;
    }

    private function render_template(array $settings, bool $full_page): string
    {
        $carousel_images = array_values(array_filter([
            $settings['carousel_image_1'],
            $settings['carousel_image_2'],
            $settings['carousel_image_3'],
        ]));

        ob_start();
        ?>
        <main class="hcs-coming-soon<?php echo $full_page ? ' hcs-coming-soon--full' : ''; ?>">
            <section class="hcs-stage" aria-label="<?php echo esc_attr($settings['page_title']); ?>">
                <div class="hcs-copy">
                    <div class="hcs-topline">
                        <span class="hcs-eyebrow"><?php echo esc_html($settings['eyebrow']); ?></span>
                        <?php echo $this->render_link_button($settings['linkedin_url'], $settings['linkedin_label'], 'hcs-button--outline hcs-button--wide'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="hcs-brand-block">
                        <?php if (!empty($settings['badge_logo'])) : ?>
                            <img class="hcs-badge-logo" src="<?php echo esc_url($settings['badge_logo']); ?>" alt="">
                        <?php endif; ?>

                        <img class="hcs-wordmark hcs-wordmark--desktop" src="<?php echo esc_url($settings['wordmark_logo']); ?>" alt="Hummarh">
                        <img class="hcs-wordmark hcs-wordmark--mobile" src="<?php echo esc_url($settings['wordmark_logo_mobile']); ?>" alt="Hummarh">

                        <div class="hcs-bottom-row">
                            <p class="hcs-tagline"><?php echo nl2br(esc_html($settings['tagline'])); ?></p>
                            <div class="hcs-actions" aria-label="<?php esc_attr_e('Contact links', 'hummarh-coming-soon'); ?>">
                                <?php echo $this->render_link_button($settings['phone_url'], $settings['phone_label'], 'hcs-button--light hcs-button--compact'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php echo $this->render_link_button($settings['mail_url'], $settings['mail_label'], 'hcs-button--light hcs-button--compact'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="hcs-visual" aria-label="<?php esc_attr_e('Hummarh gallery and contact', 'hummarh-coming-soon'); ?>">
                    <div class="hcs-carousel" data-hcs-carousel>
                        <div class="hcs-carousel__track">
                            <?php foreach ($carousel_images as $index => $image_url) : ?>
                                <figure class="hcs-carousel__slide">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(sprintf(__('Carousel image %d', 'hummarh-coming-soon'), $index + 1)); ?>">
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            </section>
        </main>
        <?php
        return (string) ob_get_clean();
    }

    private function render_link_button(string $url, string $label, string $classes): string
    {
        if (empty($url) || empty($label)) {
            return '';
        }

        return sprintf(
            '<a class="hcs-button %1$s" href="%2$s" target="_blank" rel="noopener">%3$s</a>',
            esc_attr($classes),
            esc_url($url),
            esc_html($label)
        );
    }
}

register_activation_hook(__FILE__, ['Hummarh_Coming_Soon_Plugin', 'activate']);
Hummarh_Coming_Soon_Plugin::instance();
