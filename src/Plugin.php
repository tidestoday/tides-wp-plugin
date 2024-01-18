<?php

namespace TidesToday\TideTimes;

use TidesToday\TideTimes\Polyfill\Php55Polyfill;

/**
 * Class Plugin
 * @package TidesToday\TideTimes
 */
class Plugin
{
    const TEXTDOMAIN = 'tides-today';

    const ENDPOINT = 'https://tides.today/widget-api/locations/';

    /** @var string $pluginBase */
    private $pluginBase;

    /**
     * Plugin constructor.
     * @param $pluginBase
     */
    public function __construct($pluginBase)
    {
        $this
            ->registerPolyfills()
            ->setPluginBase($pluginBase)
            ->registerEditor()
            ->registerShortcode();

        add_action('widgets_init', [$this, 'registerWidget']);
        add_action('plugins_loaded', [$this, 'initiateLocalisation']);
    }

    /**
     * Loads PHP previous version polyfills.
     *
     * @return $this
     */
    private function registerPolyfills()
    {
        new Php55Polyfill();
        return $this;
    }

    /**
     * Initiates localisation.
     */
    public function initiateLocalisation()
    {
        load_plugin_textdomain(self::TEXTDOMAIN, false, $this->getPluginBase(DIRECTORY_SEPARATOR . 'languages'));
    }

    /**
     * Registers the shortcode.
     *
     * @return $this
     */
    private function registerShortcode()
    {
        new Shortcode();
        return $this;
    }

    /**
     * Registers the editor.
     *
     * @return $this
     */
    private function registerEditor()
    {
        new Editor();
        return $this;
    }

    /**
     * Registers the widget.
     */
    public function registerWidget()
    {
        register_widget('TidesToday\TideTimes\Widget');
    }

    /**
     * @param string $path
     * @return string
     */
    public function getPluginBase($path = '')
    {
        return $this->pluginBase . $path;
    }

    /**
     * @param $pluginBase
     * @return $this
     */
    public function setPluginBase($pluginBase)
    {
        $this->pluginBase = $pluginBase;
        return $this;
    }
}
