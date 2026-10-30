<?php
/**
 * Keep localhost login sessions separate from the production site's sessions.
 *
 * This checkout shares its user database with production. WordPress normally
 * stores every login for a user in the same `session_tokens` user meta record.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_HOME' ) || 'http://localhost:8061' !== WP_HOME ) {
	return;
}

class Journal_Local_Session_Tokens extends WP_User_Meta_Session_Tokens {
	private const META_KEY = '_journal_local_8061_session_tokens';

	protected function get_sessions() {
		$sessions = get_user_meta( $this->user_id, self::META_KEY, true );
		if ( ! is_array( $sessions ) ) {
			return array();
		}

		$sessions = array_map( array( $this, 'prepare_session' ), $sessions );
		return array_filter( $sessions, array( $this, 'is_still_valid' ) );
	}

	protected function update_sessions( $sessions ) {
		if ( $sessions ) {
			update_user_meta( $this->user_id, self::META_KEY, $sessions );
		} else {
			delete_user_meta( $this->user_id, self::META_KEY );
		}
	}

	public static function drop_sessions() {
		delete_metadata( 'user', 0, self::META_KEY, false, true );
	}
}

add_filter(
	'session_token_manager',
	static function () {
		return Journal_Local_Session_Tokens::class;
	}
);

/*
 * Production PHP uses bcrypt cost 10, while this local PHP defaults to 12.
 * With a shared user table, alternating logins otherwise rehash the same
 * password on every request and invalidate the other site's auth cookie.
 */
add_filter(
	'wp_hash_password_options',
	static function ( $options, $algorithm ) {
		if ( PASSWORD_BCRYPT === $algorithm ) {
			$options['cost'] = 10;
		}
		return $options;
	},
	10,
	2
);
