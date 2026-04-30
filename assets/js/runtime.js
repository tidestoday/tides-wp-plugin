(function (window, document) {
	'use strict';

	var runtimeData = window.TTTWRuntimeData || {};
	var defaultLabels = {
		title: 'Tide times for %s',
		type: 'Type',
		time: 'Time',
		height: 'Height',
		high: 'High',
		low: 'Low',
		temperature: '%1$s high %2$s low',
		leader: 'See 7 days tide times and weather for %s',
		disclaimer: 'Tide and weather data is predicted from scientific models or third party data. No guarantees are made regarding the accuracy, completeness, or suitability of data on this website. You are responsible for your own safety at sea.',
		copyright: 'Copyright %1$s %2$s. By using this data, you are agreeing to the %3$s.',
		terms: 'Terms and Conditions',
		error: 'Tides Today widget data could not be loaded.'
	};

	function mergeLabels(labels) {
		var merged = {};
		var key;

		for (key in defaultLabels) {
			if (Object.prototype.hasOwnProperty.call(defaultLabels, key)) {
				merged[key] = defaultLabels[key];
			}
		}

		labels = labels || {};

		for (key in labels) {
			if (Object.prototype.hasOwnProperty.call(labels, key) && typeof labels[key] === 'string') {
				merged[key] = labels[key];
			}
		}

		return merged;
	}

	function format(formatString) {
		var args = Array.prototype.slice.call(arguments, 1);

		return String(formatString || '').replace(/%([0-9]+\$)?s/g, function (match, position) {
			var index = position ? parseInt(position, 10) - 1 : 0;
			var value = typeof args[index] === 'undefined' ? '' : args[index];

			if (! position) {
				args.shift();
			}

			return value;
		});
	}

	function appendFormattedLink(parent, formatString, placeholder, link) {
		var parts = String(formatString || '').split(placeholder);
		var index;

		for (index = 0; index < parts.length; index += 1) {
			if (parts[index]) {
				parent.appendChild(document.createTextNode(parts[index]));
			}

			if (index < parts.length - 1) {
				parent.appendChild(link.cloneNode(true));
			}
		}
	}

	function appendFormattedParts(parent, formatString, replacements) {
		var pattern = /%([0-9]+\$)?s/g;
		var cursor = 0;
		var sequentialIndex = 0;
		var match;

		while ((match = pattern.exec(formatString)) !== null) {
			var index = match[1] ? parseInt(match[1], 10) - 1 : sequentialIndex;
			var replacement = replacements[index];

			if (! match[1]) {
				sequentialIndex += 1;
			}

			if (match.index > cursor) {
				parent.appendChild(document.createTextNode(formatString.slice(cursor, match.index)));
			}

			if (replacement && replacement.nodeType) {
				parent.appendChild(replacement.cloneNode(true));
			} else if (typeof replacement !== 'undefined') {
				parent.appendChild(document.createTextNode(String(replacement)));
			}

			cursor = pattern.lastIndex;
		}

		if (cursor < formatString.length) {
			parent.appendChild(document.createTextNode(formatString.slice(cursor)));
		}
	}

	function createLink(url, text) {
		var link = document.createElement('a');

		link.href = url || '#';
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
		link.textContent = text || '';

		return link;
	}

	function titleCase(value) {
		value = String(value || '').toLowerCase();

		return value.charAt(0).toUpperCase() + value.slice(1);
	}

	function formatNumber(value) {
		var number = Number(value);

		if (! isFinite(number)) {
			return '0';
		}

		return String(Math.round(number * 100) / 100);
	}

	function formatTemperature(value, unit) {
		var number = Number(value);

		if (! isFinite(number)) {
			number = 0;
		}

		return Math.round(number) + unit;
	}

	function buildTitle(payload, labels) {
		var title = document.createElement('h2');

		title.textContent = format(labels.title, payload.data.location);

		return title;
	}

	function buildMap(payload) {
		var map = document.createElement('div');

		map.className = 'tides-widget__map';
		map.style.backgroundImage = 'url("' + payload.data.map + '")';

		return map;
	}

	function buildWeather(day, config, labels) {
		var weather = day.weather || {};
		var container = document.createElement('div');
		var content = document.createElement('div');
		var title = document.createElement('h3');
		var temp = document.createElement('p');
		var tempText = document.createElement('small');
		var unitLabel = 'c' === config.weatherUnit ? String.fromCharCode(176) + 'C' : String.fromCharCode(176) + 'F';
		var high = 'c' === config.weatherUnit ? weather.highC : weather.highF;
		var low = 'c' === config.weatherUnit ? weather.lowC : weather.lowF;
		var icon;

		title.textContent = weather.description || '';
		title.className = 'title';

		tempText.textContent = format(labels.temperature, formatTemperature(high, unitLabel), formatTemperature(low, unitLabel));
		temp.className = 'min-max';
		temp.appendChild(tempText);

		container.className = 'tides-widget__weather tides-widget__weather-' + String(weather.description || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
		content.className = 'tides-widget__weather-content';
		content.appendChild(title);
		content.appendChild(temp);

		if (weather.icon) {
			icon = document.createElement('img');
			icon.className = 'tides-widget__weather-icon';
			icon.src = weather.icon;
			icon.alt = '';
			icon.loading = 'lazy';
			container.appendChild(icon);
		}

		container.appendChild(content);

		return container;
	}

	function buildDate(day) {
		var caption = document.createElement('p');
		var date = document.createElement('time');

		caption.className = 'tides-widget__caption';
		date.dateTime = day.dateTime || day.date;
		date.textContent = day.date;
		caption.appendChild(date);

		return caption;
	}

	function buildTable(day, payload, labels) {
		var config = payload.config || {};
		var tableContainer = document.createElement('div');
		var table = document.createElement('table');
		var thead = document.createElement('thead');
		var headerRow = document.createElement('tr');
		var tbody = document.createElement('tbody');
		var headerLabels = [labels.type, labels.time, labels.height];
		var heightUnit = 'ft' === config.heightUnit ? 'ft' : 'm';
		var index;

		tableContainer.className = 'tides-widget__table-container';
		tableContainer.appendChild(buildDate(day));

		if (true === config.includeWeather && day.weather) {
			tableContainer.appendChild(buildWeather(day, config, labels));
		}

		for (index = 0; index < headerLabels.length; index += 1) {
			var th = document.createElement('th');

			th.scope = 'col';
			th.textContent = headerLabels[index];
			headerRow.appendChild(th);
		}

		thead.appendChild(headerRow);

		(day.tides || []).forEach(function (tide) {
			var row = document.createElement('tr');
			var type = document.createElement('td');
			var time = document.createElement('td');
			var height = document.createElement('td');
			var heightValue = 'ft' === heightUnit ? tide.heightF : tide.heightM;

			type.textContent = 'high' === tide.type ? labels.high : ('low' === tide.type ? labels.low : titleCase(tide.type));
			time.textContent = tide.time || '';
			height.textContent = formatNumber(heightValue) + heightUnit;

			row.appendChild(type);
			row.appendChild(time);
			row.appendChild(height);
			tbody.appendChild(row);
		});

		table.appendChild(thead);
		table.appendChild(tbody);
		tableContainer.appendChild(table);

		return tableContainer;
	}

	function buildTables(payload, labels) {
		var wrapper = document.createElement('div');
		var days = payload.data.days || [];
		var limit = parseInt(payload.config.numberDays, 10) || 1;

		wrapper.className = 'tides-widget__tables';

		days.slice(0, limit).forEach(function (day) {
			wrapper.appendChild(buildTable(day, payload, labels));
		});

		return wrapper;
	}

	function buildCopyright(payload, labels) {
		var container = document.createElement('div');
		var leader = document.createElement('p');
		var disclaimer = document.createElement('p');
		var notice = document.createElement('p');
		var locationLink = createLink(payload.data.locationUrl, payload.data.location);
		var siteLink = createLink('https://tides.today/' + ('fr' === payload.language ? 'fr' : 'en'), 'Tides Today');
		var termsLink = createLink(payload.data.termsUrl, labels.terms);
		var year = String.fromCharCode(169) + ' ' + new Date().getFullYear();

		container.className = 'tides-widget__copyright';
		appendFormattedLink(leader, labels.leader, '%s', locationLink);
		disclaimer.textContent = labels.disclaimer;
		appendFormattedParts(notice, labels.copyright, [year, siteLink, termsLink]);

		container.appendChild(leader);
		container.appendChild(disclaimer);
		container.appendChild(notice);

		return container;
	}

	function buildWidget(payload) {
		var labels = mergeLabels(payload.labels || runtimeData.labels);
		var container = document.createElement('div');

		container.className = 'tides-widget__container';

		if (payload.error || ! payload.data) {
			container.className += ' tides-widget__container--error';
			container.textContent = payload.error || labels.error;
			return container;
		}

		if (true === payload.config.includeTitle) {
			container.appendChild(buildTitle(payload, labels));
		}

		if (true === payload.config.includeMap && payload.data.map) {
			container.appendChild(buildMap(payload));
		}

		container.appendChild(buildTables(payload, labels));
		container.appendChild(buildCopyright(payload, labels));

		return container;
	}

	function parsePayload(script) {
		try {
			return JSON.parse(script.textContent || '{}');
		} catch (error) {
			return {
				error: defaultLabels.error
			};
		}
	}

	function renderPayload(script) {
		var payload = parsePayload(script);
		var containerId = payload.containerId || script.getAttribute('data-tttw-container');
		var host = containerId ? document.getElementById(containerId) : null;

		if (! host || host.getAttribute('data-tides-widget-initialized') === 'true') {
			return;
		}

		host.setAttribute('data-tides-widget-initialized', 'true');
		host.textContent = '';
		host.appendChild(buildWidget(payload));
	}

	function init() {
		var payloads = document.querySelectorAll('script.tttw-widget-payload[type="application/json"]');
		var index;

		for (index = 0; index < payloads.length; index += 1) {
			renderPayload(payloads[index]);
		}
	}

	window.TTTWRuntime = {
		buildWidget: buildWidget,
		init: init,
		renderPayload: renderPayload
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}(window, document));
