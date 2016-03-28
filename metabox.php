<?php

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function event_add_meta_box() {

	$screens = array( 'event');

	foreach ( $screens as $screen ) {

		add_meta_box(
			'event_sectionid',
			__( 'Event Details', 'event_textdomain' ),
			'event_meta_box_callback',
			$screen
		);
	}
}
add_action( 'add_meta_boxes', 'event_add_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function event_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'event_meta_box', 'event_meta_box_nonce' );

	$start = get_post_meta($post->ID, 'start', true);
	$end = get_post_meta($post->ID, 'end', true);
	$duration = get_post_meta($post->ID, 'duration', true);
	$location = get_post_meta($post->ID, 'location', true);
	$organizer = get_post_meta($post->ID, 'organizer', true);
	$start_iso = get_post_meta($post->ID, 'start_iso', true);

	?>

	<p>
		<b>Início:</b> <?php echo format_ical_date($start); ?><br>
		<b>Fim:</b> <?php echo format_ical_date($end); ?><br>
		<?php if($duration): ?>
			<b>Duração:</b> <?php echo $duration; ?><br>
		<?php endif; ?>
		<b>Local:</b> <?php echo $location; ?><br>
		<?php if($organizer): ?>
			<b>Organizador:</b> <?php echo $organizer['params']['CN']; ?>
		<?php endif; ?>
	</p>

	<?php

}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function event_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['event_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['event_meta_box_nonce'], 'event_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

	// Make sure that it is set.
	if ( ! isset( $_POST['event_new_field'] ) ) {
		return;
	}

	// Sanitize user input.
	$my_data = sanitize_text_field( $_POST['event_new_field'] );

	// Update the meta field in the database.
	update_post_meta( $post_id, '_my_meta_value_key', $my_data );
}
add_action( 'save_post', 'event_save_meta_box_data' );
