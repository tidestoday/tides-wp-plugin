const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function createElement(type, props) {
	return {
		type: type,
		props: props || {},
		children: Array.prototype.slice.call(arguments, 2),
	};
}

function loadBlockScript(blockData) {
	const registrations = [];
	const scriptPath = path.join(__dirname, '..', '..', 'assets', 'js', 'block.js');
	const script = fs.readFileSync(scriptPath, 'utf8');
	const context = {
		window: {
			TTTWBlockData: blockData || {},
			wp: {
				blocks: {
					registerBlockType: function (name, settings) {
						registrations.push({
							name: name,
							settings: settings,
						});
					},
				},
				element: {
					createElement: createElement,
				},
				components: {
					SelectControl: 'SelectControl',
				},
				i18n: {
					__: function (value) {
						return value;
					},
				},
			},
		},
	};

	vm.createContext(context);
	vm.runInContext(script, context, { filename: 'assets/js/block.js' });

	assert.equal(registrations.length, 1, 'Expected the block script to register exactly one block.');

	return registrations[0];
}

function findElement(node, predicate) {
	let index;
	let match;

	if (!node || typeof node !== 'object') {
		return null;
	}

	if (predicate(node)) {
		return node;
	}

	if (!Array.isArray(node.children)) {
		return null;
	}

	for (index = 0; index < node.children.length; index += 1) {
		match = findElement(node.children[index], predicate);

		if (match) {
			return match;
		}
	}

	return null;
}

function collectText(node) {
	let output = '';
	let index;

	if (typeof node === 'string') {
		return node;
	}

	if (!node || typeof node !== 'object' || !Array.isArray(node.children)) {
		return output;
	}

	for (index = 0; index < node.children.length; index += 1) {
		output += collectText(node.children[index]);
	}

	return output;
}

test('block script registers the saved widget block with a null save callback', function () {
	const registration = loadBlockScript({});

	assert.equal(registration.name, 'tides-today/tides-weather');
	assert.equal(registration.settings.category, 'widgets');
	assert.equal(registration.settings.save(), null);
});

test('block edit renders the empty state when no saved widgets exist', function () {
	const registration = loadBlockScript({
		widgets: [],
		labels: {
			title: 'Saved tides',
			empty: 'Create a widget first.',
			help: 'Manage widgets in the admin screen.',
		},
	});
	const tree = registration.settings.edit({
		attributes: {},
		setAttributes: function () {},
	});
	const select = findElement(tree, function (node) {
		return node.type === 'SelectControl';
	});
	const meta = findElement(tree, function (node) {
		return node.props && node.props.className === 'tttw-block-editor__meta';
	});

	assert.equal(tree.type, 'div');
	assert.equal(select, null);
	assert.match(collectText(tree), /Create a widget first\./);
	assert.match(collectText(meta), /Manage widgets in the admin screen\./);
});

test('block edit builds widget options and updates the selected widget', function () {
	let receivedAttributes = null;
	const widgets = [
		{
			id: 'tttw_alpha',
			name: 'Alpha',
			locationLabel: 'Llandudno, Conwy, Wales',
			shortcode: '[tides_today_widget id="tttw_alpha"]',
		},
		{
			id: 'tttw_beta',
			name: 'Beta',
			locationLabel: 'Conwy, Conwy, Wales',
			shortcode: '[tides_today_widget id="tttw_beta"]',
		},
	];
	const registration = loadBlockScript({
		widgets: widgets,
		labels: {
			selectWidget: 'Select a saved widget',
		},
	});
	const tree = registration.settings.edit({
		attributes: {
			widgetId: 'tttw_beta',
		},
		setAttributes: function (attributes) {
			receivedAttributes = attributes;
		},
	});
	const select = findElement(tree, function (node) {
		return node.type === 'SelectControl';
	});
	const meta = findElement(tree, function (node) {
		return node.props && node.props.className === 'tttw-block-editor__meta';
	});

	assert.ok(select, 'Expected the saved widget selector to be rendered.');
	assert.equal(select.props.options.length, 3);
	assert.equal(select.props.options[1].label, 'Alpha - Llandudno, Conwy, Wales');
	assert.equal(select.props.options[1].value, 'tttw_alpha');

	select.props.onChange('tttw_alpha');

	assert.equal(receivedAttributes.widgetId, 'tttw_alpha');
	assert.match(collectText(meta), /Conwy, Conwy, Wales/);
	assert.match(collectText(meta), /\[tides_today_widget id="tttw_beta"\]/);
});
