const { tideLocations } = require("./tide-locations");

const { registerBlockType } = wp.blocks;
const { SelectControl, CheckboxControl } = wp.components;

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
        },
    },
    edit: ({ attributes, setAttributes }) => {
        const {
            country,
            region,
            location,
            daysToShow,
            weatherUnit,
            includeWeather,
            includeMap,
            includeBaseStyles,
            includeTitle
        } = attributes;

        const countries = Object.keys(tideLocations);
        const regions = country ? Object.keys(tideLocations[country]) : [];
        const locations = region ? tideLocations[country][region] : [];

        return (
            <div class="tides-editor__container">
                <SelectControl
                    label="Country"
                    value={country}
                    options={[{ label: 'Select a Country', value: '' }, ...countries.map(c => ({ label: c, value: c }))]}
                    onChange={(newCountry) => {
                        setAttributes({ country: newCountry, region: '', location: '' });
                    }}
                />
                {country && (
                    <SelectControl
                        label="Region"
                        value={region}
                        options={[{ label: 'Select a Region', value: '' }, ...regions.map(r => ({ label: r, value: r }))]}
                        onChange={(newRegion) => {
                            setAttributes({ region: newRegion, location: '' });
                        }}
                    />
                )}
                {region && (
                    <SelectControl
                        label="Location"
                        value={location}
                        options={[{ label: 'Select a Location', value: '' }, ...locations.map(l => ({ label: l, value: l }))]}
                        onChange={(newLocation) => {
                            setAttributes({ location: newLocation });
                        }}
                    />
                )}
                {location && (<div>
                    <SelectControl
                        label="Days to Show"
                        value={daysToShow}
                        options={[
                            { label: '1 day', value: 1 },
                            { label: '2 days', value: 2 },
                            { label: '3 days', value: 3 },
                            { label: '4 days', value: 4 },
                            { label: '5 days', value: 5 }
                        ]}
                        onChange={(newDaysToShow) => {
                            setAttributes({ daysToShow: parseInt(newDaysToShow, 10) });
                        }}
                    />
                    <CheckboxControl
                        label="Include Weather"
                        checked={includeWeather}
                        onChange={(newValue) => setAttributes({ includeWeather: newValue })}
                    />
                    {includeWeather && (
                        <SelectControl
                            label="Temperature unit"
                            value={weatherUnit}
                            options={[
                                { label: '°C', value: 'c' },
                                { label: '°F', value: 'f' },
                            ]}
                            onChange={(newWeatherUnit) => {
                                setAttributes({ weatherUnit: newWeatherUnit });
                            }}
                        />
                    )}

                    <CheckboxControl
                        label="Include Map"
                        checked={includeMap}
                        onChange={(newValue) => setAttributes({ includeMap: newValue })}
                    />

                    <CheckboxControl
                        label="Include Base Styles"
                        checked={includeBaseStyles}
                        onChange={(newValue) => setAttributes({ includeBaseStyles: newValue })}
                    />

                    <CheckboxControl
                        label="Include Title"
                        checked={includeTitle}
                        onChange={(newValue) => setAttributes({ includeTitle: newValue })}
                    />
                </div>)}
            </div>
        );
    },
    save: () => {
        return null;
    },});
