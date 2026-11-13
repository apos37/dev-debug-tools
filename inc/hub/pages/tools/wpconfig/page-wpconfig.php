<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template included inside AdminMenu::render_page(); variables are local to that method.

$this_filename = 'wp-config.php';
$this_abspath  = ABSPATH . $this_filename;
$this_shortname = 'wpconfig';
$this_tool_slug = 'wpconfig';
$this_title = 'WP-CONFIG';

include_once Bootstrap::path( 'inc/helpers/file-editor/page-file-editor.php' );