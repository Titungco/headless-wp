<?php
/**
 * Plugin Name: Security Hardening
 * Description: XML-RPC disabled, REST API user-enumeration closed off (grill Q19). nginx also 404s xmlrpc.php directly, for defense in depth before PHP even runs.
 */

// Disable XML-RPC entirely — brute-force/pingback-amplification vector with no
// legitimate use in this Auth0-gated, headless setup.
add_filter( 'xmlrpc_enabled', '__return_false' );

add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );

// Remove the user-listing REST routes for unauthenticated requests. Everything
// else on the default REST API stays (some plugin internals depend on it).
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'] );
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );

// Block the ?author=N -> /author-slug/ redirect, which otherwise lets someone
// enumerate usernames by incrementing an ID.
add_filter( 'redirect_canonical', function ( $redirect, $requested_url ) {
	if ( preg_match( '/\?author=([0-9]*)(\/*)/i', $requested_url ) ) {
		return false;
	}
	return $redirect;
}, 10, 2 );
