<?php
/**
 * Plugin Name: GF Geolocation Display
 * Description: Displays a map and location information for Gravity Forms Geolocation. Provides instructions in GF settings.
 * Author: Your Name
 * Version: 1.0.0
 * Text Domain: gf-geolocation-display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Main plugin class.
 */
class GF_Geolocation_Display {

	/**
	 * Holds the singleton instance.
	 *
	 * @var GF_Geolocation_Display
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance of this class.
	 *
	 * @return GF_Geolocation_Display
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Hooks into WP.
	 */
	private function __construct() {
		// Only load if Gravity Forms is active.
		add_action( 'admin_init', array( $this, 'check_for_gravity_forms' ) );

		// Add a settings link under Gravity Forms > Settings.
		add_action( 'admin_menu', array( $this, 'add_gf_settings_submenu' ), 20 );

		// Enqueue frontend scripts (for the map display).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Ensure Gravity Forms is active before proceeding.
	 */
	public function check_for_gravity_forms() {
		if ( ! class_exists( 'GFForms' ) ) {
			add_action( 'admin_notices', array( $this, 'gf_missing_notice' ) );
		}
	}

	/**
	 * Show admin notice if GF is missing.
	 */
	public function gf_missing_notice() {
		?>
		<div class="error notice">
			<p><?php esc_html_e( 'GF Geolocation Display requires Gravity Forms to be installed and active.', 'gf-geolocation-display' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add a submenu item under "Forms > Settings".
	 */
	public function add_gf_settings_submenu() {
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
	 * Render instructions / HTML for the Settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap gf-geolocation-display-settings">
			<h1><?php esc_html_e( 'GF Geolocation Display Settings', 'gf-geolocation-display' ); ?></h1>
			<p>
				<?php esc_html_e( 'Below is an example HTML snippet you can insert into a Gravity Forms "HTML" field to display a map and location text (city/postcode).', 'gf-geolocation-display' ); ?>
			</p>

<pre><code>&lt;div id="gform-geo-map" style="width: 100%; height: 400px; background: #eee;"&gt;&lt;/div&gt;
&lt;div id="gform-geo-details"&gt;
  &lt;strong&gt;City:&lt;/strong&gt; &lt;span id="gform-geo-city"&gt;&lt;/span&gt;&lt;br&gt;
  &lt;strong&gt;Postcode:&lt;/strong&gt; &lt;span id="gform-geo-postcode"&gt;&lt;/span&gt;
&lt;/div&gt;

&lt;!-- The script below is optional if you'd rather place your custom script in a theme file. --&gt;
&lt;script&gt;
gform.addAction('gform_geolocation_found', function(locationData, formId) {
  // Replace 123 with your form ID (if you want to target a specific form).
  // if (formId !== 123) return;

  // locationData should contain lat, lng, city, postal_code, etc.
  var lat = parseFloat(locationData.lat);
  var lng = parseFloat(locationData.lng);

  // Update City/Postcode text
  document.getElementById('gform-geo-city').textContent = locationData.city || '';
  document.getElementById('gform-geo-postcode').textContent = locationData.postal_code || '';

  // If the Google Maps API is loaded, center or create a map/marker.
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

			<h3><?php esc_html_e( 'Steps to Use', 'gf-geolocation-display' ); ?></h3>
			<ol style="list-style-type: decimal;">
				<li><?php esc_html_e( 'Create or edit a Gravity Form with an Address field configured for Geolocation autocomplete.', 'gf-geolocation-display' ); ?></li>
				<li><?php esc_html_e( 'Add an "HTML" field to that form and paste the code snippet above into it.', 'gf-geolocation-display' ); ?></li>
				<li><?php esc_html_e( 'Make sure you load the Google Maps JS API on the front-end (see instructions below).', 'gf-geolocation-display' ); ?></li>
				<li><?php esc_html_e( 'Test your form: when you select an address, the map and location details should update.', 'gf-geolocation-display' ); ?></li>
			</ol>
			<hr>
			<h3><?php esc_html_e( 'Load Google Maps JavaScript API', 'gf-geolocation-display' ); ?></h3>
			<p>
				<?php esc_html_e( 'To display a map, you must load the Google Maps JS API (with a valid API key that has Places/Maps enabled). This plugin enqueues a default script with no API key. You should update it to include your own key.', 'gf-geolocation-display' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue frontend scripts.
	 *
	 * - Example loading Google Maps JS (needs your API key).
	 * - Also enqueues a small JS file if you want to handle advanced logic.
	 */
	public function enqueue_frontend_scripts() {
		// Only load if GF is active. (Optional check)
		if ( ! class_exists( 'GFForms' ) ) {
			return;
		}

		// Replace YOUR_API_KEY with your real key.
		wp_register_script(
			'gf-geolocation-gmaps',
			'https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places',
			array(),
			null,
			true
		);

		wp_enqueue_script( 'gf-geolocation-gmaps' );

		// (Optional) Enqueue a small custom JS file if you want to handle logic separately.
		wp_register_script(
			'gf-geolocation-display-js',
			plugin_dir_url( __FILE__ ) . 'js/gf-geolocation-display.js',
			array( 'jquery', 'gf-geolocation-gmaps' ),
			'1.0.0',
			true
		);

		wp_enqueue_script( 'gf-geolocation-display-js' );
	}
}

/**
 * Initialize the plugin.
 */
function gf_geolocation_display_init() {
	GF_Geolocation_Display::instance();
}
add_action( 'plugins_loaded', 'gf_geolocation_display_init' );
