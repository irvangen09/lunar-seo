/**
 * Admin entry point.
 *
 * Mounts the React Settings app onto the root container rendered by
 * Admin.php (GENERAL_MODULE_ARCHITECTURE.md §7).
 *
 * @package Lunar\SEO
 */

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import App from './app';
import './style.css';

const ROOT_ELEMENT_ID = 'lunar-seo-general-settings-root';

domReady( () => {
	const root = document.getElementById( ROOT_ELEMENT_ID );

	if ( ! root ) {
		return;
	}

	createRoot( root ).render( <App /> );
} );