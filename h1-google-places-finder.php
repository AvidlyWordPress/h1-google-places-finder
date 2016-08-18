<?php
/**
 * Plugin Name: H1 Google Places Finder
 * Plugin URI:
 * Description: Insert a autocomplete search for Google Places.
 * Version: 0.1
 * Author: Marco Martins / H1
 * Author URI: https://h1.fi
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: h1-google-places-finder
 */

/*  Copyright 2015  Marco Martins / H1  (email : marco@h1.fi)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists('H1_Google_Places_Finder') ) {

	class H1_Google_Places_Finder {


		/**
		 * Plugin prefix.
		 *
		 * @var string
		 */
		public $prefix = 'h1-google-places-finder';


		/**
		 * Variables to set in the JS file.
		 *
		 * @var array
		 */
		public $js_variables = array();


		/**
		 * Current Language
		 *
		 * @var string
		 */
		public $language = 'en';


		/**
		 * Type of places to look for.
		 *
		 * @link List of supported types: https://developers.google.com/places/supported_types
		 *
		 * @var string
		 */
		public $places_type = 'park';


		/**
		 * Google Maps API key.
		 * @var string
		 */
		private $api_key = null;


		/**
		 *
		 */
		function __construct() {

			// Load textdomain
			load_plugin_textdomain( 'h1-google-places-finder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			// Register the shortcode.
			add_action( 'init', array( $this, 'register_shortcode' ) );

		}


		/**
		 * Register shortcode.
		 */
		public function register_shortcode() {

			add_shortcode( 'h1-google-places-finder', array( $this, 'render_shortcode' ) );

			// Set the current language.
			$this->set_language();

			// Register the scripts - doesn't enqueue anything.
			$this->register_scripts();

			// Set variables for JS use.
			$this->js_variables = $this->set_js_variables();

			add_filter( 'wp_footer', array( $this, 'enqueue_scripts' ) );


		}


		/**
		 * Render the shortcode content.
		 */
		public function render_shortcode( $atts ) {

			$shortcode_atts = shortcode_atts( array(
				'type' => $this->places_type,
			), $atts );

			$this->js_variables['placesType'] = apply_filters( 'h1-google-places-finder-place-type', $shortcode_atts['type'] );


			return $this->get_map_html();

		}


		/**
		 * Set the language.
		 */
		public function set_language() {

			$this->language = apply_filters( 'h1-google-places-finder-language', $this->language );

		}


		/**
		 * Register scripts and styles.
		 */
		public function register_scripts() {

			// Set API Key.
			$this->api_key = apply_filters( 'h1-google-places-finder-api-key', false );

			if ( ! $this->api_key ) {
				return new WP_Error( 'h1-google-places-finder_missing_api_key', __( 'The Google Places API key is not set.', 'h1-google-places-finder' ) );
			}

			// Set Google Maps API URL.
			$google_maps_api_url = 'https://maps.googleapis.com/maps/api/js?key=' . $this->api_key . '&libraries=places&callback=h1GoogleMapsAPIPlacesFinder&language=' . $this->language;

			// Register plugin CSS.
			wp_register_style( 'h1-google-maps-api-places-finder-css', plugins_url( 'css/h1-google-places-finder.css', __FILE__ ), array(), '20160517', 'all' );

			// Register plugin JS.
			wp_register_script( 'h1-google-maps-api-places-finder', plugins_url( 'js/h1-google-places-finder.js', __FILE__ ), array(), '20160528', true );

			wp_register_script( 'google-maps-api-places', $google_maps_api_url, array('h1-google-maps-api-places-finder'), '20160528', true );

		}


		/**
		 * Enqueue scripts and styles.
		 */
		public function enqueue_scripts() {

			// Localize and enqueue JS.
			wp_localize_script( 'h1-google-maps-api-places-finder', 'H1GooglePlacesFinder', $this->js_variables );

			// Enqueue CSS.
			wp_enqueue_style('h1-google-maps-api-places-finder-css');

			// Enqueue JS.
			wp_enqueue_script('h1-google-maps-api-places-finder');
			wp_enqueue_script('google-maps-api-places');

		}


		/**
		 * Set the required JavaScript variables.
		 */
		public function set_js_variables() {

			// Google Places place type.
			$js_variables['placesType'] = $this->places_type;

			// Language code. Default english (en).
			// List of accepted languages available in: https://developers.google.com/maps/faq#using-google-maps-apis
			$js_variables['language'] = $this->language;

			// Country to which the results will be restricted. This is not ideal
			// but we don't have the information.
			$js_variables['countryRestrict'] = array(
				'country' => $this->language,
			);

			// Countries full view bounds
			$js_variables['countries'] = array(
				'fi' => array (
					'center' => array(
						'lat' => 65.474456,
						'lng' => 28.1228546,
					),
					'zoom' => 5,
				),
				'se' => array (
					'center' => array(
						'lat' => 61.9179455,
						'lng' => 8.6119689,
					),
					'zoom' => 5,
				),
				'dk' => array (
					'center' => array(
						'lat' => 56.1554911,
						'lng' => 10.4328648,
					),
					'zoom' => 5,
				),
				'no' => array (
					'center' => array(
						'lat' => 64.4988097,
						'lng' => 10.8646539,
					),
					'zoom' => 5,
				),
				'us' => array (
					'center' => array(
						'lat' => 37.1,
						'lng' => -95.7,
					),
					'zoom' => 3,
				),
				'uk' => array (
					'center' => array(
						'lat' => 54.8,
						'lng' => -4.6,
					),
					'zoom' => 5,
				),
			);

			// Initial location.
			$js_variables['initialLocation'] = array(
				'center' => array(
					'lat' => 65.474456,
					'lng' => 28.1228546,
				),
				'zoom' => 5,
			);

			// Radius of the search. Default is 50000 meters, this is the maximum
			// value accepted by Google Places.
			$js_variables['radius'] = 50000;

			// Google Maps Autocomplete types. Check:
			// https://developers.google.com/maps/documentation/javascript/places-autocomplete#add_autocomplete
			$js_variables['autocompleteTypes'] = array('(regions)');

			// Set the marker icons. If the value is false then the default
			// icon from google is used.
			$js_variables['markerIcon'] = false;

			return apply_filters( 'h1-google-places-finder-js-variables', $js_variables );

		}


		/**
		 * Generate Map HTML.
		 *
		 * @return string Base map and input HTML.
		 */
		protected function get_map_html() {

			$html = '';

			// Add location field.
			$html .= '<div id="' . $this->prefix . '-location" class="' . $this->prefix . '-location">';
			$html .= '	<label for="' . $this->prefix . '-autocomplete" class="' . $this->prefix . '-location-label">' . __( 'Postal Code / Address ', 'h1-google-places-finder' ) . '</label>';
			$html .= '	<input id="' . $this->prefix . '-autocomplete" placeholder="' . __( 'Postal Code / Address ', 'h1-google-places-finder' ) . '" type="text" class="' . $this->prefix . '-location-input" />';
			$html .= '</div>';

			$html .= '<div class="' . $this->prefix . '-map-wrapper">';
			$html .= '	<div class="' . $this->prefix . '-map-area">';
			$html .= '		<div id="' . $this->prefix . '-map"></div>';
			$html .= '	</div>';
			$html .= '</div>';

			$html .= '	<div style="display: none">';
			$html .= '		<div id="info-window-content">';
			$html .= '			<table>';

			$html .= '				<tr id="info-window-container-url" class="info-window-row">';
			$html .= '					<td id="info-url"></td>';
			// $html .= '					<td id="iw-icon" class="iw_table_icon"></td>';
			$html .= '					</td>';
			$html .= '				</tr>';

			$html .= '				<tr id="info-window-container-address" class="info-window-row">';
			$html .= '					<td class="info-window-header">' . __( 'Address', 'h1-google-places-finder' ) . ':</td>';
			$html .= '					<td id="info-address"></td>';
			$html .= '				</tr>';

			$html .= '				<tr id="info-window-container-phone" class="info-window-row">';
			$html .= '					<td class="info-window-header">' . __( 'Telephone', 'h1-google-places-finder' ) . ':</td>';
			$html .= '					<td id="info-phone"></td>';
			$html .= '				</tr>';

			$html .= '				<tr id="info-window-container-rating" class="info-window-row">';
			$html .= '					<td class="info-window-header">' . __( 'Rating', 'h1-google-places-finder' ) . ':</td>';
			$html .= '					<td id="info-rating"></td>';
			$html .= '				</tr>';

			$html .= '				<tr id="info-window-container-website" class="info-window-row">';
			$html .= '					<td class="info-window-header">' . __( 'Website', 'h1-google-places-finder' ) . ':</td>';
			$html .= '					<td id="info-website"><a href=""></a></td>';
			$html .= '				</tr>';

			$html .= '			</table>';
			$html .= '		</div>';
			$html .= '	</div>';

			// Filter output HTML.
			$html = apply_filters( 'h1-google-places-finder-html', $html );

			return $html;

		}

	}

}

new H1_Google_Places_Finder();
