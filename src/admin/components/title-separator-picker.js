/**
 * Title Separator Picker.
 *
 * A grid of separator choice buttons. The character whitelist must
 * stay consistent with Settings/SiteInfo.php::ALLOWED_SEPARATORS
 * (PHP) — see the note there for why it's a whitelist, not free text.
 *
 * Accepts `labelledBy` (the id of an external label element, rendered
 * by the caller) for role="group" + aria-labelledby — giving the
 * group context to screen readers without forcing a native
 * <fieldset> (avoiding the extra box-model reset for .lunar-field,
 * which is also used as a plain <div> elsewhere).
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

// Consistent with Settings/SiteInfo.php::ALLOWED_SEPARATORS.
const SEPARATORS = [ '|', '-', '—', ':', '.', '•', '*', '~', '«', '»', '/', '\\', '>', '<' ];

// A screen reader announces these symbols inconsistently on their own,
// so each button gets an explicit accessible name alongside the
// visible character.
const SEPARATOR_LABELS = {
	'|': __( 'Vertical bar', 'lunar-seo' ),
	'-': __( 'Hyphen', 'lunar-seo' ),
	'—': __( 'Em dash', 'lunar-seo' ),
	':': __( 'Colon', 'lunar-seo' ),
	'.': __( 'Period', 'lunar-seo' ),
	'•': __( 'Bullet', 'lunar-seo' ),
	'*': __( 'Asterisk', 'lunar-seo' ),
	'~': __( 'Tilde', 'lunar-seo' ),
	'«': __( 'Left angle quotes', 'lunar-seo' ),
	'»': __( 'Right angle quotes', 'lunar-seo' ),
	'/': __( 'Slash', 'lunar-seo' ),
	'\\': __( 'Backslash', 'lunar-seo' ),
	'>': __( 'Greater than', 'lunar-seo' ),
	'<': __( 'Less than', 'lunar-seo' ),
};

export default function TitleSeparatorPicker( { value, onChange, labelledBy } ) {
	return (
		<div className="lunar-separator-picker" role="group" aria-labelledby={ labelledBy }>
			{ SEPARATORS.map( ( separator ) => (
				<Button
					key={ separator }
					variant={ value === separator ? 'primary' : 'secondary' }
					onClick={ () => onChange( separator ) }
					aria-pressed={ value === separator }
					aria-label={ SEPARATOR_LABELS[ separator ] }
				>
					{ separator }
				</Button>
			) ) }
		</div>
	);
}