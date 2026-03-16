<?php

if (! defined('ABSPATH')) {
	exit;
}

class TTTW_Sidebar_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'tttw_sidebar_widget',
			__('Tides Today Tides and Weather', 'tides-today-tides-and-weather'),
			array(
				'description' => __('Display a saved Tides Today tide and weather widget.', 'tides-today-tides-and-weather'),
			)
		);
	}

	public function widget($args, $instance) {
		$widget_id = ! empty($instance['widget_id']) ? sanitize_key($instance['widget_id']) : '';

		if (empty($widget_id)) {
			return;
		}

		echo isset($args['before_widget']) ? wp_kses_post($args['before_widget']) : '';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Saved widget markup includes the required third-party embed scripts.
		echo TTTW_Plugin::instance()->render_saved_widget($widget_id);
		echo isset($args['after_widget']) ? wp_kses_post($args['after_widget']) : '';
	}

	public function form($instance) {
		$selected_widget_id = ! empty($instance['widget_id']) ? sanitize_key($instance['widget_id']) : '';
		$widgets            = TTTW_Plugin::instance()->get_widgets();
		$field_id           = $this->get_field_id('widget_id');
		$field_name         = $this->get_field_name('widget_id');
		?>
		<p>
			<label for="<?php echo esc_attr($field_id); ?>"><?php esc_html_e('Saved widget', 'tides-today-tides-and-weather'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_name); ?>">
				<option value=""><?php esc_html_e('Select a saved widget', 'tides-today-tides-and-weather'); ?></option>
				<?php foreach ($widgets as $widget) : ?>
					<option value="<?php echo esc_attr($widget['id']); ?>" <?php selected($selected_widget_id, $widget['id']); ?>>
						<?php echo esc_html($widget['name'] . ' - ' . TTTW_Plugin::instance()->get_widget_location_label($widget)); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php if (empty($widgets)) : ?>
			<p>
				<a href="<?php echo esc_url(admin_url('admin.php?page=' . TTTW_Plugin::ADMIN_SLUG)); ?>">
					<?php esc_html_e('Create your first saved widget in Tides Today.', 'tides-today-tides-and-weather'); ?>
				</a>
			</p>
		<?php endif; ?>
		<?php
	}

	public function update($new_instance, $old_instance) {
		$instance              = array();
		$instance['widget_id'] = ! empty($new_instance['widget_id']) ? sanitize_key($new_instance['widget_id']) : '';

		return $instance;
	}
}
