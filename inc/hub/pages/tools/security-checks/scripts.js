// Helper logs
DevDebugTools.Helpers.log_file_path();
DevDebugTools.Helpers.log_localization( 'ddtt_security_checks' );

// Now start jQuery
jQuery( document ).ready( function( $ ) {

    const i18n = ddtt_security_checks.i18n;
    const scoring = ddtt_security_checks.score;
    const issueStatuses = [ 'fail', 'warning', 'unknown', 'info' ];
    let stopRunning = false;


    /**
     * Fill in a translated string's placeholders in order
     */
    const format = ( text, ...values ) => {
        let index = 0;
        return text.replace( /%(\d+\$)?d/g, function( match, position ) {
            const valueIndex = position ? parseInt( position, 10 ) - 1 : index++;
            return values[ valueIndex ];
        } );
    };


    /**
     * Calculate the security score from the rows that have a pass, warning or fail result
     */
    const calculateScore = () => {
        let earned = 0;
        let possible = 0;
        let counted = 0;
        let cap = 100;

        $( '.ddtt-security-row' ).each( function() {
            const status = $( this ).attr( 'data-status' );
            const severity = $( this ).find( '.ddtt-security-severity' ).attr( 'data-severity' );
            const weight = scoring.weights[ severity ] || 0;

            if ( ! weight || ! ( status in scoring.credit ) ) {
                return;
            }

            earned += weight * scoring.credit[ status ];
            possible += weight;
            counted++;

            if ( status === 'fail' && severity in scoring.caps ) {
                cap = Math.min( cap, scoring.caps[ severity ] );
            }
        } );

        if ( ! possible ) {
            return null;
        }

        const value = Math.min( cap, Math.floor( earned / possible * 100 ) );
        const band = value >= scoring.bands.good ? 'good' : ( value >= scoring.bands.fair ? 'fair' : 'poor' );

        return { value: value, band: band, counted: counted };
    };


    /**
     * Update the summary above the tables
     */
    const updateSummary = () => {
        const counts = {};
        const rows = $( '.ddtt-security-row' );

        rows.each( function() {
            const status = $( this ).attr( 'data-status' );
            counts[ status ] = ( counts[ status ] || 0 ) + 1;
        } );

        let headline = i18n.allPassed;
        let state = 'pass';

        if ( ( counts.none || 0 ) === rows.length ) {
            headline = i18n.notRunYet;
            state = 'none';
        } else if ( counts.fail ) {
            headline = format( i18n.issues, counts.fail );
            state = 'fail';
        } else if ( counts.warning ) {
            headline = format( i18n.warningsOnly, counts.warning );
            state = 'warning';
        } else if ( counts.unknown ) {
            headline = format( i18n.unknownOnly, counts.unknown );
            state = 'unknown';
        }

        const pills = Object.keys( i18n.statuses ).filter( ( status ) => counts[ status ] ).map( ( status ) => {
            return `<span class="ddtt-security-status" data-status="${status}">${i18n.statuses[ status ]}: ${counts[ status ]}</span>`;
        } ).join( ' ' );

        const score = calculateScore();
        const scoreText = score ? `${score.value}%` : '\u2014';
        let basis = '';

        if ( score ) {
            basis = format( i18n.scoreBasis, score.counted, rows.length );
            if ( score.counted < rows.length ) {
                basis += ' ' + i18n.scoreSkipped;
            }
        }

        $( '#ddtt-security-summary' ).attr( 'data-state', state ).html( `<div class="ddtt-security-score" data-band="${score ? score.band : 'none'}"><span class="ddtt-security-score-number"></span><span class="ddtt-security-score-label"></span></div><div class="ddtt-security-summary-text"><p class="ddtt-security-headline"></p><p class="ddtt-security-basis description"></p><div class="ddtt-security-counts">${pills}</div></div>` );
        $( '#ddtt-security-summary .ddtt-security-score-number' ).text( scoreText );
        $( '#ddtt-security-summary .ddtt-security-score-label' ).text( i18n.scoreLabel );
        $( '#ddtt-security-summary .ddtt-security-headline' ).text( headline );
        $( '#ddtt-security-summary .ddtt-security-basis' ).text( basis ).prop( 'hidden', ! basis );
    };


    /**
     * Show only the rows that match the filter
     */
    const applyFilter = () => {
        const filter = $( '#ddtt-security-filter' ).val();

        $( '.ddtt-security-row' ).each( function() {
            const row = $( this );
            const status = row.attr( 'data-status' );
            const visible = filter === 'all' || ( filter === 'issues' && issueStatuses.includes( status ) ) || filter === status;
            const details = row.next( '.ddtt-security-details' );

            row.toggle( visible );
            details.prop( 'hidden', ! visible || row.find( '.ddtt-security-toggle' ).attr( 'aria-expanded' ) !== 'true' );
        } );

        $( '.ddtt-security-category' ).each( function() {
            $( this ).toggle( $( this ).find( '.ddtt-security-row' ).filter( function() {
                return $( this ).css( 'display' ) !== 'none';
            } ).length > 0 );
        } );
    };


    /**
     * Run one check and replace its rows
     */
    const runCheck = async ( key ) => {
        const row = $( `.ddtt-security-row[data-check="${key}"]` );
        const wasOpen = row.find( '.ddtt-security-toggle' ).attr( 'aria-expanded' ) === 'true';

        row.find( '.ddtt-security-status' ).attr( 'data-status', 'running' ).html( `<em class="ddtt-loading-msg">${i18n.running}</em>` );
        row.find( '.ddtt-security-run' ).prop( 'disabled', true );

        let response = null;
        try {
            response = await $.post( ajaxurl, {
                action: 'ddtt_security_run_check',
                nonce: ddtt_security_checks.nonce,
                check: key
            } );
        } catch ( error ) {
            response = null;
        }

        if ( ! response || ! response.success ) {
            row.find( '.ddtt-security-status' ).attr( 'data-status', 'unknown' ).text( i18n.error );
            row.find( '.ddtt-security-run' ).prop( 'disabled', false );
            $( '#ddtt-security-progress' ).text( i18n.errorMsg );
            return false;
        }

        const newRows = $( response.data.html );
        row.next( '.ddtt-security-details' ).remove();
        row.replaceWith( newRows );

        if ( wasOpen ) {
            newRows.find( '.ddtt-security-toggle' ).attr( 'aria-expanded', 'true' );
        }

        $( '#ddtt-security-clear' ).prop( 'disabled', false );
        updateSummary();
        applyFilter();
        return true;
    };


    /**
     * Run every check, one request at a time
     */
    const runAll = async () => {
        const keys = $( '.ddtt-security-row' ).map( function() {
            return $( this ).attr( 'data-check' );
        } ).get();

        stopRunning = false;
        $( '#ddtt-security-run-all, #ddtt-security-clear' ).prop( 'disabled', true );
        $( '#ddtt-security-stop' ).prop( 'hidden', false );

        for ( let index = 0; index < keys.length; index++ ) {
            if ( stopRunning ) {
                break;
            }

            $( '#ddtt-security-progress' ).text( format( i18n.runningOf, index + 1, keys.length ) );

            const success = await runCheck( keys[ index ] );
            if ( ! success ) {
                break;
            }

            if ( index === keys.length - 1 ) {
                $( '#ddtt-security-progress' ).text( '' );
            }
        }

        $( '#ddtt-security-run-all, #ddtt-security-clear' ).prop( 'disabled', false );
        $( '#ddtt-security-stop' ).prop( 'hidden', true );
    };


    $( '#ddtt-security-run-all' ).on( 'click', function( e ) {
        e.preventDefault();
        runAll();
    } );

    $( '#ddtt-security-stop' ).on( 'click', function( e ) {
        e.preventDefault();
        stopRunning = true;
        $( this ).prop( 'hidden', true );
        $( '#ddtt-security-progress' ).text( '' );
    } );

    $( document ).on( 'click', '.ddtt-security-run', function( e ) {
        e.preventDefault();
        runCheck( $( this ).attr( 'data-check' ) );
    } );

    $( document ).on( 'click', '.ddtt-security-toggle', function( e ) {
        e.preventDefault();
        const expanded = $( this ).attr( 'aria-expanded' ) === 'true';
        $( this ).attr( 'aria-expanded', expanded ? 'false' : 'true' );
        $( this ).closest( '.ddtt-security-row' ).next( '.ddtt-security-details' ).prop( 'hidden', expanded );
    } );

    $( '#ddtt-security-filter' ).on( 'change', applyFilter );

    $( '#ddtt-security-clear' ).on( 'click', function( e ) {
        e.preventDefault();
        if ( ! window.confirm( i18n.clearConfirm ) ) {
            return;
        }

        $.post( ajaxurl, {
            action: 'ddtt_security_clear_results',
            nonce: ddtt_security_checks.nonce
        }, function( response ) {
            if ( response && response.success ) {
                window.location.reload();
            }
        } );
    } );

    updateSummary();


    /**
     * Run every check when opened from the dashboard's Run Security Checks button
     */
    const params = new URLSearchParams( window.location.search );
    if ( params.get( 'run' ) === '1' ) {
        params.delete( 'run' );
        window.history.replaceState( null, '', window.location.pathname + '?' + params.toString() );

        if ( $( '#ddtt-security-run-all' ).length && ! $( '#ddtt-security-run-all' ).prop( 'disabled' ) ) {
            runAll();
        }
    }

} );
