/**
 * Wordpress dependencies
 */
const { registerBlockType } = wp.blocks;
const {
	Panel,
	PanelBody,
	RangeControl,
	FormTokenField,
	SelectControl,
	ToggleControl,
	TextControl,
} = wp.components;
const apiFetch = wp.apiFetch;
const { addQueryArgs } = wp.url;
const {
	InspectorControls,
	BlockControls,
	useBlockProps,
	BlockVerticalAlignmentToolbar,
} = wp.blockEditor;
const { useState, useEffect, useRef } = wp.element;

import CmlsServerSideRender from './cmls-server-side-render.jsx';

import { debounce } from 'lodash';

import metadata from './block.json';

registerBlockType( metadata.name, {
	...metadata,

	edit: ( props ) => {
		const { attributes: attr, setAttributes } = props;

		const [ categoriesList, setCategoriesList ] = useState( [] );
		const [ categoriesInput, setCategoriesInput ] = useState( [] );
		const [ tagsList, setTagsList ] = useState( [] );
		const [ tagInput, setTagInput ] = useState( [] );

		// Convert an array of objects to a new object in the
		// form of { [ object.name ]: object, ... }
		const compileList = ( list ) => {
			if ( typeof list === 'object' ) {
				return list.reduce(
					( accumulator, item ) => ( {
						...accumulator,
						[ item.name ]: item,
					} ),
					{}
				);
			}
			return {};
		};
		const categorySuggestions = compileList( categoriesList );
		const tagSuggestions = compileList( tagsList );

		// Update a tokenized attribute from suggestion list
		const updateListAttribute = ( attrName, tokens, suggestions ) => {
			if ( tokens ) {
				const hasNoSuggestion = tokens.some(
					( token ) =>
						typeof token === 'string' && ! suggestions[ token ]
				);
				if ( hasNoSuggestion ) {
					return;
				}
				// existing tokens in the list will be objects, new ones will be
				// strings to look up in the suggestions
				const newList = tokens.map( ( token ) => {
					return typeof token === 'string'
						? suggestions[ token ]
						: token;
				} );

				// Do nothing if not coming from suggestions
				if ( newList.includes( null ) ) {
					return false;
				}

				setAttributes( {
					[ attrName ]: newList,
				} );
			}
		};

		const isStillMounted = useRef();

		let currentFetchRequest;

		// Fetch and search data for categories and tags
		const fetchData = ( type ) => {
			isStillMounted.current = true;

			let apiUrl, searchData, returner;
			switch ( type ) {
				case 'categories':
					apiUrl = '/wp/v2/cmls_testimonial-category';
					searchData = categoriesInput;
					returner = setCategoriesList;
					break;
				case 'tags':
					apiUrl = '/wp/v2/cmls_testimonial-tags';
					searchData = tagInput;
					returner = setTagsList;
					break;
			}

			if ( apiUrl ) {
				const fetchRequest = ( currentFetchRequest = apiFetch( {
					path: addQueryArgs( apiUrl, {
						per_page: -1,
						search: searchData,
					} ),
				} )
					.then( ( data ) => {
						if (
							isStillMounted.current &&
							fetchRequest === currentFetchRequest
						) {
							returner( data );
						}
					} )
					.catch( () => {
						if (
							isStillMounted.current &&
							fetchRequest === currentFetchRequest
						) {
							returner( [] );
						}
					} ) );
			}

			return () => {
				isStillMounted.current = false;
			};
		};

		// Populate category list
		useEffect( () => {
			return fetchData( 'categories' );
		}, [ categoriesInput ] );

		// Populate tags list from current search input
		useEffect( () => {
			return fetchData( 'tags' );
		}, [ tagInput ] );

		// Initialize a splide in the current block
		const initSplide = () => {
			const splider = document.querySelector(
				`#block-${ props.clientId } .splide`
			);
			if ( splider ) {
				if ( splider.splide ) {
					splider.splide.destroy( true );
				}
				splider.splide = new Splide( splider ).mount();
			}
		};

		const blockProps = useBlockProps();
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<Panel>
						<PanelBody title="Query Controls">
							<SelectControl
								label="Order By"
								value={ attr.orderBy + '/' + attr.order }
								onChange={ ( val ) => {
									const newVal = val.split( '/' );
									setAttributes( {
										orderBy: newVal[ 0 ],
										order: newVal[ 1 ],
									} );
								} }
								options={ [
									{
										value: 'rand/asc',
										label: 'Random',
									},
									{
										value: 'date/desc',
										label: 'Newest to oldest',
									},
									{
										value: 'date/asc',
										label: 'Oldest to newest',
									},
									{
										value: 'post_title/asc',
										label: 'Title A → Z',
									},
									{
										value: 'post_title/desc',
										label: 'Title Z → A',
									},
									{
										value: 'menu_order/asc',
										label: 'Manual Order Field (Ascending)',
									},
									{
										value: 'menu_order/desc',
										label:
											'Manual Order Field (Descending)',
									},
								] }
							/>
							<FormTokenField
								label="Categories"
								placeholder="Choose from existing categories."
								value={
									attr.categories &&
									attr.categories.map( ( cat ) => cat.name )
								}
								suggestions={ Object.keys(
									categorySuggestions
								) }
								onChange={ ( val ) =>
									updateListAttribute(
										'categories',
										val,
										categorySuggestions
									)
								}
								onInputChange={ debounce(
									( val ) => setCategoriesInput( val ),
									300
								) }
							/>
							<FormTokenField
								label="Tags"
								placeholder="Choose from existing tags."
								value={
									attr.tags &&
									attr.tags.map( ( tag ) => tag.name )
								}
								suggestions={ Object.keys( tagSuggestions ) }
								onChange={ ( val ) =>
									updateListAttribute(
										'tags',
										val,
										tagSuggestions
									)
								}
								onInputChange={ debounce(
									( val ) => setTagInput( val ),
									300
								) }
							/>
							<RangeControl
								label="Number of Testimonials to display"
								help="Choose 0 to fetch all."
								allowReset
								resetFallbackValue={ 0 }
								step={ 1 }
								withInputField={ true }
								marks={ [
									{
										value: 0,
										label: 'All',
									},
									{
										value: 50,
										label: '50',
									},
								] }
								value={ attr.postsToShow }
								onChange={ ( val ) =>
									setAttributes( {
										postsToShow:
											val !== null
												? parseInt( val, 10 )
												: 0,
									} )
								}
								min={ 0 }
								max={ 50 }
							/>
						</PanelBody>
						<PanelBody
							title="Content Display"
							initialOpen={ false }
						>
							<SelectControl
								label="Show Content"
								value={ attr.showContent }
								onChange={ ( val ) =>
									setAttributes( { showContent: val } )
								}
								options={ [
									{
										value: 'full',
										label: 'Full',
									},
									{
										value: 'excerpt',
										label: 'Excerpt',
									},
									{
										value: 'none',
										label: 'None',
									},
								] }
							/>
							<ToggleControl
								label="Show Customer Name"
								checked={ attr.showCustomerName }
								onChange={ ( val ) =>
									setAttributes( { showCustomerName: val } )
								}
							/>
							<ToggleControl
								label="Show Customer Title"
								checked={ attr.showCustomerTitle }
								onChange={ ( val ) =>
									setAttributes( { showCustomerTitle: val } )
								}
							/>
							<ToggleControl
								label="Show Company"
								checked={ attr.showCompany }
								onChange={ ( val ) =>
									setAttributes( { showCompany: val } )
								}
							/>
							<ToggleControl
								label="Show Logo"
								checked={ attr.showLogo }
								onChange={ ( val ) =>
									setAttributes( { showLogo: val } )
								}
							/>
						</PanelBody>
						<PanelBody title="Slider Options" initialOpen={ false }>
							<SelectControl
								label="Animation Type"
								value={ attr.animationType }
								onChange={ ( val ) =>
									setAttributes( { animationType: val } )
								}
								options={ [
									{
										value: 'slide',
										label: 'Slide',
									},
									{
										value: 'fade',
										label: 'Fade',
									},
								] }
							/>
							<ToggleControl
								label="Show Arrows"
								checked={ attr.showArrows }
								onChange={ ( val ) =>
									setAttributes( { showArrows: val } )
								}
							/>
							<ToggleControl
								label="Show Dots"
								checked={ attr.showDots }
								onChange={ ( val ) =>
									setAttributes( { showDots: val } )
								}
							/>
							<RangeControl
								label="Number of slides to show at once"
								allowReset
								resetFallbackValue={ 1 }
								step={ 1 }
								withInputField={ true }
								marks={ [
									{
										value: 1,
										label: '1',
									},
									{
										value: 5,
										label: '5',
									},
								] }
								value={ attr.slidesToShow }
								onChange={ ( val ) =>
									setAttributes( {
										slidesToShow: val
											? parseInt( val, 10 )
											: 1,
									} )
								}
								min={ 1 }
								max={ 5 }
							/>
							<RangeControl
								label="Number of slides to move at once"
								allowReset
								resetFallbackValue={ 1 }
								step={ 1 }
								withInputField={ true }
								marks={ [
									{
										value: 1,
										label: '1',
									},
									{
										value: 5,
										label: '5',
									},
								] }
								value={ attr.slidesToScroll }
								onChange={ ( val ) =>
									setAttributes( {
										slidesToScroll: val
											? parseInt( val, 10 )
											: 1,
									} )
								}
								min={ 1 }
								max={ 5 }
							/>
							<ToggleControl
								label="Autoplay"
								checked={ attr.autoplay }
								onChange={ ( val ) =>
									setAttributes( { autoplay: val } )
								}
							/>
							{ attr.autoplay && (
								<TextControl
									label="Autoplay speed (in seconds)"
									type="number"
									value={ parseFloat( attr.autoplaySpeed ) }
									onChange={ ( val ) =>
										setAttributes( {
											autoplaySpeed:
												val || val === 0
													? parseFloat( val )
													: 0,
										} )
									}
								/>
							) }
							<TextControl
								label="Transition speed (in seconds)"
								type="number"
								value={ parseFloat( attr.transitionSpeed ) }
								onChange={ ( val ) =>
									setAttributes( {
										transitionSpeed:
											val || val === 0
												? parseFloat( val )
												: 0,
									} )
								}
							/>
						</PanelBody>
					</Panel>
				</InspectorControls>
				<BlockControls>
					<BlockVerticalAlignmentToolbar
						value={ attr.verticalAlign }
						onChange={ ( val ) => {
							setAttributes( { verticalAlign: val } );
						} }
					/>
				</BlockControls>
				<CmlsServerSideRender
					block={ metadata.name }
					attributes={ attr }
					httpMethod="POST"
					onChange={ initSplide }
				/>
			</div>
		);
	},
} );
