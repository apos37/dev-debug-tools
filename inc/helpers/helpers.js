(function( window, document ) {

    window.DevDebugTools = window.DevDebugTools || {};

    const INFO_COLOR    = '#007cba';
    const SUCCESS_COLOR = '#28a745';
    const WARNING_COLOR = '#ffc107';
    const DANGER_COLOR  = '#dc3545';

    window.DevDebugTools.Helpers = class {

        // Internal log store
        static _logStore = [];

        /**
         * Store or log a message with a specific style.
         * @param {string} message 
         * @param {string} style 
         * @param {*} data 
         */
        static _store_or_log( message, style, data = null ) {
            // Always store
            this._logStore.push( { message, style, data } );

            // Log immediately if test mode is active
            if ( window.ddtt_helpers && window.ddtt_helpers.test_mode ) {
                if ( data ) {
                    console.log( `%c${message}`, style, data );
                } else {
                    console.log( `%c${message}`, style );
                }
            }
        }

        /**
         * Logs the current file path.
         *
         * @param {string} label Optional label for the log message.
         */
        static log_file_path( label = 'DDT Loaded' ) {
            let current = document.currentScript;
            if ( current ) {
                let src = current.src || '[inline script]';

                // Remove plugin root URL if defined
                if ( ddtt_helpers.plugin_root && src.startsWith( ddtt_helpers.plugin_root ) ) {
                    src = src.replace( ddtt_helpers.plugin_root, '' );
                }

                // Extract last folder name
                let pathWithoutQuery = src.split('?')[0];
                let pathParts = pathWithoutQuery.split('/');
                let lastFolder = pathParts.length > 1 ? pathParts[pathParts.length - 2] : '';
                if ( lastFolder ) {
                    lastFolder = lastFolder
                        .split('-')
                        .map( word => word.charAt(0).toUpperCase() + word.slice(1) )
                        .join('');
                    src += ` (${lastFolder})`;
                }

                const style = `color: #fff; background: ${INFO_COLOR}; font-weight: bold; padding: 2px 4px; border-radius: 3px;`;
                this._store_or_log( label ? `${label}: ${src}` : src, style );
            }
        }

        /**
         * Logs a localization object if it is undefined or missing.
         *
         * @param {any} locObject The localization object to check (optional).
         * @param {string} label Name or description of the object for logging.
         */
        static log_localization( label ) {
            let obj = window[label];

            const style = obj === undefined
                ? `color: #fff; background: ${DANGER_COLOR}; font-weight: bold; padding: 2px 4px; border-radius: 3px;`
                : `color: #fff; background: ${SUCCESS_COLOR}; font-weight: bold; padding: 2px 4px; border-radius: 3px;`;

            if ( obj === undefined ) {
                this._store_or_log( `Localization is not defined for "${label}"`, style );
            } else {
                this._store_or_log( `Localization is found for "${label}":`, style, obj );
            }
        }


        /**
         * Show all stored logs.
         */
        static show_logs() {
            this._logStore.forEach( entry => {
                if ( entry.data ) {
                    console.log( `%c${entry.message}`, entry.style, entry.data );
                } else {
                    console.log( `%c${entry.message}`, entry.style );
                }
            });
        }


        /**
         * Log any message with styled output.
         * @param {string} message Message to log
         * @param {string} label Optional label
         * @param {string} type Optional type: 'info'|'success'|'warning'|'danger'
         */
        static log_message( message, label = '', type = 'info' ) {
            let bgColor;
            switch ( type.toLowerCase() ) {
                case 'success': bgColor = SUCCESS_COLOR; break;
                case 'warning': bgColor = WARNING_COLOR; break;
                case 'danger':  bgColor = DANGER_COLOR;  break;
                case 'info':
                default:        bgColor = INFO_COLOR;     break;
            }

            const style = `color: #fff; background: ${bgColor}; font-weight: bold; padding: 2px 4px; border-radius: 3px;`;
            console.log( label ? `%c${label}: ${message}` : `%c${message}`, style );
        }


        /**
         * Reloads the current page.
         */
        static reload_page() {
            const currentUrl = window.location.href;
            window.location.href = currentUrl;
            window.onload = () => window.scrollTo( 0, 0 );
        }

    };

})( window, document );