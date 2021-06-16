/**
 * Separate output module for Splide slider library
 */
import '@splidejs/splide/dist/css/splide.min.css';
import Splide from '@splidejs/splide';

if ( ! window.Splide ) {
	window.Splide = Splide;
}
