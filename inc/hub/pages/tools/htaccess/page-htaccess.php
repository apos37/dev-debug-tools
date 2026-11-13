<?php
namespace Apos37\DevDebugTools;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template included inside AdminMenu::render_page(); variables are local to that method.

$this_filename = '.htaccess';
$this_abspath  = ABSPATH . $this_filename;
$this_shortname = 'htaccess';
$this_tool_slug = 'htaccess';
$this_title = 'HTACCESS';

include_once Bootstrap::path( 'inc/helpers/file-editor/page-file-editor.php' );