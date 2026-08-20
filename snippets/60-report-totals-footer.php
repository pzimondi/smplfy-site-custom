<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_footer', function () {
    if ( is_admin() ) { return; }
    ?>
    <script>
    ( function ( $ ) {
        if ( ! $ ) { return; }
        // Metrics are keyed to the tfoot cells' own GravityView field
        // classes (form 75 field ids), NOT positional indices - the old
        // index map assumed no Entry ID cell and shifted every total one
        // column left the moment the view's column set changed.
        //
        // 30 Jul fix: the class match alone was not enough. GravityView
        // mirrors the thead LABELS into tfoot and DataTables overwrites
        // them with sums during the draw; matching .last() row (or
        // matching before the sums land) captured the label text, so
        // every tile printed its own column name - "$Sales Revenue"
        // instead of "$50,000.00". Cells are now read from EVERY tfoot
        // row and the first candidate CONTAINING A DIGIT wins, so a
        // label can never be mistaken for a total. render() is also
        // retried after the draw settles.
        var METRICS = [
            { cls: 'gv-field-75-10', icon: 'fa-solid fa-money-bill-1-wave',   label: 'Sales Revenue',   money: true  },
            { cls: 'gv-field-75-12', icon: 'fa-regular fa-money-bill-1-wave', label: 'Gross Profit',    money: true  },
            { cls: 'gv-field-75-19', icon: 'fa-light fa-money-bill-1-wave',   label: 'Net Profit',      money: true  },
            { cls: 'gv-field-75-13', icon: 'fa-sharp fa-solid fa-people-group', label: 'Leads Generated', money: false },
            { cls: 'gv-field-75-14', icon: 'fa-solid fa-calendar-plus',      label: 'Appts Scheduled', money: false },
            { cls: 'gv-field-75-15', icon: 'fa-solid fa-calendar-check',     label: 'Appts Kept',      money: false },
            { cls: 'gv-field-75-17', icon: 'fa-solid fa-flag-checkered',     label: 'Sales Closed',    money: false },
            { cls: 'gv-field-75-16', icon: 'fa-solid fa-trophy',             label: 'Sales Won',       money: false }
        ];
        // WHY THIS READS THE WAY IT DOES
        //
        // tfoot holds MORE THAN ONE ROW: a row mirroring the thead
        // labels, and the row DataTables writes column sums into. Three
        // earlier approaches each mis-read it:
        //   - positional tfoot.eq(i)           : column set had changed
        //   - class match inside tfoot         : classes on the sums row
        //     sit one column out of step, so every tile showed the
        //     figure belonging to the column on its left
        //   - DataTables column().footer()     : resolves to the LABEL
        //     row cell, which has text but no digits
        //
        // What is stable: in the sums row the figures appear in METRICS
        // order, and these eight metrics are the RIGHTMOST eight columns
        // of the view. So the sums row is located by finding the tfoot
        // row with the most digit-bearing cells, and its last eight such
        // cells are taken IN ORDER. Fewer than eight and nothing renders
        // - an empty card is recoverable, a mislabelled figure is not.
        function readTotals( $table ) {
            var best = [];
            $table.find( 'tfoot tr' ).each( function () {
                var nums = [];
                $( this ).find( 'td, th' ).each( function () {
                    var t = $.trim( $( this ).text() );
                    if ( t && /\d/.test( t ) ) { nums.push( t ); }
                } );
                if ( nums.length > best.length ) { best = nums; }
            } );
            if ( best.length < METRICS.length ) { return null; }
            return best.slice( best.length - METRICS.length );
        }
        function render() {
            var $target = $( '#smplfy-report-totals' );
            var $table  = $( '.gv-container-418 table.dataTable' ).first();
            if ( ! $target.length || ! $table.length ) { return; }
            var totals = readTotals( $table );
            if ( ! totals ) { return; }
            var tiles = METRICS.map( function ( m, i ) {
                var raw = totals[ i ];
                if ( ! raw ) { return ''; }
                // Sums arrive unprefixed; guard anyway so a currency
                // format change can never print "$$".
                var bare = raw.replace( /^\s*\$\s*/, '' );
                var val  = m.money ? '$' + bare : bare.replace( /\.00$/, '' );
                return '<div class="smplfy-report-totals__tile">'
                     +   '<i class="' + m.icon + '"></i>'
                     +   '<span>'
                     +     '<span class="smplfy-report-totals__label">' + m.label + '</span>'
                     +     '<span class="smplfy-report-totals__value">' + val + '</span>'
                     +   '</span>'
                     + '</div>';
            } ).join( '' );
            if ( ! tiles ) { return; }
            $target.html(
                '<div class="smplfy-report-totals">'
              +   '<p class="smplfy-report-totals__title">Totals — all reports</p>'
              +   '<div class="smplfy-report-totals__grid">' + tiles + '</div>'
              + '</div>'
            );
        }
        // Bound late and retried: the sums are written by DataTables'
        // own footer callback during the draw, and handler order between
        // that callback and this one is not guaranteed.
        function renderSoon() {
            render();
            setTimeout( render, 0 );
            setTimeout( render, 400 );
        }
        $( document ).on( 'draw.dt', renderSoon );
        $( window ).on( 'load', renderSoon );
    } )( window.jQuery );
    </script>
    <?php
}, 100 );
