/**
 * Admin entry point.
 *
 * Mounts the React Settings app onto the empty root container that
 * Admin.php renders server-side.
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