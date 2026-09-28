<?php
/**
 * Template Fallback: singolo progetto / case study.
 * Si integra col tema Digifluss riusando .df-section/.df-container/.df-kicker/
 * .df-h1/.df-lead/.split-section/.sticky-block/.rich-text/.cta-block/.df-btn.
 * Su altri temi resta dignitoso grazie ai fallback CSS.
 *
 * Due livelli di profondità, decisi dai campi compilati:
 * - progetto "leggero": sfida / approccio / risultato + galleria;
 * - case study completo: hero dedicato, panoramica, racconto del prodotto,
 *   capacità chiave, approfondimenti tecnici, architettura e tag.
 *
 * @package DigiflussPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
$dfp_accent = get_post_meta( get_queried_object_id(), '_dfp_accent', true );
if ( ! $dfp_accent ) { $dfp_accent = '#2ee7f2'; }
?>
<main id="primary" class="site-main inner-page dfp-single dfp-case" role="main" style="--dfp-accent:<?php echo esc_attr( $dfp_accent ); ?>;">

	<?php while ( have_posts() ) : the_post();
		$pid       = get_the_ID();
		$category  = get_post_meta( $pid, '_dfp_category', true );
		$year      = get_post_meta( $pid, '_dfp_year', true );
		$role      = get_post_meta( $pid, '_dfp_role', true );
		$stack_raw = (string) get_post_meta( $pid, '_dfp_stack', true );
		$stack     = array_values( array_filter( array_map( 'trim', explode( ',', $stack_raw ) ) ) );
		$ext_link  = get_post_meta( $pid, '_dfp_link', true );
		$client    = get_post_meta( $pid, '_dfp_client', true );
		$integ_raw = (string) get_post_meta( $pid, '_dfp_integrations', true );
		$integrations = array_values( array_filter( array_map( 'trim', explode( ',', $integ_raw ) ) ) );
		$challenge = get_post_meta( $pid, '_dfp_challenge', true );
		$approach  = get_post_meta( $pid, '_dfp_approach', true );
		$result    = get_post_meta( $pid, '_dfp_result', true );
		// Solo allegati ancora esistenti: un'immagine eliminata dalla media
		// library lascerebbe altrimenti un riquadro vuoto in pagina.
		$gallery_ids = array_values( array_filter(
			array_map( 'absint', explode( ',', (string) get_post_meta( $pid, '_dfp_gallery', true ) ) ),
			function ( $gid ) {
				return $gid && wp_attachment_is_image( $gid );
			}
		) );
		$img       = get_the_post_thumbnail_url( $pid, 'large' );
		$archive   = (string) get_post_type_archive_link( 'df_progetto' );

		// Case study approfondito.
		$cs_title     = get_post_meta( $pid, '_dfp_cs_title', true );
		$cs_subtitle  = get_post_meta( $pid, '_dfp_cs_subtitle', true );
		$industry     = get_post_meta( $pid, '_dfp_industry', true );
		$product      = get_post_meta( $pid, '_dfp_product', true );
		$team_role    = get_post_meta( $pid, '_dfp_team_role', true );
		$timeline     = get_post_meta( $pid, '_dfp_timeline', true );
		$overview     = get_post_meta( $pid, '_dfp_overview', true );
		$story        = get_post_meta( $pid, '_dfp_story', true );
		$architecture = get_post_meta( $pid, '_dfp_architecture', true );
		$tags_raw     = (string) get_post_meta( $pid, '_dfp_tags', true );
		$cs_tags      = array_values( array_filter( array_map( 'trim', explode( ',', $tags_raw ) ) ) );
		$capabilities = function_exists( 'dfp_json_meta' ) ? dfp_json_meta( $pid, '_dfp_capabilities' ) : array();
		$highlights   = function_exists( 'dfp_json_meta' ) ? dfp_json_meta( $pid, '_dfp_highlights' ) : array();
		$design_intro  = get_post_meta( $pid, '_dfp_design_intro', true );
		$design_blocks = function_exists( 'dfp_json_meta' ) ? dfp_json_meta( $pid, '_dfp_design_blocks' ) : array();

		// Nelle lingue non italiane usa i campi tradotti, con fallback
		// all'italiano per quelli mancanti.
		$excerpt = get_the_excerpt();
		$tr      = function_exists( 'dfp_translation' ) ? dfp_translation( $pid ) : null;
		if ( $tr ) {
			foreach ( array(
				'excerpt'      => 'excerpt',
				'category'     => 'category',
				'role'         => 'role',
				'industry'     => 'industry',
				'product'      => 'product',
				'team_role'    => 'team_role',
				'timeline'     => 'timeline',
				'cs_title'     => 'cs_title',
				'cs_subtitle'  => 'cs_subtitle',
				'overview'     => 'overview',
				'story'        => 'story',
				'architecture' => 'architecture',
				'challenge'    => 'challenge',
				'approach'     => 'approach',
				'result'       => 'result',
				'design_intro' => 'design_intro',
			) as $tr_key => $var_name ) {
				if ( ! empty( $tr[ $tr_key ] ) && is_string( $tr[ $tr_key ] ) ) {
					$$var_name = $tr[ $tr_key ];
				}
			}
			if ( ! empty( $tr['tags'] ) && is_string( $tr['tags'] ) ) {
				$cs_tags = array_values( array_filter( array_map( 'trim', explode( ',', $tr['tags'] ) ) ) );
			}
			foreach ( array( 'capabilities', 'highlights', 'design_blocks' ) as $tr_list ) {
				if ( ! empty( $tr[ $tr_list ] ) && is_array( $tr[ $tr_list ] ) ) {
					$$tr_list = $tr[ $tr_list ];
				}
			}
		}

		$hero_title = $cs_title ? $cs_title : get_the_title();
		$hero_lead  = $cs_subtitle ? $cs_subtitle : $excerpt;

		// Distribuzione immagini della galleria, nell'ordine di pagina:
		// le prime affiancano le sezioni narrative (Il prodotto, Design),
		// poi i blocchi sfida/approccio/risultato; le altre in galleria.
		// La copertina apre già la pagina a tutta larghezza: se è anche la
		// prima della galleria (com'è per convenzione del file .md) va tolta
		// dalla coda, altrimenti ricomparirebbe subito sotto accanto al testo.
		$featured_id = (int) get_post_thumbnail_id( $pid );
		$img_queue   = $featured_id
			? array_values( array_filter( $gallery_ids, function ( $gid ) use ( $featured_id ) {
				return $gid !== $featured_id;
			} ) )
			: $gallery_ids;
		$story_img  = ( $story && $img_queue ) ? array_shift( $img_queue ) : 0;
		$design_img = ( ( $design_intro || ! empty( $design_blocks ) ) && $img_queue ) ? array_shift( $img_queue ) : 0;

		// Immagine cliccabile: link al file pieno, intercettato dal lightbox JS.
		$dfp_zoom_img = function ( $attachment_id, $alt ) {
			$full = wp_get_attachment_image_url( $attachment_id, 'full' );
			$img  = wp_get_attachment_image( $attachment_id, 'large', false, array( 'loading' => 'lazy', 'alt' => $alt ) );
			if ( ! $full || ! $img ) { return ''; }
			/* translators: %s: titolo del progetto */
			$label = sprintf( __( 'Apri l\'immagine a schermo intero: %s', 'digifluss-portfolio' ), $alt );
			return '<a class="dfp-zoom" href="' . esc_url( $full ) . '" aria-label="' . esc_attr( $label ) . '">' . $img . '</a>';
		};
		?>

		<section class="inner-hero">
			<div class="df-container">
				<div class="reveal">
					<?php if ( $archive ) : ?>
						<p class="dfp-back">
							<a class="inline-link" href="<?php echo esc_url( $archive ); ?>"><span aria-hidden="true">&larr;</span> <?php esc_html_e( 'Tutti i progetti', 'digifluss-portfolio' ); ?></a>
						</p>
					<?php endif; ?>
					<div class="df-kicker"><span></span><?php echo esc_html( $category ? $category : __( 'Progetto', 'digifluss-portfolio' ) ); ?></div>
					<h1 class="df-h1"><?php
					if ( function_exists( 'digifluss_display_title' ) ) {
						echo digifluss_display_title( $hero_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapato nell'helper del tema.
					} else {
						echo esc_html( $hero_title );
					}
					?></h1>
					<?php if ( $hero_lead ) : ?>
						<p class="df-lead"><?php echo esc_html( $hero_lead ); ?></p>
					<?php endif; ?>
					<?php
					$facts = array_filter( array( $client, $industry, $year ) );
					if ( $facts ) : ?>
						<p class="dfp-hero-facts"><?php echo esc_html( implode( '  ·  ', $facts ) ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<?php if ( $img ) : ?>
			<section class="df-section df-section--compact">
				<div class="df-container">
					<figure class="dfp-hero-figure reveal">
						<?php
						// È l'immagine più grande sopra la piega, quindi quasi
						// sempre l'LCP: va caricata subito e con priorità, non
						// in lazy. width/height evitano il salto di layout e il
						// srcset serve il taglio giusto sui piccoli schermi.
						echo wp_get_attachment_image(
							get_post_thumbnail_id( $pid ),
							'large',
							false,
							array(
								'alt'           => get_the_title(),
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
								'sizes'         => '(max-width: 1180px) 100vw, 1120px',
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup generato da WordPress.
						?>
					</figure>
				</div>
			</section>
		<?php endif; ?>

		<section class="df-section df-section--tight">
			<div class="df-container split-section">
				<aside class="sticky-block reveal dfp-side">
					<div class="dfp-side-meta">
						<?php
						$side_rows = array(
							array( __( 'Cliente', 'digifluss-portfolio' ), $client ),
							array( __( 'Settore', 'digifluss-portfolio' ), $industry ),
							array( __( 'Prodotto', 'digifluss-portfolio' ), $product ),
							array( __( 'Ruolo', 'digifluss-portfolio' ), $team_role ),
							array( __( 'Obiettivo', 'digifluss-portfolio' ), $role ),
							array( __( 'Anno', 'digifluss-portfolio' ), $year ),
							array( __( 'Tempi', 'digifluss-portfolio' ), $timeline ),
						);
						foreach ( $side_rows as $sr ) :
							if ( ! $sr[1] ) { continue; }
							?>
							<dl><dt><?php echo esc_html( $sr[0] ); ?></dt><dd><?php echo esc_html( $sr[1] ); ?></dd></dl>
						<?php endforeach; ?>
						<?php if ( ! empty( $stack ) ) : ?>
							<dl>
								<dt><?php esc_html_e( 'Stack', 'digifluss-portfolio' ); ?></dt>
								<dd class="dfp-side-stack">
									<?php foreach ( $stack as $s ) : ?>
										<span class="tag"><?php echo esc_html( $s ); ?></span>
									<?php endforeach; ?>
								</dd>
							</dl>
						<?php endif; ?>
						<?php if ( ! empty( $integrations ) ) : ?>
							<dl>
								<dt><?php esc_html_e( 'Integrazioni', 'digifluss-portfolio' ); ?></dt>
								<dd class="dfp-side-stack">
									<?php foreach ( $integrations as $integration ) : ?>
										<span class="tag"><?php echo esc_html( $integration ); ?></span>
									<?php endforeach; ?>
								</dd>
							</dl>
						<?php endif; ?>
					</div>
					<?php if ( $ext_link ) : ?>
						<a class="df-btn df-btn--primary dfp-side-cta" href="<?php echo esc_url( $ext_link ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Vai al sito live', 'digifluss-portfolio' ); ?> <span aria-hidden="true">&rarr;</span>
						</a>
					<?php endif; ?>
				</aside>

				<div class="rich-text reveal">
					<?php if ( $overview && function_exists( 'dfp_paragraphs' ) ) : ?>
						<div class="dfp-overview">
							<span class="dfp-block-label"><span></span><?php esc_html_e( 'Panoramica', 'digifluss-portfolio' ); ?></span>
							<?php echo dfp_paragraphs( $overview ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paragrafi con escape interno. ?>
						</div>
					<?php endif; ?>
					<?php
					$content = apply_filters( 'the_content', get_the_content() );
					echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered the_content.
					?>
				</div>
			</div>
		</section>

		<?php if ( $story && function_exists( 'dfp_paragraphs' ) ) : ?>
			<section class="df-section df-section--tight dfp-story">
				<div class="df-container<?php echo $story_img ? ' dfp-splitmedia' : ''; ?>">
					<div class="dfp-story-inner reveal">
						<div class="df-kicker"><span></span><?php esc_html_e( 'Il prodotto', 'digifluss-portfolio' ); ?></div>
						<h2 class="df-h2"><?php esc_html_e( 'Cosa vede chi lo usa.', 'digifluss-portfolio' ); ?></h2>
						<div class="dfp-story-copy">
							<?php echo dfp_paragraphs( $story ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paragrafi con escape interno. ?>
						</div>
					</div>
					<?php if ( $story_img ) : ?>
						<figure class="dfp-side-figure reveal"><?php echo $dfp_zoom_img( $story_img, get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup con escape interno. ?></figure>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ( $design_intro || ! empty( $design_blocks ) ) && function_exists( 'dfp_paragraphs' ) ) : ?>
			<section class="df-section df-section--tight dfp-design">
				<div class="df-container<?php echo $design_img ? ' dfp-splitmedia' : ''; ?>">
					<div class="dfp-design-inner">
						<div class="reveal">
							<div class="df-kicker"><span></span><?php esc_html_e( 'Design e identità visiva', 'digifluss-portfolio' ); ?></div>
							<h2 class="df-h2"><?php esc_html_e( 'Ogni scelta visiva ha un perché.', 'digifluss-portfolio' ); ?></h2>
						</div>
						<?php if ( $design_intro ) : ?>
							<div class="dfp-design-intro reveal">
								<?php echo dfp_paragraphs( $design_intro ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paragrafi con escape interno. ?>
							</div>
						<?php endif; ?>
						<?php foreach ( $design_blocks as $ds ) :
							$ds_title = isset( $ds['title'] ) ? $ds['title'] : '';
							$ds_text  = isset( $ds['text'] ) ? $ds['text'] : '';
							if ( ! $ds_title && ! $ds_text ) { continue; }
							?>
							<article class="dfp-design-block reveal">
								<?php if ( $ds_title ) : ?><h3><?php echo esc_html( $ds_title ); ?></h3><?php endif; ?>
								<?php echo dfp_paragraphs( $ds_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paragrafi con escape interno. ?>
							</article>
						<?php endforeach; ?>
					</div>
					<?php if ( $design_img ) : ?>
						<figure class="dfp-side-figure reveal"><?php echo $dfp_zoom_img( $design_img, get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup con escape interno. ?></figure>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $capabilities ) ) : ?>
			<section class="df-section df-section--tight dfp-caps">
				<div class="df-container">
					<div class="reveal">
						<div class="df-kicker"><span></span><?php esc_html_e( 'Capacità chiave', 'digifluss-portfolio' ); ?></div>
						<h2 class="df-h2"><?php esc_html_e( 'Cosa sa fare, e perché conta.', 'digifluss-portfolio' ); ?></h2>
					</div>
					<div class="dfp-caps-grid">
						<?php foreach ( $capabilities as $cap ) :
							$cap_title = isset( $cap['title'] ) ? $cap['title'] : '';
							$cap_text  = isset( $cap['text'] ) ? $cap['text'] : '';
							if ( ! $cap_title && ! $cap_text ) { continue; }
							?>
							<article class="dfp-cap reveal">
								<?php if ( $cap_title ) : ?><h3><?php echo esc_html( $cap_title ); ?></h3><?php endif; ?>
								<?php if ( $cap_text ) : ?><p><?php echo nl2br( esc_html( $cap_text ) ); ?></p><?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $highlights ) ) : ?>
			<section class="df-section df-section--tight dfp-hl">
				<div class="df-container">
					<div class="reveal">
						<div class="df-kicker"><span></span><?php esc_html_e( 'Dentro l\'ingegneria', 'digifluss-portfolio' ); ?></div>
						<h2 class="df-h2"><?php esc_html_e( 'I problemi interessanti, e come li abbiamo risolti.', 'digifluss-portfolio' ); ?></h2>
					</div>
					<div class="dfp-hl-list">
						<?php foreach ( $highlights as $hi => $hl ) :
							$hl_title = isset( $hl['title'] ) ? $hl['title'] : '';
							if ( ! $hl_title ) { continue; }
							?>
							<article class="dfp-hl-item reveal">
								<span class="dfp-hl-num"><?php echo esc_html( str_pad( $hi + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
								<div class="dfp-hl-body">
									<h3><?php echo esc_html( $hl_title ); ?></h3>
									<?php if ( ! empty( $hl['challenge'] ) ) : ?>
										<p class="dfp-hl-row"><strong><?php esc_html_e( 'Sfida', 'digifluss-portfolio' ); ?></strong><?php echo nl2br( esc_html( $hl['challenge'] ) ); ?></p>
									<?php endif; ?>
									<?php if ( ! empty( $hl['solution'] ) ) : ?>
										<p class="dfp-hl-row"><strong><?php esc_html_e( 'Soluzione', 'digifluss-portfolio' ); ?></strong><?php echo nl2br( esc_html( $hl['solution'] ) ); ?></p>
									<?php endif; ?>
									<?php if ( ! empty( $hl['tech'] ) ) :
										$hl_tech = array_values( array_filter( array_map( 'trim', explode( ',', (string) $hl['tech'] ) ) ) );
										?>
										<div class="dfp-hl-tech">
											<?php foreach ( $hl_tech as $t ) : ?>
												<span class="tag"><?php echo esc_html( $t ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$cs_blocks = array();
		if ( $challenge ) { $cs_blocks[] = array( 'label' => __( 'La sfida', 'digifluss-portfolio' ), 'text' => $challenge ); }
		if ( $approach )  { $cs_blocks[] = array( 'label' => __( 'L\'approccio', 'digifluss-portfolio' ), 'text' => $approach ); }
		if ( $result )    { $cs_blocks[] = array( 'label' => __( 'Il risultato', 'digifluss-portfolio' ), 'text' => $result ); }
		?>
		<?php if ( ! empty( $cs_blocks ) ) : ?>
			<section class="df-section df-section--tight dfp-case-blocks">
				<div class="df-container">
					<div class="dfp-blocks">
						<?php foreach ( $cs_blocks as $bi => $b ) :
							$bimg = $img_queue ? array_shift( $img_queue ) : 0;
							?>
							<article class="dfp-block reveal<?php echo $bimg ? '' : ' dfp-block--solo'; ?>">
								<?php if ( $bimg ) : ?>
									<div class="dfp-block-media"><?php echo $dfp_zoom_img( $bimg, $b['label'] . ' — ' . get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup con escape interno. ?></div>
								<?php endif; ?>
								<div class="dfp-block-text">
									<span class="dfp-block-label"><span></span><?php echo esc_html( $b['label'] ); ?></span>
									<p><?php echo nl2br( esc_html( $b['text'] ) ); ?></p>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $architecture && function_exists( 'dfp_paragraphs' ) ) : ?>
			<section class="df-section df-section--tight dfp-arch">
				<div class="df-container">
					<div class="dfp-arch-panel reveal">
						<div class="dfp-arch-head"><span><?php esc_html_e( 'architettura', 'digifluss-portfolio' ); ?></span><strong><?php esc_html_e( 'come sta in piedi', 'digifluss-portfolio' ); ?></strong></div>
						<div class="dfp-arch-body">
							<?php echo dfp_paragraphs( $architecture ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paragrafi con escape interno. ?>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$cs_extra = $img_queue;
		if ( ! empty( $cs_extra ) ) :
			?>
			<section class="df-section df-section--tight dfp-case-gallery">
				<div class="df-container">
					<div class="df-kicker reveal"><span></span><?php esc_html_e( 'Galleria', 'digifluss-portfolio' ); ?></div>
					<div class="dfp-gallery-grid reveal">
						<?php foreach ( $cs_extra as $gid ) :
							$gcap = function_exists( 'dfp_image_caption' ) ? dfp_image_caption( $gid, get_post_field( 'post_name', $pid ) ) : '';
							?>
							<figure>
								<?php echo $dfp_zoom_img( $gid, $gcap ? $gcap : get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup con escape interno. ?>
								<?php if ( $gcap ) : ?>
									<figcaption><?php echo esc_html( $gcap ); ?></figcaption>
								<?php endif; ?>
							</figure>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $cs_tags ) ) : ?>
			<section class="df-section df-section--compact dfp-tags">
				<div class="df-container">
					<div class="dfp-tags-row reveal">
						<?php foreach ( $cs_tags as $tg ) : ?>
							<span class="tag"><?php echo esc_html( $tg ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="df-section cta-section">
			<div class="df-container">
				<div class="cta-block reveal">
					<div>
						<div class="df-kicker"><span></span><?php esc_html_e( 'Iniziamo', 'digifluss-portfolio' ); ?></div>
						<h2 class="df-h2"><?php esc_html_e( 'Un progetto come questo, anche per te?', 'digifluss-portfolio' ); ?></h2>
						<p class="df-lead"><?php esc_html_e( 'Partiamo da una lettura del tuo flusso. Senza impegno.', 'digifluss-portfolio' ); ?></p>
					</div>
					<div class="cta-actions">
						<a class="df-btn df-btn--primary" href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>"><?php esc_html_e( 'Analizziamo il tuo flusso', 'digifluss-portfolio' ); ?> <span aria-hidden="true">&rarr;</span></a>
						<?php if ( $archive ) : ?>
							<a class="df-btn df-btn--ghost" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Altri progetti', 'digifluss-portfolio' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

	<?php endwhile; ?>

</main>

<style>
	.dfp-single .dfp-back{ margin:0 0 18px; font-size:.92rem; }
	.dfp-single .dfp-hero-facts{ margin:18px 0 0; font-family:var(--df-mono,monospace); font-size:.82rem; letter-spacing:.14em; text-transform:uppercase; color:var(--df-dim,#6f7a82); }
	.dfp-single .dfp-hero-figure{ margin:0; border-radius:18px; overflow:hidden; border:1px solid var(--df-line,rgba(255,255,255,.10)); box-shadow:var(--df-shadow-dark,0 22px 70px rgba(0,0,0,.34)); }
	.dfp-single .dfp-hero-figure img{ display:block; width:100%; height:auto; }
	.dfp-single .dfp-side-meta dl{ margin:0 0 18px; padding:0 0 18px; border-bottom:1px solid var(--df-line,rgba(255,255,255,.10)); }
	.dfp-single .dfp-side-meta dl:last-of-type{ border-bottom:0; }
	.dfp-single .dfp-side-meta dt{ font-size:.7rem; font-weight:900; letter-spacing:.14em; text-transform:uppercase; color:var(--df-dim,#6f7a82); margin-bottom:6px; }
	.dfp-single .dfp-side-meta dd{ margin:0; font-size:1rem; color:var(--df-text,#f7faf9); letter-spacing:-.01em; }
	.dfp-single .dfp-side-stack{ display:flex; flex-wrap:wrap; gap:6px; margin:0; }
	.dfp-single .dfp-side-stack .tag, .dfp-single .dfp-hl-tech .tag, .dfp-single .dfp-tags-row .tag{ font-size:.74rem; padding:5px 10px; border-radius:999px; border:1px solid var(--df-line,rgba(255,255,255,.10)); background:rgba(255,255,255,.04); color:var(--df-muted,#aab5b9); }
	.dfp-single .dfp-side-cta{ margin-top:8px; }

	/* --- Panoramica dentro la colonna principale --- */
	.dfp-case .dfp-overview{ margin-bottom:26px; }
	.dfp-case .dfp-overview p{ font-size:clamp(1.02rem,1.5vw,1.14rem); line-height:1.75; color:var(--df-text,#f7faf9); margin:0 0 16px; }

	/* --- Il prodotto: racconto a colonna centrata --- */
	.dfp-case .dfp-story-inner{ max-width:820px; }
	.dfp-case .dfp-story-copy{ margin-top:18px; }
	.dfp-case .dfp-story-copy p{ font-size:clamp(1.02rem,1.5vw,1.14rem); line-height:1.78; color:var(--df-muted,#aab5b9); margin:0 0 16px; }
	.dfp-case .dfp-story-copy p:first-child{ color:var(--df-text,#f7faf9); }

	/* --- Sezioni con immagine a fianco --- */
	.dfp-case .dfp-splitmedia{ display:grid; grid-template-columns:minmax(0,1.05fr) minmax(280px,.75fr); gap:clamp(26px,4.5vw,64px); align-items:start; }
	.dfp-case .dfp-side-figure{ margin:0; position:sticky; top:96px; border-radius:18px; overflow:hidden; border:1px solid var(--df-line,rgba(255,255,255,.10)); box-shadow:var(--df-shadow-dark,0 22px 70px rgba(0,0,0,.34)); }
	.dfp-case .dfp-side-figure img{ display:block; width:100%; height:auto; }
	@media (max-width:920px){
		.dfp-case .dfp-splitmedia{ grid-template-columns:1fr; }
		.dfp-case .dfp-side-figure{ position:static; order:-1; }
	}

	/* --- Immagini cliccabili + lightbox --- */
	.dfp-case .dfp-zoom{ display:block; cursor:zoom-in; }
	.dfp-case .dfp-zoom img{ transition:transform .45s cubic-bezier(.22,.61,.36,1); }
	.dfp-case .dfp-zoom:hover img{ transform:scale(1.03); }
	.dfp-lightbox{ position:fixed; inset:0; z-index:9999; display:flex; align-items:center; justify-content:center; padding:4vmin; background:rgba(4,6,9,.92); backdrop-filter:blur(6px); opacity:0; transition:opacity .25s ease; }
	.dfp-lightbox.is-open{ opacity:1; }
	.dfp-lightbox img{ max-width:92vw; max-height:90vh; border-radius:12px; box-shadow:0 30px 90px rgba(0,0,0,.6); }
	.dfp-lightbox-close{ position:absolute; top:18px; right:22px; width:44px; height:44px; border:1px solid rgba(255,255,255,.3); border-radius:50%; background:rgba(255,255,255,.06); color:#fff; font-size:22px; line-height:1; cursor:pointer; }
	.dfp-lightbox-close:hover{ border-color:var(--dfp-accent,#2ee7f2); color:var(--dfp-accent,#2ee7f2); }

	/* --- Design e identità visiva: racconto a colonna con sottotitoli ad accento --- */
	.dfp-case .dfp-design-inner{ max-width:820px; }
	.dfp-case .dfp-design-intro{ margin-top:18px; }
	.dfp-case .dfp-design-intro p{ font-size:clamp(1.02rem,1.5vw,1.14rem); line-height:1.78; color:var(--df-text,#f7faf9); margin:0 0 16px; }
	.dfp-case .dfp-design-block{ margin-top:clamp(26px,4vw,40px); padding-left:clamp(16px,2.5vw,26px); border-left:2px solid transparent; border-image:linear-gradient(180deg,var(--dfp-accent,#2ee7f2),transparent) 1; }
	.dfp-case .dfp-design-block h3{ margin:0 0 10px; font-size:clamp(1.1rem,1.8vw,1.3rem); letter-spacing:-.02em; }
	.dfp-case .dfp-design-block p{ margin:0 0 14px; font-size:.99rem; line-height:1.75; color:var(--df-muted,#aab5b9); }
	.dfp-case .dfp-design-block p:last-child{ margin-bottom:0; }
	.dfp-case .dfp-design-block ul, .dfp-case .dfp-arch-body ul, .dfp-case .dfp-overview ul, .dfp-case .dfp-story-copy ul{ margin:0 0 14px; padding-left:20px; color:var(--df-muted,#aab5b9); }
	.dfp-case .dfp-design-block li, .dfp-case .dfp-arch-body li, .dfp-case .dfp-overview li, .dfp-case .dfp-story-copy li{ margin:0 0 8px; font-size:.97rem; line-height:1.65; }
	.dfp-case .dfp-design-block li::marker, .dfp-case .dfp-arch-body li::marker, .dfp-case .dfp-overview li::marker, .dfp-case .dfp-story-copy li::marker{ color:var(--dfp-accent,#2ee7f2); }

	/* --- Capacità chiave: griglia di card --- */
	.dfp-case .dfp-caps-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(290px,1fr)); gap:16px; margin-top:clamp(22px,3.5vw,36px); }
	.dfp-case .dfp-cap{ padding:24px 22px; border:1px solid var(--df-line,rgba(255,255,255,.10)); border-radius:16px; background:rgba(255,255,255,.02); transition:transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
	.dfp-case .dfp-cap:hover{ transform:translateY(-5px); border-color:var(--dfp-accent,#2ee7f2); box-shadow:0 22px 60px rgba(0,0,0,.4); }
	.dfp-case .dfp-cap h3{ margin:0 0 10px; font-size:1.08rem; letter-spacing:-.02em; }
	.dfp-case .dfp-cap p{ margin:0; font-size:.96rem; line-height:1.7; color:var(--df-muted,#aab5b9); }

	/* --- Approfondimenti tecnici: lista numerata --- */
	.dfp-case .dfp-hl-list{ display:flex; flex-direction:column; gap:clamp(20px,3vw,30px); margin-top:clamp(22px,3.5vw,36px); }
	.dfp-case .dfp-hl-item{ display:grid; grid-template-columns:auto 1fr; gap:clamp(16px,3vw,30px); padding:clamp(20px,3vw,30px); border:1px solid var(--df-line,rgba(255,255,255,.10)); border-radius:18px; background:rgba(255,255,255,.02); }
	.dfp-case .dfp-hl-num{ font-family:var(--df-mono,monospace); font-size:clamp(1.4rem,2.6vw,2rem); font-weight:700; color:transparent; -webkit-text-stroke:1px var(--dfp-accent,#2ee7f2); line-height:1; padding-top:4px; }
	.dfp-case .dfp-hl-body h3{ margin:0 0 14px; font-size:clamp(1.14rem,1.9vw,1.4rem); letter-spacing:-.025em; }
	.dfp-case .dfp-hl-row{ margin:0 0 12px; font-size:.98rem; line-height:1.7; color:var(--df-muted,#aab5b9); }
	.dfp-case .dfp-hl-row strong{ display:block; font-size:.68rem; font-weight:900; letter-spacing:.16em; text-transform:uppercase; color:var(--dfp-accent,#2ee7f2); margin-bottom:3px; }
	.dfp-case .dfp-hl-tech{ display:flex; flex-wrap:wrap; gap:6px; margin-top:14px; }

	/* --- Architettura: pannello tipo cockpit --- */
	.dfp-case .dfp-arch-panel{ border:1px solid var(--df-line-2,rgba(255,255,255,.18)); border-radius:18px; overflow:hidden; background:rgba(255,255,255,.02); box-shadow:var(--df-shadow-dark,0 22px 70px rgba(0,0,0,.34)); }
	.dfp-case .dfp-arch-head{ display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-bottom:1px solid var(--df-line,rgba(255,255,255,.10)); font-family:var(--df-mono,monospace); font-size:.78rem; letter-spacing:.1em; }
	.dfp-case .dfp-arch-head span{ color:var(--df-dim,#6f7a82); }
	.dfp-case .dfp-arch-head strong{ color:var(--dfp-accent,#2ee7f2); font-weight:700; }
	.dfp-case .dfp-arch-body{ padding:clamp(20px,3vw,30px); }
	.dfp-case .dfp-arch-body p{ margin:0 0 14px; font-size:.99rem; line-height:1.75; color:var(--df-muted,#aab5b9); }
	.dfp-case .dfp-arch-body p:last-child{ margin-bottom:0; }

	/* --- Tag del case study --- */
	.dfp-case .dfp-tags-row{ display:flex; flex-wrap:wrap; gap:8px; }

	/* --- Caso studio: blocchi alternati foto/testo --- */
	.dfp-case .dfp-blocks{ display:flex; flex-direction:column; gap:clamp(30px,6vw,72px); }
	.dfp-case .dfp-block{ display:grid; grid-template-columns:1.02fr .98fr; gap:clamp(22px,4vw,54px); align-items:center; }
	.dfp-case .dfp-block--solo{ grid-template-columns:1fr; max-width:760px; margin-inline:auto; text-align:center; }
	.dfp-case .dfp-block:nth-child(even):not(.dfp-block--solo) .dfp-block-media{ order:2; }
	.dfp-case .dfp-block-media{ border-radius:18px; overflow:hidden; border:1px solid var(--df-line,rgba(255,255,255,.10)); box-shadow:var(--df-shadow-dark,0 22px 70px rgba(0,0,0,.34)); }
	.dfp-case .dfp-block-media img{ display:block; width:100%; height:100%; object-fit:cover; aspect-ratio:4/3; }
	.dfp-case .dfp-block-label{ display:inline-flex; align-items:center; gap:10px; font-size:.72rem; font-weight:900; letter-spacing:.16em; text-transform:uppercase; color:var(--df-muted,#aab5b9); margin-bottom:14px; }
	.dfp-case .dfp-block-label > span{ width:24px; height:2px; border-radius:2px; background:var(--dfp-accent,#2ee7f2); box-shadow:0 0 8px var(--dfp-accent,#2ee7f2); }
	.dfp-case .dfp-block-text p{ margin:0; font-size:clamp(1rem,1.6vw,1.12rem); line-height:1.75; color:var(--df-text,#f7faf9); }
	.dfp-case .dfp-block--solo .dfp-block-label{ justify-content:center; }

	/* --- Caso studio: griglia galleria --- */
	.dfp-case .dfp-case-gallery .df-kicker{ margin-bottom:clamp(16px,3vw,26px); }
	.dfp-case .dfp-gallery-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:14px; }
	.dfp-case .dfp-gallery-grid figure{ margin:0; border-radius:14px; overflow:hidden; border:1px solid var(--df-line,rgba(255,255,255,.10)); background:rgba(255,255,255,.02); transition:border-color .3s ease; }
	.dfp-case .dfp-gallery-grid figure:hover{ border-color:rgba(46,231,242,.4); }
	.dfp-case .dfp-gallery-grid img{ display:block; width:100%; height:100%; object-fit:cover; aspect-ratio:4/3; transition:transform .5s cubic-bezier(.22,.61,.36,1); }
	.dfp-case .dfp-gallery-grid figure:hover img{ transform:scale(1.04); }
	.dfp-case .dfp-gallery-grid figcaption{ display:flex; align-items:center; gap:8px; padding:11px 14px 13px; font-size:.83rem; line-height:1.4; color:var(--df-muted,#aab5b9); border-top:1px solid var(--df-line,rgba(255,255,255,.08)); }
	.dfp-case .dfp-gallery-grid figcaption:before{ content:""; flex:none; width:14px; height:1px; background:var(--dfp-accent,#2ee7f2); }
	.dfp-case .dfp-gallery-grid figure:hover figcaption{ color:var(--df-text,#f7faf9); }

	@media (max-width:781px){
		.dfp-case .dfp-block{ grid-template-columns:1fr; gap:18px; }
		.dfp-case .dfp-block:nth-child(even):not(.dfp-block--solo) .dfp-block-media{ order:0; }
		.dfp-case .dfp-hl-item{ grid-template-columns:1fr; gap:10px; }
	}
</style>

<?php
get_footer();
