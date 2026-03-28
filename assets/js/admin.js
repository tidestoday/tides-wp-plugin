(function ($) {
	'use strict';

	if (typeof TTTWAdmin === 'undefined') {
		return;
	}

	var $form = $('#tttw-builder-form');

	if (! $form.length) {
		return;
	}

	var $language = $('#tttw-language');
	var $country = $('#tttw-country');
	var $region = $('#tttw-region');
	var $location = $('#tttw-location');
	var $status = $('#tttw-api-status');
	var $previewFrame = $('#tttw-preview-frame');
	var $previewPlaceholder = $('#tttw-preview-placeholder');
	var currentWidget = TTTWAdmin.currentWidget || null;
	var catalog = {
		countries: [],
		regions: [],
		locations: []
	};
	var previewToken = 0;

	function setStatus(message, isError) {
		$status.text(message || '');
		$status.toggleClass('is-error', !! isError);
	}

	function setPreviewPlaceholder(message, isError) {
		if (! $previewPlaceholder.length) {
			return;
		}

		$previewPlaceholder.text(message || '');
		$previewPlaceholder.toggleClass('is-error', !! isError);
		$previewPlaceholder.show();
		$previewFrame.removeClass('is-active');
	}

	function resetSelect($select, placeholder, disabled) {
		$select.empty();
		$select.append($('<option />').val('').text(placeholder));
		$select.prop('disabled', disabled !== false);
	}

	function populateSelect($select, items, placeholder, selectedValue) {
		resetSelect($select, placeholder, false);

		$.each(items || [], function (index, item) {
			$select.append(
				$('<option />')
					.val(String(item.id))
					.text(item.name)
			);
		});

		if (selectedValue) {
			$select.val(String(selectedValue));
		}

		if ($select.find('option').length <= 1) {
			$select.prop('disabled', true);
		}
	}

	function findItemById(items, id) {
		var index;
		var normalizedId = String(id || '');

		for (index = 0; index < (items || []).length; index += 1) {
			if (String(items[index].id) === normalizedId) {
				return items[index];
			}
		}

		return null;
	}

	function getSelectedItem($select, items, fallback) {
		var selectedId = String($select.val() || '');
		var item = null;

		if (! selectedId) {
			return null;
		}

		item = findItemById(items, selectedId);

		if (! item && fallback && String(fallback.id || '') === selectedId) {
			item = fallback;
		}

		return item;
	}

	function escapeAttribute(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;');
	}

	function getPreviewSelection() {
		var fallbackCountry = currentWidget && currentWidget.country ? currentWidget.country : null;
		var fallbackRegion = currentWidget && currentWidget.region ? currentWidget.region : null;
		var fallbackLocation = currentWidget && currentWidget.location ? currentWidget.location : null;

		return {
			language: $language.val() || 'en',
			country: getSelectedItem($country, catalog.countries, fallbackCountry),
			region: getSelectedItem($region, catalog.regions, fallbackRegion),
			location: getSelectedItem($location, catalog.locations, fallbackLocation)
		};
	}

	function getPreviewSettings() {
		return {
			numberDays: $form.find('#tttw-number-days').val() || '1',
			includeMap: $form.find('#tttw-include-map').is(':checked'),
			includeWeather: $form.find('#tttw-include-weather').is(':checked'),
			includeStyles: $form.find('#tttw-include-styles').is(':checked'),
			includeTitle: $form.find('#tttw-include-title').is(':checked'),
			weatherUnit: $form.find('#tttw-weather-unit').val() || 'c',
			heightUnit: $form.find('#tttw-height-unit').val() || 'm'
		};
	}

	function buildPreviewUrl(scriptName, selection, settings) {
		var baseUrl = TTTWAdmin.previewBaseUrl + scriptName;
		var query = $.param({
			language: selection.language,
			countrySlug: selection.country.slug,
			regionSlug: selection.region.slug,
			locationSlug: selection.location.slug,
			numberDays: settings.numberDays,
			includeMap: settings.includeMap ? 'true' : 'false',
			includeWeather: settings.includeWeather ? 'true' : 'false',
			includeStyles: settings.includeStyles ? 'true' : 'false',
			includeTitle: settings.includeTitle ? 'true' : 'false',
			weatherUnit: settings.weatherUnit,
			heightUnit: settings.heightUnit
		});
		var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';

		return baseUrl + separator + query;
	}

	function buildPreviewDocument(selection, settings, token) {
		var widgetSrc = escapeAttribute(buildPreviewUrl('widget.js', selection, settings));
		var containerId = 'tidewidget__' + selection.location.id;
		var previewConfig = JSON.stringify({
			includeMap: !! settings.includeMap,
			includeWeather: !! settings.includeWeather,
			includeStyles: !! settings.includeStyles,
			includeTitle: !! settings.includeTitle,
			numberDays: parseInt(settings.numberDays, 10) || 1,
			weatherUnit: settings.weatherUnit,
			heightUnit: settings.heightUnit
		});

		return [
			'<!doctype html>',
			'<html><head><meta charset="utf-8" />',
			'<meta name="viewport" content="width=device-width, initial-scale=1" />',
			'<sty' + 'le>',
			'html,body{margin:0;padding:0;background:#fff;}',
			'body{padding:16px;box-sizing:border-box;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}',
			'#tttw-preview-root{min-height:280px;}',
			'#tttw-preview-root,#tttw-preview-root *{font-family:inherit !important;}',
			'a{color:inherit;}',
			'</sty' + 'le>',
			'</head><body>',
			'<div id="tttw-preview-root">',
			'<div id="' + escapeAttribute(containerId) + '"></div>',
			'<scr' + 'ipt type="text/javascript" src="' + widgetSrc + '"></scr' + 'ipt>',
			'<scr' + 'ipt>',
			'(function(){',
			'var attempts=0;',
			'var initialized=false;',
			'var token=' + JSON.stringify(token) + ';',
			'var containerId=' + JSON.stringify(containerId) + ';',
			'var config=' + previewConfig + ';',
			'var send=function(){',
			'var height=Math.max(document.body.scrollHeight,document.documentElement.scrollHeight,320);',
			'parent.postMessage({type:"tttwPreviewHeight",token:token,height:height},"*");',
			'};',
			'var init=function(){',
			'if(initialized){',
			'return;',
			'}',
			'if(typeof createTideInstance!=="function"){',
			'attempts+=1;',
			'if(attempts<40){window.setTimeout(init,50);}',
			'return;',
			'}',
			'initialized=true;',
			'try{',
			'createTideInstance(containerId,config);',
			'window.setTimeout(send,120);',
			'window.setTimeout(send,600);',
			'window.setTimeout(send,1400);',
			'}catch(error){',
			'initialized=false;',
			'parent.postMessage({type:"tttwPreviewError",token:token,message:(error&&error.message)?error.message:"Preview init failed."},"*");',
			'return;',
			'}',
			'};',
			'window.addEventListener("load",function(){',
			'init();',
			'window.setTimeout(send,200);',
			'window.setTimeout(send,900);',
			'window.setTimeout(send,1800);',
			'});',
			'document.addEventListener("readystatechange",function(){',
			'if(document.readyState==="interactive"||document.readyState==="complete"){',
			'init();',
			'}',
			'send();',
			'});',
			'window.setTimeout(init,0);',
			'window.setTimeout(send,60);',
			'}());',
			'</scr' + 'ipt>',
			'</div></body></html>'
		].join('');
	}

	function renderPreview() {
		var selection = getPreviewSelection();
		var settings = getPreviewSettings();
		var frame = $previewFrame.get(0);
		var documentHtml;

		if (! frame) {
			return;
		}

		if (! selection.country || ! selection.region || ! selection.location) {
			setPreviewPlaceholder(TTTWAdmin.i18n.previewEmpty, false);

			try {
				frame.contentWindow.document.open();
				frame.contentWindow.document.write('<!doctype html><html><body></body></html>');
				frame.contentWindow.document.close();
			} catch (error) {
				// Ignore iframe reset failures and leave the placeholder visible.
			}

			return;
		}

		previewToken += 1;
		documentHtml = buildPreviewDocument(selection, settings, previewToken);

		setPreviewPlaceholder(TTTWAdmin.i18n.previewLoading, false);
		$previewFrame.addClass('is-active').css('height', '360px');

		try {
			frame.contentWindow.document.open();
			frame.contentWindow.document.write(documentHtml);
			frame.contentWindow.document.close();
		} catch (error) {
			setPreviewPlaceholder(TTTWAdmin.i18n.previewError, true);
		}
	}

	function request(action, data) {
		return $.ajax({
			url: TTTWAdmin.ajaxUrl,
			method: 'GET',
			dataType: 'json',
			data: $.extend(
				{
					action: action,
					nonce: TTTWAdmin.nonce
				},
				data || {}
			)
		});
	}

	function handleRequestFailure(response) {
		var message = TTTWAdmin.i18n.loadError;

		if (response && response.responseJSON && response.responseJSON.data && response.responseJSON.data.message) {
			message = response.responseJSON.data.message;
		}

		setStatus(message, true);
	}

	function loadCountries(selectedCountryId) {
		var deferred = $.Deferred();
		var language = $language.val();

		resetSelect($country, TTTWAdmin.i18n.selectCountry, true);
		resetSelect($region, TTTWAdmin.i18n.chooseCountryFirst, true);
		resetSelect($location, TTTWAdmin.i18n.chooseRegionFirst, true);

		if (! language) {
			setStatus(TTTWAdmin.i18n.chooseLanguageFirst, false);
			deferred.resolve();
			return deferred.promise();
		}

		setStatus(TTTWAdmin.i18n.loading, false);
		catalog.countries = [];
		catalog.regions = [];
		catalog.locations = [];

		request('tttw_get_countries', {
			language: language
		}).done(function (response) {
			if (! response.success) {
				handleRequestFailure(response);
				deferred.reject();
				return;
			}

			catalog.countries = response.data.items || [];
			populateSelect($country, response.data.items, TTTWAdmin.i18n.selectCountry, selectedCountryId);
			setStatus('', false);
			renderPreview();
			deferred.resolve();
		}).fail(function (response) {
			handleRequestFailure(response);
			deferred.reject();
		});

		return deferred.promise();
	}

	function loadRegions(selectedRegionId) {
		var deferred = $.Deferred();
		var language = $language.val();
		var countryId = $country.val();

		resetSelect($region, TTTWAdmin.i18n.selectRegion, true);
		resetSelect($location, TTTWAdmin.i18n.chooseRegionFirst, true);
		catalog.regions = [];
		catalog.locations = [];

		if (! language || ! countryId) {
			renderPreview();
			deferred.resolve();
			return deferred.promise();
		}

		setStatus(TTTWAdmin.i18n.loading, false);

		request('tttw_get_regions', {
			language: language,
			country_id: countryId
		}).done(function (response) {
			if (! response.success) {
				handleRequestFailure(response);
				deferred.reject();
				return;
			}

			catalog.regions = response.data.items || [];
			populateSelect($region, response.data.items, TTTWAdmin.i18n.selectRegion, selectedRegionId);
			setStatus('', false);
			renderPreview();
			deferred.resolve();
		}).fail(function (response) {
			handleRequestFailure(response);
			deferred.reject();
		});

		return deferred.promise();
	}

	function loadLocations(selectedLocationId) {
		var deferred = $.Deferred();
		var language = $language.val();
		var countryId = $country.val();
		var regionId = $region.val();

		resetSelect($location, TTTWAdmin.i18n.selectLocation, true);
		catalog.locations = [];

		if (! language || ! countryId || ! regionId) {
			renderPreview();
			deferred.resolve();
			return deferred.promise();
		}

		setStatus(TTTWAdmin.i18n.loading, false);

		request('tttw_get_locations', {
			language: language,
			country_id: countryId,
			region_id: regionId
		}).done(function (response) {
			if (! response.success) {
				handleRequestFailure(response);
				deferred.reject();
				return;
			}

			catalog.locations = response.data.items || [];
			populateSelect($location, response.data.items, TTTWAdmin.i18n.selectLocation, selectedLocationId);
			setStatus('', false);
			renderPreview();
			deferred.resolve();
		}).fail(function (response) {
			handleRequestFailure(response);
			deferred.reject();
		});

		return deferred.promise();
	}

	$language.on('change', function () {
		loadCountries('');
	});

	$country.on('change', function () {
		loadRegions('');
	});

	$region.on('change', function () {
		loadLocations('');
	});

	$location.on('change', function () {
		renderPreview();
	});

	$form.on('change', '#tttw-number-days, #tttw-include-title, #tttw-include-map, #tttw-include-weather, #tttw-include-styles, #tttw-weather-unit, #tttw-height-unit', function () {
		renderPreview();
	});

	$('.tttw-delete-form').on('submit', function () {
		return window.confirm(TTTWAdmin.i18n.deleteConfirm);
	});

	$(window).on('message', function (event) {
		var data = event.originalEvent && event.originalEvent.data ? event.originalEvent.data : null;
		var height;

		if (! data || data.token !== previewToken) {
			return;
		}

		if (data.type === 'tttwPreviewError') {
			setPreviewPlaceholder(TTTWAdmin.i18n.previewError, true);
			return;
		}

		if (data.type !== 'tttwPreviewHeight') {
			return;
		}

		height = parseInt(data.height, 10);

		if (isNaN(height) || height < 1) {
			return;
		}

		$previewFrame.css('height', String(Math.max(360, height + 8)) + 'px');
		$previewFrame.addClass('is-active');
		$previewPlaceholder.hide();
	});

	loadCountries(currentWidget && currentWidget.country ? currentWidget.country.id : '').done(function () {
		if (currentWidget && currentWidget.country && currentWidget.region) {
			loadRegions(currentWidget.region.id).done(function () {
				if (currentWidget.location) {
					loadLocations(currentWidget.location.id);
				} else {
					renderPreview();
				}
			});
		} else {
			renderPreview();
		}
	}).fail(function () {
		setPreviewPlaceholder(TTTWAdmin.i18n.previewError, true);
	});
}(jQuery));
