<?php

class WSU_Portfolio_Starter_Theme {

	/**
	 * @var string Current theme version.
	 */
	public $theme_version = '0.0.1';

	public function __construct() {
		add_shortcode( 'wsuwp_portfolio_signup', array( $this, 'portfolio_signup_display' ) );
		add_action( 'wp_ajax_submit_portfolio_create_request', array( $this, 'handle_portfolio_request' ), 10, 1 );
		add_action( 'wp_ajax_nopriv_submit_portfolio_create_request', array( $this, 'handle_portfolio_request' ), 10, 1 );
	}

	/**
	 * Provide a string as a cache breaker for the theme Javascript.
	 *
	 * @return string
	 */
	public function get_theme_version() {
		return spine_get_script_version() . $this->theme_version;
	}

	/**
	 * Display a form when the shortcode is used on the home page to capture information for
	 * the creation of a new student portfolio site.
	 *
	 * This should only be used on the front page.
	 *
	 * @return string HTML output.
	 */
	public function portfolio_signup_display() {
		if ( ! is_front_page() ) {
			return '';
		}

		ob_start();
		if ( is_user_logged_in() ) :
			wp_enqueue_script( 'portfolio_create_request', get_stylesheet_directory_uri() . '/js/portfolio-create.js', array( 'jquery' ), $this->get_theme_version(), true );
			wp_localize_script( 'portfolio_create_request', 'portfolio_create_data', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			?>
			<div class="portfolio-loading" style="display: none; background-image: url(<?php echo get_stylesheet_directory_uri() . '/spinner.gif'; ?>);"></div>
			<div class="portfolio-create-form">
				<input type="hidden" id="portfolio-create-nonce" value="<?php echo esc_attr( wp_create_nonce( 'portfolio-create-nonce' ) ); ?>" />
				<label for="portfolio-name">What should the portfolio title be?</label>
				<input type="text" name="portfolio_name" id="portfolio-name" value="" />
				<label for="portfolio-path" class="portfolio-path-label">Choose a URL for your portfolio:</label>
				<span class="portfolio-pre-input">https://sites.wsu.edu/hbm-182-</span><input type="text" name="portfolio_path" id="portfolio-path" value="" />
				<input type="submit" class="portfolio-create" id="submit-portfolio-create" value="Create">
			</div>
		<?php else : ?>
			<div class="portfolio-auth-form">
				WSU Student Portfolios can be created by authorized users of this network. Please <a href="<?php echo esc_url( wp_login_url( home_url() ) ); ?>">authenticate</a> with your WSU NID to access the portfolio creation form.
			</div>
		<?php endif;

		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Handle AJAX requests from the home page to create new portfolios.
	 */
	public function handle_portfolio_request() {
		global $wpdb;

		if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'portfolio-create-nonce' ) ) {
			echo json_encode( array( 'error' => 'There was a problem submitting your request.' ) );
			die();
		}

		if ( ! isset( $_POST['portfolio_name'] ) || empty( sanitize_text_field( $_POST['portfolio_name'] ) ) ) {
			echo json_encode( array( 'error' => 'Please enter a few words as a title for your portfolio. It is possible the last attempt contained invalid characters.' ) );
			die();
		}

		if ( ! isset( $_POST['portfolio_path'] ) || empty( sanitize_title( $_POST['portfolio_path'] ) ) ) {
			echo json_encode( array( 'error' => 'Please enter a path for your portfolio. This will appear as the second part of the URL and should not contain spaces or invalid characters.' ) );
			die();
		}

		if ( 'project.wp.wsu.dev' === $_SERVER['HTTP_HOST'] ) {
			$portfolio_domain = 'project.wp.wsu.dev';
			$portfolio_scheme = 'http://';
		} else {
			$portfolio_domain = 'sites.wsu.edu';
			$portfolio_scheme = 'https://';
		}

		$portfolio_path = sanitize_title( $_POST['portfolio_path'] );
		// @todo This is hardcoded for HBM 182, it should be dynamic to the site, maybe via the shortcode somehow.
		$portfolio_path = '/hbm-182-' . trailingslashit( $portfolio_path );

		$user_id = get_current_user_id();
		$site_id = get_current_site()->id;

		// Use a direct query rather than `domain_exists()` as multiple networks may share this domain and path combination.
		$query = $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s AND path = %s", $portfolio_domain, $portfolio_path );
		$found_site_id = $wpdb->get_var( $query );

		if ( $found_site_id ) {
			echo json_encode( array( 'error' => 'Sorry, the portfolio site with that path - ' . $portfolio_path . ' - already exists.' ) );
			die();
		}

		$blog_id = wpmu_create_blog( $portfolio_domain, $portfolio_path, sanitize_text_field( $_POST['portfolio_name'] ), $user_id, array(), $site_id );

		if ( is_wp_error( $blog_id ) ) {
			echo json_encode( array( 'error' => esc_attr( $blog_id->get_error_message() ) ) );
			die();
		}

		$portfolio_url = esc_url( $portfolio_scheme . $portfolio_domain . $portfolio_path );
		$success_message = '<p class="success">A new WSU portfolio site has been configured!</p><p class="success">Start communicating at <a href="' . $portfolio_url . '">' . $portfolio_url . '</a>.</p><p>New collaborators can be added to the portfolio through its <a href="' . $portfolio_url . 'wp-admin/">administration interface</a>.</p>';
		$success_message .= '<p class="success"><a href="' . esc_url( home_url() ) . '">Create</a> another one?</p>';
		echo json_encode( array( 'success' => $success_message ) );
		die();
	}
}
new WSU_Portfolio_Starter_Theme();