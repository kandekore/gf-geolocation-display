<?php
/**
 * Plugin Name: GF Geolocation Display
 * Description: Displays a map and location info for Gravity Forms Geolocation, without adding extra Google Maps API code.
 * Author: Darren Kandekore
 * Version: 1.0.0
 * Text Domain: gf-geolocation-display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class GF_Geolocation_Display {

	private static $instance = null;

	/**
	 * Singleton instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'check_for_gravity_forms' ) );

		// Add a submenu under "Forms > Settings" if GF is active.
		add_action( 'admin_menu', array( $this, 'add_gf_settings_submenu' ), 20 );
	}

	/**
	 * Check if Gravity Forms is active.
	 */
	public function check_for_gravity_forms() {
		if ( ! class_exists( 'GFForms' ) ) {
			add_action( 'admin_notices', array( $this, 'gf_missing_notice' ) );
		}
	}

	/**
	 * Admin notice if GF is not installed/active.
	 */
	public function gf_missing_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'GF Geolocation Display requires Gravity Forms to be installed and active.', 'gf-geolocation-display' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add "Geolocation Display" submenu under "Forms > Settings".
	 */
	public function add_gf_settings_submenu() {
		// Only add if GF is active.
		if ( class_exists( 'GFForms' ) ) {
			add_submenu_page(
				'gf_settings',                      // parent slug
				__( 'Geolocation Display', 'gf-geolocation-display' ), // page title
				__( 'Geolocation Display', 'gf-geolocation-display' ), // menu title
				'manage_options',                   // capability
				'gf-geolocation-display-settings',  // menu slug
				array( $this, 'render_settings_page' ) // callback
			);
		}
	}

	/**
	 * Render the Settings page in GF for user instructions.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap gf-geolocation-display-settings">
			<h1><?php esc_html_e( 'GF Geolocation Display Settings', 'gf-geolocation-display' ); ?></h1>
			<p>
				<?php esc_html_e( 'Use this snippet in a Gravity Forms "HTML" field to show a map and location details once an address is autocompleted by the GF Geolocation plugin.', 'gf-geolocation-display' ); ?>
			</p>

<pre><code>&lt;div id="gform-geo-map" style="width: 100%; height: 400px; background: #eee;"&gt;&lt;/div&gt;
&lt;div id="gform-geo-details"&gt;
  &lt;strong&gt;City:&lt;/strong&gt; &lt;span id="gform-geo-city"&gt;&lt;/span&gt;&lt;br&gt;
  &lt;strong&gt;Postcode:&lt;/strong&gt; &lt;span id="gform-geo-postcode"&gt;&lt;/span&gt;
&lt;/div&gt;

&lt;script&gt;
gform.addAction('gform_geolocation_found', function(locationData, formId) {
  // If you only want to target a specific form, uncomment &amp; replace with the correct ID:
  // if (formId !== 123) return;

  // locationData should have lat, lng, city, postal_code, etc.
  var lat = parseFloat(locationData.lat);
  var lng = parseFloat(locationData.lng);

  // Update City/Postcode text
  document.getElementById('gform-geo-city').textContent = locationData.city || '';
  document.getElementById('gform-geo-postcode').textContent = locationData.postal_code || '';

  // If the Google Maps API is loaded by the GF Geolocation plugin, we can create/update the map:
  if (typeof google !== 'undefined' &amp;&amp; google.maps) {
    if (!window.gformGeoMap) {
      var mapOptions = {
        center: { lat: lat, lng: lng },
        zoom: 14
      };
      window.gformGeoMap = new google.maps.Map(document.getElementById('gform-geo-map'), mapOptions);

      window.gformGeoMarker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: window.gformGeoMap
      });
    } else {
      // Update existing map/marker
      window.gformGeoMap.setCenter({ lat: lat, lng: lng });
      window.gformGeoMarker.setPosition({ lat: lat, lng: lng });
    }
  }
});
&lt;/script&gt;
</code></pre>

			<h3><?php esc_html_e( 'Instructions', 'gf-geolocation-display' ); ?></h3>
			<ol style="list-style: decimal;">
				<li><?php esc_html_e( 'Make sure the GF Geolocation plugin is configured to load Google Maps JS with your API key.', 'gf-geolocation-display' ); ?></li>
				<li><?php esc_html_e( 'Add an Address field in your form (or whichever field the GF Geolocation plugin supports) for autocomplete.', 'gf-geolocation-display' ); ?></li>
				<li><?php esc_html_e( 'Add an "HTML" field and paste the above snippet in it.', 'gf-geolocation-display' ); ?></li>
				<li><?php esc_html_e( 'Submit or preview the form, and when you select an address, the map and details should update.', 'gf-geolocation-display' ); ?></li>
			</ol>
			<p>
				<?php esc_html_e( 'If you prefer to target a specific form, uncomment the check for formId in the script snippet.', 'gf-geolocation-display' ); ?>
			</p>
		</div>
		<?php
	}
}

/**
 * Initialize the plugin.
 */
function gf_geolocation_display_init() {
	GF_Geolocation_Display::instance();
}
add_action( 'plugins_loaded', 'gf_geolocation_display_init' );
