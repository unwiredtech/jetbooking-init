import CheckInOutEdit from "./edit";
import metadata from "./block.json";

const { __ } = wp.i18n;
const { name, icon } = metadata;

/**
 * Available items for `useEditProps`:
 *  - uniqKey
 *  - formFields
 *  - blockName
 *  - attrHelp
 */
const settings = {
	icon: <span dangerouslySetInnerHTML={ { __html: icon } }></span>,
	edit: CheckInOutEdit,
	useEditProps: [ 'uniqKey', 'blockName', 'attrHelp' ],
};

export { metadata, name, settings};