<?php

if (!defined('ABSPATH')) {
    exit;
}

class Hummarh_Coming_Soon_Elementor_Widget extends \Elementor\Widget_Base
{
    public function get_name(): string
    {
        return 'hummarh_coming_soon';
    }

    public function get_title(): string
    {
        return __('Hummarh Coming Soon', 'hummarh-coming-soon');
    }

    public function get_icon(): string
    {
        return 'eicon-clock-o';
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    public function get_keywords(): array
    {
        return ['hummarh', 'coming soon', 'maintenance'];
    }

    protected function render(): void
    {
        echo do_shortcode('[hummarh_coming_soon]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
