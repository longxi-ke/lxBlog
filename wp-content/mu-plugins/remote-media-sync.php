<?php
/**
 * Keep local editing while serving uploaded media from the live site.
 *
 * Both WordPress installations use the same database. A random database option
 * signs file transfers, so no server password or SSH key is stored in this repo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

final class Journal_Remote_Media_Sync {
	private const LIVE_ORIGIN = 'http://1.14.158.22:8061';
	private const SECRET_OPTION = 'journal_media_sync_secret_v1';
	private const ENABLED_OPTION = 'journal_media_sync_enabled_v1';
	private const MAX_FILE_BYTES = 67108864;
	private static $sent = array();

	public static function init() {
		if ( self::is_local() ) {
			if ( '1' === get_option( self::ENABLED_OPTION, '0' ) ) {
				add_filter( 'upload_dir', array( __CLASS__, 'use_live_media_url' ) );
				add_filter( 'wp_handle_upload', array( __CLASS__, 'sync_upload' ), 20, 2 );
				add_filter( 'wp_update_attachment_metadata', array( __CLASS__, 'sync_metadata_files' ), 20, 2 );
				add_action( 'admin_notices', array( __CLASS__, 'show_sync_error' ) );
			}
		} else {
			add_action( 'rest_api_init', array( __CLASS__, 'register_receiver' ) );
		}
	}

	private static function is_local() {
		return defined( 'WP_HOME' ) && 'http://localhost:8061' === WP_HOME;
	}

	public static function use_live_media_url( $uploads ) {
		$uploads['baseurl'] = self::LIVE_ORIGIN . '/wp-content/uploads';
		$uploads['url']     = $uploads['baseurl'] . $uploads['subdir'];
		return $uploads;
	}

	private static function secret( $create = false ) {
		$secret = get_option( self::SECRET_OPTION, '' );
		if ( ! $secret && $create ) {
			try {
				add_option( self::SECRET_OPTION, bin2hex( random_bytes( 32 ) ), '', false );
				$secret = get_option( self::SECRET_OPTION, '' );
			} catch ( Exception $e ) {
				return '';
			}
		}
		return is_string( $secret ) && preg_match( '/^[a-f0-9]{64}$/', $secret ) ? $secret : '';
	}

	private static function relative_path( $file ) {
		$uploads = wp_get_upload_dir();
		$root    = realpath( $uploads['basedir'] );
		$real    = realpath( $file );
		if ( false === $root || false === $real ) {
			return false;
		}
		$root = rtrim( wp_normalize_path( $root ), '/' ) . '/';
		$real = wp_normalize_path( $real );
		$starts_in_root = 'Windows' === PHP_OS_FAMILY
			? 0 === stripos( $real, $root )
			: 0 === strpos( $real, $root );
		return $starts_in_root ? substr( $real, strlen( $root ) ) : false;
	}

	/** Synchronize an existing local upload. Also used by the one-time backfill. */
	public static function sync_file( $file ) {
		if ( ! self::is_local() ) {
			return new WP_Error( 'wrong_site', 'Media sync must run on localhost.' );
		}
		$relative = self::relative_path( $file );
		if ( ! $relative || ! is_readable( $file ) ) {
			return new WP_Error( 'invalid_file', '文件不在本地上传目录中，或无法读取。' );
		}
		$size = filesize( $file );
		if ( false === $size || $size > self::MAX_FILE_BYTES ) {
			return new WP_Error( 'file_too_large', '图片超过同步接口的 64 MB 限制。' );
		}
		$body = file_get_contents( $file );
		if ( false === $body ) {
			return new WP_Error( 'read_failed', '无法读取本地图片。' );
		}
		$hash = hash( 'sha256', $body );
		$key  = $relative . ':' . $hash;
		if ( isset( self::$sent[ $key ] ) ) {
			return true;
		}
		$secret = self::secret( true );
		if ( ! $secret ) {
			return new WP_Error( 'no_secret', '无法创建媒体同步密钥。' );
		}
		$time      = (string) time();
		$signature = hash_hmac( 'sha256', $time . "\n" . $relative . "\n" . $hash, $secret );
		$response  = wp_remote_post(
			self::LIVE_ORIGIN . '/?rest_route=/journal-media/v1/file',
			array(
				'timeout'     => 90,
				'redirection' => 0,
				'headers'     => array(
					'Content-Type'        => 'application/octet-stream',
					'X-Journal-Path'     => base64_encode( $relative ),
					'X-Journal-Time'     => $time,
					'X-Journal-SHA256'   => $hash,
					'X-Journal-Signature'=> $signature,
				),
				'body'        => $body,
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'sync_request_failed', '连接线上媒体接口失败：' . $response->get_error_message() );
		}
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$code = wp_remote_retrieve_response_code( $response );
			return new WP_Error(
				'sync_rejected',
				409 === $code ? '线上已有不同内容的同名文件，请更换文件名后重试。' : '线上媒体接口返回 HTTP ' . $code . '。'
			);
		}
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $result ) || empty( $result['ok'] ) || ! hash_equals( $hash, (string) ( $result['sha256'] ?? '' ) ) ) {
			return new WP_Error( 'sync_bad_response', '线上媒体接口未确认文件内容。' );
		}
		self::$sent[ $key ] = true;
		return true;
	}

	public static function sync_upload( $upload, $context ) {
		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return $upload;
		}
		$result = self::sync_file( $upload['file'] );
		if ( is_wp_error( $result ) ) {
			$upload['error'] = '图片未同步到线上：' . $result->get_error_message();
		}
		return $upload;
	}

	public static function sync_metadata_files( $metadata, $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! is_array( $metadata ) ) {
			return $metadata;
		}
		$files = array( $file );
		if ( ! empty( $metadata['original_image'] ) ) {
			$files[] = dirname( $file ) . '/' . $metadata['original_image'];
		}
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size ) {
				if ( ! empty( $size['file'] ) ) {
					$files[] = dirname( $file ) . '/' . $size['file'];
				}
			}
		}
		foreach ( array_unique( $files ) as $path ) {
			if ( ! is_file( $path ) ) {
				continue;
			}
			$result = self::sync_file( $path );
			if ( is_wp_error( $result ) ) {
				set_transient( 'journal_media_sync_error', $result->get_error_message(), HOUR_IN_SECONDS );
				error_log( 'Journal media sync: ' . $result->get_error_message() );
				return $metadata;
			}
		}
		delete_transient( 'journal_media_sync_error' );
		return $metadata;
	}

	public static function show_sync_error() {
		$message = get_transient( 'journal_media_sync_error' );
		if ( $message && current_user_can( 'upload_files' ) ) {
			echo '<div class="notice notice-error"><p>媒体同步失败：' . esc_html( $message ) . '</p></div>';
		}
	}

	public static function register_receiver() {
		register_rest_route(
			'journal-media/v1',
			'/file',
			array(
				'methods'             => 'POST',
				'permission_callback'=> array( __CLASS__, 'authorize_receiver' ),
				'callback'            => array( __CLASS__, 'receive_file' ),
			)
		);
	}

	private static function request_path( $request ) {
		$encoded = $request->get_header( 'x-journal-path' );
		$path    = is_string( $encoded ) ? base64_decode( $encoded, true ) : false;
		if ( ! is_string( $path ) || ! preg_match( '~^20[0-9]{2}/(?:0[1-9]|1[0-2])/[^/\\\\\x00-\x1f]+$~u', $path ) ) {
			return false;
		}
		return $path;
	}

	public static function authorize_receiver( $request ) {
		$path      = self::request_path( $request );
		$time      = $request->get_header( 'x-journal-time' );
		$hash      = $request->get_header( 'x-journal-sha256' );
		$signature = $request->get_header( 'x-journal-signature' );
		$secret    = self::secret();
		if ( ! $path || ! $secret || ! ctype_digit( (string) $time ) || abs( time() - (int) $time ) > 600
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $hash )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $signature ) ) {
			return new WP_Error( 'sync_unauthorized', 'Unauthorized.', array( 'status' => 403 ) );
		}
		$body = $request->get_body();
		if ( strlen( $body ) > self::MAX_FILE_BYTES || ! hash_equals( $hash, hash( 'sha256', $body ) ) ) {
			return new WP_Error( 'sync_invalid_body', 'Invalid upload.', array( 'status' => 400 ) );
		}
		$expected = hash_hmac( 'sha256', $time . "\n" . $path . "\n" . $hash, $secret );
		if ( ! hash_equals( $expected, $signature ) ) {
			return new WP_Error( 'sync_unauthorized', 'Unauthorized.', array( 'status' => 403 ) );
		}
		$type = wp_check_filetype( basename( $path ), get_allowed_mime_types() );
		if ( empty( $type['type'] ) ) {
			return new WP_Error( 'sync_invalid_type', 'File type is not allowed.', array( 'status' => 415 ) );
		}
		return true;
	}

	public static function receive_file( $request ) {
		$path    = self::request_path( $request );
		$uploads = wp_get_upload_dir();
		$target  = trailingslashit( $uploads['basedir'] ) . $path;
		$dir     = dirname( $target );
		if ( ! wp_mkdir_p( $dir ) || ! is_writable( $dir ) ) {
			return new WP_Error( 'sync_directory_unwritable', 'Upload directory is not writable.', array( 'status' => 500 ) );
		}
		$body = $request->get_body();
		$hash = hash( 'sha256', $body );
		if ( is_file( $target ) ) {
			$existing_hash = hash_file( 'sha256', $target );
			if ( is_string( $existing_hash ) && hash_equals( $existing_hash, $hash ) ) {
				return new WP_REST_Response( array( 'ok' => true, 'sha256' => $hash ) );
			}
			return new WP_Error( 'sync_conflict', 'A different file already exists at this path.', array( 'status' => 409 ) );
		}
		$temp = tempnam( $dir, '.journal-sync-' );
		if ( false === $temp ) {
			return new WP_Error( 'sync_temp_failed', 'Could not create upload file.', array( 'status' => 500 ) );
		}
		$wrote = file_put_contents( $temp, $body, LOCK_EX );
		if ( false === $wrote || $wrote !== strlen( $body ) || ! rename( $temp, $target ) ) {
			@unlink( $temp );
			return new WP_Error( 'sync_write_failed', 'Could not save upload file.', array( 'status' => 500 ) );
		}
		@chmod( $target, 0644 );
		return new WP_REST_Response( array( 'ok' => true, 'sha256' => $hash ) );
	}
}

Journal_Remote_Media_Sync::init();
