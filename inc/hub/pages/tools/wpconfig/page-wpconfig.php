<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;

$this_filename = 'wp-config.php';
$this_abspath  = ABSPATH . $this_filename;
$this_shortname = 'wpconfig';
$this_tool_slug = 'wpconfig';
$this_title = 'WP-CONFIG';

include_once Bootstrap::path( 'inc/helpers/file-editor/page-file-editor.php' );