/**
 * Title Separator Picker.
 *
 * Grid tombol pilihan separator. Whitelist karakter HARUS konsisten
 * dengan Settings/SiteInfo.php::ALLOWED_SEPARATORS (PHP) - lihat
 * catatan di sana soal alasan whitelist (bukan free-text).
 *
 * @package Lunar\SEO
 */

import { Button } from '@wordpress/components';

// Konsisten dengan Settings/SiteInfo.php::ALLOWED_SEPARATORS.
const SEPARATORS = [ '|', '-', '—', ':', '.', '•', '*', '~', '«', '»', '/', '\\', '>', '<' ];

export default function TitleSeparatorPicker( { value, onChange } ) {
	return (
		<div className="lunar-separator-picker">
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
