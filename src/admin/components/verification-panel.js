/**
 * Verification Panel.
 *
 * Fields for the "Verification" section — Google, Bing, Yandex site
 * verification codes. Rendered inside a PanelBody by app.js.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

export default function VerificationPanel( { value, onChange } ) {
	return (
		<>
			<TextControl
				label={ __( 'Google', 'lunar-seo' ) }
				help={ __( 'Get your verification code in Google Search Console.', 'lunar-seo' ) }
				placeholder={ __( 'Add verification code', 'lunar-seo' ) }
				value={ value.google || '' }
				onChange={ ( v ) => onChange( 'google', v ) }
			/>

			<TextControl
				label={ __( 'Bing', 'lunar-seo' ) }
				help={ __( 'Get your verification code in Bing Webmaster Tools.', 'lunar-seo' ) }
				placeholder={ __( 'Add verification code', 'lunar-seo' ) }
				value={ value.bing || '' }
				onChange={ ( v ) => onChange( 'bing', v ) }
			/>

			<TextControl
				label={ __( 'Yandex', 'lunar-seo' ) }
				help={ __( 'Get your verification code in Yandex Webmaster.', 'lunar-seo' ) }
				placeholder={ __( 'Add verification code', 'lunar-seo' ) }
				value={ value.yandex || '' }
				onChange={ ( v ) => onChange( 'yandex', v ) }
			/>
		</>
	);
}