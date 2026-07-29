/**
 * Title Separator Picker.
 *
 * Grid tombol pilihan separator. Whitelist karakter HARUS konsisten
 * dengan Settings/SiteInfo.php::ALLOWED_SEPARATORS (PHP) - lihat
 * catatan di sana soal alasan whitelist (bukan free-text).
 *
 * Menerima `labelledBy` (id elemen label eksternal, dirender oleh
 * pemanggil) untuk role="group" + aria-labelledby - memberi konteks
 * grup ke screen reader tanpa memaksakan elemen <fieldset> native
 * (menghindari reset CSS box-model tambahan untuk .lunar-field yang
 * juga dipakai sebagai <div> biasa di tempat lain).
 *
 * @package Lunar\SEO
 */

import { Button } from '@wordpress/components';

// Konsisten dengan Settings/SiteInfo.php::ALLOWED_SEPARATORS.
const SEPARATORS = [ '|', '-', '—', ':', '.', '•', '*', '~', '«', '»', '/', '\\', '>', '<' ];

export default function TitleSeparatorPicker( { value, onChange, labelledBy } ) {
	return (
		<div className="lunar-separator-picker" role="group" aria-labelledby={ labelledBy }>
			{ SEPARATORS.map( ( separator ) => (
				<Button
					key={ separator }
					variant={ value === separator ? 'primary' : 'secondary' }
					onClick={ () => onChange( separator ) }
					aria-pressed={ value === separator }
				>
					{ separator }
				</Button>
			) ) }
		</div>
	);
}
