<?php
/**
 * Child theme bootstrap. Wires the includes/ modules.
 *
 * Data, hooks, and shortcodes live in the riches-core plugin; this theme only renders.
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

defined( 'ABSPATH' ) || exit;

include_once __DIR__ . '/includes/header-functions.php';
include_once __DIR__ . '/includes/nav-functions.php';
include_once __DIR__ . '/includes/footer-functions.php';
include_once __DIR__ . '/includes/config.php';
include_once __DIR__ . '/includes/shortcodes.php';
include_once __DIR__ . '/includes/aggregator.php';
