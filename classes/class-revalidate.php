<?php
/**
 * NextJS Revalidate Class
 *
 * @package nextjs-revalidate
 */

namespace NextJSRevalidate\Classes;

/**
 * Class Revalidate
 */
class Revalidate {

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	private $option_name = 'nextjs_revalidate_options';

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		// Admin menu and settings.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Post status change and save hooks.
		add_action( 'transition_post_status', array( $this, 'on_post_status_change' ), 10, 3 );
		add_action( 'save_post', array( $this, 'on_save_post' ), 10, 3 );

		// Post trash delete hook.
		add_action( 'trashed_post', array( $this, 'on_trash_post' ), 5 );
		add_action( 'untrashed_post', array( $this, 'on_untrash_post' ), 5 );
		add_action( 'delete_post', array( $this, 'on_delete_post' ), 5 );
		add_action( 'after_delete_post', array( $this, 'on_delete_post', 5 ) );

		// Term change hooks.
		add_action( 'created_term', array( $this, 'on_term_change' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'on_term_change' ), 10, 3 );

		// User change hooks.
		add_action( 'user_register', array( $this, 'on_user_change' ), 10 );
		add_action( 'profile_update', array( $this, 'on_user_change' ), 10 );
		add_action( 'delete_user', array( $this, 'on_user_change' ), 10 );

		// Media change hooks.
		add_action( 'add_attachment', array( $this, 'on_media_change' ), 10 );
		add_action( 'edit_attachment', array( $this, 'on_media_change' ), 10 );
		add_action( 'delete_attachment', array( $this, 'on_media_change' ), 10 );
	}

	/**
	 * Add admin menu page.
	 *
	 * @return void
	 */
	public function add_admin_menu() {

		add_menu_page(
			__( 'NextJS Revalidate', 'nextjs-revalidate' ),
			__( 'Next Revalidate', 'nextjs-revalidate' ),
			'manage_options',
			'nextjs-revalidate',
			array( $this, 'admin_page' ),
			'dashicons-update',
			80
		);
	}

	/**
	 * Register settings, section and fields.
	 *
	 * @return void
	 */
	public function register_settings() {

		// Register the settings group and option.
		register_setting(
			'nextjs_revalidate_group',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// Add settings section and fields.
		add_settings_section(
			'nextjs_revalidate_section',
			__( 'NextJS Revalidate Settings', 'nextjs-revalidate' ),
			array( $this, 'settings_section_callback' ),
			'nextjs-revalidate-settings'
		);

		add_settings_field(
			'nextjs_url',
			__( 'Next.js URL', 'nextjs-revalidate' ),
			array( $this, 'nextjs_url_callback' ),
			'nextjs-revalidate-settings',
			'nextjs_revalidate_section'
		);

		add_settings_field(
			'webhook_secret',
			__( 'Webhook Secret', 'nextjs-revalidate' ),
			array( $this, 'webhook_secret_callback' ),
			'nextjs-revalidate-settings',
			'nextjs_revalidate_section'
		);
	}

	/**
	 * Callback for Next.js URL field.
	 *
	 * @return void
	 */
	public function nextjs_url_callback() {

		$options = get_option( $this->option_name );
		$value   = isset( $options['nextjs_url'] ) ? $options['nextjs_url'] : '';

		printf(
			'<input type="text" id="nextjs_url" name="%s[nextjs_url]" value="%s" class="regular-text">',
			esc_attr( $this->option_name ),
			esc_attr( $value )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Enter the full URL of your Next.js revalidate API endpoint without trailing slash.', 'nextjs-revalidate' )
		);
	}

	/**
	 * Callback for Webhook Secret field.
	 *
	 * @return void
	 */
	public function webhook_secret_callback() {

		$options = get_option( $this->option_name );
		$value   = isset( $options['webhook_secret'] ) ? $options['webhook_secret'] : '';

		printf(
			'<input type="text" id="webhook_secret" name="%s[webhook_secret]" value="%s" class="regular-text">',
			esc_attr( $this->option_name ),
			esc_attr( $value )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Enter the secret token used to secure the webhook.', 'nextjs-revalidate' )
		);
	}

	/**
	 * Callback for settings section.
	 *
	 * @return void
	 */
	public function settings_section_callback() {

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Ensure that the Next.js URL is correct and that your Next.js application is set up to handle revalidation requests.', 'nextjs-revalidate' )
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function admin_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'nextjs_revalidate_group' );
				do_settings_sections( 'nextjs-revalidate-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array $input The input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {

		$sanitized_input = array();

		if ( isset( $input['nextjs_url'] ) ) {
			$sanitized_input['nextjs_url'] = esc_url_raw( $input['nextjs_url'] );
		}

		if ( isset( $input['webhook_secret'] ) ) {
			$sanitized_input['webhook_secret'] = sanitize_text_field( $input['webhook_secret'] );
		}

		return $sanitized_input;
	}

	/**
	 * Handle post status changes to trigger revalidation.
	 *
	 * @param string  $new_status The new post status.
	 * @param string  $old_status The old post status.
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function on_post_status_change( $new_status, $old_status, $post ) {

		if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
			// Ignore revisions and autosaves.
			return;
		}

		if ( $new_status !== $old_status ) {
			$this->revalidate_nextjs( $post->post_type, $post->ID );
		}
	}

	/**
	 * Handle post save to trigger revalidation.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 * @return void
	 */
	public function on_save_post( $post_id, $post, $update ) {

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			// Ignore revisions and autosaves.
			return;
		}

		if ( $update ) {
			$this->revalidate_nextjs( $post->post_type, $post_id );
		}
	}

	/**
	 * Handle post trash to trigger revalidation.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function on_trash_post( $post_id ) {

		$post = get_post( $post_id );

		if ( $post ) {
			$this->revalidate_nextjs( $post->post_type, $post_id );
		}
	}

	/**
	 * Handle post untrash to trigger revalidation.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function on_untrash_post( $post_id ) {

		$post = get_post( $post_id );

		if ( $post ) {
			$this->revalidate_nextjs( $post->post_type, $post_id );
		}
	}

	/**
	 * Handle post delete to trigger revalidation.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function on_delete_post( $post_id ) {

		$post = get_post( $post_id );

		if ( $post ) {
			$this->revalidate_nextjs( $post->post_type, $post_id );
		}
	}

	/**
	 * Handle term changes to trigger revalidation.
	 *
	 * @param int    $term_id The term ID.
	 * @param int    $tt_id The term taxonomy ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return void
	 */
	public function on_term_change( $term_id, $tt_id, $taxonomy ) {

		// Get the term object.
		$term = get_term( $term_id, $taxonomy );

		if ( ! is_wp_error( $term ) && $term ) {
			// Trigger revalidation for the term.
			$this->revalidate_nextjs( $taxonomy, $term_id );
		}
	}

	/**
	 * Handle user changes to trigger revalidation.
	 *
	 * @param int|WP_User $user_id The user ID or WP_User object.
	 * @return void
	 */
	public function on_user_change( $user_id ) {

		$this->revalidate_nextjs( 'author', $user_id );
	}

	/**
	 * Handle media changes to trigger revalidation.
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return void
	 */
	public function on_media_change( $attachment_id ) {

		$this->revalidate_nextjs( 'attachment', $attachment_id );
	}

	/**
	 * Trigger revalidation in Next.js.
	 *
	 * @param string $content_type The type of content (e.g., post, term).
	 * @param int    $content_id The ID of the content to revalidate.
	 * @return void
	 */
	private function revalidate_nextjs( $content_type, $content_id ) {

		$options = get_option( $this->option_name );

		if ( empty( $options['nextjs_url'] ) || empty( $options['webhook_secret'] ) ) {
			return;
		}

		$url = rtrim( $options['nextjs_url'], '/' ) . '/api/revalidate';

		error_log( sprintf( 'Revalidating %s with ID %d', $content_type, $content_id ) );

		$payload = array(
			'contentType' => $content_type,
			'contentId'   => $content_id
		);

		// Send revalidation request to nextjs app.
		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.1',
			'headers'     => array(
				'Content-Type' => 'application/json',
				'x-webhook-secret' => $options['webhook_secret']
			),
			'body'         => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( sprintf( 'Revalidation failed for %s with ID %d: %s', $content_type, $content_id, $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		$success = ( $status_code >= 200 && $status_code < 300 );

		if ( $success ) {
			error_log( sprintf( 'Revalidation successful for %s with ID %d', $content_type, $content_id ) );
		} else {
			error_log( sprintf( 'Revalidation failed for %s with ID %d: HTTP status code %d', $content_type, $content_id, $status_code ) );
		}
	}
}
