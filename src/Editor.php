<?php

namespace TidesToday\TideTimes;

use TidesToday\TideTimes\Factory\TideTimesFactory;

/**
 * Class Editor
 * @package TidesToday\TideTimes
 */
class Editor
{
    /**
     * Editor constructor.
     */
    public function __construct()
    {
        add_action( 'enqueue_block_editor_assets', [$this, 'enqueue'] );
        add_action( 'init', [$this, 'registerBlock'] );
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

    function registerBlock() {
        register_block_type( 'tides-today/editor-block', array(
            'render_callback' => [$this, 'renderCallback'],
        ));
    }

    public function renderCallback( $attributes, $content ) {        
        $uniqueId = 'tidewidget__52';
        $scriptSrc = esc_url( 'https://tides.today/en/%F0%9F%8C%8D/england/london/chelsea-bridge/widget.js' );
        var_dump( $attributes );
        $includeMap = $attributes["includeMap"] ? "true" : "false";
        $includeWeather = $attributes["includeWeather"] ? "true" : "false";
        $includeTitle = $attributes["includeTitle"] ? "true" : "false";
        $includeStyles = $attributes["includeStyles"] ? "true" : "false";
        
        return "<script
            type=\"text/javascript\"
            onload=\"createTideInstance('$uniqueId', { includeMap: $includeMap, includeWeather: $includeWeather, includeTitle: $includeTitle, includeStyles: $includeStyles, numberDays: $attributes[daysToShow], weatherUnit: '$attributes[weatherUnit]' })\" 
            src=\"$scriptSrc\" async></script>
            <div id=\"$uniqueId\"></div>";
    }

}
