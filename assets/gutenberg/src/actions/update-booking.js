const {SelectControl} = wp.components;
const {useEffect, useState} = wp.element;

const {addAction, Tools: {withPlaceholder}} = JetFBActions;
const {ActionFieldsMap, WrapperRequiredControl} = JetFBComponents;
const {useFields} = JetFBHooks;

addAction('update_booking', function MyAction({help, label, settings, source, onChangeSetting}) {
    const [fieldsMap, setFieldsMap] = useState([]);

    const fields = useFields({withInner: false});

    useEffect(() => {
        const fieldsMap = {};

        source.bookingFields.forEach(field => {
            fieldsMap[field] = {label: field};
        });

        setFieldsMap(Object.entries(fieldsMap));
    }, []);

    return <>
        <SelectControl
            label={label('booking_field_id')}
            labelPosition='side'
            value={settings.booking_field_id}
            options={withPlaceholder(fields)}
            onChange={val => onChangeSetting(val, 'booking_field_id')}
        />

        <SelectControl
            label={label('booking_field_dates')}
            labelPosition='side'
            value={settings.booking_field_dates}
            options={withPlaceholder(fields)}
            onChange={val => onChangeSetting(val, 'booking_field_dates')}
        />

        <ActionFieldsMap
            label={label('fields_map')}
            plainHelp={help('fields_map')}
            fields={fieldsMap}
        >
            {
                ({fieldId, fieldData}) => <WrapperRequiredControl field={[fieldId, fieldData]}>
                    <SelectControl
                        help={'apartment_id' === fieldId ?  help('apartment_field') : ''}
                        key={`booking_field_${fieldId}`}
                        value={settings[`booking_field_${fieldId}`]}
                        options={withPlaceholder(fields)}
                        onChange={val => onChangeSetting(val, `booking_field_${fieldId}`)}
                    />
                </WrapperRequiredControl>
            }
        </ActionFieldsMap>
    </>;
});