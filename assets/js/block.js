(function (blocks, element, components, i18n) {
	'use strict';

	if (! blocks || ! element || ! components || ! i18n) {
		return;
	}

	var el = element.createElement;
	var __ = i18n.__;
	var blockData = window.TTTWBlockData || {};
	var widgets = blockData.widgets || [];
	var labels = blockData.labels || {};

	function findWidget(widgetId) {
		var index;

		for (index = 0; index < widgets.length; index += 1) {
			if (widgets[index].id === widgetId) {
				return widgets[index];
			}
		}

		return null;
	}

	blocks.registerBlockType('tides-today/tides-weather', {
		title: labels.title || __('Tides Today Tides and Weather', 'tides-today-tides-and-weather'),
		description: labels.description || __('Insert a saved Tides Today tide and weather widget.', 'tides-today-tides-and-weather'),
		icon: 'location-alt',
		category: 'widgets',
		attributes: {
			widgetId: {
				type: 'string',
				default: ''
			}
		},
		edit: function (props) {
			var selectedWidget = findWidget(props.attributes.widgetId || '');
			var options = [
				{
					label: labels.selectWidget || __('Select a saved widget', 'tides-today-tides-and-weather'),
					value: ''
				}
			];
			var index;

			for (index = 0; index < widgets.length; index += 1) {
				options.push({
					label: widgets[index].name + ' - ' + widgets[index].locationLabel,
					value: widgets[index].id
				});
			}

			return el(
				'div',
				{
					className: 'tttw-block-editor components-placeholder'
				},
				el(
					'strong',
					{
						className: 'tttw-block-editor__title'
					},
					labels.title || __('Tides Today Tides and Weather', 'tides-today-tides-and-weather')
				),
				el(
					'p',
					{
						className: 'tttw-block-editor__description'
					},
					widgets.length ? (labels.instructions || __('Choose which saved widget to embed on the front end.', 'tides-today-tides-and-weather')) : (labels.empty || __('Create a saved widget in Tides Today before using this block.', 'tides-today-tides-and-weather'))
				),
				widgets.length ? el(components.SelectControl, {
					label: labels.selectWidget || __('Select a saved widget', 'tides-today-tides-and-weather'),
					value: props.attributes.widgetId || '',
					options: options,
					onChange: function (value) {
						props.setAttributes({
							widgetId: value
						});
					}
				}) : null,
				selectedWidget ? el(
					'p',
					{
						className: 'tttw-block-editor__meta'
					},
					selectedWidget.locationLabel + ' | ' + selectedWidget.shortcode
				) : el(
					'p',
					{
						className: 'tttw-block-editor__meta'
					},
					labels.help || __('Saved widgets are managed in the Tides Today admin screen.', 'tides-today-tides-and-weather')
				)
			);
		},
		save: function () {
			return null;
		}
	});
}(window.wp.blocks, window.wp.element, window.wp.components, window.wp.i18n));
