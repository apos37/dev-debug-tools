<style>
.full_width_container {
    overflow-x: auto;
}

.table-container {
    overflow-x: auto;
    white-space: nowrap;
}

.db-table th {
    min-width: 100px;
}

.db-table th.id { min-width: 50px; }
.db-table th.post_content { min-width: 500px; }

.full-value {
    display: none;
}

.view-more {
    display: block;
    margin-top: 1rem;
    width: fit-content;
}

#records-per-page-form {
    float: left;
    margin-top: 10px;
}

#records-per-page {
    margin-left: 10px;
    text-align: center;
    width: 70px;
    padding: 0 !important;
    height: 30px !important;
    min-height: 30px !important;
}

.pagination {
    text-align: center;
    margin-top: 1rem;
}

.page-num, .page-info {
    display: inline-block;
    margin: 0 20px;
}

.learn-more {
    display: inline-block;
    font-family: sans-serif;
    font-weight: bold;
    text-align: center;
    width: 2ex;
    height: 2ex;
    font-size: 1.4ex;
    line-height: 2.2ex;
    border-radius: 1.8ex;
    margin-left: 4px;
    padding: 1px;
    color: blue !important;
    background: white;
    border: 1px solid blue;
    text-decoration: none;
}

.field-desc {
    margin-top: 10px;
    display: none;
}

.field-desc.is-open {
    display: block;
}

h3 code {
    font-size: 1rem;
}
</style>

<?php include 'header.php'; 

// Build the current URL
$page = ddtt_plugin_options_short_path();
$tab = 'domain';
$current_url = ddtt_plugin_options_path( $tab );

// Hidden inputs
$hidden_allowed_html = [
    'input' => [
        'type'      => [],
        'name'      => [],
        'value'     => []
    ],
];
$hidden_path = '<input type="hidden" name="page" value="'.$page.'">
<input type="hidden" name="tab" value="'.$tab.'">';

// Are we viewing a single table
$domain = ddtt_get( 'domain', '!=', '', 'view_dns_records' ) ?? '';
if ( !$domain ) {
    $domain = ddtt_get_domain();
}

?>
<form id="search-domain-form" method="get" action="<?php echo esc_url( $current_url ); ?>">
    <?php echo wp_kses( $hidden_path, $hidden_allowed_html ); ?>
    <?php wp_nonce_field( 'view_dns_records' ); ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="domain-input">Enter Domain to Check</label></th>
            <td><input type="text" id="domain-input" name="domain" value="<?php echo esc_attr( $domain ); ?>">
            <input type="submit" value="Public DNS Records" class="button button-primary"/>
            <button type="button" id="check-propagation-button" class="button button-primary">Propagation</button>
            <button type="button" id="check-whois-button" class="button button-primary">Whois</button>
            <button type="button" id="check-mx-button" class="button button-primary">MX Toolbox</button></td>
        </tr>
    </table>
</form>
<br><br>

<?php
// Get the hostname first
$hostname = false;
$dns_records = dns_get_record( $domain, DNS_A + DNS_AAAA );
foreach ( $dns_records as $record ) {
    if ( isset( $record[ 'ip' ] ) ) {
        $ip = sanitize_text_field( $record[ 'ip' ] );
        $hostname = gethostbyaddr( $record['ip'] );
        break;
    }
}
if ( $hostname ) {
    echo '<h3>Hostname: <code class="hl">'.esc_attr( $hostname ).'</code></h3>';
}

// SSL cert expiration
$cert = ddtt_check_ssl_cert_expiration( $domain );
if ( $cert ) {
    if ( is_array( $cert ) ) {
        echo '<h3>SSL Certificate Status: <code class="hl">'.esc_attr( $cert[ 'is_active' ] ).'</code></h3>';
        echo '<h3>SSL Certificate Expiration Date: <code class="hl">'.esc_attr( ddtt_convert_timezone( $cert[ 'expiration_date' ] ) ).'</code></h3>';
    } else {
        echo '<h3>SSL Certificate Check: <code class="hl">'.esc_attr( $cert ).'</code></h3>';
    }    
}

// Get the dns records
$records = dns_get_record( $domain, DNS_ALL );
if ( !empty( $records ) ) {

    $record_types = [
        'A' => [
            'label' => 'Address',
            'desc'  => 'Maps a domain name to an IPv4 address.'
        ],
        'AAAA' => [
            'label' => 'Address (IPv6)',
            'desc'  => 'Maps a domain name to an IPv6 address.'
        ],
        'CNAME' => [
            'label' => 'Canonical Name',
            'desc'  => 'Maps an alias to the canonical (true) domain name.'
        ],
        'MX' => [
            'label' => 'Mail Exchange',
            'desc'  => 'Specifies the mail server responsible for receiving email for the domain. Priority of mail exchanger. Lower numbers indicate greater priority.'
        ],
        'NS' => [
            'label' => 'Name Server',
            'desc'  => 'Indicates the authoritative DNS servers for the domain.'
        ],
        'PTR' => [
            'label' => 'Pointer',
            'desc'  => 'Maps an IP address to a domain name (reverse DNS lookup).'
        ],
        'SOA' => [
            'label' => 'Start of Authority',
            'desc'  => 'Provides information about the domain\'s zone, including the primary DNS server and domain serial number.'
        ],
        'TXT' => [
            'label' => 'Text',
            'desc'  => 'Holds arbitrary text data, often used for domain verification or SPF records.'
        ],
        'SRV' => [
            'label' => 'Service',
            'desc'  => 'Specifies the location of a service, including its hostname and port.'
        ],
        'CAA' => [
            'label' => 'Certification Authority Authorization',
            'desc'  => 'Specifies which certificate authorities are allowed to issue certificates for the domain.'
        ],
        'DNSKEY' => [
            'label' => 'DNS Key',
            'desc'  => 'Used in DNSSEC to provide a public key for verifying DNSSEC signatures.'
        ],
        'RRSIG' => [
            'label' => 'DNSSEC Signature',
            'desc'  => 'Contains a DNSSEC signature for verifying the authenticity of DNS records.'
        ],
        'NSEC' => [
            'label' => 'Next Secure',
            'desc'  => 'Used in DNSSEC to provide proof of non-existence of a DNS record.'
        ],
        'NSEC3' => [
            'label' => 'Next Secure 3',
            'desc'  => 'Used in DNSSEC to provide proof of non-existence and prevent domain enumeration attacks.'
        ]
    ];    

    // Separate nameservers from other records
    $nameservers = array_filter( $records, function( $record ) {
        return sanitize_text_field( $record[ 'type' ] ) === 'NS';
    } );
    $other_records = array_filter( $records, function( $record ) {
        return sanitize_text_field( $record[ 'type' ] ) !== 'NS';
    } );
    ?>
    <br><br>
    <div class="full_width_container">
        <table class="admin-large-table">
            <tr>
                <th style="width: 300px;">Type</th>
                <th>Target</th>
                <th>Priority</th>
                <th>Weight</th>
                <th>Port</th>
                <th>TTL</th>
                <th>Other</th>
            </tr>
            <?php
            // Display nameservers first
            foreach ( $nameservers as $key => $record ) {
                $type = sanitize_text_field( $record[ 'type' ] );
                $label = isset( $record_types[ $type ][ 'label' ] ) ? $type.' ('.$record_types[ $type ][ 'label' ].')' : $type;
                $description = isset( $record_types[ $type ][ 'desc' ] ) ? $record_types[ $type ][ 'desc' ] : '';
                ?>
                <tr>
                    <td><?php echo esc_html( $label ); ?> <a href="#" class="learn-more" data-type="<?php echo esc_html( $type.'-'.$key ); ?>">?</a>
                    <div id="desc-<?php echo esc_html( $type.'-'.$key ); ?>" class="field-desc"><?php echo esc_html( $description ); ?></div></td>
                    <td><?php echo esc_html( $record[ 'target' ] ); ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><?php echo isset( $record[ 'ttl' ] ) ? esc_html( $record[ 'ttl' ] ) : ''; ?></td>
                    <td></td>
                </tr>
                <?php
            }
 
            // Display other records
            foreach ( $other_records as $key => $record ) {
                $type = sanitize_text_field( $record[ 'type' ] );
                $label = isset( $record_types[ $type ][ 'label' ] ) ? $type.' ('.$record_types[ $type ][ 'label' ].')' : $type;
                $description = isset( $record_types[ $type ][ 'desc' ] ) ? $record_types[ $type ][ 'desc' ] : '';
                ?>
                <tr>
                    <td><?php echo esc_html( $label ); ?> <a href="#" class="learn-more" data-type="<?php echo esc_html( $type.'-'.$key ); ?>">?</a>
                    <div id="desc-<?php echo esc_html( $type.'-'.$key ); ?>" class="field-desc"><?php echo esc_html( $description ); ?></div></td>
                    <td>
                        <?php
                        // Display target information
                        if ( isset( $record[ 'target' ] ) ) {
                            echo esc_html( $record[ 'target' ] );
                        } elseif ( isset( $record[ 'mname' ] ) ) {
                            echo esc_html( $record[ 'mname' ] );
                        } elseif ( isset( $record[ 'txt' ] ) ) {
                            echo esc_html( $record[ 'txt' ] );
                        } elseif ( isset( $record[ 'ip' ] ) ) {
                            echo esc_html( $record[ 'ip' ] );
                        } else {
                            echo '';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Display priority information
                        if (isset( $record[ 'pri' ] ) ) {
                            echo esc_html( $record[ 'pri' ] );
                        } else {
                            echo '';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Display weight information
                        if ( isset( $record[ 'weight' ] ) ) {
                            echo esc_html( $record[ 'weight' ] );
                        } else {
                            echo '';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Display port information
                        if ( isset( $record[ 'port' ] ) ) {
                            echo esc_html( $record[ 'port' ] );
                        } else {
                            echo '';
                        }
                        ?>
                    </td>
                    <td>
                        <?php echo isset( $record[ 'ttl' ] ) ? esc_html( $record[ 'ttl' ] ) : 'N/A'; ?>
                    </td>
                    <td>
                        <?php
                        // Display other relevant information
                        if ( isset( $record[ 'rname' ] ) ) {
                            echo 'Rname: '.esc_html( $record[ 'rname' ] );
                        } elseif (isset( $record[ 'mname' ] ) && isset( $record[ 'rname' ] ) ) {
                            echo 'Mname: '.esc_html( $record[ 'mname' ] ).', Rname: '.esc_html( $record[ 'rname' ] );
                        } else {
                            echo '';
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>

    <?php
} else {
    echo '<h3>No records found. :(</h3>';
}

?>
<script>
jQuery( document ).ready( function( $ ) {
    // Buttons
    $( '#check-propagation-button' ).on( 'click', function() {
        var domain = $( '#domain-input' ).val();
        var url = 'https://dnschecker.org/#NS/' + encodeURIComponent( domain );
        window.open( url, '_blank' );
    } );

    $( '#check-whois-button' ).on( 'click', function() {
        var domain = $( '#domain-input' ).val();
        var url = 'https://www.whois.com/whois/' + encodeURIComponent( domain );
        window.open( url, '_blank' );
    } );

    $( '#check-mx-button' ).on( 'click', function() {
        var domain = $( '#domain-input' ).val();
        var url = 'https://mxtoolbox.com/supertool3?abt_id=AB-631B&abt_var=Variation&run=toolpage&action=mx%3a' + encodeURIComponent( domain );
        window.open( url, '_blank' );
    } );

    // Show/Hide Descriptions
    $( '.learn-more' ).on( 'click', function( e ) {
        e.preventDefault();
        const type = $( this ).data( 'type' );
        $( `#desc-${type}` ).toggleClass( 'is-open' );
    } );
} );
</script>