<?php

if (! defined('ABSPATH')) {
	exit;
}

class TTTW_Plugin {
	const OPTION_KEY     = 'tttw_widgets';
	const CACHE_GROUP    = 'tttw';
	const CACHE_PREFIX   = 'tttw_cache_';
	const ADMIN_SLUG     = 'tttw-builder';
	const ADMIN_ADD_SLUG = 'tttw-add-widget';
	const REST_NAMESPACE = 'tides-today/v1';
	const CATALOG_TTL    = 86400;
	const SCRIPT_TTL     = 300;
	const API_BASE       = 'https://api.tidestoday.io/widgets-api/js-v1';

	private static $instance = null;

	private $environment_errors = null;

	public static function instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate() {
		if (false === get_option(self::OPTION_KEY, false)) {
			add_option(self::OPTION_KEY, array(), '', 'no');
		}
	}

	private function __construct() {
		add_action('plugins_loaded', array($this, 'load_textdomain'));
		add_action('admin_notices', array($this, 'render_environment_notice'));

		if (! $this->environment_is_ready()) {
			return;
		}

		add_action('admin_menu', array($this, 'register_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
		add_action('init', array($this, 'register_runtime_features'));
		add_action('widgets_init', array($this, 'register_sidebar_widget'));
		add_action('rest_api_init', array($this, 'register_rest_routes'));
		add_filter('rest_pre_serve_request', array($this, 'serve_proxy_rest_response'), 10, 4);
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
		add_action('wp_ajax_tttw_get_countries', array($this, 'ajax_get_countries'));
		add_action('wp_ajax_tttw_get_regions', array($this, 'ajax_get_regions'));
		add_action('wp_ajax_tttw_get_locations', array($this, 'ajax_get_locations'));
		add_action('admin_post_tttw_save_widget', array($this, 'handle_save_widget'));
		add_action('admin_post_tttw_delete_widget', array($this, 'handle_delete_widget'));
	}

	public function load_textdomain() {
		load_plugin_textdomain('tides-today', false, dirname(plugin_basename(TTTW_PLUGIN_FILE)) . '/languages');
	}

	private function get_asset_version($relative_path) {
		$path = TTTW_PLUGIN_DIR . ltrim($relative_path, '/');

		if (file_exists($path)) {
			return (string) filemtime($path);
		}

		return TTTW_PLUGIN_VERSION;
	}

	public function register_runtime_features() {
		add_shortcode('tides_today_widget', array($this, 'render_shortcode'));
		add_shortcode('tides_today_tides_weather', array($this, 'render_shortcode'));

		wp_register_script(
			'tttw-block',
			TTTW_PLUGIN_URL . 'assets/js/block.js',
			array('wp-blocks', 'wp-element', 'wp-components', 'wp-i18n'),
			$this->get_asset_version('assets/js/block.js'),
			true
		);

		if (function_exists('register_block_type')) {
			register_block_type(
				'tides-today/tides-weather',
				array(
					'editor_script'   => 'tttw-block',
					'render_callback' => array($this, 'render_block'),
					'attributes'      => array(
						'widgetId' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				)
			);
		}
	}

	public function register_sidebar_widget() {
		register_widget('TTTW_Sidebar_Widget');
	}

	public function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/proxy/(?P<widget_id>[A-Za-z0-9_-]+)/(?P<script>widget(?:-init)?\.js)',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'handle_proxy_request'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/preview/(?P<script>widget(?:-init)?\.js)',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'handle_preview_proxy_request'),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function serve_proxy_rest_response($served, $result, $request, $server) {
		$route = $request instanceof WP_REST_Request ? $request->get_route() : '';

		if (0 !== strpos($route, '/' . self::REST_NAMESPACE . '/proxy/') && 0 !== strpos($route, '/' . self::REST_NAMESPACE . '/preview/')) {
			return $served;
		}

		if ($result instanceof WP_REST_Response) {
			$data = $result->get_data();
		} else {
			$data = $result;
		}

		if (! is_string($data)) {
			$data = '';
		}

		if (function_exists('status_header')) {
			status_header(200);
		}

		header('Content-Type: application/javascript; charset=' . get_option('blog_charset'), true);
		header('Cache-Control: max-age=' . self::SCRIPT_TTL . ', must-revalidate', true);

		echo $data;

		return true;
	}

	public function enqueue_admin_assets($hook_suffix) {
		$page = ! empty($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

		if (! in_array($page, array(self::ADMIN_SLUG, self::ADMIN_ADD_SLUG), true)) {
			return;
		}

		wp_enqueue_style(
			'tttw-admin',
			TTTW_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$this->get_asset_version('assets/css/admin.css')
		);

		wp_enqueue_script(
			'tttw-admin',
			TTTW_PLUGIN_URL . 'assets/js/admin.js',
			array('jquery'),
			$this->get_asset_version('assets/js/admin.js'),
			true
		);

		wp_localize_script(
			'tttw-admin',
			'TTTWAdmin',
			array(
				'ajaxUrl'       => admin_url('admin-ajax.php'),
				'nonce'         => wp_create_nonce('tttw_admin'),
				'previewBaseUrl' => trailingslashit(rest_url(self::REST_NAMESPACE . '/preview')),
				'currentWidget' => $this->get_editing_widget(),
				'i18n'          => array(
					'loading'             => __('Loading Tides Today data...', 'tides-today'),
					'selectCountry'       => __('Select a country', 'tides-today'),
					'selectRegion'        => __('Select a region', 'tides-today'),
					'selectLocation'      => __('Select a location', 'tides-today'),
					'chooseLanguageFirst' => __('Choose a language first', 'tides-today'),
					'chooseCountryFirst'  => __('Choose a country first', 'tides-today'),
					'chooseRegionFirst'   => __('Choose a region first', 'tides-today'),
					'loadError'           => __('We could not load data from Tides Today. Please try again.', 'tides-today'),
					'deleteConfirm'       => __('Delete this saved widget?', 'tides-today'),
					'previewEmpty'        => __('Choose a location to preview the widget.', 'tides-today'),
					'previewLoading'      => __('Loading live preview...', 'tides-today'),
					'previewError'        => __('We could not build the live preview right now.', 'tides-today'),
					'previewTitle'        => __('Tides Today widget preview', 'tides-today'),
				),
			)
		);
	}

	public function enqueue_block_editor_assets() {
		if (! wp_script_is('tttw-block', 'registered')) {
			return;
		}

		wp_add_inline_script(
			'tttw-block',
			'window.TTTWBlockData = ' . wp_json_encode(
				array(
					'widgets' => $this->get_block_widget_data(),
					'labels'  => array(
						'title'        => __('Tides Today Tides and Weather', 'tides-today'),
						'description'  => __('Insert a saved Tides Today tide and weather widget.', 'tides-today'),
						'selectWidget' => __('Select a saved widget', 'tides-today'),
						'instructions' => __('Choose which saved widget to embed on the front end.', 'tides-today'),
						'empty'        => __('Create a saved widget in Tides Today before using this block.', 'tides-today'),
						'help'         => __('Saved widgets are managed in the Tides Today admin screen.', 'tides-today'),
					),
				)
			) . ';',
			'before'
		);
	}

	public function register_admin_menu() {
		add_menu_page(
			__('Tides Today', 'tides-today'),
			__('Tides Today', 'tides-today'),
			'manage_options',
			self::ADMIN_SLUG,
			array($this, 'render_admin_page'),
			'dashicons-location-alt',
			56
		);

		add_submenu_page(
			self::ADMIN_SLUG,
			__('All widgets', 'tides-today'),
			__('All widgets', 'tides-today'),
			'manage_options',
			self::ADMIN_SLUG,
			array($this, 'render_admin_page')
		);

		add_submenu_page(
			self::ADMIN_SLUG,
			__('Add Widget', 'tides-today'),
			__('Add Widget', 'tides-today'),
			'manage_options',
			self::ADMIN_ADD_SLUG,
			array($this, 'render_admin_page')
		);
	}

	public function render_environment_notice() {
		$errors = $this->get_environment_errors();

		if (empty($errors) || ! current_user_can('activate_plugins')) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>' . esc_html__('Tides Today Tides and Weather cannot finish loading:', 'tides-today') . '</strong></p><ul>';

		foreach ($errors as $error) {
			echo '<li>' . esc_html($error) . '</li>';
		}

		echo '</ul></div>';
	}

	public function render_admin_page() {
		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to manage Tides Today widgets.', 'tides-today'));
		}

		$editing_widget = $this->get_editing_widget();
		$widgets        = $this->get_widgets();
		$settings       = $this->get_widget_settings($editing_widget);
		$is_builder     = $this->is_builder_view();
		?>
		<div class="wrap tttw-admin">
			<h1><?php esc_html_e('Tides Today Tides and Weather', 'tides-today'); ?></h1>
			<p><?php esc_html_e('Create reusable tide and weather widgets for shortcodes, sidebars, and the block editor.', 'tides-today'); ?></p>

			<?php $this->render_admin_page_notice(); ?>

			<div class="tttw-admin__stack">
				<?php if (! $is_builder) : ?>
					<div class="tttw-card">
						<div class="tttw-overview">
							<div class="tttw-overview__intro">
								<img
									class="tttw-overview__logo"
									src="<?php echo esc_url('https://tides-assets.lon1.cdn.digitaloceanspaces.com/prod/media/Halo_750x750_min_d0b74a0f1e.png'); ?>"
									alt="<?php esc_attr_e('Tides Today logo', 'tides-today'); ?>"
								/>
								<div>
									<h2><?php esc_html_e('Tides Today Tides and Weather', 'tides-today'); ?></h2>
									<p><?php esc_html_e('The Tides Today Tides and Weather Plugin allows you to add tide times and weather, for over 8,000 locations world-wide.', 'tides-today'); ?></p>
								</div>
							</div>

							<div class="tttw-overview__links">
								<ul>
									<?php foreach ($this->get_overview_links() as $link) : ?>
										<li>
											<a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener noreferrer">
												<span class="tttw-overview__icon" aria-hidden="true"><?php echo $this->get_overview_icon_markup($link['icon']); ?></span>
												<span><?php echo esc_html($link['label']); ?></span>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</div>

					<div class="tttw-card">
						<?php if (empty($widgets)) : ?>
							<h2><?php esc_html_e('Saved widgets', 'tides-today'); ?></h2>
							<p><?php esc_html_e('There are no Tides Today widgets yet. Create one to get started', 'tides-today'); ?></p>
							<p>
								<a class="button button-primary" href="<?php echo esc_url($this->get_admin_builder_url()); ?>">
									<?php esc_html_e('Create a widget', 'tides-today'); ?>
								</a>
							</p>
						<?php else : ?>
							<div class="tttw-card__toolbar">
								<h2><?php esc_html_e('Saved widgets', 'tides-today'); ?></h2>
								<a class="button button-primary" href="<?php echo esc_url($this->get_admin_builder_url()); ?>">
									<?php esc_html_e('Create widget', 'tides-today'); ?>
								</a>
							</div>

							<table class="widefat striped tttw-saved-widgets">
								<thead>
									<tr>
										<th scope="col"><?php esc_html_e('Name', 'tides-today'); ?></th>
										<th scope="col"><?php esc_html_e('Language', 'tides-today'); ?></th>
										<th scope="col"><?php esc_html_e('Location', 'tides-today'); ?></th>
										<th scope="col"><?php esc_html_e('Shortcode', 'tides-today'); ?></th>
										<th scope="col"><?php esc_html_e('Actions', 'tides-today'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($widgets as $widget) : ?>
										<tr>
											<td><?php echo esc_html($widget['name']); ?></td>
											<td><?php echo esc_html($this->get_language_label($widget['language'])); ?></td>
											<td><?php echo esc_html($this->get_widget_location_label($widget)); ?></td>
											<td><code><?php echo esc_html($this->get_shortcode_string($widget)); ?></code></td>
											<td class="tttw-actions">
												<a class="button button-secondary" href="<?php echo esc_url($this->get_admin_builder_url($widget['id'])); ?>">
													<?php esc_html_e('Edit', 'tides-today'); ?>
												</a>
												<form class="tttw-inline-form tttw-delete-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
													<input type="hidden" name="action" value="tttw_delete_widget" />
													<input type="hidden" name="widget_id" value="<?php echo esc_attr($widget['id']); ?>" />
													<?php wp_nonce_field('tttw_delete_widget_' . $widget['id'], 'tttw_delete_nonce'); ?>
													<button type="submit" class="button-link-delete"><?php esc_html_e('Delete', 'tides-today'); ?></button>
												</form>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="tttw-card tttw-card--main">
						<div class="tttw-card__header">
							<h2><?php echo $editing_widget ? esc_html__('Edit widget', 'tides-today') : esc_html__('Create widget', 'tides-today'); ?></h2>
							<a class="button button-secondary" href="<?php echo esc_url($this->get_admin_overview_url()); ?>">
								<?php esc_html_e('Back to widgets', 'tides-today'); ?>
							</a>
						</div>

						<p id="tttw-api-status" class="tttw-api-status" aria-live="polite"></p>

						<div class="tttw-builder-layout">
							<div class="tttw-builder-layout__form">
								<form id="tttw-builder-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
									<input type="hidden" name="action" value="tttw_save_widget" />
									<input type="hidden" name="widget_id" value="<?php echo $editing_widget ? esc_attr($editing_widget['id']) : ''; ?>" />
									<?php wp_nonce_field('tttw_save_widget', 'tttw_nonce'); ?>

									<table class="form-table" role="presentation">
										<tbody>
											<tr>
												<th scope="row">
													<label for="tttw-widget-name"><?php esc_html_e('Unique name', 'tides-today'); ?></label>
												</th>
												<td>
													<input
														type="text"
														class="regular-text"
														id="tttw-widget-name"
														name="widget_name"
														required="required"
														value="<?php echo $editing_widget ? esc_attr($editing_widget['name']) : ''; ?>"
													/>
													<p class="description"><?php esc_html_e('This name is used in shortcode, block, and widget pickers.', 'tides-today'); ?></p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-language"><?php esc_html_e('Language', 'tides-today'); ?></label>
												</th>
												<td>
													<select id="tttw-language" name="language">
														<option value="en" <?php selected(isset($editing_widget['language']) ? $editing_widget['language'] : 'en', 'en'); ?>>
															<?php esc_html_e('English', 'tides-today'); ?>
														</option>
														<option value="fr" <?php selected(isset($editing_widget['language']) ? $editing_widget['language'] : '', 'fr'); ?>>
															<?php esc_html_e('French', 'tides-today'); ?>
														</option>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-country"><?php esc_html_e('Country', 'tides-today'); ?></label>
												</th>
												<td>
													<select id="tttw-country" name="country_id" disabled="disabled">
														<option value=""><?php esc_html_e('Select a country', 'tides-today'); ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-region"><?php esc_html_e('Region', 'tides-today'); ?></label>
												</th>
												<td>
													<select id="tttw-region" name="region_id" disabled="disabled">
														<option value=""><?php esc_html_e('Select a region', 'tides-today'); ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-location"><?php esc_html_e('Location', 'tides-today'); ?></label>
												</th>
												<td>
													<select id="tttw-location" name="location_id" disabled="disabled">
														<option value=""><?php esc_html_e('Select a location', 'tides-today'); ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-number-days"><?php esc_html_e('Days to show', 'tides-today'); ?></label>
												</th>
												<td>
													<select id="tttw-number-days" name="number_days">
														<?php for ($day = 1; $day <= 5; $day++) : ?>
															<option value="<?php echo esc_attr($day); ?>" <?php selected($settings['number_days'], $day); ?>>
																<?php echo esc_html($day); ?>
															</option>
														<?php endfor; ?>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row"><?php esc_html_e('Display options', 'tides-today'); ?></th>
												<td>
													<fieldset>
														<label for="tttw-include-title">
															<input type="checkbox" id="tttw-include-title" name="include_title" value="1" <?php checked($settings['include_title']); ?> />
															<?php esc_html_e('Include title', 'tides-today'); ?>
														</label>
														<br />
														<label for="tttw-include-map">
															<input type="checkbox" id="tttw-include-map" name="include_map" value="1" <?php checked($settings['include_map']); ?> />
															<?php esc_html_e('Include map', 'tides-today'); ?>
														</label>
														<br />
														<label for="tttw-include-weather">
															<input type="checkbox" id="tttw-include-weather" name="include_weather" value="1" <?php checked($settings['include_weather']); ?> />
															<?php esc_html_e('Include weather', 'tides-today'); ?>
														</label>
														<br />
														<label for="tttw-include-styles">
															<input type="checkbox" id="tttw-include-styles" name="include_styles" value="1" <?php checked($settings['include_styles']); ?> />
															<?php esc_html_e('Include base styles', 'tides-today'); ?>
														</label>
													</fieldset>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-custom-css"><?php esc_html_e('Custom CSS', 'tides-today'); ?></label>
												</th>
												<td>
													<textarea
														id="tttw-custom-css"
														name="custom_css"
														rows="8"
														class="large-text code"
														placeholder=".tides-widget__container { border-radius: 12px; }"
													><?php echo esc_textarea($settings['custom_css']); ?></textarea>
													<p class="description"><?php esc_html_e('If filled, this CSS is printed inline with the widget on the front end.', 'tides-today'); ?></p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-weather-unit"><?php esc_html_e('Weather unit', 'tides-today'); ?></label>
												</th>
												<td>
													<select id="tttw-weather-unit" name="weather_unit">
														<option value="c" <?php selected($settings['weather_unit'], 'c'); ?>><?php esc_html_e('Celsius', 'tides-today'); ?></option>
														<option value="f" <?php selected($settings['weather_unit'], 'f'); ?>><?php esc_html_e('Fahrenheit', 'tides-today'); ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="tttw-height-unit"><?php esc_html_e('Height unit', 'tides-today'); ?></label>
												</th>
												<td>
													<select id="tttw-height-unit" name="height_unit">
														<option value="m" <?php selected($settings['height_unit'], 'm'); ?>><?php esc_html_e('Meters', 'tides-today'); ?></option>
														<option value="ft" <?php selected($settings['height_unit'], 'ft'); ?>><?php esc_html_e('Feet', 'tides-today'); ?></option>
													</select>
												</td>
											</tr>
										</tbody>
									</table>

									<?php
									submit_button(
										$editing_widget ? __('Update widget', 'tides-today') : __('Save widget', 'tides-today'),
										'primary',
										'submit',
										false
									);
									?>
								</form>
							</div>

							<div class="tttw-builder-layout__preview">
								<div class="tttw-preview">
									<div class="tttw-preview__header">
										<h3><?php esc_html_e('Live preview', 'tides-today'); ?></h3>
										<p><?php esc_html_e('The preview updates after you choose a location and whenever you change the widget options.', 'tides-today'); ?></p>
									</div>
									<div id="tttw-preview-placeholder" class="tttw-preview__placeholder">
										<?php esc_html_e('Choose a location to preview the widget.', 'tides-today'); ?>
									</div>
									<iframe
										id="tttw-preview-frame"
										class="tttw-preview__frame"
										title="<?php esc_attr_e('Tides Today widget preview', 'tides-today'); ?>"
										scrolling="no"
									></iframe>
								</div>
							</div>
						</div>
					</div>

					<?php if ($editing_widget) : ?>
						<div class="tttw-card">
							<h2><?php esc_html_e('Usage', 'tides-today'); ?></h2>
							<p><?php esc_html_e('Use this shortcode anywhere shortcodes are supported:', 'tides-today'); ?></p>
							<code class="tttw-shortcode"><?php echo esc_html($this->get_shortcode_string($editing_widget)); ?></code>
							<p><?php esc_html_e('The same saved widget will also appear in the classic Widgets screen and in the Gutenberg block dropdown.', 'tides-today'); ?></p>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function handle_save_widget() {
		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to save Tides Today widgets.', 'tides-today'));
		}

		check_admin_referer('tttw_save_widget', 'tttw_nonce');

		$widget_id   = isset($_POST['widget_id']) ? sanitize_key(wp_unslash($_POST['widget_id'])) : '';
		$widget_name = isset($_POST['widget_name']) ? sanitize_text_field(wp_unslash($_POST['widget_name'])) : '';
		$language    = $this->sanitize_language(isset($_POST['language']) ? wp_unslash($_POST['language']) : 'en');
		$country_id  = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
		$region_id   = isset($_POST['region_id']) ? absint(wp_unslash($_POST['region_id'])) : 0;
		$location_id = isset($_POST['location_id']) ? absint(wp_unslash($_POST['location_id'])) : 0;
		$widgets     = $this->get_widgets();

		if (empty($widget_name) || empty($country_id) || empty($region_id) || empty($location_id)) {
			$this->redirect_to_admin(
				array(
					'tttw_notice'  => 'error',
					'tttw_message' => __('Please complete every required field before saving.', 'tides-today'),
					'edit'         => $widget_id,
				)
			);
		}

		foreach ($widgets as $existing_widget) {
			if ($existing_widget['id'] === $widget_id) {
				continue;
			}

			if (0 === strcasecmp($existing_widget['name'], $widget_name)) {
				$this->redirect_to_admin(
					array(
						'tttw_notice'  => 'error',
						'tttw_message' => __('Widget names must be unique.', 'tides-today'),
						'edit'         => $widget_id,
					)
				);
			}
		}

		$countries = $this->get_countries($language);

		if (is_wp_error($countries)) {
			$this->redirect_to_admin(
				array(
					'tttw_notice'  => 'error',
					'tttw_message' => $countries->get_error_message(),
					'edit'         => $widget_id,
				)
			);
		}

		$country = $this->find_item_by_id($countries, $country_id);

		if (empty($country)) {
			$this->redirect_to_admin(
				array(
					'tttw_notice'  => 'error',
					'tttw_message' => __('The selected country is no longer available.', 'tides-today'),
					'edit'         => $widget_id,
				)
			);
		}

		$regions = $this->get_regions($language, $country_id);

		if (is_wp_error($regions)) {
			$this->redirect_to_admin(
				array(
					'tttw_notice'  => 'error',
					'tttw_message' => $regions->get_error_message(),
					'edit'         => $widget_id,
				)
			);
		}

		$region = $this->find_item_by_id($regions, $region_id);

		if (empty($region)) {
			$this->redirect_to_admin(
				array(
					'tttw_notice'  => 'error',
					'tttw_message' => __('The selected region is no longer available.', 'tides-today'),
					'edit'         => $widget_id,
				)
			);
		}

		$locations = $this->get_locations($language, $country_id, $region_id);

		if (is_wp_error($locations)) {
			$this->redirect_to_admin(
				array(
					'tttw_notice'  => 'error',
					'tttw_message' => $locations->get_error_message(),
					'edit'         => $widget_id,
				)
			);
		}

		$location = $this->find_item_by_id($locations, $location_id);

		if (empty($location)) {
			$this->redirect_to_admin(
				array(
					'tttw_notice'  => 'error',
					'tttw_message' => __('The selected location is no longer available.', 'tides-today'),
					'edit'         => $widget_id,
				)
			);
		}

		if (empty($widget_id) || empty($widgets[ $widget_id ])) {
			$widget_id = $this->generate_widget_id();
		}

		$timestamp = current_time('mysql');
		$widget    = array(
			'id'         => $widget_id,
			'name'       => $widget_name,
			'language'   => $language,
			'country'    => $country,
			'region'     => $region,
			'location'   => $location,
			'settings'   => $this->sanitize_widget_settings(
				array(
					'number_days'     => isset($_POST['number_days']) ? wp_unslash($_POST['number_days']) : '',
					'include_map'     => ! empty($_POST['include_map']),
					'include_weather' => ! empty($_POST['include_weather']),
					'include_styles'  => ! empty($_POST['include_styles']),
			'include_title'   => ! empty($_POST['include_title']),
					'custom_css'      => isset($_POST['custom_css']) ? wp_unslash($_POST['custom_css']) : '',
					'weather_unit'    => isset($_POST['weather_unit']) ? wp_unslash($_POST['weather_unit']) : '',
					'height_unit'     => isset($_POST['height_unit']) ? wp_unslash($_POST['height_unit']) : '',
				)
			),
			'created_at' => ! empty($widgets[ $widget_id ]['created_at']) ? $widgets[ $widget_id ]['created_at'] : $timestamp,
			'updated_at' => $timestamp,
		);

		$widgets[ $widget_id ] = $widget;

		update_option(self::OPTION_KEY, $widgets);

		$this->redirect_to_admin(
			array(
				'tttw_notice' => 'saved',
				'edit'        => $widget_id,
			)
		);
	}

	public function handle_delete_widget() {
		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to delete Tides Today widgets.', 'tides-today'));
		}

		$widget_id = isset($_POST['widget_id']) ? sanitize_key(wp_unslash($_POST['widget_id'])) : '';

		check_admin_referer('tttw_delete_widget_' . $widget_id, 'tttw_delete_nonce');

		$widgets = $this->get_widgets();

		if (! empty($widget_id) && isset($widgets[ $widget_id ])) {
			unset($widgets[ $widget_id ]);
			update_option(self::OPTION_KEY, $widgets);
		}

		$this->redirect_to_admin(
			array(
				'tttw_notice' => 'deleted',
			)
		);
	}

	public function ajax_get_countries() {
		$this->verify_admin_ajax_request();

		$language = $this->sanitize_language(isset($_GET['language']) ? wp_unslash($_GET['language']) : 'en');
		$data     = $this->get_countries($language);

		if (is_wp_error($data)) {
			wp_send_json_error(
				array(
					'message' => $data->get_error_message(),
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'items' => array_values($data),
			)
		);
	}

	public function ajax_get_regions() {
		$this->verify_admin_ajax_request();

		$language   = $this->sanitize_language(isset($_GET['language']) ? wp_unslash($_GET['language']) : 'en');
		$country_id = isset($_GET['country_id']) ? absint(wp_unslash($_GET['country_id'])) : 0;
		$data       = $this->get_regions($language, $country_id);

		if (is_wp_error($data)) {
			wp_send_json_error(
				array(
					'message' => $data->get_error_message(),
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'items' => array_values($data),
			)
		);
	}

	public function ajax_get_locations() {
		$this->verify_admin_ajax_request();

		$language   = $this->sanitize_language(isset($_GET['language']) ? wp_unslash($_GET['language']) : 'en');
		$country_id = isset($_GET['country_id']) ? absint(wp_unslash($_GET['country_id'])) : 0;
		$region_id  = isset($_GET['region_id']) ? absint(wp_unslash($_GET['region_id'])) : 0;
		$data       = $this->get_locations($language, $country_id, $region_id);

		if (is_wp_error($data)) {
			wp_send_json_error(
				array(
					'message' => $data->get_error_message(),
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'items' => array_values($data),
			)
		);
	}

	public function handle_proxy_request($request) {
		$widget_id = sanitize_key($request['widget_id']);
		$script    = isset($request['script']) ? sanitize_text_field($request['script']) : '';
		$widget    = $this->get_widget($widget_id);

		if (empty($widget)) {
			return $this->javascript_response($this->build_console_error_script(__('Saved Tides Today widget not found.', 'tides-today')));
		}

		if ('widget.js' === $script) {
			$body = $this->get_runtime_proxy_script($widget);
		} elseif ('widget-init.js' === $script) {
			$body = $this->get_init_proxy_script($widget);
		} else {
			$body = new WP_Error('tttw_invalid_script', __('Unknown proxy script requested.', 'tides-today'));
		}

		if (is_wp_error($body)) {
			return $this->javascript_response($this->build_console_error_script($body->get_error_message()));
		}

		return $this->javascript_response($body);
	}

	public function handle_preview_proxy_request($request) {
		$script  = isset($request['script']) ? sanitize_text_field($request['script']) : '';
		$preview = $this->get_preview_request_context($request);

		if (is_wp_error($preview)) {
			return $this->javascript_response($this->build_console_error_script($preview->get_error_message()));
		}

		$body = $this->get_cached_remote_body($this->build_preview_script_url($preview, $script), self::SCRIPT_TTL);

		if (is_wp_error($body)) {
			return $this->javascript_response($this->build_console_error_script($body->get_error_message()));
		}

		return $this->javascript_response($body);
	}

	public function render_shortcode($atts) {
		$atts = shortcode_atts(
			array(
				'id'   => '',
				'name' => '',
			),
			$atts,
			'tides_today_widget'
		);

		if (! empty($atts['id'])) {
			return $this->render_saved_widget($atts['id']);
		}

		if (! empty($atts['name'])) {
			$widget = $this->get_widget_by_name($atts['name']);

			if (! empty($widget)) {
				return $this->render_saved_widget($widget);
			}
		}

		return '';
	}

	public function render_block($attributes) {
		$widget_id = ! empty($attributes['widgetId']) ? sanitize_key($attributes['widgetId']) : '';

		if (empty($widget_id)) {
			return '';
		}

		return $this->render_saved_widget($widget_id);
	}

	public function render_saved_widget($widget_reference) {
		$widget = is_array($widget_reference) ? $widget_reference : $this->get_widget($widget_reference);

		if (empty($widget)) {
			return '';
		}

		$container_id = $this->get_container_id($widget);
		$widget_src   = esc_url($this->get_proxy_url($widget, 'widget.js'));
		$init_src     = esc_url($this->get_proxy_url($widget, 'widget-init.js'));
		$custom_css   = $this->get_widget_custom_css_markup($widget);

		return $custom_css .
			'<script type="text/javascript" src="' . $widget_src . '" async></script>' .
			'<script type="text/javascript" src="' . $init_src . '" async></script>' .
			'<div id="' . esc_attr($container_id) . '"></div>';
	}

	public function get_widgets() {
		$stored_widgets = get_option(self::OPTION_KEY, array());
		$widgets        = array();

		if (! is_array($stored_widgets)) {
			return $widgets;
		}

		foreach ($stored_widgets as $widget_id => $widget) {
			$normalized_widget = $this->normalize_saved_widget($widget, $widget_id);

			if (empty($normalized_widget)) {
				continue;
			}

			$widgets[ $normalized_widget['id'] ] = $normalized_widget;
		}

		uasort($widgets, array($this, 'sort_widgets_by_name'));

		return $widgets;
	}

	public function get_widget($widget_id) {
		$widget_id = sanitize_key($widget_id);
		$widgets   = $this->get_widgets();

		return isset($widgets[ $widget_id ]) ? $widgets[ $widget_id ] : array();
	}

	public function get_widget_by_name($widget_name) {
		$widgets = $this->get_widgets();

		foreach ($widgets as $widget) {
			if (0 === strcasecmp($widget['name'], $widget_name)) {
				return $widget;
			}
		}

		return array();
	}

	public function get_widget_location_label($widget) {
		$parts = array();

		if (! empty($widget['location']['name'])) {
			$parts[] = $widget['location']['name'];
		}

		if (! empty($widget['region']['name'])) {
			$parts[] = $widget['region']['name'];
		}

		if (! empty($widget['country']['name'])) {
			$parts[] = $widget['country']['name'];
		}

		return implode(', ', $parts);
	}

	public function get_environment_errors() {
		global $wp_version;

		if (null !== $this->environment_errors) {
			return $this->environment_errors;
		}

		$errors = array();

		if (version_compare(PHP_VERSION, '5.5', '<')) {
			$errors[] = sprintf(
				__('Tides Today Tides and Weather requires PHP %1$s or newer. You are running %2$s.', 'tides-today'),
				'5.5',
				PHP_VERSION
			);
		}

		if (! empty($wp_version) && version_compare($wp_version, '5.0', '<')) {
			$errors[] = sprintf(
				__('Tides Today Tides and Weather requires WordPress %1$s or newer. You are running %2$s.', 'tides-today'),
				'5.0',
				$wp_version
			);
		}

		if (! function_exists('json_encode') || ! function_exists('json_decode')) {
			$errors[] = __('The PHP JSON extension is required.', 'tides-today');
		}

		if (! extension_loaded('curl') && ! extension_loaded('openssl')) {
			$errors[] = __('Either the PHP cURL or OpenSSL extension is required to fetch Tides Today data over HTTPS.', 'tides-today');
		}

		$this->environment_errors = $errors;

		return $errors;
	}

	private function environment_is_ready() {
		return empty($this->get_environment_errors());
	}

	private function normalize_saved_widget($widget, $fallback_id) {
		if (! is_array($widget)) {
			return array();
		}

		$normalized_widget = array(
			'id'         => ! empty($widget['id']) ? sanitize_key($widget['id']) : sanitize_key($fallback_id),
			'name'       => ! empty($widget['name']) ? sanitize_text_field($widget['name']) : '',
			'language'   => $this->sanitize_language(isset($widget['language']) ? $widget['language'] : 'en'),
			'country'    => $this->normalize_place(isset($widget['country']) ? $widget['country'] : array()),
			'region'     => $this->normalize_place(isset($widget['region']) ? $widget['region'] : array()),
			'location'   => $this->normalize_place(isset($widget['location']) ? $widget['location'] : array()),
			'settings'   => $this->sanitize_widget_settings(isset($widget['settings']) ? $widget['settings'] : array()),
			'created_at' => ! empty($widget['created_at']) ? sanitize_text_field($widget['created_at']) : '',
			'updated_at' => ! empty($widget['updated_at']) ? sanitize_text_field($widget['updated_at']) : '',
		);

		if (empty($normalized_widget['id']) || empty($normalized_widget['name']) || empty($normalized_widget['location']['id'])) {
			return array();
		}

		return $normalized_widget;
	}

	private function normalize_place($place) {
		if (! is_array($place)) {
			return array(
				'id'   => 0,
				'name' => '',
				'slug' => '',
			);
		}

		return array(
			'id'   => ! empty($place['id']) ? absint($place['id']) : 0,
			'name' => ! empty($place['name']) ? sanitize_text_field($place['name']) : '',
			'slug' => ! empty($place['slug']) ? sanitize_text_field($place['slug']) : '',
		);
	}

	private function get_editing_widget() {
		if (! current_user_can('manage_options')) {
			return array();
		}

		if (empty($_GET['edit'])) {
			return array();
		}

		return $this->get_widget(wp_unslash($_GET['edit']));
	}

	private function is_builder_view() {
		$page = ! empty($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
		$view = ! empty($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : '';

		return self::ADMIN_ADD_SLUG === $page || ! empty($_GET['edit']) || 'builder' === $view;
	}

	private function get_admin_overview_url() {
		return admin_url('admin.php?page=' . self::ADMIN_SLUG);
	}

	private function get_admin_builder_url($widget_id = '') {
		$base_url = admin_url('admin.php?page=' . self::ADMIN_ADD_SLUG);

		if (empty($widget_id)) {
			return $base_url;
		}

		return add_query_arg(array('edit' => sanitize_key($widget_id)), $base_url);
	}

	private function get_widget_settings($widget) {
		$defaults = $this->get_default_widget_settings();

		if (empty($widget['settings']) || ! is_array($widget['settings'])) {
			return $defaults;
		}

		return wp_parse_args($widget['settings'], $defaults);
	}

	private function get_default_widget_settings() {
		return array(
			'number_days'     => 1,
			'include_map'     => true,
			'include_weather' => true,
			'include_styles'  => true,
			'include_title'   => true,
			'custom_css'      => '',
			'weather_unit'    => 'c',
			'height_unit'     => 'm',
		);
	}

	private function sanitize_widget_settings($raw_settings) {
		$defaults = $this->get_default_widget_settings();
		$settings = array();

		$settings['number_days']     = isset($raw_settings['number_days']) ? absint($raw_settings['number_days']) : $defaults['number_days'];
		$settings['number_days']     = max(1, min(5, $settings['number_days']));
		$settings['include_map']     = $this->sanitize_boolean_setting(isset($raw_settings['include_map']) ? $raw_settings['include_map'] : $defaults['include_map']);
		$settings['include_weather'] = $this->sanitize_boolean_setting(isset($raw_settings['include_weather']) ? $raw_settings['include_weather'] : $defaults['include_weather']);
		$settings['include_styles']  = $this->sanitize_boolean_setting(isset($raw_settings['include_styles']) ? $raw_settings['include_styles'] : $defaults['include_styles']);
		$settings['include_title']   = $this->sanitize_boolean_setting(isset($raw_settings['include_title']) ? $raw_settings['include_title'] : $defaults['include_title']);
		$settings['custom_css']      = $this->sanitize_custom_css(isset($raw_settings['custom_css']) ? $raw_settings['custom_css'] : $defaults['custom_css']);
		$settings['weather_unit']    = (isset($raw_settings['weather_unit']) && in_array($raw_settings['weather_unit'], array('c', 'f'), true)) ? $raw_settings['weather_unit'] : $defaults['weather_unit'];
		$settings['height_unit']     = (isset($raw_settings['height_unit']) && in_array($raw_settings['height_unit'], array('m', 'ft'), true)) ? $raw_settings['height_unit'] : $defaults['height_unit'];

		return $settings;
	}

	private function sanitize_boolean_setting($value) {
		if (is_bool($value)) {
			return $value;
		}

		if (is_numeric($value)) {
			return (bool) absint($value);
		}

		if (is_string($value)) {
			$value = strtolower(trim($value));

			if (in_array($value, array('1', 'true', 'yes', 'on'), true)) {
				return true;
			}

			if (in_array($value, array('0', 'false', 'no', 'off', ''), true)) {
				return false;
			}
		}

		return ! empty($value);
	}

	private function sanitize_custom_css($value) {
		$value = is_string($value) ? $value : '';
		$value = str_replace("\r", '', $value);

		if (function_exists('wp_kses_no_null')) {
			$value = wp_kses_no_null($value, array('slash_zero' => 'keep'));
		}

		// Strip "<" so CSS can be safely printed inside a <style> tag.
		$value = str_replace('<', '', $value);

		return trim($value);
	}

	private function get_shortcode_string($widget) {
		return sprintf('[tides_today_widget id="%s"]', $widget['id']);
	}

	private function get_widget_custom_css_markup($widget) {
		$settings = $this->get_widget_settings($widget);

		if (empty($settings['custom_css'])) {
			return '';
		}

		return '<style type="text/css" data-tttw-widget="' . esc_attr($widget['id']) . '">' . $settings['custom_css'] . '</style>';
	}

	private function get_block_widget_data() {
		$data = array();

		foreach ($this->get_widgets() as $widget) {
			$data[] = array(
				'id'            => $widget['id'],
				'name'          => $widget['name'],
				'locationLabel' => $this->get_widget_location_label($widget),
				'shortcode'     => $this->get_shortcode_string($widget),
			);
		}

		return $data;
	}

	private function verify_admin_ajax_request() {
		if (! current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'message' => __('You do not have permission to manage Tides Today widgets.', 'tides-today'),
				),
				403
			);
		}

		check_ajax_referer('tttw_admin', 'nonce');
	}

	private function get_countries($language) {
		$url  = trailingslashit(self::API_BASE) . $language;
		$data = $this->get_cached_remote_json($url, self::CATALOG_TTL);

		if (is_wp_error($data)) {
			return $data;
		}

		$items = $this->normalize_api_items(isset($data['data']) ? $data['data'] : array());

		return $this->reorder_countries($items, $language);
	}

	private function get_regions($language, $country_id) {
		if (empty($country_id)) {
			return array();
		}

		$url  = trailingslashit(self::API_BASE) . $language . '/' . absint($country_id);
		$data = $this->get_cached_remote_json($url, self::CATALOG_TTL);

		if (is_wp_error($data)) {
			return $data;
		}

		return $this->normalize_api_items(isset($data['data']) ? $data['data'] : array());
	}

	private function get_locations($language, $country_id, $region_id) {
		if (empty($country_id) || empty($region_id)) {
			return array();
		}

		$url  = trailingslashit(self::API_BASE) . $language . '/' . absint($country_id) . '/' . absint($region_id);
		$data = $this->get_cached_remote_json($url, self::CATALOG_TTL);

		if (is_wp_error($data)) {
			return $data;
		}

		return $this->normalize_api_items(isset($data['data']) ? $data['data'] : array());
	}

	private function normalize_api_items($items) {
		$normalized_items = array();

		if (! is_array($items)) {
			return $normalized_items;
		}

		foreach ($items as $item) {
			if (! is_array($item) || empty($item['id']) || empty($item['name']) || empty($item['slug'])) {
				continue;
			}

			$normalized_items[] = array(
				'id'   => absint($item['id']),
				'name' => sanitize_text_field($item['name']),
				'slug' => sanitize_text_field($item['slug']),
			);
		}

		return $normalized_items;
	}

	private function reorder_countries($items, $language) {
		$preferred_names = $this->get_preferred_country_names($language);

		if (empty($preferred_names)) {
			return $items;
		}

		$preferred_items = array();
		$remaining_items = array();

		foreach ($items as $item) {
			$position = array_search($item['name'], $preferred_names, true);

			if (false === $position) {
				$remaining_items[] = $item;
				continue;
			}

			$preferred_items[ $position ] = $item;
		}

		ksort($preferred_items);

		return array_merge(array_values($preferred_items), $remaining_items);
	}

	private function get_preferred_country_names($language) {
		if ('fr' === $language) {
			return array(
				'France',
				'Canada',
			);
		}

		return array(
			'United States',
			'England',
			'Wales',
			'Scotland',
			'Canada',
			'Australia',
			'New Zealand',
			'South Africa',
			'Isle of Man',
			'Jersey',
			'Falkland Islands',
		);
	}

	private function get_cached_remote_json($url, $ttl) {
		$body = $this->get_cached_remote_body($url, $ttl);

		if (is_wp_error($body)) {
			return $body;
		}

		$data = json_decode($body, true);

		if (! is_array($data)) {
			return new WP_Error('tttw_invalid_json', __('Tides Today returned invalid JSON.', 'tides-today'));
		}

		return $data;
	}

	private function get_cached_remote_body($url, $ttl) {
		$cache_key = $this->get_cache_key($url);
		$cached    = $this->get_cached_value($cache_key);

		if (false !== $cached) {
			return $cached;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'redirection' => 3,
				'user-agent' => 'Tides Today Tides and Weather/' . TTTW_PLUGIN_VERSION . '; ' . home_url('/'),
			)
		);

		if (is_wp_error($response)) {
			return new WP_Error('tttw_remote_request_failed', __('The Tides Today service could not be reached.', 'tides-today'));
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$body          = wp_remote_retrieve_body($response);

		if ($response_code < 200 || $response_code >= 300 || '' === $body) {
			return new WP_Error('tttw_remote_http_error', __('Tides Today returned an unexpected response.', 'tides-today'));
		}

		$this->set_cached_value($cache_key, $body, $ttl);

		return $body;
	}

	private function get_cache_key($url) {
		return self::CACHE_PREFIX . md5($url);
	}

	private function get_cached_value($cache_key) {
		$cached = wp_cache_get($cache_key, self::CACHE_GROUP);

		if (false !== $cached) {
			return $cached;
		}

		return get_transient($cache_key);
	}

	private function set_cached_value($cache_key, $value, $ttl) {
		wp_cache_set($cache_key, $value, self::CACHE_GROUP, $ttl);
		set_transient($cache_key, $value, $ttl);
	}

	private function find_item_by_id($items, $id) {
		foreach ($items as $item) {
			if ((int) $item['id'] === (int) $id) {
				return $item;
			}
		}

		return array();
	}

	private function get_runtime_proxy_script($widget) {
		return $this->get_cached_remote_body($this->build_remote_script_url($widget, 'widget.js'), self::SCRIPT_TTL);
	}

	private function get_init_proxy_script($widget) {
		return $this->get_cached_remote_body($this->build_remote_script_url($widget, 'widget-init.js'), self::SCRIPT_TTL);
	}

	private function build_remote_script_url($widget, $script) {
		$url = trailingslashit(self::API_BASE) .
			rawurlencode($widget['language']) . '/' .
			rawurlencode($widget['country']['slug']) . '/' .
			rawurlencode($widget['region']['slug']) . '/' .
			rawurlencode($widget['location']['slug']) . '/' . $script;

		if ('widget-init.js' === $script) {
			$url = add_query_arg($this->get_init_query_args($widget), $url);
		}

		return $url;
	}

	private function get_preview_request_context($request) {
		$language     = $this->sanitize_language($request->get_param('language'));
		$country_slug = $this->sanitize_slug_segment($request->get_param('countrySlug'));
		$region_slug  = $this->sanitize_slug_segment($request->get_param('regionSlug'));
		$location_slug = $this->sanitize_slug_segment($request->get_param('locationSlug'));

		if (empty($country_slug) || empty($region_slug) || empty($location_slug)) {
			return new WP_Error('tttw_preview_missing_location', __('A complete preview location was not provided.', 'tides-today'));
		}

		return array(
			'language'      => $language,
			'country_slug'  => $country_slug,
			'region_slug'   => $region_slug,
			'location_slug' => $location_slug,
			'settings'      => $this->sanitize_widget_settings(
				array(
					'number_days'     => $request->get_param('numberDays'),
					'include_map'     => $request->get_param('includeMap'),
					'include_weather' => $request->get_param('includeWeather'),
					'include_styles'  => $request->get_param('includeStyles'),
					'include_title'   => $request->get_param('includeTitle'),
					'weather_unit'    => $request->get_param('weatherUnit'),
					'height_unit'     => $request->get_param('heightUnit'),
				)
			),
		);
	}

	private function build_preview_script_url($preview, $script) {
		$url = trailingslashit(self::API_BASE) .
			rawurlencode($preview['language']) . '/' .
			rawurlencode($preview['country_slug']) . '/' .
			rawurlencode($preview['region_slug']) . '/' .
			rawurlencode($preview['location_slug']) . '/' . $script;

		if ('widget-init.js' === $script) {
			$url = add_query_arg($this->get_init_query_args_from_settings($preview['settings']), $url);
		}

		return $url;
	}

	private function get_init_query_args($widget) {
		return $this->get_init_query_args_from_settings($this->get_widget_settings($widget));
	}

	private function get_init_query_args_from_settings($settings) {
		return array(
			'includeMap'     => $settings['include_map'] ? 'true' : 'false',
			'includeWeather' => $settings['include_weather'] ? 'true' : 'false',
			'includeStyles'  => $settings['include_styles'] ? 'true' : 'false',
			'includeTitle'   => $settings['include_title'] ? 'true' : 'false',
			'numberDays'     => (int) $settings['number_days'],
			'weatherUnit'    => $settings['weather_unit'],
			'heightUnit'     => $settings['height_unit'],
		);
	}

	private function sanitize_language($language) {
		return ('fr' === $language) ? 'fr' : 'en';
	}

	private function sanitize_slug_segment($value) {
		$value = strtolower((string) $value);
		$value = preg_replace('/[^a-z0-9-]/', '-', $value);
		$value = preg_replace('/-+/', '-', $value);

		return trim($value, '-');
	}

	private function generate_widget_id() {
		return 'tttw_' . substr(md5(uniqid('', true) . mt_rand()), 0, 12);
	}

	private function get_container_id($widget) {
		return 'tidewidget__' . absint($widget['location']['id']);
	}

	private function get_proxy_url($widget, $script) {
		$args = array(
			'rev' => substr(md5($widget['id'] . $widget['updated_at']), 0, 10),
		);

		if ('widget-init.js' === $script) {
			$args = array_merge($args, $this->get_init_query_args($widget));
		}

		return add_query_arg($args, rest_url(self::REST_NAMESPACE . '/proxy/' . rawurlencode($widget['id']) . '/' . $script));
	}

	private function javascript_response($body) {
		$response = new WP_REST_Response($body, 200);
		$response->header('Content-Type', 'application/javascript; charset=' . get_option('blog_charset'));
		$response->header('Cache-Control', 'max-age=' . self::SCRIPT_TTL . ', must-revalidate');

		return $response;
	}

	private function build_console_error_script($message) {
		return 'console.error(' . wp_json_encode($message) . ');';
	}

	private function redirect_to_admin($query_args) {
		$base_url = $this->get_admin_overview_url();

		if (isset($query_args['edit']) && empty($query_args['edit'])) {
			unset($query_args['edit']);
			$base_url = $this->get_admin_builder_url();
		} elseif (! empty($query_args['edit'])) {
			$base_url = $this->get_admin_builder_url(sanitize_key($query_args['edit']));
			unset($query_args['edit']);
		} elseif (! empty($query_args['view']) && 'builder' === $query_args['view']) {
			$base_url = $this->get_admin_builder_url();
			unset($query_args['view']);
		}

		$url = add_query_arg($query_args, $base_url);

		wp_safe_redirect($url);
		exit;
	}

	private function render_admin_page_notice() {
		if (empty($_GET['tttw_notice'])) {
			return;
		}

		$notice_type = sanitize_key(wp_unslash($_GET['tttw_notice']));
		$message     = '';
		$class_name  = 'notice-info';

		if ('saved' === $notice_type) {
			$message    = __('Widget saved successfully.', 'tides-today');
			$class_name = 'notice-success';
		} elseif ('deleted' === $notice_type) {
			$message    = __('Widget deleted successfully.', 'tides-today');
			$class_name = 'notice-success';
		} elseif ('error' === $notice_type) {
			$message    = ! empty($_GET['tttw_message']) ? sanitize_text_field(wp_unslash($_GET['tttw_message'])) : __('Something went wrong.', 'tides-today');
			$class_name = 'notice-error';
		}

		if (empty($message)) {
			return;
		}

		echo '<div class="notice ' . esc_attr($class_name) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
	}

	private function get_language_label($language) {
		return ('fr' === $language) ? __('French', 'tides-today') : __('English', 'tides-today');
	}

	private function get_overview_links() {
		return array(
			array(
				'label' => __('Visit Tides Today', 'tides-today'),
				'url'   => 'https://tides.today',
				'icon'  => 'site',
			),
			array(
				'label' => __('Find on Facebook', 'tides-today'),
				'url'   => 'https://www.facebook.com/gettidetimes',
				'icon'  => 'facebook',
			),
			array(
				'label' => __('Follow on X', 'tides-today'),
				'url'   => 'https://twitter.com/tidesToday',
				'icon'  => 'x',
			),
			array(
				'label' => __('Subscribe on Youtube', 'tides-today'),
				'url'   => 'https://www.youtube.com/@tidestoday',
				'icon'  => 'youtube',
			),
			array(
				'label' => __('Follow on Instagram', 'tides-today'),
				'url'   => 'https://instagram.com/tidestoday',
				'icon'  => 'instagram',
			),
		);
	}

	private function get_overview_icon_markup($icon) {
		switch ($icon) {
			case 'facebook':
				return '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.2-1.5 1.5-1.5H16.8V5c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4V11H8v3h2.6v8h2.9z" fill="currentColor"/></svg>';
			case 'x':
				return '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M18.9 3H21l-4.6 5.3L21.8 21h-4.7l-3.7-4.9L9.1 21H7l5-5.8L2.9 3h4.8l3.3 4.4L14.9 3h4zm-1.6 16h1.3L6.9 4.9H5.5L17.3 19z" fill="currentColor"/></svg>';
			case 'youtube':
				return '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M21.6 7.2c-.2-.9-.9-1.6-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4c-.9.2-1.6.9-1.8 1.8C2 8.8 2 12 2 12s0 3.2.4 4.8c.2.9.9 1.6 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8zM10 15.1V8.9l5.2 3.1-5.2 3.1z" fill="currentColor"/></svg>';
			case 'instagram':
				return '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 7.1A4.9 4.9 0 1 0 16.9 12 4.9 4.9 0 0 0 12 7.1zm0 8.1A3.2 3.2 0 1 1 15.2 12 3.2 3.2 0 0 1 12 15.2z" fill="currentColor"/><path d="M17.3 2.9H6.7A3.8 3.8 0 0 0 2.9 6.7v10.6a3.8 3.8 0 0 0 3.8 3.8h10.6a3.8 3.8 0 0 0 3.8-3.8V6.7a3.8 3.8 0 0 0-3.8-3.8zm2.1 14.4a2.1 2.1 0 0 1-2.1 2.1H6.7a2.1 2.1 0 0 1-2.1-2.1V6.7a2.1 2.1 0 0 1 2.1-2.1h10.6a2.1 2.1 0 0 1 2.1 2.1z" fill="currentColor"/><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor"/></svg>';
			case 'site':
			default:
				return '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm7.8 9h-3.1a15.4 15.4 0 0 0-1.1-5A8.4 8.4 0 0 1 19.8 11zM12 4.2c.9 1 2.1 3.3 2.7 6.8H9.3C9.9 7.5 11.1 5.2 12 4.2zM8.4 6a15.4 15.4 0 0 0-1.1 5H4.2A8.4 8.4 0 0 1 8.4 6zM4.2 13h3.1a15.4 15.4 0 0 0 1.1 5A8.4 8.4 0 0 1 4.2 13zm7.8 6.8c-.9-1-2.1-3.3-2.7-6.8h5.4c-.6 3.5-1.8 5.8-2.7 6.8zm3.6-1.8a15.4 15.4 0 0 0 1.1-5h3.1a8.4 8.4 0 0 1-4.2 5z" fill="currentColor"/></svg>';
		}
	}

	private function sort_widgets_by_name($left, $right) {
		return strcasecmp($left['name'], $right['name']);
	}
}
