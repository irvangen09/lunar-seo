/**
 * Template Field.
 *
 * A reusable component for a field that supports placeholders/
 * variables (SEO Title, Meta Description). Provides an "insert
 * variable" button that appends a {variable} token to the end of the
 * text, plus an optional character counter.
 *
 * Placed in src/shared/ (not src/admin/ or src/editor/) because it's
 * used by BOTH bundles — the Admin Settings app and the Editor
 * Sidebar — avoiding duplicated code across two different webpack
 * entries. Each bundle still imports this file independently at build
 * time (a small amount of duplicated bundle size, a reasonable
 * trade-off against duplicating the source code).
 *
 * Variable insertion happens at the end of the text (not at the
 * cursor position) — a deliberate simplification to avoid the
 * complexity of cursor-position tracking (over-engineering for what's
 * actually needed right now).
 *
 * @package Lunar\SEO
 */

import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * A user-friendly label for each variable slug. This one place is the
 * Single Source of Truth so naming stays consistent everywhere
 * TemplateField is used (Admin Settings & Editor Sidebar) — avoiding
 * a label that reads differently in each place it's used (DRY).
 *
 * An unregistered slug still displays fine (automatic fallback
 * formatting), so adding a new variable later doesn't require
 * updating this dictionary.
 */
const VARIABLE_LABELS = {
	title: __( 'Title', 'lunar-seo' ),
	term_title: __( 'Term Title', 'lunar-seo' ),
	site_name: __( 'Site Name', 'lunar-seo' ),
	tagline: __( 'Tagline', 'lunar-seo' ),
	separator: __( 'Separator', 'lunar-seo' ),
	query: __( 'Search Query', 'lunar-seo' ),
};

function getVariableLabel( variable ) {
	if ( VARIABLE_LABELS[ variable ] ) {
		return VARIABLE_LABELS[ variable ];
	}

	// Fallback: "custom_field" -> "Custom Field".
	return variable
		.split( '_' )
		.map( ( word ) => word.charAt( 0 ).toUpperCase() + word.slice( 1 ) )
		.join( ' ' );
}

export default function TemplateField( { label, help, value, onChange, variables, maxLength, multiline, placeholder } ) {
	const currentValue = value || '';

	const insertVariable = ( variable ) => {
		onChange( `${ currentValue }{${ variable }}` );
	};

	const Control = multiline ? TextareaControl : TextControl;

	return (
		<div className="lunar-template-field">
			{ variables && variables.length > 0 && (
				<div className="lunar-template-field__variables">
					{ variables.map( ( variable ) => (
						<Button
							key={ variable }
							className="lunar-template-field__variable"
							onClick={ () => insertVariable( variable ) }
							title={ `{${ variable }}` }
						>
							{ getVariableLabel( variable ) }
						</Button>
					) ) }
				</div>
			) }

			{ /*
			 * label & help are passed DIRECTLY to Control (not rendered
			 * separately as <p><strong>) — TextControl/TextareaControl
			 * renders a <label for="..."> correctly associated with the
			 * input's id, giving it an accessible name for screen
			 * readers. Rendering the label separately (as it was
			 * before) left the input with no announceable name at all.
			 */ }
			<Control
				label={ label }
				help={ help }
				value={ currentValue }
				onChange={ onChange }
				placeholder={ placeholder }
			/>

			{ maxLength && (
				<p className="lunar-field__counter" aria-live="polite">
					{ currentValue.length } / { maxLength }
				</p>
			) }
		</div>
	);
}