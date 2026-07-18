/**
 * Entry point Admin - Module Sitemap.
 *
 * Mount React Settings app ke root container yang dirender oleh
 * modules/sitemap/Admin.php.
 *
 * @package Lunar\SEO
 */

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import App from './app';
import './style.css';

const ROOT_ELEMENT_ID = 'lunar-seo-sitemap-settings-root';

domReady( () => {
	const root = document.getElementById( ROOT_ELEMENT_ID );

	if ( ! root ) {
		return;
	}

	createRoot( root ).render( <App /> );
} );
