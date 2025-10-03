// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_user_profile_edit' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    $( '#profile-page h1.wp-heading-inline' ).after( '<a href="' + ddtt_user_profile_edit.quick_link_url + '" target="_blank" class="page-title-action" style="margin: 0 10px;">' + ddtt_user_profile_edit.quick_link_icon + ' ' + ddtt_user_profile_edit.i18n.debug_user + '</a>' );

} );