<?php

namespace TidesToday\TideTimes;

use TidesToday\TideTimes\Factory\TideTimesFactory;

/**
 * Class DynamicBlock
 * @package TidesToday\TideTimes
 */
class DynamicBlock
{
    /**
     * Shortcode constructor.
     */
    public function __construct()
    {
        add_action( 'enqueue_block_editor_assets', [$this, 'enqueue'] );
    }

    /**
     * @param array $attr
     * @param string|null $content
     * @return string
     */
    public function enqueue($attr = [], $content = null)
    {
        wp_enqueue_script(
            'tides-editor-js',
            plugins_url( 'editor.js', __FILE__ ),
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
            '1.0.0',
            true
        );
    
        wp_enqueue_style(
            'tides-editor-css',
            plugins_url( 'style.css', __FILE__ ),
            array(),
            '1.0.0'
        );
    }
}
