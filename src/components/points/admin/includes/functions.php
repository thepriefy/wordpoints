<?php

/**
 * Admin-side functions of the points component.
 *
 * @package WordPoints\Points
 * @since 2.1.0
 */

/**
 * Register points component admin scripts.
 *
 * @since 2.1.0
 *
 * @WordPress\action init
 */
function wordpoints_points_admin_register_scripts() {

	$assets_url        = WORDPOINTS_URL . '/components/points/admin/assets';
	$suffix            = SCRIPT_DEBUG ? '' : '.min';
	$manifested_suffix = SCRIPT_DEBUG ? '.manifested' : '.min';

	// CSS

	wp_register_style(
		'wordpoints-admin-points-hooks'
		, "{$assets_url}/css/hooks{$suffix}.css"
		, array( 'dashicons' )
		, WORDPOINTS_VERSION
	);

	$styles = wp_styles();
	$styles->add_data( 'wordpoints-admin-points-hooks', 'rtl', 'replace' );

	if ( $suffix ) {
		$styles->add_data( 'wordpoints-admin-points-hooks', 'suffix', $suffix );
	}

	// JS

	wp_register_script(
		'wordpoints-admin-points-types'
		, "{$assets_url}/js/points-types{$suffix}.js"
		, array( 'backbone', 'jquery-ui-dialog', 'wp-util' )
		, WORDPOINTS_VERSION
	);

	wp_localize_script(
		'wordpoints-admin-points-types'
		, 'WordPointsPointsTypesL10n'
		, array(
			'confirmAboutTo' => esc_html__( 'You are about to delete the following points type:', 'wordpoints' ),
			'confirmDelete'  => esc_html__( 'Are you sure that you want to delete this points type? This will delete all logs, event reactions, and other data associated with this points type.', 'wordpoints' )
				. ' ' . esc_html__( 'Once a points type has been deleted, you cannot bring it back.', 'wordpoints' ),
			'confirmType'    => esc_html__( 'If you are sure you want to delete this points type, confirm by typing its name below:', 'wordpoints' ),
			'confirmLabel'   => esc_html_x( 'Name:', 'points type', 'wordpoints' ),
			'confirmTitle'   => esc_html__( 'Are you sure?', 'wordpoints' ),
			'deleteText'     => esc_html__( 'Delete', 'wordpoints' ),
			'cancelText'     => esc_html__( 'Cancel', 'wordpoints' ),
		)
	);

	wp_register_script(
		'wordpoints-hooks-reactor-points'
		, "{$assets_url}/js/hooks/reactors/points{$manifested_suffix}.js"
		, array( 'wordpoints-hooks-views' )
		, WORDPOINTS_VERSION
	);

	wp_register_script(
		'wordpoints-admin-points-hooks'
		, "{$assets_url}/js/hooks{$suffix}.js"
		, array( 'jquery', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-dialog' )
		, WORDPOINTS_VERSION
	);
}

/**
 * Add admin screens to the administration menu.
 *
 * @since 1.0.0
 *
 * @WordPress\action admin_menu
 * @WordPress\action network_admin_menu Only when the component is network-active.
 */
function wordpoints_points_admin_menu() {

	$wordpoints_menu = wordpoints_get_main_admin_menu();

	/** @var WordPoints_Admin_Screens $admin_screens */
	$admin_screens = wordpoints_apps()->get_sub_app( 'admin' )->get_sub_app(
		'screen'
	);

	// Hooks page.
	$id = add_submenu_page(
		$wordpoints_menu
		, __( 'WordPoints — Points Types', 'wordpoints' )
		, __( 'Points Types', 'wordpoints' )
		, 'manage_options'
		, 'wordpoints_points_types'
		, array( $admin_screens, 'display' )
	);

	if ( $id ) {
		$admin_screens->register( $id, 'WordPoints_Points_Admin_Screen_Points_Types' );
	}

	// Remove the old hooks screen if not needed.
	$disabled_hooks = wordpoints_get_maybe_network_array_option(
		'wordpoints_legacy_points_hooks_disabled'
		, is_network_admin()
	);

	$hooks = WordPoints_Points_Hooks::get_handlers();

	// If all of the registered hooks have been imported and disabled, then there is
	// no need to keep the old hooks screen.
	if ( array_diff_key( $hooks, $disabled_hooks ) ) {
		// Legacy hooks page.
		add_submenu_page(
			$wordpoints_menu
			, __( 'WordPoints — Points Hooks', 'wordpoints' )
			, __( 'Points Hooks', 'wordpoints' )
			, 'manage_options'
			, 'wordpoints_points_hooks'
			, 'wordpoints_points_admin_screen_hooks'
		);
	}

	// Logs page.
	add_submenu_page(
		$wordpoints_menu
		, __( 'WordPoints — Points Logs', 'wordpoints' )
		, __( 'Points Logs', 'wordpoints' )
		, 'manage_options'
		, 'wordpoints_points_logs'
		, 'wordpoints_points_admin_screen_logs'
	);
}

/**
 * Display the points hooks admin page.
 *
 * @since 1.0.0
 */
function wordpoints_points_admin_screen_hooks() {

	if ( isset( $_GET['edithook'] ) || isset( $_POST['savehook'] ) || isset( $_POST['removehook'] ) ) { // WPCS: CSRF OK.

		// - We're doing this without AJAX (JS).

		/**
		 * The non-JS version of the points hooks admin screen.
		 *
		 * @since 1.0.0
		 */
		require WORDPOINTS_DIR . 'components/points/admin/screens/hooks-no-js.php';

	} else {

		/**
		 * The points hooks admin screen.
		 *
		 * @since 1.0.0
		 */
		require WORDPOINTS_DIR . 'components/points/admin/screens/hooks.php';
	}
}

/**
 * Display the points logs admin page.
 *
 * @since 1.0.0
 */
function wordpoints_points_admin_screen_logs() {

	/**
	 * The points logs page template.
	 *
	 * @since 1.0.0
	 */
	require WORDPOINTS_DIR . 'components/points/admin/screens/logs.php';
}

/**
 * Add help tabs to the points hooks page.
 *
 * @since 1.0.0
 *
 * @WordPress\action load-wordpoints_page_wordpoints_points_hooks
 */
function wordpoints_admin_points_hooks_help() {

	/**
	 * Add help tabs and enqueue scripts and styles for the hooks screen.
	 *
	 * @since 1.2.0
	 */
	require WORDPOINTS_DIR . 'components/points/admin/screens/hooks-load.php';
}

/**
 * Save points hooks from the non-JS form.
 *
 * @since 1.0.0
 *
 * @WordPress\action load-wordpoints_page_wordpoints_points_hooks
 */
function wordpoints_no_js_points_hooks_save() {

	if ( ! isset( $_POST['savehook'] ) && ! isset( $_POST['removehook'] ) ) { // WPCS: CSRF OK.
		return;
	}

	/**
	 * Save the hooks for non-JS/accessibility mode hooks screen.
	 *
	 * @since 1.2.0
	 */
	require WORDPOINTS_DIR . 'components/points/admin/screens/hooks-no-js-load.php';
}

/**
 * Add accessibility mode screen option to the points hooks page.
 *
 * @since 1.0.0
 *
 * @WordPress\action screen_settings
 *
 * @param string    $screen_options The options for the screen.
 * @param WP_Screen $screen         The screen object.
 *
 * @return string Options for this screen.
 */
function wordpoints_admin_points_hooks_screen_options( $screen_options, $screen ) {

	$path = 'admin.php?page=wordpoints_points_hooks';

	switch ( $screen->id ) {

		case 'wordpoints_page_wordpoints_points_hooks':
			$url = admin_url( $path );
			// Fall through.

		case 'wordpoints_page_wordpoints_points_hooks-network':
			if ( ! isset( $url ) ) {
				$url = network_admin_url( $path );
			}

			$screen_options = '<p><a id="access-on" href="' . esc_url( wp_nonce_url( $url, 'wordpoints_points_hooks_accessiblity', 'wordpoints-accessiblity-nonce' ) ) . '&amp;accessibility-mode=on">'
				. esc_html__( 'Enable accessibility mode', 'wordpoints' )
				. '</a><a id="access-off" href="' . esc_url( wp_nonce_url( $url, 'wordpoints_points_hooks_accessiblity', 'wordpoints-accessiblity-nonce' ) ) . '&amp;accessibility-mode=off">'
				. esc_html__( 'Disable accessibility mode', 'wordpoints' ) . "</a></p>\n";
		break;
	}

	return $screen_options;
}

/**
 * Filter the class of the points hooks page for accessibility mode.
 *
 * @since 1.0.0
 *
 * @WordPress\filter admin_body_class Added when needed by wordpoints_admin_points_hooks_help()
 *
 * @param string $classes The body classes.
 *
 * @return string The classes, with 'wordpoints_hooks_access' added.
 */
function wordpoints_points_hooks_access_body_class( $classes ) {

	return "{$classes} wordpoints_hooks_access ";
}

/**
 * Display the hook description field in the hook forms.
 *
 * @since 1.4.0
 *
 * @WordPress\action wordpoints_in_points_hook_form
 *
 * @param bool                   $has_form Whether this instance displayed a form.
 * @param array                  $instance The settings for this hook instance.
 * @param WordPoints_Points_Hook $hook     The points hook object.
 */
function wordpoints_points_hook_description_form( $has_form, $instance, $hook ) {

	$description = ( isset( $instance['_description'] ) ) ? $instance['_description'] : '';

	?>

	<?php if ( $has_form ) : ?>
		<hr />
	<?php else : ?>
		<br />
	<?php endif; ?>

	<div class="hook-instance-description">
		<label for="<?php $hook->the_field_id( '_description' ); ?>"><?php echo esc_html_x( 'Description (optional):', 'points hook', 'wordpoints' ); ?></label>
		<input type="text" id="<?php $hook->the_field_id( '_description' ); ?>" name="<?php $hook->the_field_name( '_description' ); ?>" class="widefat" value="<?php echo esc_attr( $description ); ?>" />
		<p class="description">
			<?php

			echo esc_html(
				sprintf(
					// translators: Default points hook description.
					_x( 'Default: %s', 'points hook description', 'wordpoints' )
					, $hook->get_description( 'generated' )
				)
			);

			?>
		</p>
	</div>

	<br />

	<?php
}

/**
 * Display the user's points on their profile page.
 *
 * @since 1.0.0
 *
 * @WordPress\action personal_options 20 Late so stuff doesn't end up in the wrong section.
 *
 * @param WP_User $user The user object for the user being edited.
 */
function wordpoints_points_profile_options( $user ) {

	if ( current_user_can( 'set_wordpoints_points', $user->ID ) ) {

		?>

		</table>

		<h2><?php esc_html_e( 'WordPoints', 'wordpoints' ); ?></h2>
		<p><?php esc_html_e( "If you would like to change the value for a type of points, enter the desired value in the text field, and check the checkbox beside it. If you don't check the checkbox, the change will not be saved. To provide a reason for the change, fill out the text field below.", 'wordpoints' ); ?></p>
		<label><?php esc_html_e( 'Reason', 'wordpoints' ); ?> <input type="text" name="wordpoints_set_reason" /></label>
		<table class="form-table">

		<?php

		wp_nonce_field( 'wordpoints_points_set_profile', 'wordpoints_points_set_nonce' );

		foreach ( wordpoints_get_points_types() as $slug => $type ) {

			$points = wordpoints_get_points( $user->ID, $slug );

			?>

			<tr>
				<th scope="row"><?php echo esc_html( $type['name'] ); ?></th>
				<td>
					<input type="hidden" name="<?php echo esc_attr( "wordpoints_points_old-{$slug}" ); ?>" value="<?php echo esc_attr( $points ); ?>" />
					<input type="number" name="<?php echo esc_attr( "wordpoints_points-{$slug}" ); ?>" value="<?php echo esc_attr( $points ); ?>" autocomplete="off" />
					<input type="checkbox" value="1" name="<?php echo esc_attr( "wordpoints_points_set-{$slug}" ); ?>" />
					<span>
						<?php

						// translators: Number of points.
						echo esc_html( sprintf( __( '(current: %s)', 'wordpoints' ), $points ) );

						?>
					</span>
				</td>
			</tr>

			<?php
		}

	} elseif ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE ) {

		/**
		 * My points admin profile heading.
		 *
		 * The text displayed as the heading for the points section when the user is
		 * viewing their profile page.
		 *
		 * HTML will be escaped.
		 *
		 * @since 1.0.0
		 *
		 * @param string $heading The text for the heading.
		 */
		$heading = apply_filters( 'wordpoints_profile_points_heading', __( 'My Points', 'wordpoints' ) );

		?>

		</table>

		<h2><?php echo esc_html( $heading ); ?></h2>

		<table>
		<tbody>
		<?php foreach ( wordpoints_get_points_types() as $slug => $type ) : ?>
			<tr>
				<th scope="row" style="text-align: left;"><?php echo esc_html( $type['name'] ); ?></th>
				<td style="text-align: right;"><?php wordpoints_display_points( $user->ID, $slug, 'profile_page' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>

		<?php

	} // End if ( can set points ) elseif ( is my profile ).
}

/**
 * Save the user's points on profile edit.
 *
 * @since 1.0.0
 *
 * @WordPress\action personal_options_update  User editing own profile.
 * @WordPress\action edit_user_profile_update Other users editing profile.
 *
 * @param int $user_id The ID of the user being edited.
 *
 * @return void
 */
function wordpoints_points_profile_options_update( $user_id ) {

	if ( ! current_user_can( 'set_wordpoints_points', $user_id ) ) {
		return;
	}

	if (
		! isset( $_POST['wordpoints_points_set_nonce'], $_POST['wordpoints_set_reason'] )
		|| ! wordpoints_verify_nonce( 'wordpoints_points_set_nonce', 'wordpoints_points_set_profile', null, 'post' )
	) {
		return;
	}

	foreach ( wordpoints_get_points_types() as $slug => $type ) {

		if (
			isset(
				$_POST[ "wordpoints_points_set-{$slug}" ]
				, $_POST[ "wordpoints_points-{$slug}" ]
				, $_POST[ "wordpoints_points_old-{$slug}" ]
			)
			&& false !== wordpoints_int( $_POST[ "wordpoints_points-{$slug}" ] )
			&& false !== wordpoints_int( $_POST[ "wordpoints_points_old-{$slug}" ] )
		) {

			wordpoints_alter_points(
				$user_id
				, (int) $_POST[ "wordpoints_points-{$slug}" ] - (int) $_POST[ "wordpoints_points_old-{$slug}" ]
				, $slug
				, 'profile_edit'
				, array(
					'user_id' => get_current_user_id(),
					'reason'  => sanitize_text_field( wp_unslash( $_POST['wordpoints_set_reason'] ) ),
				)
			);
		}
	}
}

/**
 * Add settings to the top of the admin settings form.
 *
 * Currently only displays one setting: Default Points Type.
 *
 * @since 1.0.0
 *
 * @WordPress\action wordpoints_admin_settings_top
 */
function wordpoints_points_admin_settings() {

	$dropdown_args = array(
		'selected'         => wordpoints_get_default_points_type(),
		'id'               => 'default_points_type',
		'name'             => 'default_points_type',
		'show_option_none' => __( 'No default', 'wordpoints' ),
	);

	?>

	<h3><?php esc_html_e( 'Default Points Type', 'wordpoints' ); ?></h3>
	<p><?php esc_html_e( 'You can optionally set one points type to be the default. The default points type will, for example, be used by shortcodes when no type is specified. This is also useful if you only have one type of points.', 'wordpoints' ); ?></p>
	<table class="form-table">
		<tbody>
			<tr>
				<th>
					<label for="default_points_type"><?php esc_html_e( 'Default', 'wordpoints' ); ?></label>
				</th>
				<td>
					<?php wordpoints_points_types_dropdown( $dropdown_args ); ?>
					<?php wp_nonce_field( 'wordpoints_default_points_type', 'wordpoints_default_points_type_nonce' ); ?>
				</td>
			</tr>
		</tbody>
	</table>

	<?php
}

/**
 * Save settings on general settings panel.
 *
 * @since 1.0.0
 *
 * @WordPress\action wordpoints_admin_settings_update
 */
function wordpoints_points_admin_settings_save() {

	if (
		isset( $_POST['default_points_type'] )
		&& wordpoints_verify_nonce( 'wordpoints_default_points_type_nonce', 'wordpoints_default_points_type', null, 'post' )
	) {

		$points_type = sanitize_key( $_POST['default_points_type'] );

		if ( '-1' === $points_type ) {

			wordpoints_update_maybe_network_option( 'wordpoints_default_points_type', '' );

		} elseif ( wordpoints_is_points_type( $points_type ) ) {

			wordpoints_update_maybe_network_option( 'wordpoints_default_points_type', $points_type );
		}
	}
}

/**
 * Display notices to the user on the administration panels.
 *
 * @since 1.9.0
 *
 * @WordPress\action admin_notices
 */
function wordpoints_points_admin_notices() {

	if (
		( ! isset( $_GET['page'] ) || 'wordpoints_points_types' !== $_GET['page'] ) // WPCS: CSRF OK.
		&& current_user_can( 'manage_wordpoints_points_types' )
		&& ! wordpoints_get_points_types()
	) {

		wordpoints_show_admin_message(
			sprintf(
				// translators: URL of Points Types admin screen.
				__( 'Welcome to WordPoints! Get started by <a href="%s">creating a points type</a>.', 'wordpoints' )
				, esc_url( self_admin_url( 'admin.php?page=wordpoints_points_types' ) )
			)
			, 'info'
		);
	}
}

// EOF
