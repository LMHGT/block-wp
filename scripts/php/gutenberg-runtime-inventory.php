<?php
/**
 * Read-only Gutenberg runtime inventory for the LMHG development site.
 *
 * This file is first mounted read-only and loaded through WP-CLI's `--require`
 * option, then executed through `wp eval-file -`. The preloader establishes a
 * database-session read-only guard before extension code loads. The collector
 * itself intentionally calls only WordPress read APIs and SELECT queries. It
 * never creates, updates, deletes, or saves a WordPress object.
 *
 * @package LMHGTooling
 */

if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'ABSPATH' ) ) {
	WP_CLI::add_wp_hook(
		'enable_loading_object_cache_dropin',
		static function ( bool $enabled ): bool {
			global $wpdb;

			$guard = array(
				'active'                        => false,
				'automaticCronDisabled'         => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
				'blockedOperationCounts'        => array(),
				'blockedTargetCounts'           => array(),
				'expectedBlockedOperationCounts' => array(),
				'expectedBlockedTargetCounts'    => array(),
				'objectCacheDropinSuppressed'   => true,
				'sessionDefaultReadOnly'        => false,
				'suppressedCallbacks'           => array(),
				'transactionReadOnly'           => false,
				'transactionRolledBack'         => false,
			);

			if ( ! isset( $wpdb->dbh ) || ! $wpdb->dbh instanceof mysqli ) {
				fwrite( STDERR, "Gutenberg inventory read-only database guard could not start.\n" );
				exit( 71 );
			}

			$session_read_only = mysqli_query( $wpdb->dbh, 'SET SESSION TRANSACTION READ ONLY' );
			$transaction       = mysqli_query( $wpdb->dbh, 'START TRANSACTION READ ONLY' );
			if ( true !== $session_read_only || true !== $transaction ) {
				fwrite( STDERR, "Gutenberg inventory read-only database guard could not start.\n" );
				exit( 71 );
			}

			$guard['active']                 = true;
			$guard['sessionDefaultReadOnly'] = true;
			$guard['transactionReadOnly']    = true;
			$GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] = $guard;
			$wpdb->suppress_errors( true );

			add_filter(
				'query',
				static function ( string $query ): string {
					global $wpdb;
					$normalized = preg_replace(
						'/\A(?:\s|\/\*.*?\*\/|--[^\r\n]*(?:\r?\n|$)|#[^\r\n]*(?:\r?\n|$))+/s',
						'',
						$query
					);
					$normalized = is_string( $normalized ) ? ltrim( $normalized ) : ltrim( $query );
					$operation  = null;

					if (
						preg_match(
							'/\A(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE|RENAME|GRANT|REVOKE|CALL|DO|LOAD|RESET|INSTALL|UNINSTALL|ANALYZE|OPTIMIZE|REPAIR|FLUSH|LOCK|KILL|PURGE|SHUTDOWN)\b/i',
							$normalized,
							$matches
						)
					) {
						$operation = strtoupper( $matches[1] );
					} elseif (
						preg_match( '/\AWITH\b[\s\S]*?\b(INSERT|UPDATE|DELETE|REPLACE)\b/i', $normalized, $matches )
					) {
						$operation = 'WITH-' . strtoupper( $matches[1] );
					} elseif (
						preg_match( '/\ASELECT\b[\s\S]*?\bINTO\s+(?:OUTFILE|DUMPFILE)\b/i', $normalized )
					) {
						$operation = 'SELECT-INTO-FILE';
					} elseif (
						preg_match( '/\ASET\s+GLOBAL\b/i', $normalized )
						|| preg_match( '/\ASET\s+(?:SESSION\s+)?TRANSACTION\b[\s\S]*?\bREAD\s+WRITE\b/i', $normalized )
						|| preg_match( '/\ASTART\s+TRANSACTION\b[\s\S]*?\bREAD\s+WRITE\b/i', $normalized )
					) {
						$operation = 'READ-ONLY-GUARD-ESCAPE';
					}

					if ( null === $operation ) {
						return $query;
					}

					$state     = $GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] ?? array();
					$target    = 'unclassified';
					$table_map = array(
						'options'            => $wpdb->options ?? '',
						'postmeta'           => $wpdb->postmeta ?? '',
						'posts'              => $wpdb->posts ?? '',
						'term_relationships' => $wpdb->term_relationships ?? '',
						'term_taxonomy'      => $wpdb->term_taxonomy ?? '',
						'termmeta'           => $wpdb->termmeta ?? '',
						'terms'              => $wpdb->terms ?? '',
						'usermeta'           => $wpdb->usermeta ?? '',
						'users'              => $wpdb->users ?? '',
					);
					foreach ( $table_map as $table_label => $table_name ) {
						if ( '' === $table_name ) {
							continue;
						}
						$table_pattern = preg_quote( (string) $table_name, '/' );
						if (
							preg_match(
								'/\A(?:UPDATE\s+|INSERT\s+INTO\s+|DELETE\s+FROM\s+)`?'
									. $table_pattern . '`?\b/i',
								$normalized
							)
						) {
							$target = 'table:' . $table_label;
							break;
						}
					}
					if ( 'table:options' === $target ) {
						$option_name = null;
						if (
							preg_match(
								'/\bWHERE\s+`?option_name`?\s*=\s*[\'\"]([a-z0-9_-]{1,120})[\'\"]/i',
								$normalized,
								$target_matches
							)
						) {
							$option_name = strtolower( $target_matches[1] );
						} elseif (
							'INSERT' === $operation
							&& preg_match(
								'/\(\s*`?option_name`?\s*,[\s\S]*?\)\s*VALUES\s*\(\s*[\'\"]([a-z0-9_-]{1,120})[\'\"]/i',
								$normalized,
								$target_matches
							)
						) {
							$option_name = strtolower( $target_matches[1] );
						}
						if ( null !== $option_name ) {
							$target = 'option:' . $option_name;
						}
					}
					$expected_cache_write = in_array( $operation, array( 'DELETE', 'INSERT' ), true )
						&& preg_match(
							'/^option:_site_transient_(?:timeout_)?wp_theme_files_patterns-[a-f0-9]{32}$/',
							$target
						);
					$operation_bucket = $expected_cache_write
						? 'expectedBlockedOperationCounts'
						: 'blockedOperationCounts';
					$target_bucket = $expected_cache_write
						? 'expectedBlockedTargetCounts'
						: 'blockedTargetCounts';
					$state[ $operation_bucket ][ $operation ] =
						( $state[ $operation_bucket ][ $operation ] ?? 0 ) + 1;
					$state[ $target_bucket ][ $target ] =
						( $state[ $target_bucket ][ $target ] ?? 0 ) + 1;
					$GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] = $state;

					return 'SELECT 1';
				},
				PHP_INT_MIN
			);

			return false;
		},
		PHP_INT_MIN
	);
	/**
	 * Suppress the CTA seed only when its exact prerequisites prove it would be
	 * a no-op. If either system term or its single lifecycle/system metadata row
	 * is absent or ambiguous, leave the callback active so the database guard
	 * blocks and reports the attempted repair.
	 */
	WP_CLI::add_wp_hook(
		'plugins_loaded',
		static function (): void {
			add_action(
				'init',
				static function (): void {
					$callback = 'lmhg_site_core_seed_cta_terms';
					$priority = has_action( 'init', $callback );
					if ( 26 !== $priority || ! taxonomy_exists( 'lmhg_cta_variant' ) ) {
						return;
					}

					foreach ( array( 'default-cta', 'no-lower-cta' ) as $slug ) {
						$term = get_term_by( 'slug', $slug, 'lmhg_cta_variant' );
						if ( ! $term instanceof WP_Term ) {
							return;
						}
						$lifecycle = get_term_meta( $term->term_id, '_lmhg_cta_lifecycle', false );
						$system    = get_term_meta( $term->term_id, '_lmhg_cta_system', false );
						if (
							1 !== count( $lifecycle )
							|| '' === (string) $lifecycle[0]
							|| array( '1' ) !== array_map( 'strval', $system )
						) {
							return;
						}
					}

					if ( ! remove_action( 'init', $callback, $priority ) ) {
						return;
					}
					$state = $GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] ?? array();
					$state['suppressedCallbacks'][] = 'init:' . $callback . '@' . (string) $priority;
					$GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] = $state;
				},
				25
			);
		},
		PHP_INT_MIN
	);
} else {
	if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
		fwrite( STDERR, "This inventory must run through WP-CLI.\n" );
		exit( 70 );
	}

	define( 'LMHG_GUTENBERG_INVENTORY_SENTINEL', 'LMHG_GUTENBERG_RUNTIME_INVENTORY_JSON:' );

class LMHG_Gutenberg_Inventory_Database_Read_Error extends RuntimeException {
}

/** Runs one database-backed read and fails without retaining its error text. */
function lmhg_gutenberg_inventory_database_read( callable $reader ): mixed {
	global $wpdb;

	if ( ! $wpdb instanceof wpdb ) {
		throw new LMHG_Gutenberg_Inventory_Database_Read_Error( 'database-handle-unavailable' );
	}

	$wpdb->flush();
	$result = $reader();
	if ( '' !== (string) $wpdb->last_error ) {
		throw new LMHG_Gutenberg_Inventory_Database_Read_Error( 'database-read-failed' );
	}

	return $result;
}

/** Runs one read that must produce an array. */
function lmhg_gutenberg_inventory_database_array( callable $reader ): array {
	$result = lmhg_gutenberg_inventory_database_read( $reader );
	if ( ! is_array( $result ) ) {
		throw new LMHG_Gutenberg_Inventory_Database_Read_Error( 'database-read-shape-invalid' );
	}

	return $result;
}

/** Returns safe active-plugin filenames plus a malformed-entry count. */
function lmhg_gutenberg_inventory_active_plugins(): array {
	$entries = lmhg_gutenberg_inventory_database_read(
		static fn() => get_option( 'active_plugins', array() )
	);
	if ( ! is_array( $entries ) ) {
		throw new LMHG_Gutenberg_Inventory_Database_Read_Error( 'active-plugin-option-invalid' );
	}

	$plugin_files       = array();
	$unreportable_count = 0;
	foreach ( $entries as $entry ) {
		$entry = str_replace( '\\', '/', trim( (string) $entry ) );
		if ( ! preg_match( '/\A[a-z0-9][a-z0-9._-]*(?:\/[a-z0-9][a-z0-9._-]*)*\.php\z/i', $entry ) ) {
			$unreportable_count++;
			continue;
		}
		$plugin_files[] = $entry;
	}
	$plugin_files = array_values( array_unique( $plugin_files ) );
	sort( $plugin_files, SORT_STRING );

	return array(
		'files'             => $plugin_files,
		'unreportableCount' => $unreportable_count,
	);
}

/** Returns the stable base report shape. */
function lmhg_gutenberg_inventory_report(): array {
	return array(
		'schemaVersion'       => 1,
		'sentinel'            => 'lmhg-gutenberg-runtime-inventory-v1',
		'wordpress'           => array(
			'version'                       => '',
			'home'                          => '',
			'siteUrl'                       => '',
			'activeStylesheet'              => '',
			'activeTemplate'                => '',
			'isBlockTheme'                  => false,
			'activePluginFiles'             => array(),
			'unreportableActivePluginCount' => 0,
		),
		'readOnlyGuard'       => array(),
		'policy'              => array(),
		'registeredPostTypes' => array(),
		'contentInventory'    => array(),
		'siteEditorInventory' => array(),
		'dormantPostTypes'    => array(),
		'blockers'            => array(),
		'integrityFindings'   => array(),
		'risks'               => array(),
	);
}

/** Populates runtime identity through guarded reads. */
function lmhg_gutenberg_inventory_populate_wordpress( array &$report ): void {
	$active_plugins    = lmhg_gutenberg_inventory_active_plugins();
	$home              = lmhg_gutenberg_inventory_database_read( static fn() => get_option( 'home' ) );
	$site_url          = lmhg_gutenberg_inventory_database_read( static fn() => get_option( 'siteurl' ) );
	$active_stylesheet = lmhg_gutenberg_inventory_database_read( static fn() => get_stylesheet() );
	$active_template   = lmhg_gutenberg_inventory_database_read( static fn() => get_template() );
	$is_block_theme    = lmhg_gutenberg_inventory_database_read( static fn() => wp_is_block_theme() );

	$report['wordpress'] = array(
		'version'                       => (string) get_bloginfo( 'version' ),
		'home'                          => (string) $home,
		'siteUrl'                       => (string) $site_url,
		'activeStylesheet'              => (string) $active_stylesheet,
		'activeTemplate'                => (string) $active_template,
		'isBlockTheme'                  => (bool) $is_block_theme,
		'activePluginFiles'             => $active_plugins['files'],
		'unreportableActivePluginCount' => $active_plugins['unreportableCount'],
	);
}

/** Records guard evidence and rolls back its read-only transaction. */
function lmhg_gutenberg_inventory_finalize_read_only_guard( array &$report ): void {
	global $wpdb;

	$guard = $GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] ?? null;
	if ( ! is_array( $guard ) || true !== ( $guard['active'] ?? false ) ) {
		$report['readOnlyGuard'] = array(
			'active'                      => false,
			'automaticCronDisabled'       => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'blockedOperationCount'       => 0,
			'blockedOperationCounts'      => array(),
			'blockedTargetCounts'         => array(),
			'expectedBlockedOperationCount'  => 0,
			'expectedBlockedOperationCounts' => array(),
			'expectedBlockedTargetCounts'    => array(),
			'objectCacheDropinSuppressed' => false,
			'sessionDefaultReadOnly'      => false,
			'suppressedCallbacks'         => array(),
			'transactionReadOnly'         => false,
			'transactionRolledBack'       => false,
		);
		lmhg_gutenberg_inventory_add_diagnostic(
			$report,
			'blockers',
			array( 'code' => 'read-only-database-guard-not-active' )
		);
		return;
	}

	$operation_counts = is_array( $guard['blockedOperationCounts'] ?? null )
		? $guard['blockedOperationCounts']
		: array();
	ksort( $operation_counts, SORT_STRING );
	$suppressed_callbacks = array_values(
		array_unique( array_map( 'strval', (array) ( $guard['suppressedCallbacks'] ?? array() ) ) )
	);
	sort( $suppressed_callbacks, SORT_STRING );
	$operations = array();
	$total      = 0;
	foreach ( $operation_counts as $operation => $count ) {
		$count = max( 0, (int) $count );
		$total += $count;
		$operations[] = array(
			'operation' => (string) $operation,
			'count'     => $count,
		);
		lmhg_gutenberg_inventory_add_diagnostic(
			$report,
			'blockers',
			array(
				'code'      => 'runtime-write-attempt-blocked',
				'count'     => $count,
				'operation' => (string) $operation,
			)
		);
	}
	$target_counts = is_array( $guard['blockedTargetCounts'] ?? null )
		? $guard['blockedTargetCounts']
		: array();
	ksort( $target_counts, SORT_STRING );
	$targets = array();
	foreach ( $target_counts as $target => $count ) {
		$targets[] = array(
			'target' => preg_match( '/^(?:option:[a-z0-9_-]{1,120}|table:[a-z_]{1,40}|unclassified)$/', (string) $target )
				? (string) $target
				: 'unclassified',
			'count'  => max( 0, (int) $count ),
		);
	}
	$expected_operation_counts = is_array( $guard['expectedBlockedOperationCounts'] ?? null )
		? $guard['expectedBlockedOperationCounts']
		: array();
	ksort( $expected_operation_counts, SORT_STRING );
	$expected_operations = array();
	$expected_total      = 0;
	foreach ( $expected_operation_counts as $operation => $count ) {
		$count = max( 0, (int) $count );
		$expected_total += $count;
		$expected_operations[] = array(
			'operation' => (string) $operation,
			'count'     => $count,
		);
	}
	$expected_target_counts = is_array( $guard['expectedBlockedTargetCounts'] ?? null )
		? $guard['expectedBlockedTargetCounts']
		: array();
	ksort( $expected_target_counts, SORT_STRING );
	$expected_targets = array();
	foreach ( $expected_target_counts as $target => $count ) {
		$expected_targets[] = array(
			'target' => preg_match( '/^option:_site_transient_(?:timeout_)?wp_theme_files_patterns-[a-f0-9]{32}$/', (string) $target )
				? (string) $target
				: 'unclassified',
			'count'  => max( 0, (int) $count ),
		);
	}

	$rolled_back = isset( $wpdb->dbh )
		&& $wpdb->dbh instanceof mysqli
		&& mysqli_rollback( $wpdb->dbh );
	if ( ! $rolled_back ) {
		lmhg_gutenberg_inventory_add_diagnostic(
			$report,
			'blockers',
			array( 'code' => 'read-only-database-guard-rollback-failed' )
		);
	}

	$report['readOnlyGuard'] = array(
		'active'                      => true,
		'automaticCronDisabled'       => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		'blockedOperationCount'       => $total,
		'blockedOperationCounts'      => $operations,
		'blockedTargetCounts'         => $targets,
		'expectedBlockedOperationCount'  => $expected_total,
		'expectedBlockedOperationCounts' => $expected_operations,
		'expectedBlockedTargetCounts'    => $expected_targets,
		'objectCacheDropinSuppressed' => true === ( $guard['objectCacheDropinSuppressed'] ?? false ),
		'sessionDefaultReadOnly'      => true === ( $guard['sessionDefaultReadOnly'] ?? false ),
		'suppressedCallbacks'         => $suppressed_callbacks,
		'transactionReadOnly'         => true === ( $guard['transactionReadOnly'] ?? false ),
		'transactionRolledBack'       => $rolled_back,
	);
}

/** Fails the command if a late shutdown callback attempts a guarded write. */
function lmhg_gutenberg_inventory_enforce_shutdown_guard(): void {
	$guard    = $GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] ?? array();
	$counts   = is_array( $guard['blockedOperationCounts'] ?? null )
		? $guard['blockedOperationCounts']
		: array();
	$expected_counts = is_array( $guard['expectedBlockedOperationCounts'] ?? null )
		? $guard['expectedBlockedOperationCounts']
		: array();
	$baseline = array_sum( array_map( 'intval', $counts ) )
		+ array_sum( array_map( 'intval', $expected_counts ) );

	register_shutdown_function(
		static function () use ( $baseline ): void {
			$latest_guard  = $GLOBALS['lmhg_gutenberg_inventory_read_only_guard'] ?? array();
			$latest_counts = is_array( $latest_guard['blockedOperationCounts'] ?? null )
				? $latest_guard['blockedOperationCounts']
				: array();
			$latest_expected_counts = is_array( $latest_guard['expectedBlockedOperationCounts'] ?? null )
				? $latest_guard['expectedBlockedOperationCounts']
				: array();
			if (
				array_sum( array_map( 'intval', $latest_counts ) )
					+ array_sum( array_map( 'intval', $latest_expected_counts ) ) > $baseline
			) {
				$operations = array();
				foreach ( $latest_counts as $operation => $count ) {
					if ( preg_match( '/^[A-Z-]{1,40}$/', (string) $operation ) ) {
						$operations[] = (string) $operation . '=' . max( 0, (int) $count );
					}
				}
				sort( $operations, SORT_STRING );
				$targets = array();
				foreach ( (array) ( $latest_guard['blockedTargetCounts'] ?? array() ) as $target => $count ) {
					if ( preg_match( '/^(?:option:[a-z0-9_-]{1,120}|table:[a-z_]{1,40}|unclassified)$/', (string) $target ) ) {
						$targets[] = (string) $target . '=' . max( 0, (int) $count );
					}
				}
				sort( $targets, SORT_STRING );
				fwrite(
					STDERR,
					'Gutenberg inventory blocked a late database write'
						. ( $operations ? ': ' . implode( ',', $operations ) : '' )
						. ( $targets ? ' [' . implode( ',', $targets ) . ']' : '' )
						. ".\n"
				);
				exit( 72 );
			}
		}
	);
}

/** Adds one uniquely keyed diagnostic and keeps diagnostics deterministic. */
function lmhg_gutenberg_inventory_add_diagnostic( array &$report, string $bucket, array $diagnostic ): void {
	if ( ! isset( $diagnostic['code'] ) || ! is_string( $diagnostic['code'] ) ) {
		return;
	}

	$key = wp_json_encode( $diagnostic );
	foreach ( $report[ $bucket ] as $existing ) {
		if ( wp_json_encode( $existing ) === $key ) {
			return;
		}
	}
	$report[ $bucket ][] = $diagnostic;
}

/** Stable comparison for records that expose a post type and optional ID. */
function lmhg_gutenberg_inventory_compare_records( array $left, array $right ): int {
	$left_type  = (string) ( $left['postType'] ?? $left['type'] ?? '' );
	$right_type = (string) ( $right['postType'] ?? $right['type'] ?? '' );
	$type_order = strcmp( $left_type, $right_type );
	if ( 0 !== $type_order ) {
		return $type_order;
	}

	$left_id  = $left['id'] ?? '';
	$right_id = $right['id'] ?? '';
	if ( is_int( $left_id ) && is_int( $right_id ) ) {
		return $left_id <=> $right_id;
	}

	return strcmp( (string) $left_id, (string) $right_id );
}

/** Sorts all report collections before encoding. */
function lmhg_gutenberg_inventory_sort_report( array &$report ): void {
	usort( $report['registeredPostTypes'], 'lmhg_gutenberg_inventory_compare_records' );
	usort( $report['dormantPostTypes'], 'lmhg_gutenberg_inventory_compare_records' );
	foreach ( array( 'blockers', 'integrityFindings', 'risks' ) as $bucket ) {
		usort(
			$report[ $bucket ],
			static function ( array $left, array $right ): int {
				return strcmp( wp_json_encode( $left ), wp_json_encode( $right ) );
			}
		);
	}
}

/** Emits exactly one machine-readable terminal line. */
function lmhg_gutenberg_inventory_emit( array $report ): void {
	lmhg_gutenberg_inventory_sort_report( $report );
	echo LMHG_GUTENBERG_INVENTORY_SENTINEL . wp_json_encode( $report, JSON_UNESCAPED_SLASHES ) . "\n";
}

/** Returns the reviewed post-type classifications for this project. */
function lmhg_gutenberg_inventory_policy(): array {
	return array(
		'post-editor'             => array( 'page', 'post', 'lmhg_faq', 'lmhg_review' ),
		'specialized-site-editor' => array( 'wp_block', 'wp_navigation' ),
		'merged-site-editor'      => array( 'wp_template', 'wp_template_part' ),
		'explicit-exclusion'      => array(
			'attachment'       => 'Media Library records are not Gutenberg block documents.',
			'lmhg_team_member' => 'Team records are metadata-managed and do not support the editor.',
			'nav_menu_item'    => 'Classic menu internals have no document editor even when a plugin adds editor support.',
			'wp_font_face'     => 'Font Library records use a structured non-block editor.',
			'wp_font_family'   => 'Font Library records use a structured non-block editor.',
			'wp_global_styles' => 'Global Styles stores JSON and is not a Gutenberg block document.',
		),
	);
}

/** Flattens the reviewed policy to post type => classification. */
function lmhg_gutenberg_inventory_classification_map( array $policy ): array {
	$map = array();
	foreach ( array( 'post-editor', 'specialized-site-editor', 'merged-site-editor' ) as $classification ) {
		foreach ( $policy[ $classification ] as $post_type ) {
			$map[ $post_type ] = $classification;
		}
	}
	foreach ( array_keys( $policy['explicit-exclusion'] ) as $post_type ) {
		$map[ $post_type ] = 'explicit-exclusion';
	}
	ksort( $map, SORT_STRING );
	return $map;
}

/** Builds the public, content-free description of one registered post type. */
function lmhg_gutenberg_inventory_post_type_summary( WP_Post_Type $object, string $classification ): array {
	$supports = array_keys( get_all_post_type_supports( $object->name ) );
	sort( $supports, SORT_STRING );

	return array(
		'postType'       => (string) $object->name,
		'classification' => $classification,
		'showInRest'     => (bool) $object->show_in_rest,
		'showUi'         => (bool) $object->show_ui,
		'supports'       => $supports,
		'supportsEditor' => post_type_supports( $object->name, 'editor' ),
		'useBlockEditor' => use_block_editor_for_post_type( $object->name ),
		'restNamespace'  => $object->rest_namespace ? (string) $object->rest_namespace : 'wp/v2',
		'restBase'       => $object->rest_base ? (string) $object->rest_base : (string) $object->name,
	);
}

/** Inventories registered types and fails closed on unreviewed Gutenberg types. */
function lmhg_gutenberg_inventory_registered_types( array &$report, array $policy ): array {
	$classification_map = lmhg_gutenberg_inventory_classification_map( $policy );
	$registered         = get_post_types( array(), 'objects' );
	$registered_names   = array_keys( $registered );
	sort( $registered_names, SORT_STRING );

	foreach ( $registered_names as $post_type ) {
		$object         = $registered[ $post_type ];
		$classification = $classification_map[ $post_type ] ?? 'not-gutenberg-capable';
		$summary        = lmhg_gutenberg_inventory_post_type_summary( $object, $classification );

		if (
			'not-gutenberg-capable' === $classification
			&& $summary['showInRest']
			&& $summary['supportsEditor']
			&& $summary['useBlockEditor']
		) {
			$summary['classification'] = 'unclassified-gutenberg-capable';
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'blockers',
				array(
					'code'     => 'unclassified-gutenberg-post-type',
					'postType' => $post_type,
				)
			);
		}

		$report['registeredPostTypes'][] = $summary;
	}

	foreach ( $classification_map as $post_type => $classification ) {
		if ( 'explicit-exclusion' === $classification ) {
			continue;
		}
		if ( ! isset( $registered[ $post_type ] ) ) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'blockers',
				array(
					'code'           => 'required-post-type-not-registered',
					'classification' => $classification,
					'postType'       => $post_type,
				)
			);
			continue;
		}

		$summary = lmhg_gutenberg_inventory_post_type_summary( $registered[ $post_type ], $classification );
		$is_capable = $summary['showInRest'] && $summary['supportsEditor'];
		if ( 'post-editor' === $classification ) {
			$is_capable = $is_capable && $summary['showUi'] && $summary['useBlockEditor'];
		}
		if ( ! $is_capable ) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'blockers',
				array(
					'code'           => 'required-post-type-not-gutenberg-capable',
					'classification' => $classification,
					'postType'       => $post_type,
				)
			);
		}
	}

	return $registered;
}

/** Returns registered, user-facing statuses that represent durable content. */
function lmhg_gutenberg_inventory_durable_statuses(): array {
	$statuses = array_values( get_post_stati( array( 'internal' => false ), 'names' ) );
	sort( $statuses, SORT_STRING );
	return $statuses;
}

/** Builds a prepared IN-list placeholder string. */
function lmhg_gutenberg_inventory_placeholders( int $count, string $placeholder = '%s' ): string {
	return implode( ',', array_fill( 0, $count, $placeholder ) );
}

/** Reduces template origin metadata to the finite values WordPress owns. */
function lmhg_gutenberg_inventory_safe_origin( $origin ): ?string {
	$origin = is_string( $origin ) ? $origin : '';
	if ( '' === $origin ) {
		return null;
	}
	return in_array( $origin, array( 'custom', 'plugin', 'theme' ), true ) ? $origin : 'other';
}

/** Returns exact ID/status rows without invoking content query filters. */
function lmhg_gutenberg_inventory_content_rows( array $post_types, array $statuses ): array {
	global $wpdb;

	if ( empty( $post_types ) || empty( $statuses ) ) {
		return array();
	}

	$sql = 'SELECT ID, post_type, post_status FROM ' . $wpdb->posts
		. ' WHERE post_type IN (' . lmhg_gutenberg_inventory_placeholders( count( $post_types ) ) . ')'
		. ' AND post_status IN (' . lmhg_gutenberg_inventory_placeholders( count( $statuses ) ) . ')'
		. ' ORDER BY post_type ASC, ID ASC';
	$sql = $wpdb->prepare( $sql, array_merge( $post_types, $statuses ) );

	return lmhg_gutenberg_inventory_database_array(
		static fn() => $wpdb->get_results( $sql, ARRAY_A )
	);
}

/** Inventories all durable records assigned to reviewed block-editor types. */
function lmhg_gutenberg_inventory_content( array &$report, array $policy, array $registered ): void {
	$post_types = array_merge( $policy['post-editor'], $policy['specialized-site-editor'] );
	sort( $post_types, SORT_STRING );
	$statuses = lmhg_gutenberg_inventory_durable_statuses();
	$rows     = lmhg_gutenberg_inventory_content_rows( $post_types, $statuses );

	$by_type = array();
	foreach ( $post_types as $post_type ) {
		$classification = in_array( $post_type, $policy['post-editor'], true )
			? 'post-editor'
			: 'specialized-site-editor';
		$object = $registered[ $post_type ] ?? null;
		$by_type[ $post_type ] = array(
			'postType'             => $post_type,
			'classification'       => $classification,
			'restNamespace'        => $object && $object->rest_namespace ? (string) $object->rest_namespace : 'wp/v2',
			'restBase'             => $object && $object->rest_base ? (string) $object->rest_base : $post_type,
			'count'                => 0,
			'statusCounts'         => array(),
			'records'              => array(),
			'excludedStatusCounts' => array(),
		);
	}

	foreach ( $rows as $row ) {
		$post_type = (string) $row['post_type'];
		$status    = (string) $row['post_status'];
		$id        = (int) $row['ID'];
		if ( ! isset( $by_type[ $post_type ] ) || $id <= 0 ) {
			continue;
		}

		$by_type[ $post_type ]['records'][] = array(
			'id'     => $id,
			'status' => $status,
		);
		$by_type[ $post_type ]['count']++;
		$by_type[ $post_type ]['statusCounts'][ $status ] =
			( $by_type[ $post_type ]['statusCounts'][ $status ] ?? 0 ) + 1;

		$post = lmhg_gutenberg_inventory_database_read(
			static fn() => get_post( $id )
		);
		$is_post_editor_type = in_array( $post_type, $policy['post-editor'], true );
		if (
			! $post instanceof WP_Post
			|| $post->post_type !== $post_type
			|| ( $is_post_editor_type && ! use_block_editor_for_post( $post ) )
		) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'blockers',
				array(
					'code'     => 'durable-record-not-block-editor-capable',
					'id'       => $id,
					'postType' => $post_type,
					'status'   => $status,
				)
			);
		}
	}

	global $wpdb;
	$sql = 'SELECT post_type, post_status, COUNT(*) AS row_count FROM ' . $wpdb->posts
		. ' WHERE post_type IN (' . lmhg_gutenberg_inventory_placeholders( count( $post_types ) ) . ')'
		. ' GROUP BY post_type, post_status ORDER BY post_type ASC, post_status ASC';
	$sql = $wpdb->prepare( $sql, $post_types );
	$status_rows = lmhg_gutenberg_inventory_database_array(
		static fn() => $wpdb->get_results( $sql, ARRAY_A )
	);
	foreach ( $status_rows as $row ) {
		$post_type = (string) $row['post_type'];
		$status    = (string) $row['post_status'];
		if ( isset( $by_type[ $post_type ] ) && ! in_array( $status, $statuses, true ) ) {
			$by_type[ $post_type ]['excludedStatusCounts'][ $status ] = (int) $row['row_count'];
		}
	}

	$total = 0;
	foreach ( $by_type as &$type_inventory ) {
		ksort( $type_inventory['statusCounts'], SORT_STRING );
		ksort( $type_inventory['excludedStatusCounts'], SORT_STRING );
		$type_inventory['statusCounts']         = (object) $type_inventory['statusCounts'];
		$type_inventory['excludedStatusCounts'] = (object) $type_inventory['excludedStatusCounts'];
		$total += $type_inventory['count'];
	}
	unset( $type_inventory );

	$report['contentInventory'] = array(
		'durableStatuses' => $statuses,
		'total'           => $total,
		'types'           => array_values( $by_type ),
	);
}

/** Reports database rows whose post type is no longer registered. */
function lmhg_gutenberg_inventory_dormant_types( array &$report, array $registered ): void {
	global $wpdb;

	$sql  = 'SELECT post_type, post_status, COUNT(*) AS row_count FROM ' . $wpdb->posts
		. ' GROUP BY post_type, post_status ORDER BY post_type ASC, post_status ASC';
	$rows = lmhg_gutenberg_inventory_database_array(
		static fn() => $wpdb->get_results( $sql, ARRAY_A )
	);
	$by_type = array();
	foreach ( $rows as $row ) {
		$post_type = (string) $row['post_type'];
		if ( isset( $registered[ $post_type ] ) ) {
			continue;
		}
		$status = (string) $row['post_status'];
		if ( ! isset( $by_type[ $post_type ] ) ) {
			$by_type[ $post_type ] = array(
				'postType'     => $post_type,
				'total'        => 0,
				'statusCounts' => array(),
			);
		}
		$count = (int) $row['row_count'];
		$by_type[ $post_type ]['total'] += $count;
		$by_type[ $post_type ]['statusCounts'][ $status ] = $count;
	}

	foreach ( $by_type as $post_type => &$inventory ) {
		ksort( $inventory['statusCounts'], SORT_STRING );
		$inventory['statusCounts'] = (object) $inventory['statusCounts'];
		lmhg_gutenberg_inventory_add_diagnostic(
			$report,
			'risks',
			array(
				'code'     => 'dormant-unregistered-post-type',
				'postType' => $post_type,
				'total'    => $inventory['total'],
			)
		);
	}
	unset( $inventory );
	$report['dormantPostTypes'] = array_values( $by_type );
}

/** Converts one merged template object to a content-free evidence record. */
function lmhg_gutenberg_inventory_merged_template_record( WP_Block_Template $template ): array {
	$record = array(
		'id'           => (string) $template->id,
		'postType'     => (string) $template->type,
		'theme'        => (string) $template->theme,
		'slug'         => (string) $template->slug,
		'status'       => (string) $template->status,
		'source'       => (string) $template->source,
		'origin'       => lmhg_gutenberg_inventory_safe_origin( $template->origin ?? null ),
		'hasThemeFile' => (bool) $template->has_theme_file,
		'wpId'         => (int) $template->wp_id,
	);

	if ( 'wp_template' === $template->type ) {
		$record['isCustom'] = (bool) $template->is_custom;
	} else {
		$record['area'] = isset( $template->area ) && null !== $template->area ? (string) $template->area : null;
	}

	return $record;
}

/** Returns sorted taxonomy slugs without exposing term descriptions. */
function lmhg_gutenberg_inventory_term_slugs( int $post_id, string $taxonomy, array &$report ): array {
	$terms = lmhg_gutenberg_inventory_database_read(
		static fn() => wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) )
	);
	if ( is_wp_error( $terms ) ) {
		lmhg_gutenberg_inventory_add_diagnostic(
			$report,
			'integrityFindings',
			array(
				'code'     => 'template-taxonomy-read-error',
				'id'       => $post_id,
				'taxonomy' => $taxonomy,
			)
		);
		return array();
	}

	$slugs = array_values( array_filter( array_map( 'strval', (array) $terms ) ) );
	sort( $slugs, SORT_STRING );
	return $slugs;
}

/** Inventories merged Site Editor entities and every raw database row. */
function lmhg_gutenberg_inventory_site_editor( array &$report ): void {
	global $wpdb;

	$template_types  = array( 'wp_template', 'wp_template_part' );
	$merged_types    = array();
	$merged_by_wp_id = array();
	$merged_total    = 0;

	foreach ( $template_types as $template_type ) {
		$entities = array();
		$seen_ids = array();
		$templates = lmhg_gutenberg_inventory_database_array(
			static fn() => get_block_templates( array(), $template_type )
		);
		foreach ( $templates as $template ) {
			if ( ! $template instanceof WP_Block_Template ) {
				continue;
			}
			$record = lmhg_gutenberg_inventory_merged_template_record( $template );
			if ( isset( $seen_ids[ $record['id'] ] ) ) {
				lmhg_gutenberg_inventory_add_diagnostic(
					$report,
					'integrityFindings',
					array(
						'code'     => 'duplicate-merged-template-id',
						'id'       => $record['id'],
						'postType' => $template_type,
					)
				);
			}
			$seen_ids[ $record['id'] ] = true;
			if ( $record['wpId'] > 0 ) {
				$merged_by_wp_id[ $record['wpId'] ] = $record['id'];
			}
			$entities[] = $record;
		}

		usort( $entities, 'lmhg_gutenberg_inventory_compare_records' );
		$object = get_post_type_object( $template_type );
		$merged_types[] = array(
			'postType'      => $template_type,
			'restNamespace' => $object && $object->rest_namespace ? (string) $object->rest_namespace : 'wp/v2',
			'restBase'      => $object && $object->rest_base ? (string) $object->rest_base : $template_type,
			'count'         => count( $entities ),
			'entities'      => $entities,
		);
		$merged_total += count( $entities );
	}

	$sql = 'SELECT ID, post_type, post_name, post_status FROM ' . $wpdb->posts
		. " WHERE post_type IN ('wp_template','wp_template_part')"
		. ' ORDER BY post_type ASC, ID ASC';
	$rows                     = lmhg_gutenberg_inventory_database_array(
		static fn() => $wpdb->get_results( $sql, ARRAY_A )
	);
	$raw_rows                 = array();
	$effective_override_count = 0;
	$active_stylesheet        = (string) lmhg_gutenberg_inventory_database_read(
		static fn() => get_stylesheet()
	);

	foreach ( $rows as $row ) {
		$id          = (int) $row['ID'];
		$post_type   = (string) $row['post_type'];
		$status      = (string) $row['post_status'];
		$theme_terms = lmhg_gutenberg_inventory_term_slugs( $id, 'wp_theme', $report );
		$area_terms  = lmhg_gutenberg_inventory_term_slugs( $id, 'wp_template_part_area', $report );
		$merged_id    = $merged_by_wp_id[ $id ] ?? null;
		$is_effective = null !== $merged_id && 'publish' === $status && in_array( $active_stylesheet, $theme_terms, true );
		if ( $is_effective ) {
			$effective_override_count++;
		}

		$has_origin = lmhg_gutenberg_inventory_database_read(
			static fn() => metadata_exists( 'post', $id, 'origin' )
		);
		$origin = $has_origin
			? lmhg_gutenberg_inventory_database_read(
				static fn() => get_post_meta( $id, 'origin', true )
			)
			: null;

		$raw_rows[] = array(
			'id'         => $id,
			'postType'   => $post_type,
			'slug'       => (string) $row['post_name'],
			'status'     => $status,
			'themeTerms' => $theme_terms,
			'areaTerms'  => $area_terms,
			'origin'     => lmhg_gutenberg_inventory_safe_origin( $origin ),
			'mergedId'   => $merged_id,
			'effective'  => $is_effective,
		);

		if ( empty( $theme_terms ) ) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'integrityFindings',
				array(
					'code'     => 'template-row-missing-theme-relationship',
					'id'       => $id,
					'postType' => $post_type,
					'status'   => $status,
				)
			);
		} elseif ( count( $theme_terms ) > 1 ) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'integrityFindings',
				array(
					'code'       => 'template-row-has-multiple-theme-relationships',
					'id'         => $id,
					'postType'   => $post_type,
					'themeTerms' => $theme_terms,
				)
			);
		}

		if ( 'wp_template_part' === $post_type && empty( $area_terms ) ) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'integrityFindings',
				array(
					'code'     => 'template-part-row-missing-area-relationship',
					'id'       => $id,
					'postType' => $post_type,
					'status'   => $status,
				)
			);
		} elseif ( 'wp_template_part' === $post_type && count( $area_terms ) > 1 ) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'integrityFindings',
				array(
					'areaTerms' => $area_terms,
					'code'      => 'template-part-row-has-multiple-area-relationships',
					'id'        => $id,
					'postType'  => $post_type,
				)
			);
		}

		if ( 'publish' === $status && in_array( $active_stylesheet, $theme_terms, true ) && null === $merged_id ) {
			lmhg_gutenberg_inventory_add_diagnostic(
				$report,
				'integrityFindings',
				array(
					'code'     => 'current-theme-template-row-not-merged',
					'id'       => $id,
					'postType' => $post_type,
				)
			);
		}
	}

	$report['siteEditorInventory'] = array(
		'activeStylesheet'               => $active_stylesheet,
		'mergedTotal'                    => $merged_total,
		'mergedTypes'                    => $merged_types,
		'rawDatabaseRowCount'            => count( $raw_rows ),
		'effectiveDatabaseOverrideCount' => $effective_override_count,
		'rawDatabaseRows'               => $raw_rows,
	);
}

$lmhg_gutenberg_inventory_report = lmhg_gutenberg_inventory_report();

try {
	lmhg_gutenberg_inventory_populate_wordpress( $lmhg_gutenberg_inventory_report );
	$lmhg_gutenberg_inventory_policy = lmhg_gutenberg_inventory_policy();
	$post_editor_types = $lmhg_gutenberg_inventory_policy['post-editor'];
	$specialized_types = $lmhg_gutenberg_inventory_policy['specialized-site-editor'];
	$merged_types      = $lmhg_gutenberg_inventory_policy['merged-site-editor'];
	sort( $post_editor_types, SORT_STRING );
	sort( $specialized_types, SORT_STRING );
	sort( $merged_types, SORT_STRING );

	$exclusions = array();
	foreach ( $lmhg_gutenberg_inventory_policy['explicit-exclusion'] as $post_type => $reason ) {
		$exclusions[] = array(
			'postType' => $post_type,
			'reason'   => $reason,
		);
	}

	$lmhg_gutenberg_inventory_report['policy'] = array(
		'postEditorTypes'             => $post_editor_types,
		'specializedSiteEditorTypes' => $specialized_types,
		'mergedSiteEditorTypes'      => $merged_types,
		'explicitSpecializedExclusions' => $exclusions,
		'durableStatuses'            => lmhg_gutenberg_inventory_durable_statuses(),
	);

	$lmhg_gutenberg_inventory_registered = lmhg_gutenberg_inventory_registered_types(
		$lmhg_gutenberg_inventory_report,
		$lmhg_gutenberg_inventory_policy
	);
	lmhg_gutenberg_inventory_content(
		$lmhg_gutenberg_inventory_report,
		$lmhg_gutenberg_inventory_policy,
		$lmhg_gutenberg_inventory_registered
	);
	lmhg_gutenberg_inventory_site_editor( $lmhg_gutenberg_inventory_report );
	lmhg_gutenberg_inventory_dormant_types(
		$lmhg_gutenberg_inventory_report,
		$lmhg_gutenberg_inventory_registered
	);
} catch ( Throwable $error ) {
	lmhg_gutenberg_inventory_add_diagnostic(
		$lmhg_gutenberg_inventory_report,
		'blockers',
		array(
			'code'           => $error instanceof LMHG_Gutenberg_Inventory_Database_Read_Error
				? 'inventory-database-read-failed'
				: 'inventory-collector-exception',
			'exceptionClass' => get_class( $error ),
		)
	);
}

lmhg_gutenberg_inventory_enforce_shutdown_guard();
lmhg_gutenberg_inventory_finalize_read_only_guard( $lmhg_gutenberg_inventory_report );
lmhg_gutenberg_inventory_emit( $lmhg_gutenberg_inventory_report );
}
