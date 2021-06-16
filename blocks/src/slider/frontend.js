import './scss/index.scss';

const sliders = document.querySelectorAll( '.cmls-wp-testimonials-slider' );
if ( sliders ) {
	sliders.forEach( ( slide ) => {
		slide.splide = new window.Splide( slide ).mount();
	} );
}
