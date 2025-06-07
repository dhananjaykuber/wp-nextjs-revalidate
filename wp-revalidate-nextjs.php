<?php
/**
 * Plugin Name:       NextJS Revalidate
 * Description:       A simple plugin to revalidate Next.js pages from WordPress.
 * Version:           0.1.0
 * Author:            Dhananjay Kuber
 *
 * @package           nextjs-revalidate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEXTJS_REVALIDATE_VERSION', '0.1.0' );
define( 'NEXTJS_REVALIDATE_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

require_once NEXTJS_REVALIDATE_PLUGIN_DIR . '/classes/class-revalidate.php';

new \NextJSRevalidate\Classes\Revalidate();
