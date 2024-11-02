/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var _require = __webpack_require__(1),
    tideLocations = _require.tideLocations;

var registerBlockType = wp.blocks.registerBlockType;
var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    CheckboxControl = _wp$components.CheckboxControl;


registerBlockType('tides-today/editor-block', {
    title: 'Tides Today Block',
    category: 'widgets',
    attributes: {
        country: {
            type: 'string',
            default: ''
        },
        region: {
            type: 'string',
            default: ''
        },
        location: {
            type: 'string',
            default: ''
        },
        daysToShow: {
            type: 'number',
            default: 3
        },
        weatherUnit: {
            type: 'string',
            default: 'c'
        },
        includeWeather: {
            type: 'boolean',
            default: true
        },
        includeMap: {
            type: 'boolean',
            default: true
        },
        includeBaseStyles: {
            type: 'boolean',
            default: true
        },
        includeTitle: {
            type: 'boolean',
            default: true
        }
    },
    edit: function edit(_ref) {
        var attributes = _ref.attributes,
            setAttributes = _ref.setAttributes;
        var country = attributes.country,
            region = attributes.region,
            location = attributes.location,
            daysToShow = attributes.daysToShow,
            weatherUnit = attributes.weatherUnit,
            includeWeather = attributes.includeWeather,
            includeMap = attributes.includeMap,
            includeBaseStyles = attributes.includeBaseStyles,
            includeTitle = attributes.includeTitle;


        var countries = Object.keys(tideLocations);
        var regions = country ? Object.keys(tideLocations[country]) : [];
        var locations = region ? tideLocations[country][region] : [];

        return wp.element.createElement(
            'div',
            { 'class': 'tides-editor__container' },
            wp.element.createElement(SelectControl, {
                label: 'Country',
                value: country,
                options: [{ label: 'Select a Country', value: '' }].concat(_toConsumableArray(countries.map(function (c) {
                    return { label: c, value: c };
                }))),
                onChange: function onChange(newCountry) {
                    setAttributes({ country: newCountry, region: '', location: '' });
                }
            }),
            country && wp.element.createElement(SelectControl, {
                label: 'Region',
                value: region,
                options: [{ label: 'Select a Region', value: '' }].concat(_toConsumableArray(regions.map(function (r) {
                    return { label: r, value: r };
                }))),
                onChange: function onChange(newRegion) {
                    setAttributes({ region: newRegion, location: '' });
                }
            }),
            region && wp.element.createElement(SelectControl, {
                label: 'Location',
                value: location,
                options: [{ label: 'Select a Location', value: '' }].concat(_toConsumableArray(locations.map(function (l) {
                    return { label: l, value: l };
                }))),
                onChange: function onChange(newLocation) {
                    setAttributes({ location: newLocation });
                }
            }),
            location && wp.element.createElement(
                'div',
                null,
                wp.element.createElement(SelectControl, {
                    label: 'Days to Show',
                    value: daysToShow,
                    options: [{ label: '1 day', value: 1 }, { label: '2 days', value: 2 }, { label: '3 days', value: 3 }, { label: '4 days', value: 4 }, { label: '5 days', value: 5 }],
                    onChange: function onChange(newDaysToShow) {
                        setAttributes({ daysToShow: parseInt(newDaysToShow, 10) });
                    }
                }),
                wp.element.createElement(CheckboxControl, {
                    label: 'Include Weather',
                    checked: includeWeather,
                    onChange: function onChange(newValue) {
                        return setAttributes({ includeWeather: newValue });
                    }
                }),
                includeWeather && wp.element.createElement(SelectControl, {
                    label: 'Temperature unit',
                    value: weatherUnit,
                    options: [{ label: '°C', value: 'c' }, { label: '°F', value: 'f' }],
                    onChange: function onChange(newWeatherUnit) {
                        setAttributes({ weatherUnit: newWeatherUnit });
                    }
                }),
                wp.element.createElement(CheckboxControl, {
                    label: 'Include Map',
                    checked: includeMap,
                    onChange: function onChange(newValue) {
                        return setAttributes({ includeMap: newValue });
                    }
                }),
                wp.element.createElement(CheckboxControl, {
                    label: 'Include Base Styles',
                    checked: includeBaseStyles,
                    onChange: function onChange(newValue) {
                        return setAttributes({ includeBaseStyles: newValue });
                    }
                }),
                wp.element.createElement(CheckboxControl, {
                    label: 'Include Title',
                    checked: includeTitle,
                    onChange: function onChange(newValue) {
                        return setAttributes({ includeTitle: newValue });
                    }
                })
            )
        );
    },
    save: function save() {
        return null;
    } });

/***/ }),
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "tideLocations", function() { return tideLocations; });
var tideLocations = {
    "USA": {
        "California": ["Los Angeles", "San Francisco"],
        "New York": ["New York City", "Buffalo"]
    },
    "Canada": {
        "Ontario": ["Toronto", "Ottawa"],
        "Quebec": ["Montreal", "Quebec City"]
        // Add more countries, regions, and locations as needed
    } };

/***/ })
/******/ ]);