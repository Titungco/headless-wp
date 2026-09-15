<?php
/**
 * Plugin Name: GraphQL Hardening
 * Description: Query depth/complexity limits and introspection lockdown for the public GraphQL API (ADR-0004, grill Q16/Q17). Config as code, not wp-admin toggles, so it survives a redeploy regardless of DB state.
 */

// Force WPGraphQL's General Settings (stored in the `graphql_general_settings` option)
// regardless of what's saved in the database.
add_filter( 'graphql_get_setting_section_fields', function ( $fields, $section_name ) {
	if ( 'graphql_general_settings' !== $section_name ) {
		return $fields;
	}

	$fields['query_depth_enabled'] = 'on';
	$fields['query_depth_max'] = 15;

	// Public introspection defaults to off for unauthenticated requests already;
	// force it explicitly so a wp-admin toggle can't silently re-enable it.
	$fields['public_introspection_enabled'] = 'off';

	return $fields;
}, 10, 2 );

// Cap the max nodes any single connection field can return — WPGraphQL's stand-in
// for query complexity limiting (there's no separate complexity setting upstream).
add_filter( 'graphql_connection_max_query_amount', function () {
	return 50;
} );
