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

export default function TemplateField( { label, help, value, onChange, variables, maxLength, multiline, placeholder } ) {
	const currentValue = value || '';

	const insertVariable = ( variable ) => {
		onChange( `${ currentValue }{${ variable }}` );
	};

	const Control = multiline ? TextareaControl : TextControl;

	return (
		<div className="lunar-template-field">
			<p className="lunar-template-field__label">
				<strong>{ label }</strong>
			</p>

			{ help && <p className="lunar-field__help">{ help }</p> }

			{ variables && variables.length > 0 && (
				<div className="lunar-template-field__variables">
					{ variables.map( ( variable ) => (
						<Button
							key={ variable }
							variant="tertiary"
							onClick={ () => insertVariable( variable ) }
						>
							{ `{${ variable }}` }
						</Button>
					) ) }
				</div>
			) }

			<Control value={ currentValue } onChange={ onChange } placeholder={ placeholder } />

			{ maxLength && (
				<p className="lunar-field__counter">
					{ currentValue.length } / { maxLength }
				</p>
			) }
		</div>
	);
}
