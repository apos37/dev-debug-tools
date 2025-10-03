(function( wp, ddtt_post_edit_box ) {
    const { createElement } = wp.element;
    const { registerPlugin } = wp.plugins;
    const { PluginPostStatusInfo } = wp.editor; // injects into status box
    const { useSelect } = wp.data;

    const DebugButton = () => {
        const postId = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostId(),
            []
        );

        if ( ! postId ) {
            return null;
        }

        const url = ddtt_post_edit_box.quick_link_url.replace( '%d', postId );

        return createElement(
            PluginPostStatusInfo,
            null,
            createElement(
                'button',
                {
                    type: 'button',
                    className: 'components-button editor-post-trash is-secondary is-destructive',
                    onClick: () => window.open( url, '_blank', 'noopener' ),
                    style: { marginTop: '8px', width: '100%' }
                },
                // Button content: [Icon] Debug Post Meta
                createElement('span', { dangerouslySetInnerHTML: { __html: ddtt_post_edit_box.quick_link_icon } }),
                ' ',
                ddtt_post_edit_box.i18n.debug_post_meta
            )
        );
    };

    registerPlugin('ddtt-gutenberg-debug-button', { render: DebugButton });

})( wp, ddtt_post_edit_box );
