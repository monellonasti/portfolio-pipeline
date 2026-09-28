<?php
/**
 * Template Fallback: archivio "Progetti".
 * Hero in stile tema + shortcode carosello + CTA finale.
 *
 * @package DigiflussPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<main id="primary" class="site-main inner-page dfp-archive" role="main">

	<section class="inner-hero">
		<div class="df-container">
			<div class="reveal">
				<div class="df-kicker"><span></span><?php esc_html_e( 'Portfolio', 'digifluss-portfolio' ); ?></div>
				<h1 class="df-h1"><?php esc_html_e( 'Progetti, non semplici siti.', 'digifluss-portfolio' ); ?></h1>
				<p class="df-lead"><?php esc_html_e( 'Strategia, sviluppo e AI per trasformare strumenti isolati in ecosistemi digitali fluidi. Una selezione di casi recenti.', 'digifluss-portfolio' ); ?></p>
			</div>
		</div>
	</section>

	<?php
	// Mostra il carosello con tutti i progetti, già wrappato dal tema (wrap=section).
	echo do_shortcode( '[digifluss_portfolio count="-1" wrap="section" kicker="" title="" lead=""]' );
	?>

	<section class="df-section cta-section">
		<div class="df-container">
			<div class="cta-block reveal">
				<div>
					<div class="df-kicker"><span></span><?php esc_html_e( 'Parliamone', 'digifluss-portfolio' ); ?></div>
					<h2 class="df-h2"><?php esc_html_e( 'Vuoi un progetto come questi?', 'digifluss-portfolio' ); ?></h2>
					<p class="df-lead"><?php esc_html_e( 'Partiamo dalla tua realtà, non da un template. Una prima call basta a capire dove c\'è valore.', 'digifluss-portfolio' ); ?></p>
				</div>
				<div class="cta-actions">
					<a class="df-btn df-btn--primary" href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>"><?php esc_html_e( 'Analizziamo il tuo flusso', 'digifluss-portfolio' ); ?> <span aria-hidden="true">&rarr;</span></a>
				</div>
			</div>
		</div>
	</section>

</main>

<style>
	/* L'hero dell'archivio nasconde la testata interna dello shortcode
	   (kicker/title/lead vuoti) per evitare doppi titoli. */
	.dfp-archive .dfp.dfp--themed .dfp-head{ display:none; }
</style>

<?php
get_footer();
