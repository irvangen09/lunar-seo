/**
 * Template Field.
 *
 * Komponen reusable untuk field yang mendukung placeholder/variable
 * (SEO Title, Meta Description). Menyediakan tombol "insert
 * variable" yang menambahkan token {variable} ke akhir teks, serta
 * character counter opsional.
 *
 * Ditempatkan di src/shared/ (bukan src/admin/ atau src/editor/)
 * karena dipakai oleh KEDUA bundle - Admin Settings app maupun
 * Editor Sidebar - menghindari duplikasi kode antar dua webpack
 * entry yang berbeda (CODING_STANDARD.md §2 - DRY). Masing-masing
 * bundle tetap meng-import file ini secara independen saat build
 * (sedikit duplikasi ukuran bundle, trade-off yang wajar
 * dibandingkan duplikasi kode sumber).
 *
 * Penyisipan variable dilakukan di akhir teks (bukan pada posisi
 * kursor) - penyederhanaan yang disengaja untuk menghindari
 * kompleksitas tracking cursor position (over-engineering untuk
 * kebutuhan saat ini).
 *
 * @package Lunar\SEO
 */

import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Label ramah-pengguna untuk tiap variable slug. Satu tempat ini
 * menjadi Single Source of Truth supaya penamaan konsisten di
 * seluruh pemakaian TemplateField (Admin Settings & Editor Sidebar) -
 * menghindari label yang beda-beda tiap tempat dipakai (DRY,
 * DESIGN_SYSTEM.md §18 - Component Consistency).
 *
 * Slug yang tidak terdaftar tetap tampil (fallback format otomatis),
 * jadi menambah variable baru di masa depan tidak wajib mengubah
 * dictionary ini.
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
			 * label & help diteruskan LANGSUNG ke Control (bukan dirender
			 * terpisah sebagai <p><strong>) - TextControl/TextareaControl
			 * merender <label for="..."> yang terhubung dengan benar ke
			 * id input, memberi accessible name untuk screen reader.
			 * Merender label secara terpisah (seperti sebelumnya) membuat
			 * input sama sekali tidak punya nama yang bisa diumumkan.
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
