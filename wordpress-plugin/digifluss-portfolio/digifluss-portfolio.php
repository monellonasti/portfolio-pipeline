<?php
/**
 * Plugin Name:       Digifluss Portfolio
 * Description:        Portfolio a carosello — Custom Post Type "Progetti" + shortcode [digifluss_portfolio]. Integrato col tema Digifluss (auto-detect): testata, bottoni e template singolo/archivio riusano i componenti del tema. Su altri temi mantiene fallback autonomi.
 * Version:           1.11.6
 * Author:            Digifluss
 * Author URI:        https://digifluss.it
 * Text Domain:       digifluss-portfolio
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package DigiflussPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'DIGIFLUSS_PORTFOLIO_VERSION' ) ) { define( 'DIGIFLUSS_PORTFOLIO_VERSION', '1.11.3' ); }
if ( ! defined( 'DIGIFLUSS_PORTFOLIO_URL' ) )     { define( 'DIGIFLUSS_PORTFOLIO_URL', plugin_dir_url( __FILE__ ) ); }
if ( ! defined( 'DIGIFLUSS_PORTFOLIO_PATH' ) )    { define( 'DIGIFLUSS_PORTFOLIO_PATH', plugin_dir_path( __FILE__ ) ); }

/* -----------------------------------------------------------------
 * 0. Helpers
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_theme_active' ) ) {
	/**
	 * True quando è attivo il tema Digifluss (o un child basato su di esso).
	 * Permette al plugin di "fondersi" col tema invece di vivere in una sua sezione.
	 */
	function dfp_theme_active() {
		$theme = wp_get_theme();
		if ( ! $theme ) { return false; }
		$slugs = array( strtolower( (string) $theme->get_template() ), strtolower( (string) $theme->get_stylesheet() ) );
		foreach ( $slugs as $s ) {
			if ( false !== strpos( $s, 'digifluss' ) ) {
				return true;
			}
		}
		$td = strtolower( (string) $theme->get( 'TextDomain' ) );
		return ( 'digifluss' === $td );
	}
}

if ( ! function_exists( 'dfp_image_caption' ) ) {
	/**
	 * Didascalia breve di un'immagine: didascalia della media library,
	 * altrimenti testo alternativo, altrimenti il nome del file reso
	 * leggibile ("04-collection-badges-desktop" → "Collection badges desktop").
	 */
	function dfp_image_caption( $attachment_id, $strip_slug = '' ) {
		$caption = wp_get_attachment_caption( $attachment_id );
		if ( $caption ) { return $caption; }
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( $alt ) { return $alt; }

		$slug = get_post_field( 'post_name', $attachment_id );
		if ( ! $slug ) { return ''; }
		$words = preg_split( '/[-_]+/', $slug );

		// Parole da scartare: numerazione, suffissi di WordPress e i pezzi
		// del nome del progetto (il file è già dentro quel case study).
		$noise = array( 'scaled', 'copy', 'final', 'png', 'jpg', 'jpeg', 'webp' );
		if ( $strip_slug ) {
			$noise = array_merge( $noise, preg_split( '/[-_]+/', $strip_slug ) );
			$noise[] = str_replace( '-', '', $strip_slug );
		}
		$keep = array();
		foreach ( $words as $w ) {
			$w = strtolower( trim( $w ) );
			if ( '' === $w ) { continue; }
			if ( preg_match( '/^\d+$/', $w ) ) { continue; }        // 01, 03…
			if ( preg_match( '/^\d+x\d+$/', $w ) ) { continue; }    // 1024x640
			if ( in_array( $w, $noise, true ) ) { continue; }
			$keep[] = $w;
		}
		$text = trim( implode( ' ', $keep ) );
		return $text ? ucfirst( $text ) : '';
	}
}

if ( ! function_exists( 'dfp_json_meta' ) ) {
	/**
	 * Legge un meta salvato come JSON e restituisce sempre un array di righe.
	 */
	function dfp_json_meta( $post_id, $key ) {
		$raw = get_post_meta( $post_id, $key, true );
		if ( ! $raw ) { return array(); }
		$data = json_decode( (string) $raw, true );
		return is_array( $data ) ? $data : array();
	}
}

if ( ! function_exists( 'dfp_paragraphs' ) ) {
	/**
	 * Trasforma testo multiriga in paragrafi <p> con escape.
	 * I paragrafi si separano con una riga vuota; i blocchi le cui righe
	 * iniziano con "- " diventano elenchi puntati <ul>.
	 */
	function dfp_paragraphs( $text ) {
		$out = '';
		foreach ( preg_split( '/\n\s*\n/', trim( (string) $text ) ) as $para ) {
			$para = trim( $para );
			if ( '' === $para ) { continue; }
			$lines = array_map( 'trim', explode( "\n", $para ) );
			$is_list = true;
			foreach ( $lines as $ln ) {
				if ( '' !== $ln && 0 !== strpos( $ln, '- ' ) ) { $is_list = false; break; }
			}
			if ( $is_list ) {
				$out .= '<ul>';
				foreach ( $lines as $ln ) {
					if ( '' === $ln ) { continue; }
					$out .= '<li>' . esc_html( ltrim( substr( $ln, 2 ) ) ) . '</li>';
				}
				$out .= '</ul>';
				continue;
			}
			// Elenco introdotto da una riga di testo nello stesso blocco.
			$intro = array();
			$items = array();
			foreach ( $lines as $ln ) {
				if ( 0 === strpos( $ln, '- ' ) ) { $items[] = ltrim( substr( $ln, 2 ) ); }
				elseif ( empty( $items ) ) { $intro[] = $ln; }
				else { $items[ count( $items ) - 1 ] .= ' ' . $ln; }
			}
			if ( $items ) {
				if ( $intro ) { $out .= '<p>' . nl2br( esc_html( implode( "\n", $intro ) ) ) . '</p>'; }
				$out .= '<ul>';
				foreach ( $items as $item ) { $out .= '<li>' . esc_html( $item ) . '</li>'; }
				$out .= '</ul>';
			} else {
				$out .= '<p>' . nl2br( esc_html( $para ) ) . '</p>';
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'dfp_locales' ) ) {
	/**
	 * Lingue di destinazione delle traduzioni (il contenuto sorgente è in italiano).
	 * Chiave = locale WordPress, valore = nome della lingua per il modello AI.
	 */
	function dfp_locales() {
		return array(
			'en_US' => 'English',
			'de_DE' => 'German',
			'fr_FR' => 'French',
			'es_ES' => 'Spanish',
			'pt_PT' => 'European Portuguese',
		);
	}
}

if ( ! function_exists( 'dfp_case_fields' ) ) {
	/**
	 * Tutti i contenuti traducibili di un progetto, in un'unica struttura.
	 * Esclude deliberatamente: titolo del post (nome del progetto), cliente,
	 * anno, stack e integrazioni (nomi propri e termini tecnici).
	 */
	function dfp_case_fields( $pid ) {
		$fields = array(
			'excerpt'      => get_post_field( 'post_excerpt', $pid ),
			'category'     => get_post_meta( $pid, '_dfp_category', true ),
			'role'         => get_post_meta( $pid, '_dfp_role', true ),
			'industry'     => get_post_meta( $pid, '_dfp_industry', true ),
			'product'      => get_post_meta( $pid, '_dfp_product', true ),
			'team_role'    => get_post_meta( $pid, '_dfp_team_role', true ),
			'timeline'     => get_post_meta( $pid, '_dfp_timeline', true ),
			'tags'         => get_post_meta( $pid, '_dfp_tags', true ),
			'cs_title'     => get_post_meta( $pid, '_dfp_cs_title', true ),
			'cs_subtitle'  => get_post_meta( $pid, '_dfp_cs_subtitle', true ),
			'overview'     => get_post_meta( $pid, '_dfp_overview', true ),
			'story'        => get_post_meta( $pid, '_dfp_story', true ),
			'architecture' => get_post_meta( $pid, '_dfp_architecture', true ),
			'challenge'    => get_post_meta( $pid, '_dfp_challenge', true ),
			'approach'     => get_post_meta( $pid, '_dfp_approach', true ),
			'result'       => get_post_meta( $pid, '_dfp_result', true ),
			'design_intro' => get_post_meta( $pid, '_dfp_design_intro', true ),
			'capabilities'  => dfp_json_meta( $pid, '_dfp_capabilities' ),
			'highlights'    => dfp_json_meta( $pid, '_dfp_highlights' ),
			'design_blocks' => dfp_json_meta( $pid, '_dfp_design_blocks' ),
		);
		return array_filter( $fields, function ( $v ) {
			return is_array( $v ) ? ! empty( $v ) : '' !== trim( (string) $v );
		} );
	}
}

if ( ! function_exists( 'dfp_translation' ) ) {
	/**
	 * Traduzione del progetto per il locale corrente, o null se il locale
	 * è italiano o la traduzione non esiste.
	 */
	function dfp_translation( $pid ) {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return dfp_translation_for_locale( $pid, $locale );
	}
}

if ( ! function_exists( 'dfp_translation_for_locale' ) ) {
	/**
	 * Legge una traduzione senza dipendere dal locale della richiesta.
	 */
	function dfp_translation_for_locale( $pid, $locale ) {
		if ( 0 === strpos( (string) $locale, 'it' ) ) { return null; }
		if ( ! isset( dfp_locales()[ $locale ] ) ) { return null; }
		$raw = get_post_meta( $pid, '_dfp_tr_' . $locale, true );
		if ( ! $raw ) { return null; }
		$data = json_decode( (string) $raw, true );
		return ( is_array( $data ) && $data ) ? $data : null;
	}
}

if ( ! function_exists( 'dfp_translation_value_complete' ) ) {
	/**
	 * Verifica ricorsivamente che ogni valore italiano compilato abbia una
	 * controparte tradotta non vuota. Conserva la stessa struttura per liste,
	 * highlight e blocchi di design.
	 */
	function dfp_translation_value_complete( $source, $translated ) {
		if ( is_array( $source ) ) {
			if ( empty( $source ) ) { return true; }
			if ( ! is_array( $translated ) ) { return false; }
			foreach ( $source as $key => $value ) {
				if ( ! array_key_exists( $key, $translated ) ) {
					if ( dfp_translation_value_complete( $value, null ) ) { continue; }
					return false;
				}
				if ( ! dfp_translation_value_complete( $value, $translated[ $key ] ) ) {
					return false;
				}
			}
			return true;
		}

		if ( '' === trim( (string) $source ) ) { return true; }
		return is_scalar( $translated ) && '' !== trim( (string) $translated );
	}
}

if ( ! function_exists( 'dfp_translation_complete' ) ) {
	/**
	 * Una variante è pubblicabile solo quando tutti i campi sorgente valorizzati
	 * sono stati tradotti. Il risultato è memorizzato per la durata della request.
	 */
	function dfp_translation_complete( $pid, $locale ) {
		static $cache = array();
		$key = absint( $pid ) . ':' . (string) $locale;
		if ( array_key_exists( $key, $cache ) ) { return $cache[ $key ]; }
		if ( 0 === strpos( (string) $locale, 'it' ) ) { return $cache[ $key ] = true; }

		$translated = dfp_translation_for_locale( $pid, $locale );
		if ( ! is_array( $translated ) ) { return $cache[ $key ] = false; }

		foreach ( dfp_case_fields( $pid ) as $field => $source ) {
			if ( ! array_key_exists( $field, $translated ) || ! dfp_translation_value_complete( $source, $translated[ $field ] ) ) {
				return $cache[ $key ] = false;
			}
		}
		return $cache[ $key ] = true;
	}
}

if ( ! function_exists( 'dfp_current_locale_has_complete_translation' ) ) {
	function dfp_current_locale_has_complete_translation( $pid ) {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return dfp_translation_complete( $pid, $locale );
	}
}

if ( ! function_exists( 'dfp_available_language_codes' ) ) {
	/**
	 * Comunica al tema quali URL lingua possono entrare in switcher, hreflang,
	 * sitemap e llms.txt per ogni progetto.
	 */
	function dfp_available_language_codes( $codes, $post_id, $post_type ) {
		if ( 'df_progetto' !== $post_type || ! $post_id ) { return $codes; }
		$locale_by_code = array(
			'en' => 'en_US',
			'de' => 'de_DE',
			'fr' => 'fr_FR',
			'es' => 'es_ES',
			'pt' => 'pt_PT',
		);
		$available = array( 'it' );
		foreach ( $locale_by_code as $code => $locale ) {
			if ( dfp_translation_complete( $post_id, $locale ) ) {
				$available[] = $code;
			}
		}
		return $available;
	}
	add_filter( 'digifluss_available_language_codes', 'dfp_available_language_codes', 10, 3 );
}

if ( ! function_exists( 'dfp_load_textdomain' ) ) {
	function dfp_load_textdomain() {
		load_plugin_textdomain( 'digifluss-portfolio', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	add_action( 'init', 'dfp_load_textdomain' );
}

/* -----------------------------------------------------------------
 * 1. Layout copertina disponibili (mock-up d'interfaccia)
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_motifs' ) ) {
	function dfp_motifs() {
		return array(
			'landing'   => __( 'Sito vetrina / Landing', 'digifluss-portfolio' ),
			'corporate' => __( 'Sito aziendale / istituzionale', 'digifluss-portfolio' ),
			'shop'      => __( 'E-commerce', 'digifluss-portfolio' ),
			'dashboard' => __( 'Web app / Dashboard', 'digifluss-portfolio' ),
			'code'      => __( 'Sviluppo software', 'digifluss-portfolio' ),
			'phone'     => __( 'App mobile', 'digifluss-portfolio' ),
			'flow'      => __( 'Automazione / AI', 'digifluss-portfolio' ),
			'seo'       => __( 'SEO / Marketing', 'digifluss-portfolio' ),
			'ds'        => __( 'Branding / Design system', 'digifluss-portfolio' ),
		);
	}
}

/* -----------------------------------------------------------------
 * 2. Custom Post Type
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_register_cpt' ) ) {
	function dfp_register_cpt() {
		$labels = array(
			'name'          => __( 'Progetti', 'digifluss-portfolio' ),
			'singular_name' => __( 'Progetto', 'digifluss-portfolio' ),
			'menu_name'     => __( 'Portfolio', 'digifluss-portfolio' ),
			'add_new'       => __( 'Aggiungi progetto', 'digifluss-portfolio' ),
			'add_new_item'  => __( 'Aggiungi nuovo progetto', 'digifluss-portfolio' ),
			'edit_item'     => __( 'Modifica progetto', 'digifluss-portfolio' ),
			'new_item'      => __( 'Nuovo progetto', 'digifluss-portfolio' ),
			'view_item'     => __( 'Vedi progetto', 'digifluss-portfolio' ),
			'search_items'  => __( 'Cerca progetti', 'digifluss-portfolio' ),
			'not_found'     => __( 'Nessun progetto', 'digifluss-portfolio' ),
			'all_items'     => __( 'Tutti i progetti', 'digifluss-portfolio' ),
		);

		register_post_type(
			'df_progetto',
			array(
				'labels'        => $labels,
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-portfolio',
				'menu_position' => 22,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
				'rewrite'       => array( 'slug' => 'progetti' ),
				'show_in_rest'  => true,
			)
		);
	}
	add_action( 'init', 'dfp_register_cpt' );

	// Autoriparazione: se le regole vengono rigenerate in una richiesta in
	// cui il plugin non è caricato (aggiornamenti automatici, WP-CLI con
	// --skip-plugins) l'archivio e i casi studio vanno in 404. Se manca la
	// regola dell'archivio, le rigeneriamo una volta: il controllo legge
	// un'opzione già in memoria e non costa nulla nelle richieste normali.
	add_action( 'init', function () {
		$rules = get_option( 'rewrite_rules' );
		$type  = get_post_type_object( 'df_progetto' );
		if ( ! is_array( $rules ) || ! $type || empty( $type->rewrite['slug'] ) ) {
			return;
		}
		if ( ! isset( $rules[ $type->rewrite['slug'] . '/?$' ] ) ) {
			flush_rewrite_rules( false );
		}
	}, 99 );
}

/* -----------------------------------------------------------------
 * 2bis. Editor classico per df_progetto
 *      I campi custom ("Dettagli progetto") sono il fulcro dell'editing:
 *      con Gutenberg vengono retrocessi in una sezione collassata in fondo.
 *      Con il classic editor riappaiono inline subito sotto l'editor.
 *      REST resta abilitata (show_in_rest=true) per integrazioni esterne.
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_disable_gutenberg' ) ) {
	function dfp_disable_gutenberg( $enabled, $post_type ) {
		if ( 'df_progetto' === $post_type ) {
			return false;
		}
		return $enabled;
	}
	add_filter( 'use_block_editor_for_post_type', 'dfp_disable_gutenberg', 10, 2 );
}

/* -----------------------------------------------------------------
 * 3. Pannello campi (meta box)
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_add_metabox' ) ) {
	function dfp_add_metabox() {
		add_meta_box(
			'dfp_details',
			__( 'Dettagli progetto', 'digifluss-portfolio' ),
			'dfp_render_metabox',
			'df_progetto',
			'normal',
			'high'
		);
	}
	add_action( 'add_meta_boxes', 'dfp_add_metabox' );
}

if ( ! function_exists( 'dfp_render_metabox' ) ) {
	function dfp_render_metabox( $post ) {
		wp_nonce_field( 'dfp_save', 'dfp_nonce' );

		$category = get_post_meta( $post->ID, '_dfp_category', true );
		$year     = get_post_meta( $post->ID, '_dfp_year', true );
		$role     = get_post_meta( $post->ID, '_dfp_role', true );
		$stack    = get_post_meta( $post->ID, '_dfp_stack', true );
		$link     = get_post_meta( $post->ID, '_dfp_link', true );
		$motif    = get_post_meta( $post->ID, '_dfp_motif', true );
		$accent   = get_post_meta( $post->ID, '_dfp_accent', true );
		$accent2  = get_post_meta( $post->ID, '_dfp_accent2', true );
		$client       = get_post_meta( $post->ID, '_dfp_client', true );
		$integrations = get_post_meta( $post->ID, '_dfp_integrations', true );
		$challenge = get_post_meta( $post->ID, '_dfp_challenge', true );
		$approach  = get_post_meta( $post->ID, '_dfp_approach', true );
		$result    = get_post_meta( $post->ID, '_dfp_result', true );
		$cs_title    = get_post_meta( $post->ID, '_dfp_cs_title', true );
		$cs_subtitle = get_post_meta( $post->ID, '_dfp_cs_subtitle', true );
		$industry    = get_post_meta( $post->ID, '_dfp_industry', true );
		$product     = get_post_meta( $post->ID, '_dfp_product', true );
		$team_role   = get_post_meta( $post->ID, '_dfp_team_role', true );
		$timeline    = get_post_meta( $post->ID, '_dfp_timeline', true );
		$overview    = get_post_meta( $post->ID, '_dfp_overview', true );
		$story       = get_post_meta( $post->ID, '_dfp_story', true );
		$architecture = get_post_meta( $post->ID, '_dfp_architecture', true );
		$tags         = get_post_meta( $post->ID, '_dfp_tags', true );
		$capabilities = dfp_json_meta( $post->ID, '_dfp_capabilities' );
		$highlights   = dfp_json_meta( $post->ID, '_dfp_highlights' );
		$design_intro  = get_post_meta( $post->ID, '_dfp_design_intro', true );
		$design_blocks = dfp_json_meta( $post->ID, '_dfp_design_blocks' );
		$gallery_ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_dfp_gallery', true ) ) ) ) );
		if ( ! $motif ) { $motif = 'dashboard'; }
		if ( ! $accent ) { $accent = '#2ee7f2'; }
		?>
		<style>
			.dfp-fields{ display:grid; grid-template-columns:1fr 1fr; gap:16px 22px; margin-top:6px; }
			.dfp-fields .full{ grid-column:1 / -1; }
			.dfp-fields label{ display:block; font-weight:600; margin-bottom:5px; }
			.dfp-fields input[type=text], .dfp-fields input[type=url], .dfp-fields select, .dfp-fields textarea{ width:100%; }
			.dfp-fields textarea{ resize:vertical; min-height:64px; }
			.dfp-fields .desc{ color:#646970; font-size:12px; margin-top:5px; }
			.dfp-sep{ margin-top:10px; padding-top:16px; border-top:1px solid #dcdcde; }
			.dfp-sep span{ font-weight:700; font-size:13px; color:#1d2327; letter-spacing:.02em; }
			#dfp-gallery .dfp-gallery-list{ display:flex; flex-wrap:wrap; gap:8px; margin:6px 0 10px; }
			#dfp-gallery .dfp-gthumb{ position:relative; width:80px; height:80px; border-radius:6px; overflow:hidden; border:1px solid #dcdcde; background:#f0f0f1; cursor:grab; }
			#dfp-gallery .dfp-gthumb.is-dragging{ opacity:.45; outline:2px dashed #2271b1; }
			#dfp-gallery .dfp-gthumb img{ width:100%; height:100%; object-fit:cover; display:block; }
			#dfp-gallery .dfp-gremove{ position:absolute; top:2px; right:2px; width:18px; height:18px; line-height:15px; padding:0; border:0; border-radius:50%; background:rgba(0,0,0,.62); color:#fff; cursor:pointer; font-size:14px; }
			.dfp-ai-row{ display:flex; align-items:center; gap:10px; margin-top:9px; }
			.dfp-ai-status{ font-size:12px; color:#646970; }
			.dfp-ai-out{ display:flex; flex-wrap:wrap; gap:6px; margin-top:9px; }
			.dfp-ai-out .dfp-ai-chip{ cursor:pointer; font-size:12px; padding:4px 11px; border-radius:999px; border:1px solid #c3c4c7; background:#fff; }
			.dfp-ai-out .dfp-ai-chip:hover{ border-color:#2271b1; color:#2271b1; }
			.dfp-ai-out .dfp-ai-all{ cursor:pointer; font-size:12px; padding:4px 11px; border-radius:999px; border:1px dashed #2271b1; background:#f0f6fc; color:#2271b1; }
			.dfp-ai-out .dfp-ai-err{ color:#b32d2e; font-size:12px; }
			.dfp-repeat{ display:flex; flex-direction:column; gap:10px; margin-bottom:10px; }
			.dfp-repeat-row{ position:relative; display:flex; flex-direction:column; gap:7px; padding:12px 12px 34px; border:1px solid #dcdcde; border-radius:8px; background:#fafafa; }
			.dfp-repeat-row input[type=text], .dfp-repeat-row textarea{ width:100%; }
			.dfp-repeat-remove{ position:absolute; right:12px; bottom:8px; color:#b32d2e !important; text-decoration:none; cursor:pointer; font-size:12px; }
			.dfp-tr-panel{ display:flex; flex-direction:column; gap:12px; }
			.dfp-tr-langs{ display:flex; gap:8px; flex-wrap:wrap; }
			.dfp-tr-lang{ display:inline-flex; align-items:center; gap:7px; padding:6px 12px 6px 9px; border-radius:999px; border:1px solid #c3c4c7; background:#fff; cursor:pointer; }
			.dfp-tr-lang b{ font-size:12px; letter-spacing:.06em; color:#1d2327; }
			.dfp-tr-lang .dfp-tr-state{ font-size:11.5px; color:#646970; }
			.dfp-tr-lang.is-done{ border-color:#00631f; background:#f4fbf5; }
			.dfp-tr-lang.is-done .dfp-tr-state{ color:#00631f; }
			.dfp-tr-lang.is-busy{ border-color:#2271b1; background:#f0f6fc; }
			.dfp-tr-lang.is-busy .dfp-tr-state{ color:#2271b1; }
			.dfp-tr-lang.is-err{ border-color:#b32d2e; background:#fcf0f1; }
			.dfp-tr-lang.is-err .dfp-tr-state{ color:#b32d2e; }
			.dfp-tr-lang input{ margin:0; }
			.dfp-tr-actions{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
			.dfp-tr-status{ font-size:12.5px; color:#646970; }
			.dfp-tr-progress{ max-width:520px; }
			.dfp-tr-bar{ height:6px; border-radius:6px; background:#e0e0e1; overflow:hidden; }
			.dfp-tr-bar i{ display:block; height:100%; width:0; border-radius:6px; background:linear-gradient(90deg,#2271b1,#00a32a); transition:width .35s ease; }
			.dfp-tr-meta{ display:flex; justify-content:space-between; margin-top:5px; font-size:11.5px; color:#646970; font-variant-numeric:tabular-nums; }
			.dfp-import{ padding:12px 14px; border:1px dashed #8c8f94; border-radius:8px; background:#f6f7f7; }
			.dfp-import label{ margin-bottom:8px; }
			.dfp-md-status{ margin-top:8px; font-size:12.5px; }
			.dfp-md-status.ok{ color:#00631f; }
			.dfp-md-status.err{ color:#b32d2e; }
		</style>
		<div class="dfp-fields">
			<p>
				<label for="dfp_category"><?php esc_html_e( 'Categoria', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_category" name="_dfp_category" value="<?php echo esc_attr( $category ); ?>" placeholder="<?php esc_attr_e( 'Es. Dashboard SaaS', 'digifluss-portfolio' ); ?>">
			</p>
			<p>
				<label for="dfp_year"><?php esc_html_e( 'Anno', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_year" name="_dfp_year" value="<?php echo esc_attr( $year ); ?>" placeholder="2025">
			</p>
			<p>
				<label for="dfp_client"><?php esc_html_e( 'Cliente / Settore', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_client" name="_dfp_client" value="<?php echo esc_attr( $client ); ?>" placeholder="<?php esc_attr_e( 'Es. PMI manifatturiera (o nome, se pubblicabile)', 'digifluss-portfolio' ); ?>">
				<span class="desc"><?php esc_html_e( 'Se il nome non è pubblicabile, indica il settore.', 'digifluss-portfolio' ); ?></span>
			</p>
			<p>
				<label for="dfp_role"><?php esc_html_e( 'Obiettivo', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_role" name="_dfp_role" value="<?php echo esc_attr( $role ); ?>" placeholder="<?php esc_attr_e( 'Es. Aumentare le conversioni', 'digifluss-portfolio' ); ?>">
			</p>
			<p>
				<label for="dfp_motif"><?php esc_html_e( 'Layout copertina', 'digifluss-portfolio' ); ?></label>
				<select id="dfp_motif" name="_dfp_motif">
					<?php foreach ( dfp_motifs() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $motif, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="desc"><?php esc_html_e( 'Ignorato se imposti un\'immagine in evidenza.', 'digifluss-portfolio' ); ?></span>
			</p>
			<p class="full">
				<label for="dfp_stack"><?php esc_html_e( 'Stack tecnologico', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_stack" name="_dfp_stack" value="<?php echo esc_attr( $stack ); ?>" placeholder="React, TypeScript, Node, Postgres">
				<span class="desc"><?php esc_html_e( 'Tecnologie separate da virgola, mostrate come chip.', 'digifluss-portfolio' ); ?></span>
				<span class="dfp-ai-row">
					<button type="button" class="button dfp-ai-suggest"><?php esc_html_e( '✨ Suggerisci con AI', 'digifluss-portfolio' ); ?></button>
					<span class="dfp-ai-status" aria-live="polite"></span>
				</span>
				<div class="dfp-ai-out"></div>
			</p>
			<p class="full">
				<label for="dfp_integrations"><?php esc_html_e( 'Integrazioni', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_integrations" name="_dfp_integrations" value="<?php echo esc_attr( $integrations ); ?>" placeholder="<?php esc_attr_e( 'Es. HubSpot, SAP, Stripe, API corrieri', 'digifluss-portfolio' ); ?>">
				<span class="desc"><?php esc_html_e( 'Sistemi collegati nel progetto, separati da virgola, mostrati come chip.', 'digifluss-portfolio' ); ?></span>
			</p>
			<p class="full">
				<label for="dfp_link"><?php esc_html_e( 'Link al sito live del progetto (opzionale)', 'digifluss-portfolio' ); ?></label>
				<input type="url" id="dfp_link" name="_dfp_link" value="<?php echo esc_attr( $link ); ?>" placeholder="https://...">
				<span class="desc"><?php esc_html_e( 'Mostrato come bottone "Vai al sito live" nella pagina del caso di studio. Le card portano sempre al caso di studio interno.', 'digifluss-portfolio' ); ?></span>
			</p>
			<p>
				<label for="dfp_accent"><?php esc_html_e( 'Colore accento', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_accent" name="_dfp_accent" value="<?php echo esc_attr( $accent ); ?>" placeholder="#2ee7f2">
				<span class="desc"><?php esc_html_e( 'Brand: cyan #2ee7f2 · mint #81f0b2 · amber #ffb84d', 'digifluss-portfolio' ); ?></span>
			</p>
			<p>
				<label for="dfp_accent2"><?php esc_html_e( 'Colore accento 2 (opzionale)', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_accent2" name="_dfp_accent2" value="<?php echo esc_attr( $accent2 ); ?>" placeholder="#81f0b2">
				<span class="desc"><?php esc_html_e( 'Per le sfumature. Se vuoto usa l\'accento principale.', 'digifluss-portfolio' ); ?></span>
			</p>

			<div class="full dfp-sep"><span><?php esc_html_e( 'Caso studio (pagina progetto)', 'digifluss-portfolio' ); ?></span></div>
			<p class="full">
				<label for="dfp_challenge"><?php esc_html_e( 'La sfida', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_challenge" name="_dfp_challenge" rows="3" placeholder="<?php esc_attr_e( 'Il problema o il contesto di partenza del cliente.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $challenge ); ?></textarea>
			</p>
			<p class="full">
				<label for="dfp_approach"><?php esc_html_e( 'L\'approccio', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_approach" name="_dfp_approach" rows="3" placeholder="<?php esc_attr_e( 'Come avete affrontato la sfida: strategia, design, sviluppo.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $approach ); ?></textarea>
			</p>
			<p class="full">
				<label for="dfp_result"><?php esc_html_e( 'Il risultato', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_result" name="_dfp_result" rows="3" placeholder="<?php esc_attr_e( 'L\'esito: numeri, impatto, cosa è cambiato.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $result ); ?></textarea>
			</p>
			<div class="full">
				<label><?php esc_html_e( 'Galleria immagini', 'digifluss-portfolio' ); ?></label>
				<div id="dfp-gallery">
					<div class="dfp-gallery-list">
						<?php foreach ( $gallery_ids as $gid ) :
							$thumb = wp_get_attachment_image_url( $gid, 'thumbnail' );
							if ( ! $thumb ) { continue; }
							?>
							<div class="dfp-gthumb" data-id="<?php echo esc_attr( $gid ); ?>">
								<img src="<?php echo esc_url( $thumb ); ?>" alt="">
								<button type="button" class="dfp-gremove" aria-label="<?php esc_attr_e( 'Rimuovi', 'digifluss-portfolio' ); ?>">&times;</button>
							</div>
						<?php endforeach; ?>
					</div>
					<button type="button" class="button dfp-gallery-add"><?php esc_html_e( 'Aggiungi immagini', 'digifluss-portfolio' ); ?></button>
					<input type="hidden" id="dfp_gallery" name="_dfp_gallery" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">
				</div>
				<span class="desc" style="display:block;">
					<strong><?php esc_html_e( 'Ordine di caricamento (con gli screenshot del file .md):', 'digifluss-portfolio' ); ?></strong><br>
					<?php esc_html_e( '· Immagine in evidenza (box a destra) = lo screenshot HERO del case study (01-homepage-hero).', 'digifluss-portfolio' ); ?><br>
					<?php esc_html_e( '· 1ª immagine della galleria → affianca la sezione "Il prodotto" (es. 02-homepage-hero-mobile).', 'digifluss-portfolio' ); ?><br>
					<?php esc_html_e( '· 2ª immagine → affianca la sezione "Design e identità visiva" (es. 03-programmes).', 'digifluss-portfolio' ); ?><br>
					<?php esc_html_e( '· 3ª, 4ª e 5ª → si alternano con sfida / approccio / risultato, solo se quei campi sono compilati.', 'digifluss-portfolio' ); ?><br>
					<?php esc_html_e( '· Tutte le rimanenti → griglia galleria in fondo alla pagina, nell\'ordine scelto qui.', 'digifluss-portfolio' ); ?><br>
					<?php esc_html_e( 'Ogni immagine è cliccabile a schermo intero (lightbox). Trascina le miniature qui sopra per riordinare: l\'ordine qui è l\'ordine in pagina.', 'digifluss-portfolio' ); ?>
				</span>
			</div>

			<div class="full dfp-sep"><span><?php esc_html_e( 'Case study approfondito (opzionale)', 'digifluss-portfolio' ); ?></span>
				<span class="desc" style="display:block;font-weight:400;margin-top:4px;"><?php esc_html_e( 'Compila questi campi per trasformare la pagina progetto in un case study professionale completo: hero dedicato, panoramica, capacità, approfondimenti tecnici e architettura. I campi vuoti non vengono mostrati.', 'digifluss-portfolio' ); ?></span>
			</div>

			<div class="full dfp-import">
				<label for="dfp_md_file"><?php esc_html_e( '⤓ Importa da file .md', 'digifluss-portfolio' ); ?></label>
				<input type="file" id="dfp_md_file" accept=".md,.markdown,.txt">
				<span class="desc" style="display:block;margin-top:6px;"><?php esc_html_e( 'Carica il documento di analisi del progetto (formato "PROJECT ANALYSIS / PORTFOLIO CASE STUDY"): i campi qui sotto — e titolo, riassunto, categoria, cliente, anno, stack, link — vengono compilati in automatico dalle sezioni del file. Nulla viene salvato finché non premi Aggiorna: controlla e correggi liberamente.', 'digifluss-portfolio' ); ?></span>
				<div class="dfp-md-status" aria-live="polite"></div>
			</div>
			<p class="full">
				<label for="dfp_cs_title"><?php esc_html_e( 'Titolo del case study', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_cs_title" name="_dfp_cs_title" value="<?php echo esc_attr( $cs_title ); ?>" placeholder="<?php esc_attr_e( 'Es. Uno store Shopify costruito attorno alla vendita assistita', 'digifluss-portfolio' ); ?>">
				<span class="desc"><?php esc_html_e( 'Se vuoto, la pagina usa il titolo del progetto.', 'digifluss-portfolio' ); ?></span>
			</p>
			<p class="full">
				<label for="dfp_cs_subtitle"><?php esc_html_e( 'Sottotitolo', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_cs_subtitle" name="_dfp_cs_subtitle" rows="2" placeholder="<?php esc_attr_e( 'Una frase che riassume il progetto: cosa, per chi, con quale differenza.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $cs_subtitle ); ?></textarea>
			</p>
			<p>
				<label for="dfp_industry"><?php esc_html_e( 'Settore (industry)', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_industry" name="_dfp_industry" value="<?php echo esc_attr( $industry ); ?>" placeholder="<?php esc_attr_e( 'Es. Wellness & Nutrition e-commerce', 'digifluss-portfolio' ); ?>">
			</p>
			<p>
				<label for="dfp_product"><?php esc_html_e( 'Tipo di prodotto', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_product" name="_dfp_product" value="<?php echo esc_attr( $product ); ?>" placeholder="<?php esc_attr_e( 'Es. Tema Shopify custom (Online Store 2.0)', 'digifluss-portfolio' ); ?>">
			</p>
			<p>
				<label for="dfp_team_role"><?php esc_html_e( 'Ruolo', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_team_role" name="_dfp_team_role" value="<?php echo esc_attr( $team_role ); ?>" placeholder="<?php esc_attr_e( 'Es. Design, sviluppo e handover', 'digifluss-portfolio' ); ?>">
			</p>
			<p>
				<label for="dfp_timeline"><?php esc_html_e( 'Tempi di realizzazione', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_timeline" name="_dfp_timeline" value="<?php echo esc_attr( $timeline ); ?>" placeholder="<?php esc_attr_e( 'Es. 14 giorni di realizzazione + 7 di revisione', 'digifluss-portfolio' ); ?>">
			</p>
			<p>
				<label for="dfp_tags"><?php esc_html_e( 'Tag del case study', 'digifluss-portfolio' ); ?></label>
				<input type="text" id="dfp_tags" name="_dfp_tags" value="<?php echo esc_attr( $tags ); ?>" placeholder="<?php esc_attr_e( 'Shopify, E-commerce, Vanilla JS, Design System', 'digifluss-portfolio' ); ?>">
				<span class="desc"><?php esc_html_e( 'Separati da virgola, mostrati come chip in fondo alla pagina.', 'digifluss-portfolio' ); ?></span>
			</p>
			<p class="full">
				<label for="dfp_overview"><?php esc_html_e( 'Panoramica (overview)', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_overview" name="_dfp_overview" rows="6" placeholder="<?php esc_attr_e( 'Il contesto e il progetto in 2-3 paragrafi: chi è il cliente, qual era il bisogno, cosa è stato costruito. Riga vuota = nuovo paragrafo.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $overview ); ?></textarea>
			</p>
			<p class="full">
				<label for="dfp_story"><?php esc_html_e( 'Il prodotto (racconto dell\'esperienza)', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_story" name="_dfp_story" rows="6" placeholder="<?php esc_attr_e( 'Cosa vede e cosa fa chi usa il prodotto, dal primo arrivo alla conversione. Riga vuota = nuovo paragrafo.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $story ); ?></textarea>
			</p>

			<p class="full">
				<label for="dfp_design_intro"><?php esc_html_e( 'Design e identità visiva — introduzione', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_design_intro" name="_dfp_design_intro" rows="3" placeholder="<?php esc_attr_e( 'La premessa del racconto di design: da quale domanda nasce l\'identità visiva. Riga vuota = nuovo paragrafo.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $design_intro ); ?></textarea>
			</p>
			<div class="full">
				<label><?php esc_html_e( 'Design e identità visiva — blocchi', 'digifluss-portfolio' ); ?></label>
				<span class="desc" style="display:block;margin-bottom:8px;"><?php esc_html_e( 'Le scelte di design raccontate una per una (es. "Perché il verde", "Gli accenti oro"). Le righe che iniziano con "- " diventano elenchi puntati.', 'digifluss-portfolio' ); ?></span>
				<div class="dfp-repeat" data-dfp-repeat="ds">
					<?php foreach ( $design_blocks as $ds ) : ?>
						<div class="dfp-repeat-row">
							<input type="text" name="dfp_ds_title[]" value="<?php echo esc_attr( isset( $ds['title'] ) ? $ds['title'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Titolo della scelta di design', 'digifluss-portfolio' ); ?>">
							<textarea name="dfp_ds_text[]" rows="4" placeholder="<?php esc_attr_e( 'Il racconto: cosa avete scelto e perché.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( isset( $ds['text'] ) ? $ds['text'] : '' ); ?></textarea>
							<button type="button" class="button-link dfp-repeat-remove"><?php esc_html_e( 'Rimuovi', 'digifluss-portfolio' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button dfp-repeat-add" data-target="ds"><?php esc_html_e( '+ Aggiungi blocco design', 'digifluss-portfolio' ); ?></button>
				<template id="dfp-tpl-ds">
					<div class="dfp-repeat-row">
						<input type="text" name="dfp_ds_title[]" value="" placeholder="<?php esc_attr_e( 'Titolo della scelta di design', 'digifluss-portfolio' ); ?>">
						<textarea name="dfp_ds_text[]" rows="4" placeholder="<?php esc_attr_e( 'Il racconto: cosa avete scelto e perché.', 'digifluss-portfolio' ); ?>"></textarea>
						<button type="button" class="button-link dfp-repeat-remove"><?php esc_html_e( 'Rimuovi', 'digifluss-portfolio' ); ?></button>
					</div>
				</template>
			</div>

			<div class="full">
				<label><?php esc_html_e( 'Capacità chiave', 'digifluss-portfolio' ); ?></label>
				<span class="desc" style="display:block;margin-bottom:8px;"><?php esc_html_e( 'Le funzionalità che contano, raccontate dal punto di vista del valore (es. "Badge che non mentono"). Compaiono come griglia di card nella pagina.', 'digifluss-portfolio' ); ?></span>
				<div class="dfp-repeat" data-dfp-repeat="cap">
					<?php foreach ( $capabilities as $cap ) : ?>
						<div class="dfp-repeat-row">
							<input type="text" name="dfp_cap_title[]" value="<?php echo esc_attr( isset( $cap['title'] ) ? $cap['title'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Titolo della capacità', 'digifluss-portfolio' ); ?>">
							<textarea name="dfp_cap_text[]" rows="3" placeholder="<?php esc_attr_e( 'Cosa fa e perché conta (2-4 frasi).', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( isset( $cap['text'] ) ? $cap['text'] : '' ); ?></textarea>
							<button type="button" class="button-link dfp-repeat-remove"><?php esc_html_e( 'Rimuovi', 'digifluss-portfolio' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button dfp-repeat-add" data-target="cap"><?php esc_html_e( '+ Aggiungi capacità', 'digifluss-portfolio' ); ?></button>
				<template id="dfp-tpl-cap">
					<div class="dfp-repeat-row">
						<input type="text" name="dfp_cap_title[]" value="" placeholder="<?php esc_attr_e( 'Titolo della capacità', 'digifluss-portfolio' ); ?>">
						<textarea name="dfp_cap_text[]" rows="3" placeholder="<?php esc_attr_e( 'Cosa fa e perché conta (2-4 frasi).', 'digifluss-portfolio' ); ?>"></textarea>
						<button type="button" class="button-link dfp-repeat-remove"><?php esc_html_e( 'Rimuovi', 'digifluss-portfolio' ); ?></button>
					</div>
				</template>
			</div>

			<div class="full">
				<label><?php esc_html_e( 'Approfondimenti tecnici (engineering highlights)', 'digifluss-portfolio' ); ?></label>
				<span class="desc" style="display:block;margin-bottom:8px;"><?php esc_html_e( 'I 3-5 punti tecnicamente interessanti del progetto, ognuno come sfida → soluzione → tecnologia.', 'digifluss-portfolio' ); ?></span>
				<div class="dfp-repeat" data-dfp-repeat="hl">
					<?php foreach ( $highlights as $hl ) : ?>
						<div class="dfp-repeat-row">
							<input type="text" name="dfp_hl_title[]" value="<?php echo esc_attr( isset( $hl['title'] ) ? $hl['title'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Titolo (es. Un sistema di badge progettato per non sbagliare)', 'digifluss-portfolio' ); ?>">
							<textarea name="dfp_hl_challenge[]" rows="2" placeholder="<?php esc_attr_e( 'Sfida: il problema tecnico o di dominio.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( isset( $hl['challenge'] ) ? $hl['challenge'] : '' ); ?></textarea>
							<textarea name="dfp_hl_solution[]" rows="3" placeholder="<?php esc_attr_e( 'Soluzione: come è stata risolta, in concreto.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( isset( $hl['solution'] ) ? $hl['solution'] : '' ); ?></textarea>
							<input type="text" name="dfp_hl_tech[]" value="<?php echo esc_attr( isset( $hl['tech'] ) ? $hl['tech'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Tecnologie (es. Liquid, tag taxonomy, snippet architecture)', 'digifluss-portfolio' ); ?>">
							<button type="button" class="button-link dfp-repeat-remove"><?php esc_html_e( 'Rimuovi', 'digifluss-portfolio' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button dfp-repeat-add" data-target="hl"><?php esc_html_e( '+ Aggiungi approfondimento', 'digifluss-portfolio' ); ?></button>
				<template id="dfp-tpl-hl">
					<div class="dfp-repeat-row">
						<input type="text" name="dfp_hl_title[]" value="" placeholder="<?php esc_attr_e( 'Titolo (es. Un sistema di badge progettato per non sbagliare)', 'digifluss-portfolio' ); ?>">
						<textarea name="dfp_hl_challenge[]" rows="2" placeholder="<?php esc_attr_e( 'Sfida: il problema tecnico o di dominio.', 'digifluss-portfolio' ); ?>"></textarea>
						<textarea name="dfp_hl_solution[]" rows="3" placeholder="<?php esc_attr_e( 'Soluzione: come è stata risolta, in concreto.', 'digifluss-portfolio' ); ?>"></textarea>
						<input type="text" name="dfp_hl_tech[]" value="" placeholder="<?php esc_attr_e( 'Tecnologie (es. Liquid, tag taxonomy, snippet architecture)', 'digifluss-portfolio' ); ?>">
						<button type="button" class="button-link dfp-repeat-remove"><?php esc_html_e( 'Rimuovi', 'digifluss-portfolio' ); ?></button>
					</div>
				</template>
			</div>

			<p class="full">
				<label for="dfp_architecture"><?php esc_html_e( 'Architettura in sintesi', 'digifluss-portfolio' ); ?></label>
				<textarea id="dfp_architecture" name="_dfp_architecture" rows="4" placeholder="<?php esc_attr_e( 'Come sta in piedi il sistema: componenti, dati, integrazioni, scelte chiave. 1-2 paragrafi.', 'digifluss-portfolio' ); ?>"><?php echo esc_textarea( $architecture ); ?></textarea>
			</p>

			<div class="full dfp-sep"><span><?php esc_html_e( 'Traduzioni (en / de / fr / es / pt)', 'digifluss-portfolio' ); ?></span>
				<span class="desc" style="display:block;font-weight:400;margin-top:4px;"><?php esc_html_e( 'Il contenuto del case study è in italiano; nelle altre lingue il sito mostra la traduzione se esiste, altrimenti l\'italiano. Il pulsante traduce tutti i campi con l\'AI (chiave in Portfolio → AI): salva prima le modifiche, poi traduci. Rilancia la traduzione quando aggiorni il testo italiano.', 'digifluss-portfolio' ); ?></span>
			</div>
			<div class="full dfp-tr-panel">
				<div class="dfp-tr-langs">
					<?php foreach ( dfp_locales() as $loc => $lang ) :
						$has = (bool) get_post_meta( $post->ID, '_dfp_tr_' . $loc, true );
						?>
						<label class="dfp-tr-lang<?php echo $has ? ' is-done' : ''; ?>" data-locale="<?php echo esc_attr( $loc ); ?>">
							<input type="checkbox" class="dfp-tr-pick" value="<?php echo esc_attr( $loc ); ?>" <?php checked( ! $has ); ?>>
							<b><?php echo esc_html( strtoupper( substr( $loc, 0, 2 ) ) ); ?></b>
							<span class="dfp-tr-state"><?php echo $has ? esc_html__( 'tradotto', 'digifluss-portfolio' ) : esc_html__( 'da tradurre', 'digifluss-portfolio' ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="dfp-tr-actions">
					<button type="button" class="button button-secondary dfp-tr-run"><?php esc_html_e( '🌍 Traduci le lingue selezionate', 'digifluss-portfolio' ); ?></button>
					<button type="button" class="button-link dfp-tr-all"><?php esc_html_e( 'seleziona tutte', 'digifluss-portfolio' ); ?></button>
					<button type="button" class="button button-secondary dfp-tr-stop" hidden><?php esc_html_e( '■ Interrompi', 'digifluss-portfolio' ); ?></button>
					<span class="dfp-tr-status" aria-live="polite"></span>
				</div>

				<div class="dfp-tr-progress" hidden>
					<div class="dfp-tr-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i></i></div>
					<div class="dfp-tr-meta"><span class="dfp-tr-count"></span><span class="dfp-tr-time"></span></div>
				</div>
			</div>
		</div>
		<p class="dfp-fields desc" style="margin-top:14px;">
			<?php esc_html_e( 'Suggerimento: il titolo del progetto è il titolo del post, la descrizione breve è il riassunto (campo "Riassunto"), e la copertina può essere l\'immagine in evidenza.', 'digifluss-portfolio' ); ?>
		</p>
		<script>
		(function(){
			function init(){
				var wrap = document.getElementById('dfp-gallery');
				if ( ! wrap || ! window.wp || ! wp.media ) { return; }
				var input  = document.getElementById('dfp_gallery');
				var list   = wrap.querySelector('.dfp-gallery-list');
				var addBtn = wrap.querySelector('.dfp-gallery-add');
				var frame;
				function ids(){ return input.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean); }
				function setIds(a){ input.value = a.join(','); }
				function syncFromDom(){
					var order = [];
					list.querySelectorAll('.dfp-gthumb').forEach(function(t){ order.push(t.getAttribute('data-id')); });
					setIds(order);
				}
				function bindRemove(el){
					var b = el.querySelector('.dfp-gremove');
					if ( b ) {
						b.addEventListener('click', function(){
							if ( el.parentNode ) { el.parentNode.removeChild(el); }
							syncFromDom();
						});
					}
					// Riordino col trascinamento: l'ordine delle miniature è l'ordine in pagina.
					el.setAttribute('draggable', 'true');
					el.addEventListener('dragstart', function(e){
						el.classList.add('is-dragging');
						e.dataTransfer.effectAllowed = 'move';
						try { e.dataTransfer.setData('text/plain', el.getAttribute('data-id')); } catch (err) {}
					});
					el.addEventListener('dragend', function(){
						el.classList.remove('is-dragging');
						syncFromDom();
					});
					el.addEventListener('dragover', function(e){
						e.preventDefault();
						var dragging = list.querySelector('.is-dragging');
						if ( ! dragging || dragging === el ) { return; }
						var rect = el.getBoundingClientRect();
						var before = ( e.clientX - rect.left ) < rect.width / 2;
						list.insertBefore( dragging, before ? el : el.nextSibling );
					});
				}
				Array.prototype.forEach.call( list.querySelectorAll('.dfp-gthumb'), bindRemove );
				function addThumb(id, url){
					var d = document.createElement('div');
					d.className = 'dfp-gthumb';
					d.setAttribute('data-id', id);
					d.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="dfp-gremove" aria-label="x">&times;</button>';
					list.appendChild(d);
					bindRemove(d);
				}
				addBtn.addEventListener('click', function(e){
					e.preventDefault();
					if ( frame ) { frame.open(); return; }
					frame = wp.media({ title: <?php echo wp_json_encode( __( 'Galleria del progetto', 'digifluss-portfolio' ) ); ?>, multiple: true, library: { type: 'image' }, button: { text: <?php echo wp_json_encode( __( 'Aggiungi alla galleria', 'digifluss-portfolio' ) ); ?> } });
					frame.on('select', function(){
						var sel = frame.state().get('selection');
						var cur = ids();
						sel.each(function(att){
							var id = String(att.id);
							if ( cur.indexOf(id) === -1 ) {
								cur.push(id);
								var s = att.attributes.sizes;
								var url = ( s && s.thumbnail ) ? s.thumbnail.url : att.attributes.url;
								addThumb(id, url);
							}
						});
						setIds(cur);
					});
					frame.open();
				});
			}
			if ( document.readyState !== 'loading' ) { init(); } else { document.addEventListener('DOMContentLoaded', init); }
		})();
		</script>
		<script>
		(function(){
			/* Repeater case study: aggiunta/rimozione righe. */
			function init(){
				function bindRemove(row){
					var b = row.querySelector('.dfp-repeat-remove');
					if ( b ) { b.addEventListener('click', function(){ row.remove(); }); }
				}
				document.querySelectorAll('.dfp-repeat .dfp-repeat-row').forEach(bindRemove);
				document.querySelectorAll('.dfp-repeat-add').forEach(function(btn){
					btn.addEventListener('click', function(){
						var key  = btn.getAttribute('data-target');
						var wrap = document.querySelector('.dfp-repeat[data-dfp-repeat="' + key + '"]');
						var tpl  = document.getElementById('dfp-tpl-' + key);
						if ( ! wrap || ! tpl ) { return; }
						var node = tpl.content.firstElementChild.cloneNode(true);
						wrap.appendChild(node);
						bindRemove(node);
						var f = node.querySelector('input,textarea');
						if ( f ) { f.focus(); }
					});
				});
			}
			if ( document.readyState !== 'loading' ) { init(); } else { document.addEventListener('DOMContentLoaded', init); }
		})();
		</script>
		<script>
		(function(){
			/* Import case study da file .md (formato "PROJECT ANALYSIS / PORTFOLIO CASE STUDY").
			   Tutto client-side: il file non viene caricato sul server, i campi
			   vengono solo precompilati e nulla è salvato finché non si preme Aggiorna. */
			var L = {
				done:    <?php echo wp_json_encode( __( 'Campi compilati dal file:', 'digifluss-portfolio' ) ); ?>,
				check:   <?php echo wp_json_encode( __( 'Controlla, correggi se serve, poi premi Aggiorna per salvare.', 'digifluss-portfolio' ) ); ?>,
				nothing: <?php echo wp_json_encode( __( 'Nessuna sezione riconosciuta: il file non sembra nel formato del case study.', 'digifluss-portfolio' ) ); ?>,
				readerr: <?php echo wp_json_encode( __( 'Impossibile leggere il file.', 'digifluss-portfolio' ) ); ?>
			};

			function strip(s){
				return String(s || '')
					.replace(/\*\*/g, '')
					.replace(/`/g, '')
					.replace(/^\s*>\s?/gm, '')
					.trim();
			}
			function cleanBody(s){
				/* Toglie citazioni e sotto-note, normalizza le righe vuote. */
				var noQuotes = String(s || '').split('\n').filter(function(ln){ return ! /^\s*>/.test(ln); }).join('\n');
				return strip(noQuotes).replace(/\n{3,}/g, '\n\n').trim();
			}
			function splitList(s){
				return strip(s).split(/[·,;|]+/).map(function(x){ return x.trim(); }).filter(Boolean).join(', ');
			}
			function unconfirmed(v){
				return /\[(?:da confermare|to confirm)|TBD/i.test(v);
			}

			function sectionsOf(md){
				var out = [], cur = null;
				md.replace(/\r\n?/g, '\n').split('\n').forEach(function(ln){
					var m = ln.match(/^(#{1,2})\s+(.+)$/);
					if ( m ) { cur = { title: m[2].trim(), body: [] }; out.push(cur); }
					else if ( cur ) { cur.body.push(ln); }
				});
				return out;
			}
			function findSec(sections, re){
				for ( var i = 0; i < sections.length; i++ ) {
					if ( re.test( sections[i].title ) ) { return sections[i].body.join('\n').trim(); }
				}
				return '';
			}
			function subBlocks(body){
				var out = [], cur = null;
				String(body || '').split('\n').forEach(function(ln){
					var m = ln.match(/^###\s+(.+)$/);
					if ( m ) { cur = { title: strip(m[1]), body: [] }; out.push(cur); }
					else if ( cur ) { cur.body.push(ln); }
				});
				return out.map(function(b){
					var raw = b.body.join('\n').trim();
					return { title: b.title, text: cleanBody(raw), raw: raw };
				});
			}
			/* Le etichette possono arrivare in grassetto (**Year:**) o già "pulite"
			   (Year:) a seconda di dove passa il testo: il ** è sempre opzionale. */
			function metaLine(body, names){
				var re = new RegExp('^\\s*(?:\\*\\*)?(?:' + names + ')\\s*:(?:\\*\\*)?\\s*(.+)$', 'mi');
				var m = String(body || '').match(re);
				if ( ! m ) { return ''; }
				var v = strip(m[1]);
				return unconfirmed(v) ? '' : v;
			}
			function labeled(text, names){
				var re = new RegExp('(?:\\*\\*)?(?:' + names + ')\\s*:(?:\\*\\*)?\\s*([\\s\\S]*?)(?=\\n\\s*(?:\\*\\*)?[A-Za-zÀ-ü][A-Za-zÀ-ü ]{1,34}\\s*:|$)', 'i');
				var m = String(text || '').match(re);
				return m ? strip(m[1]).replace(/\s+/g, ' ').trim() : '';
			}

			function setVal(id, v, overwrite){
				var el = document.getElementById(id);
				if ( ! el || ! v ) { return false; }
				if ( ! overwrite && el.value.trim() ) { return false; }
				el.value = v;
				return true;
			}
			function fillRepeat(key, rows, map){
				var wrap = document.querySelector('.dfp-repeat[data-dfp-repeat="' + key + '"]');
				var tpl  = document.getElementById('dfp-tpl-' + key);
				if ( ! wrap || ! tpl || ! rows.length ) { return 0; }
				wrap.innerHTML = '';
				rows.forEach(function(r){
					var node = tpl.content.firstElementChild.cloneNode(true);
					Object.keys(map).forEach(function(name){
						var el = node.querySelector('[name="' + name + '"]');
						if ( el ) { el.value = r[ map[name] ] || ''; }
					});
					var b = node.querySelector('.dfp-repeat-remove');
					if ( b ) { b.addEventListener('click', function(){ node.remove(); }); }
					wrap.appendChild(node);
				});
				return rows.length;
			}

			function importMd(md, status){
				var S = sectionsOf(md);
				if ( ! S.length ) { status(L.nothing, 'err'); return; }

				var card     = findSec(S, /portfolio card/i);
				var hero     = findSec(S, /case study hero/i);
				var identity = findSec(S, /project identity/i);
				var oneLine  = findSec(S, /one-?line description/i);
				var overview = findSec(S, /\boverview\b/i);
				var storySec = findSec(S, /the product\b/i);
				var designSec = findSec(S, /design e identit|design and visual|visual identity/i);
				var capsSec  = findSec(S, /key capabilities/i);
				var hlSec    = findSec(S, /engineering highlights/i);
				var archSec  = findSec(S, /architecture (?:summary|in sintesi)|architettura/i);
				var tagsSec  = findSec(S, /portfolio tags|^tags?\b/i);

				var cardSubs = {};
				subBlocks(card).forEach(function(b){ cardSubs[ b.title.toLowerCase() ] = b.text; });
				var heroSubs = {};
				subBlocks(hero).forEach(function(b){ heroSubs[ b.title.toLowerCase() ] = b.text; });
				var heroMeta = heroSubs['metadata'] || hero;

				var filled = [];
				function did(label, ok){ if ( ok ) { filled.push(label); } }

				/* Identità e card */
				var name = strip( (cardSubs['project name'] || '').split('\n')[0] );
				did('titolo', setVal('title', name, false));
				var titleEl = document.getElementById('title');
				if ( titleEl && titleEl.value ) {
					var prompt = document.getElementById('title-prompt-text');
					if ( prompt ) { prompt.className = 'screen-reader-text'; }
				}
				var shortDesc = cardSubs['short description'] || cleanBody(oneLine).split('\n')[0] || '';
				did('riassunto', setVal('excerpt', shortDesc.replace(/\s+/g, ' ').trim(), true));
				did('categoria', setVal('dfp_category', strip( (cardSubs['eyebrow'] || '').split('\n')[0] ), true));

				var stack = splitList( cardSubs['tech stack'] || metaLine(heroMeta, 'Stack') );
				did('stack', setVal('dfp_stack', stack, true));

				var client = metaLine(heroMeta, 'Client|Cliente') || metaLine(identity, 'Client|Cliente');
				did('cliente', setVal('dfp_client', client, true));
				did('settore', setVal('dfp_industry', metaLine(heroMeta, 'Industry|Settore') || metaLine(identity, 'Industry|Settore'), true));
				did('prodotto', setVal('dfp_product', metaLine(heroMeta, 'Product|Prodotto') || metaLine(identity, 'Primary product type'), true));
				did('ruolo', setVal('dfp_team_role', metaLine(heroMeta, 'Role|Ruolo'), true));
				did('tempi', setVal('dfp_timeline', metaLine(heroMeta, 'Timeline|Tempi|Durata'), true));

				var yearRaw = metaLine(heroMeta, 'Year|Anno');
				var yearM   = yearRaw.match(/\d{4}/);
				did('anno', setVal('dfp_year', yearM ? yearM[0] : '', true));

				var statusLine = metaLine(identity, 'Project status|Stato');
				var urlM = statusLine.match(/https?:\/\/[^\s)—,]+/);
				did('link live', setVal('dfp_link', urlM ? urlM[0] : '', true));

				/* Hero del case study */
				did('titolo case study', setVal('dfp_cs_title', (heroSubs['title'] || '').replace(/\s+/g, ' ').trim(), true));
				did('sottotitolo', setVal('dfp_cs_subtitle', (heroSubs['subtitle'] || '').replace(/\s+/g, ' ').trim(), true));

				/* Narrativa */
				did('panoramica', setVal('dfp_overview', cleanBody(overview), true));
				did('il prodotto', setVal('dfp_story', cleanBody(storySec), true));
				did('architettura', setVal('dfp_architecture', cleanBody(archSec), true));

				var tagsLine = cleanBody(tagsSec).split('\n').filter(Boolean)[0] || '';
				did('tag', setVal('dfp_tags', splitList(tagsLine), true));

				/* Design: intro (testo prima del primo ###) + blocchi */
				var designIntro = cleanBody( designSec.split(/\n###\s/)[0] || '' );
				did('design (intro)', setVal('dfp_design_intro', designIntro, true));
				var dsBlocks = subBlocks(designSec).filter(function(b){ return b.title && b.text; });
				var nDs = fillRepeat('ds', dsBlocks, { 'dfp_ds_title[]': 'title', 'dfp_ds_text[]': 'text' });
				if ( nDs ) { filled.push('design (' + nDs + ')'); }

				/* Repeater */
				var caps = subBlocks(capsSec).filter(function(b){ return b.title && b.text; });
				var nCaps = fillRepeat('cap', caps, { 'dfp_cap_title[]': 'title', 'dfp_cap_text[]': 'text' });
				if ( nCaps ) { filled.push('capacità (' + nCaps + ')'); }

				var hls = subBlocks(hlSec).map(function(b){
					var src = b.raw || b.text;
					return {
						title:     b.title,
						challenge: labeled(src, 'Challenge|Sfida'),
						solution:  labeled(src, 'Solution|Soluzione'),
						tech:      splitList( labeled(src, 'Technology|Technologies|Tecnologia|Tecnologie') )
					};
				}).filter(function(r){ return r.title && ( r.challenge || r.solution ); });
				var nHl = fillRepeat('hl', hls, { 'dfp_hl_title[]': 'title', 'dfp_hl_challenge[]': 'challenge', 'dfp_hl_solution[]': 'solution', 'dfp_hl_tech[]': 'tech' });
				if ( nHl ) { filled.push('approfondimenti (' + nHl + ')'); }

				if ( ! filled.length ) { status(L.nothing, 'err'); return; }
				status(L.done + ' ' + filled.join(', ') + '. ' + L.check, 'ok');
			}

			function init(){
				var input = document.getElementById('dfp_md_file');
				var box   = document.querySelector('.dfp-md-status');
				if ( ! input || ! box ) { return; }
				function status(msg, cls){ box.textContent = msg; box.className = 'dfp-md-status ' + cls; }
				input.addEventListener('change', function(){
					var f = input.files && input.files[0];
					if ( ! f ) { return; }
					var reader = new FileReader();
					reader.onload  = function(){ importMd( String(reader.result || '' ), status ); };
					reader.onerror = function(){ status(L.readerr, 'err'); };
					reader.readAsText(f);
				});
			}
			if ( document.readyState !== 'loading' ) { init(); } else { document.addEventListener('DOMContentLoaded', init); }
		})();
		</script>
		<script>
		(function(){
			/* Traduzione AI: una chiamata per lingua, in sequenza. */
			var TRCFG = {
				url:    <?php echo wp_json_encode( esc_url_raw( rest_url( 'dfp/v1/translate' ) ) ); ?>,
				nonce:  <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>,
				postId: <?php echo (int) $post->ID; ?>,
				locales: <?php echo wp_json_encode( array_keys( dfp_locales() ) ); ?>,
				msg: {
					unsaved:  <?php echo wp_json_encode( __( 'Nota: verranno tradotti i contenuti già salvati. Continuo?', 'digifluss-portfolio' ) ); ?>,
					busy:     <?php echo wp_json_encode( __( 'Traduzione in corso:', 'digifluss-portfolio' ) ); ?>,
					working:  <?php echo wp_json_encode( __( 'in corso…', 'digifluss-portfolio' ) ); ?>,
					ok:       <?php echo wp_json_encode( __( 'tradotto', 'digifluss-portfolio' ) ); ?>,
					failed:   <?php echo wp_json_encode( __( 'non riuscita', 'digifluss-portfolio' ) ); ?>,
					done:     <?php echo wp_json_encode( __( 'Traduzioni completate.', 'digifluss-portfolio' ) ); ?>,
					partial:  <?php echo wp_json_encode( __( 'Alcune lingue non sono riuscite: riprova quelle in rosso.', 'digifluss-portfolio' ) ); ?>,
					none:     <?php echo wp_json_encode( __( 'Seleziona almeno una lingua.', 'digifluss-portfolio' ) ); ?>,
					stopping: <?php echo wp_json_encode( __( 'Interruzione dopo la lingua in corso…', 'digifluss-portfolio' ) ); ?>,
					stopped:  <?php echo wp_json_encode( __( 'Interrotto. Le lingue già completate sono salvate.', 'digifluss-portfolio' ) ); ?>
				}
			};
			function init(){
				var btn = document.querySelector('.dfp-tr-run');
				if ( ! btn ) { return; }
				var stopBtn  = document.querySelector('.dfp-tr-stop');
				var allBtn   = document.querySelector('.dfp-tr-all');
				var status   = document.querySelector('.dfp-tr-status');
				var progress = document.querySelector('.dfp-tr-progress');
				var bar      = document.querySelector('.dfp-tr-bar');
				var fill     = bar ? bar.querySelector('i') : null;
				var countEl  = document.querySelector('.dfp-tr-count');
				var timeEl   = document.querySelector('.dfp-tr-time');
				var stopped  = false;
				var timer    = null;

				function row(loc){ return document.querySelector('.dfp-tr-lang[data-locale="' + loc + '"]'); }
				function setRow(loc, cls, label){
					var r = row(loc);
					if ( ! r ) { return; }
					r.className = 'dfp-tr-lang' + ( cls ? ' ' + cls : '' );
					var s = r.querySelector('.dfp-tr-state');
					if ( s ) { s.textContent = label; }
				}
				function picked(){
					return Array.prototype.slice.call(document.querySelectorAll('.dfp-tr-pick'))
						.filter(function(c){ return c.checked; })
						.map(function(c){ return c.value; });
				}
				function mmss(sec){
					var m = Math.floor(sec / 60), s = sec % 60;
					return m + ':' + ( s < 10 ? '0' : '' ) + s;
				}
				function setBar(done, total){
					var pct = total ? Math.round( done / total * 100 ) : 0;
					if ( fill ) { fill.style.width = pct + '%'; }
					if ( bar ) { bar.setAttribute('aria-valuenow', String(pct)); }
					if ( countEl ) { countEl.textContent = done + ' / ' + total + ' — ' + pct + '%'; }
				}

				if ( allBtn ) {
					allBtn.addEventListener('click', function(){
						var boxes = document.querySelectorAll('.dfp-tr-pick');
						var anyOff = Array.prototype.some.call(boxes, function(c){ return ! c.checked; });
						Array.prototype.forEach.call(boxes, function(c){ c.checked = anyOff; });
					});
				}

				if ( stopBtn ) {
					stopBtn.addEventListener('click', function(){
						stopped = true;
						stopBtn.disabled = true;
						status.textContent = TRCFG.msg.stopping;
					});
				}

				btn.addEventListener('click', function(){
					var queue = picked();
					if ( ! queue.length ) { status.textContent = TRCFG.msg.none; return; }
					if ( ! window.confirm( TRCFG.msg.unsaved ) ) { return; }

					var total = queue.length, done = 0, errors = 0;
					var t0 = Date.now();
					stopped = false;
					btn.disabled = true;
					if ( stopBtn ) { stopBtn.hidden = false; stopBtn.disabled = false; }
					if ( progress ) { progress.hidden = false; }
					setBar(0, total);
					timer = window.setInterval(function(){
						if ( timeEl ) { timeEl.textContent = mmss( Math.round( ( Date.now() - t0 ) / 1000 ) ); }
					}, 1000);

					function finish(){
						window.clearInterval(timer);
						btn.disabled = false;
						if ( stopBtn ) { stopBtn.hidden = true; }
						status.textContent = stopped ? TRCFG.msg.stopped
							: ( errors ? TRCFG.msg.partial : TRCFG.msg.done );
					}

					(function next(){
						if ( stopped || ! queue.length ) { finish(); return; }
						var loc = queue.shift();
						status.textContent = TRCFG.msg.busy + ' ' + loc.substring(0, 2).toUpperCase();
						setRow(loc, 'is-busy', TRCFG.msg.working);
						fetch(TRCFG.url, {
							method: 'POST',
							headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': TRCFG.nonce },
							body: JSON.stringify({ post_id: TRCFG.postId, locale: loc })
						})
						.then(function(r){ return r.json().then(function(j){ return { ok: r.ok, j: j }; }); })
						.then(function(res){
							if ( res.ok ) {
								setRow(loc, 'is-done', TRCFG.msg.ok);
								var box = document.querySelector('.dfp-tr-pick[value="' + loc + '"]');
								if ( box ) { box.checked = false; }
							} else {
								errors++;
								setRow(loc, 'is-err', ( res.j && res.j.message ) ? res.j.message.substring(0, 60) : TRCFG.msg.failed);
							}
							done++;
							setBar(done, total);
							next();
						})
						.catch(function(){
							errors++;
							setRow(loc, 'is-err', TRCFG.msg.failed);
							done++;
							setBar(done, total);
							next();
						});
					})();
				});
			}
			if ( document.readyState !== 'loading' ) { init(); } else { document.addEventListener('DOMContentLoaded', init); }
		})();
		</script>
		<script>
		(function(){
			var CFG = {
				url: <?php echo wp_json_encode( esc_url_raw( rest_url( 'dfp/v1/suggest-stack' ) ) ); ?>,
				nonce: <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>,
				settings: <?php echo wp_json_encode( esc_url_raw( admin_url( 'edit.php?post_type=df_progetto&page=dfp-ai' ) ) ); ?>
			};
			function init(){
				var btn = document.querySelector('.dfp-ai-suggest');
				if ( ! btn ) { return; }
				var status = document.querySelector('.dfp-ai-status');
				var out    = document.querySelector('.dfp-ai-out');
				var stackInput = document.getElementById('dfp_stack');
				function val(id){ var el = document.getElementById(id); return el ? el.value : ''; }
				function motif(){ var s = document.getElementById('dfp_motif'); return s ? s.value : ''; }
				function title(){
					var t = document.getElementById('title');
					if ( t ) { return t.value; }
					var p = document.querySelector('input[name=post_title]');
					return p ? p.value : '';
				}
				function addTech(t){
					var cur = stackInput.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean);
					var low = cur.map(function(s){ return s.toLowerCase(); });
					if ( low.indexOf( t.toLowerCase() ) === -1 ) { cur.push(t); stackInput.value = cur.join(', '); }
				}
				function render(list){
					out.innerHTML = '';
					if ( ! list.length ) { return; }
					list.forEach(function(t){
						var c = document.createElement('button');
						c.type = 'button'; c.className = 'dfp-ai-chip'; c.textContent = '+ ' + t;
						c.addEventListener('click', function(){ addTech(t); c.disabled = true; c.style.opacity = .5; });
						out.appendChild(c);
					});
					var all = document.createElement('button');
					all.type = 'button'; all.className = 'dfp-ai-all';
					all.textContent = <?php echo wp_json_encode( __( 'Aggiungi tutti', 'digifluss-portfolio' ) ); ?>;
					all.addEventListener('click', function(){ list.forEach(addTech); });
					out.appendChild(all);
				}
				btn.addEventListener('click', function(){
					out.innerHTML = '';
					btn.disabled = true;
					status.textContent = <?php echo wp_json_encode( __( 'Sto pensando…', 'digifluss-portfolio' ) ); ?>;
					fetch(CFG.url, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
						body: JSON.stringify({ title: title(), category: val('dfp_category'), objective: val('dfp_role'), motif: motif() })
					})
					.then(function(r){ return r.json().then(function(j){ return { ok: r.ok, j: j }; }); })
					.then(function(res){
						btn.disabled = false; status.textContent = '';
						if ( ! res.ok ) {
							var msg = ( res.j && res.j.message ) ? res.j.message : 'Errore.';
							var e = document.createElement('span'); e.className = 'dfp-ai-err'; e.textContent = msg; out.appendChild(e);
							if ( res.j && res.j.code === 'dfp_no_key' ) {
								var a = document.createElement('a'); a.href = CFG.settings; a.style.fontSize = '12px';
								a.textContent = ' → ' + <?php echo wp_json_encode( __( 'Apri impostazioni', 'digifluss-portfolio' ) ); ?>;
								out.appendChild(a);
							}
							return;
						}
						render( ( res.j && res.j.stack ) ? res.j.stack : [] );
					})
					.catch(function(err){
						btn.disabled = false; status.textContent = '';
						var e = document.createElement('span'); e.className = 'dfp-ai-err'; e.textContent = String(err); out.appendChild(e);
					});
				});
			}
			if ( document.readyState !== 'loading' ) { init(); } else { document.addEventListener('DOMContentLoaded', init); }
		})();
		</script>
		<?php
	}
}

/* -----------------------------------------------------------------
 * 4. Salvataggio
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_save_meta' ) ) {
	function dfp_save_meta( $post_id ) {
		if ( ! isset( $_POST['dfp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dfp_nonce'] ) ), 'dfp_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$text_fields = array( '_dfp_category', '_dfp_year', '_dfp_role', '_dfp_stack', '_dfp_client', '_dfp_integrations' );
		foreach ( $text_fields as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		if ( isset( $_POST['_dfp_link'] ) ) {
			update_post_meta( $post_id, '_dfp_link', esc_url_raw( wp_unslash( $_POST['_dfp_link'] ) ) );
		}

		$motifs = dfp_motifs();
		$motif  = isset( $_POST['_dfp_motif'] ) ? sanitize_key( wp_unslash( $_POST['_dfp_motif'] ) ) : 'dashboard';
		if ( ! isset( $motifs[ $motif ] ) ) {
			$motif = 'dashboard';
		}
		update_post_meta( $post_id, '_dfp_motif', $motif );

		$accent = isset( $_POST['_dfp_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['_dfp_accent'] ) ) : '';
		if ( ! $accent ) { $accent = '#2ee7f2'; }
		update_post_meta( $post_id, '_dfp_accent', $accent );

		$accent2 = isset( $_POST['_dfp_accent2'] ) ? sanitize_hex_color( wp_unslash( $_POST['_dfp_accent2'] ) ) : '';
		update_post_meta( $post_id, '_dfp_accent2', $accent2 ? $accent2 : '' );

		$ta_fields = array( '_dfp_challenge', '_dfp_approach', '_dfp_result', '_dfp_cs_subtitle', '_dfp_overview', '_dfp_story', '_dfp_architecture', '_dfp_design_intro' );
		foreach ( $ta_fields as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		$cs_text_fields = array( '_dfp_cs_title', '_dfp_industry', '_dfp_product', '_dfp_team_role', '_dfp_timeline', '_dfp_tags' );
		foreach ( $cs_text_fields as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Repeater "Capacità chiave": righe appaiate titolo/testo, salvate come JSON.
		if ( isset( $_POST['dfp_cap_title'] ) || isset( $_POST['dfp_cap_text'] ) ) {
			$titles = isset( $_POST['dfp_cap_title'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['dfp_cap_title'] ) ) : array();
			$texts  = isset( $_POST['dfp_cap_text'] ) ? array_map( 'sanitize_textarea_field', wp_unslash( (array) $_POST['dfp_cap_text'] ) ) : array();
			$rows   = array();
			$count  = max( count( $titles ), count( $texts ) );
			for ( $ri = 0; $ri < $count; $ri++ ) {
				$t = isset( $titles[ $ri ] ) ? trim( $titles[ $ri ] ) : '';
				$x = isset( $texts[ $ri ] ) ? trim( $texts[ $ri ] ) : '';
				if ( '' === $t && '' === $x ) { continue; }
				$rows[] = array( 'title' => $t, 'text' => $x );
			}
			update_post_meta( $post_id, '_dfp_capabilities', $rows ? wp_slash( wp_json_encode( $rows ) ) : '' );
		}

		// Repeater "Design e identità visiva": righe titolo/testo, salvate come JSON.
		if ( isset( $_POST['dfp_ds_title'] ) || isset( $_POST['dfp_ds_text'] ) ) {
			$titles = isset( $_POST['dfp_ds_title'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['dfp_ds_title'] ) ) : array();
			$texts  = isset( $_POST['dfp_ds_text'] ) ? array_map( 'sanitize_textarea_field', wp_unslash( (array) $_POST['dfp_ds_text'] ) ) : array();
			$rows   = array();
			$count  = max( count( $titles ), count( $texts ) );
			for ( $ri = 0; $ri < $count; $ri++ ) {
				$t = isset( $titles[ $ri ] ) ? trim( $titles[ $ri ] ) : '';
				$x = isset( $texts[ $ri ] ) ? trim( $texts[ $ri ] ) : '';
				if ( '' === $t && '' === $x ) { continue; }
				$rows[] = array( 'title' => $t, 'text' => $x );
			}
			update_post_meta( $post_id, '_dfp_design_blocks', $rows ? wp_slash( wp_json_encode( $rows ) ) : '' );
		}

		// Repeater "Approfondimenti tecnici": titolo / sfida / soluzione / tecnologia.
		if ( isset( $_POST['dfp_hl_title'] ) || isset( $_POST['dfp_hl_solution'] ) ) {
			$titles     = isset( $_POST['dfp_hl_title'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['dfp_hl_title'] ) ) : array();
			$challenges = isset( $_POST['dfp_hl_challenge'] ) ? array_map( 'sanitize_textarea_field', wp_unslash( (array) $_POST['dfp_hl_challenge'] ) ) : array();
			$solutions  = isset( $_POST['dfp_hl_solution'] ) ? array_map( 'sanitize_textarea_field', wp_unslash( (array) $_POST['dfp_hl_solution'] ) ) : array();
			$techs      = isset( $_POST['dfp_hl_tech'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['dfp_hl_tech'] ) ) : array();
			$rows       = array();
			$count      = max( count( $titles ), count( $challenges ), count( $solutions ), count( $techs ) );
			for ( $ri = 0; $ri < $count; $ri++ ) {
				$row = array(
					'title'     => isset( $titles[ $ri ] ) ? trim( $titles[ $ri ] ) : '',
					'challenge' => isset( $challenges[ $ri ] ) ? trim( $challenges[ $ri ] ) : '',
					'solution'  => isset( $solutions[ $ri ] ) ? trim( $solutions[ $ri ] ) : '',
					'tech'      => isset( $techs[ $ri ] ) ? trim( $techs[ $ri ] ) : '',
				);
				if ( '' === implode( '', $row ) ) { continue; }
				$rows[] = $row;
			}
			update_post_meta( $post_id, '_dfp_highlights', $rows ? wp_slash( wp_json_encode( $rows ) ) : '' );
		}

		if ( isset( $_POST['_dfp_gallery'] ) ) {
			$gids = array_filter(
				array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['_dfp_gallery'] ) ) ) ),
				function ( $gid ) {
					return $gid && wp_attachment_is_image( $gid );
				}
			);
			update_post_meta( $post_id, '_dfp_gallery', implode( ',', $gids ) );
		}
	}
	add_action( 'save_post_df_progetto', 'dfp_save_meta' );
}

/* -----------------------------------------------------------------
 * 4bis. Media Library nell'editor del progetto (per la galleria)
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_admin_media' ) ) {
	function dfp_admin_media() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'df_progetto' === $screen->post_type ) {
			wp_enqueue_media();
		}
	}
	add_action( 'admin_enqueue_scripts', 'dfp_admin_media' );
}

/* -----------------------------------------------------------------
 * 4ter. AI — suggeritore di stack in base al tipo di progetto
 *      Sicurezza: la chiave API resta lato server (opzione); la
 *      chiamata passa da un endpoint REST riservato agli editor.
 *      La chiave non è mai esposta al browser né agli utenti del sito.
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_ai_defaults' ) ) {
	function dfp_ai_defaults() {
		return array( 'api_key' => '', 'model' => 'claude-sonnet-4-6' );
	}
}
if ( ! function_exists( 'dfp_ai_opts' ) ) {
	function dfp_ai_opts() {
		$o = get_option( 'dfp_ai', array() );
		if ( ! is_array( $o ) ) { $o = array(); }
		$o = wp_parse_args( $o, dfp_ai_defaults() );
		// Una sola chiave per tutto il sito: se il plugin non ha una chiave
		// propria usa la costante di wp-config condivisa con la Flow Audit.
		if ( ! $o['api_key'] && defined( 'DFFA_AI_KEY' ) && DFFA_AI_KEY ) {
			$o['api_key'] = DFFA_AI_KEY;
		}
		return $o;
	}
}
if ( ! function_exists( 'dfp_ai_settings_menu' ) ) {
	function dfp_ai_settings_menu() {
		add_submenu_page(
			'edit.php?post_type=df_progetto',
			__( 'AI: suggeritore di stack', 'digifluss-portfolio' ),
			__( 'AI (stack)', 'digifluss-portfolio' ),
			'manage_options',
			'dfp-ai',
			'dfp_ai_settings_page'
		);
	}
	add_action( 'admin_menu', 'dfp_ai_settings_menu' );
}
if ( ! function_exists( 'dfp_ai_register_settings' ) ) {
	function dfp_ai_register_settings() {
		register_setting( 'dfp_ai_group', 'dfp_ai', 'dfp_ai_sanitize' );
	}
	add_action( 'admin_init', 'dfp_ai_register_settings' );
}
if ( ! function_exists( 'dfp_ai_sanitize' ) ) {
	function dfp_ai_sanitize( $in ) {
		$d   = dfp_ai_defaults();
		$out = array();
		$out['api_key'] = isset( $in['api_key'] ) ? trim( sanitize_text_field( $in['api_key'] ) ) : '';
		$model          = isset( $in['model'] ) ? trim( sanitize_text_field( $in['model'] ) ) : '';
		$out['model']   = $model ? $model : $d['model'];
		return $out;
	}
}
if ( ! function_exists( 'dfp_ai_settings_page' ) ) {
	function dfp_ai_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$o   = dfp_ai_opts();
		$has = $o['api_key'] ? true : false;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AI: suggeritore di stack', 'digifluss-portfolio' ); ?></h1>
			<p style="max-width:640px;color:#50575e;">
				<?php esc_html_e( 'Inserisci una chiave API Anthropic (Claude): viene usata SOLO lato server per proporre uno stack tecnologico in base al tipo di progetto. La chiave non è mai visibile nel browser né agli utenti del sito. Ogni suggerimento è una chiamata a pagamento sul tuo account Anthropic.', 'digifluss-portfolio' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'dfp_ai_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dfp_ai_key"><?php esc_html_e( 'Chiave API', 'digifluss-portfolio' ); ?></label></th>
						<td>
							<input name="dfp_ai[api_key]" id="dfp_ai_key" type="password" autocomplete="off" class="regular-text" value="<?php echo esc_attr( $o['api_key'] ); ?>" placeholder="sk-ant-...">
							<p class="description"><?php echo $has ? esc_html__( 'Chiave impostata. Sovrascrivi il campo per cambiarla.', 'digifluss-portfolio' ) : esc_html__( 'Crea una chiave su console.anthropic.com e incollala qui.', 'digifluss-portfolio' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="dfp_ai_model"><?php esc_html_e( 'Modello', 'digifluss-portfolio' ); ?></label></th>
						<td>
							<input name="dfp_ai[model]" id="dfp_ai_model" type="text" class="regular-text" value="<?php echo esc_attr( $o['model'] ); ?>">
							<p class="description"><?php esc_html_e( 'Predefinito: claude-sonnet-4-6 (buon equilibrio qualità/costo per suggerimenti e traduzioni). Alternative: claude-haiku-4-5-20251001 (più economico), claude-opus-5 (massima qualità).', 'digifluss-portfolio' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
if ( ! function_exists( 'dfp_ai_rest' ) ) {
	function dfp_ai_rest() {
		register_rest_route(
			'dfp/v1',
			'/suggest-stack',
			array(
				'methods'             => 'POST',
				'callback'            => 'dfp_ai_suggest',
				'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
			)
		);
		register_rest_route(
			'dfp/v1',
			'/translate',
			array(
				'methods'             => 'POST',
				'callback'            => 'dfp_ai_translate_rest',
				'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
			)
		);
	}
	add_action( 'rest_api_init', 'dfp_ai_rest' );
}

if ( ! function_exists( 'dfp_ai_translate_post' ) ) {
	/**
	 * Traduce tutti i contenuti del case study in un locale e salva il
	 * risultato in _dfp_tr_{locale}. Ritorna il numero di campi tradotti
	 * o un WP_Error. Un'unica chiamata al modello per locale.
	 */
	function dfp_ai_translate_post( $pid, $locale ) {
		$o = dfp_ai_opts();
		if ( empty( $o['api_key'] ) ) {
			return new WP_Error( 'dfp_no_key', __( 'Chiave API non configurata. Vai in Portfolio → AI.', 'digifluss-portfolio' ), array( 'status' => 400 ) );
		}
		$locales = dfp_locales();
		if ( ! isset( $locales[ $locale ] ) ) {
			return new WP_Error( 'dfp_bad_locale', __( 'Lingua non supportata.', 'digifluss-portfolio' ), array( 'status' => 400 ) );
		}
		$fields = dfp_case_fields( $pid );
		if ( ! $fields ) {
			return new WP_Error( 'dfp_empty', __( 'Nessun contenuto da tradurre: compila prima i campi del case study.', 'digifluss-portfolio' ), array( 'status' => 400 ) );
		}

		$language = $locales[ $locale ];
		$sys = 'Sei un traduttore professionista specializzato in copy di marketing per studi digitali. '
			. 'Ricevi un JSON con i contenuti di un case study di portfolio in italiano. '
			. 'Traduci TUTTI i valori testuali in ' . $language . ', con stile naturale e professionale, non letterale. '
			. 'NON tradurre: nomi propri, nomi di brand e prodotti (es. Shopify, Theme Editor, Liquid), nomi di file e di codice, prezzi, numeri, sigle tecniche. '
			. 'Mantieni ESATTAMENTE la stessa struttura JSON, le stesse chiavi e lo stesso numero di elementi negli array. '
			. 'Mantieni i ritorni a capo (\n) e le righe che iniziano con "- " come elenchi. '
			. 'Rispondi SOLO con il JSON tradotto, senza commenti e senza code fence.';

		$payload = array(
			'model'      => $o['model'],
			'max_tokens' => 8000,
			'system'     => $sys,
			'messages'   => array( array( 'role' => 'user', 'content' => wp_json_encode( $fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) ),
		);
		// Sui modelli recenti il thinking consuma il budget max_tokens:
		// effort basso lo contiene (il parametro non esiste su Haiku 4.5).
		if ( ! preg_match( '/(haiku|-4-[015]\b|claude-3)/', $o['model'] ) ) {
			$payload['output_config'] = array( 'effort' => 'low' );
		}

		$res = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 180,
				'headers' => array(
					'content-type'      => 'application/json',
					'x-api-key'         => $o['api_key'],
					'anthropic-version' => '2023-06-01',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'dfp_http', $res->get_error_message(), array( 'status' => 502 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( 200 !== $code ) {
			$msg = isset( $json['error']['message'] ) ? $json['error']['message'] : __( 'Errore della API.', 'digifluss-portfolio' );
			return new WP_Error( 'dfp_api', $msg, array( 'status' => $code ? $code : 502 ) );
		}

		$text = '';
		if ( isset( $json['content'] ) && is_array( $json['content'] ) ) {
			foreach ( $json['content'] as $blk ) {
				if ( isset( $blk['type'], $blk['text'] ) && 'text' === $blk['type'] ) {
					$text .= $blk['text'];
				}
			}
		}
		// Estrae il JSON anche se il modello aggiunge testo o code fence.
		$start = strpos( $text, '{' );
		$end   = strrpos( $text, '}' );
		if ( false === $start || false === $end || $end <= $start ) {
			return new WP_Error( 'dfp_parse', __( 'Risposta del modello non interpretabile. Riprova.', 'digifluss-portfolio' ), array( 'status' => 502 ) );
		}
		$data = json_decode( substr( $text, $start, $end - $start + 1 ), true );
		if ( ! is_array( $data ) || ! $data ) {
			return new WP_Error( 'dfp_parse', __( 'Traduzione non valida. Riprova.', 'digifluss-portfolio' ), array( 'status' => 502 ) );
		}

		// Solo chiavi conosciute, per non salvare campi inventati dal modello.
		$data = array_intersect_key( $data, $fields );
		update_post_meta( $pid, '_dfp_tr_' . $locale, wp_slash( wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
		return count( $data );
	}
}

if ( ! function_exists( 'dfp_ai_translate_rest' ) ) {
	function dfp_ai_translate_rest( WP_REST_Request $req ) {
		$pid    = absint( $req->get_param( 'post_id' ) );
		$locale = (string) $req->get_param( 'locale' );
		if ( ! $pid || 'df_progetto' !== get_post_type( $pid ) || ! current_user_can( 'edit_post', $pid ) ) {
			return new WP_Error( 'dfp_forbidden', __( 'Progetto non valido.', 'digifluss-portfolio' ), array( 'status' => 403 ) );
		}
		$out = dfp_ai_translate_post( $pid, $locale );
		if ( is_wp_error( $out ) ) {
			return $out;
		}
		return rest_ensure_response( array( 'ok' => true, 'locale' => $locale, 'fields' => $out ) );
	}
}
if ( ! function_exists( 'dfp_ai_suggest' ) ) {
	function dfp_ai_suggest( WP_REST_Request $req ) {
		$o = dfp_ai_opts();
		if ( empty( $o['api_key'] ) ) {
			return new WP_Error( 'dfp_no_key', __( 'Chiave API non configurata. Vai in Portfolio → AI (stack).', 'digifluss-portfolio' ), array( 'status' => 400 ) );
		}
		$title     = sanitize_text_field( (string) $req->get_param( 'title' ) );
		$category  = sanitize_text_field( (string) $req->get_param( 'category' ) );
		$objective = sanitize_text_field( (string) $req->get_param( 'objective' ) );
		$motif_k   = sanitize_key( (string) $req->get_param( 'motif' ) );
		$motifs    = dfp_motifs();
		$motif     = isset( $motifs[ $motif_k ] ) ? $motifs[ $motif_k ] : $motif_k;

		$sys  = 'Sei un assistente tecnico per un\'agenzia di sviluppo web e software. Suggerisci uno stack tecnologico realistico e moderno in base al tipo di progetto. Rispondi SOLO con 4-7 tecnologie separate da virgola (linguaggi, framework, servizi/strumenti). Niente frasi, niente spiegazioni, nessun punto finale.';
		$user = sprintf(
			"Tipo di progetto: %s\nCategoria: %s\nObiettivo: %s\nTitolo: %s\n\nStack:",
			$motif ? $motif : '—',
			$category ? $category : '—',
			$objective ? $objective : '—',
			$title ? $title : '—'
		);

		$payload = wp_json_encode( array(
			'model'      => $o['model'],
			'max_tokens' => 200,
			'system'     => $sys,
			'messages'   => array( array( 'role' => 'user', 'content' => $user ) ),
		) );

		$res = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 25,
				'headers' => array(
					'content-type'      => 'application/json',
					'x-api-key'         => $o['api_key'],
					'anthropic-version' => '2023-06-01',
				),
				'body'    => $payload,
			)
		);

		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'dfp_http', $res->get_error_message(), array( 'status' => 502 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		$json = json_decode( $raw, true );
		if ( 200 !== $code ) {
			$msg = isset( $json['error']['message'] ) ? $json['error']['message'] : __( 'Errore della API.', 'digifluss-portfolio' );
			return new WP_Error( 'dfp_api', $msg, array( 'status' => $code ? $code : 502 ) );
		}

		$text = '';
		if ( isset( $json['content'] ) && is_array( $json['content'] ) ) {
			foreach ( $json['content'] as $blk ) {
				if ( isset( $blk['type'], $blk['text'] ) && 'text' === $blk['type'] ) {
					$text .= $blk['text'];
				}
			}
		}
		$text  = trim( preg_replace( '/^\s*stack\s*[:\-]?/i', '', trim( $text ) ) );
		$parts = preg_split( '/[,\n;]+/', $text );
		$stack = array();
		foreach ( (array) $parts as $p ) {
			$p = trim( $p, " \t\r\n\"'`.\xe2\x80\xa2*-" );
			if ( '' === $p ) { continue; }
			$lk = strtolower( $p );
			if ( isset( $stack[ $lk ] ) ) { continue; }
			$stack[ $lk ] = $p;
			if ( count( $stack ) >= 8 ) { break; }
		}
		return rest_ensure_response( array( 'stack' => array_values( $stack ), 'text' => $text ) );
	}
}

/* -----------------------------------------------------------------
 * 5. Asset — REGISTER sempre, ENQUEUE solo dove serve (shortcode/CPT)
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_register_assets' ) ) {
	function dfp_register_assets() {
		$style_deps  = wp_style_is( 'digifluss-style', 'registered' ) ? array( 'digifluss-style' ) : array();
		$script_deps = wp_script_is( 'digifluss-main', 'registered' ) ? array( 'digifluss-main' ) : array();

		// Versione = data di modifica del file: invalida cache di browser/CDN a ogni aggiornamento.
		$css_path = DIGIFLUSS_PORTFOLIO_PATH . 'assets/css/portfolio.css';
		$js_path  = DIGIFLUSS_PORTFOLIO_PATH . 'assets/js/portfolio.js';
		$css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : DIGIFLUSS_PORTFOLIO_VERSION;
		$js_ver   = file_exists( $js_path ) ? filemtime( $js_path ) : DIGIFLUSS_PORTFOLIO_VERSION;

		wp_register_style(
			'digifluss-portfolio',
			DIGIFLUSS_PORTFOLIO_URL . 'assets/css/portfolio.css',
			$style_deps,
			$css_ver
		);
		wp_register_script(
			'digifluss-portfolio',
			DIGIFLUSS_PORTFOLIO_URL . 'assets/js/portfolio.js',
			$script_deps,
			$js_ver,
			true
		);
	}
	add_action( 'wp_enqueue_scripts', 'dfp_register_assets', 20 );
}

if ( ! function_exists( 'dfp_maybe_enqueue' ) ) {
	/**
	 * Enqueue solo dove serve: pagina con shortcode, archivio o singolo df_progetto.
	 */
	function dfp_maybe_enqueue() {
		wp_enqueue_style( 'digifluss-portfolio' );
		wp_enqueue_script( 'digifluss-portfolio' );
	}
	add_action( 'wp', function () {
		if ( is_singular( 'df_progetto' ) || is_post_type_archive( 'df_progetto' ) ) {
			dfp_maybe_enqueue();
			return;
		}
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && has_shortcode( (string) $post->post_content, 'digifluss_portfolio' ) ) {
				dfp_maybe_enqueue();
			}
		}
	} );
}

/* -----------------------------------------------------------------
 * 6. Mock-up d'interfaccia (markup statico per ogni layout)
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_motif_html' ) ) {
	function dfp_motif_html( $type ) {
		switch ( $type ) {
			case 'landing':
				return '<div class="scene"><div class="win"><div class="win-bar"><i class="d c"></i><i class="d a"></i><i class="d m"></i><i class="win-pill wide"></i></div><div class="win-body m-landing"><div class="lp-hero"><div class="lp-copy"><i class="lp-h"></i><i class="lp-h2"></i><i class="lp-sub"></i><span class="lp-cta"></span></div><div class="lp-art"></div></div><div class="lp-cards"><i></i><i></i><i></i></div></div></div></div>';

			case 'corporate':
				return '<div class="scene"><div class="win"><div class="win-bar"><i class="d c"></i><i class="d a"></i><i class="d m"></i><i class="win-pill"></i></div><div class="win-body m-corp"><div class="corp-nav"><span class="corp-logo"></span><i></i><i></i><i></i><span class="corp-btn"></span></div><div class="corp-hero"><div class="corp-text"><i class="h"></i><i class="h2"></i><i class="s"></i></div><div class="corp-vis"></div></div><div class="corp-strip"><i></i><i></i><i></i><i></i></div></div></div></div>';

			case 'seo':
				return '<div class="scene"><div class="win"><div class="win-bar"><i class="d c"></i><i class="d a"></i><i class="d m"></i><i class="win-pill"></i></div><div class="win-body m-seo"><div class="seo-search"><span class="seo-mag"></span><i></i></div><div class="seo-list"><div class="seo-row top"><span class="seo-rank"></span><i></i></div><div class="seo-row"><span class="seo-rank"></span><i></i></div><div class="seo-row"><span class="seo-rank"></span><i></i></div></div><div class="seo-bars"><i></i><i></i><i></i><i></i><i></i><i></i></div></div></div></div>';

			case 'shop':
				$prod = '<div class="prod"><i class="pimg"></i><i class="pt"></i><i class="pp"></i></div>';
				return '<div class="scene"><div class="win"><div class="win-bar"><i class="d c"></i><i class="d a"></i><i class="d m"></i><i class="win-pill wide"></i></div><div class="win-body m-shop"><div class="shop-grid">' . str_repeat( $prod, 6 ) . '</div></div></div></div>';

			case 'code':
				return '<div class="scene"><div class="win"><div class="win-bar"><i class="d c"></i><i class="d a"></i><i class="d m"></i><i class="win-pill"></i></div><div class="win-body m-code"><div class="code-side"><i></i><i></i><i></i><i></i><i></i></div><div class="code-main"><i class="ln kw" style="width:32%"></i><i class="ln fn" style="width:60%;margin-left:14px"></i><i class="ln str" style="width:46%;margin-left:14px"></i><i class="ln" style="width:38%;margin-left:28px"></i><i class="ln kw" style="width:24%"></i><i class="ln fn" style="width:54%;margin-left:14px"></i><i class="ln str" style="width:40%;margin-left:28px"></i><i class="ln" style="width:30%"></i></div></div></div></div>';

			case 'flow':
				return '<div class="scene m-flow"><svg viewBox="0 0 200 250" preserveAspectRatio="xMidYMid meet"><g class="flines"><path d="M40,42 C92,42 70,118 120,120"></path><path d="M40,42 C60,100 60,158 100,200"></path><path d="M120,120 C152,120 160,180 160,210"></path><path d="M120,120 C92,150 110,180 100,200"></path><path d="M100,200 C132,200 150,210 160,210"></path></g><g class="fnodes"><circle class="nd c" cx="40" cy="42" r="9"></circle><circle class="nd m" cx="120" cy="120" r="11"></circle><circle class="nd c" cx="100" cy="200" r="8"></circle><circle class="nd a pulse" cx="160" cy="210" r="9"></circle></g></svg></div>';

			case 'phone':
				return '<div class="scene m-phone"><div class="phone"><span class="notch"></span><div class="screen"><i class="ph-top"></i><div class="ph-hero"></div><i class="ph-row"></i><i class="ph-row"></i><i class="ph-row short"></i><div class="ph-tabs"><i class="on"></i><i></i><i></i><i></i></div></div></div></div>';

			case 'ds':
				return '<div class="scene m-ds"><div class="ds-sw"><i class="c"></i><i class="m"></i><i class="a"></i><i class="ink"></i></div><div class="ds-main"><span class="ds-aa">Aa</span><div class="ds-comps"><i class="btn"></i><i class="ghost"></i><i class="tag"></i></div></div><div class="ds-rows"><i style="width:70%"></i><i style="width:50%"></i><i style="width:60%"></i></div></div>';

			case 'dashboard':
			default:
				return '<div class="scene"><div class="win"><div class="win-bar"><i class="d c"></i><i class="d a"></i><i class="d m"></i><i class="win-pill"></i></div><div class="win-body m-dash"><div class="stat-row"><div class="stat"><b></b><i></i></div><div class="stat"><b></b><i></i></div></div><svg class="chart" viewBox="0 0 120 46" preserveAspectRatio="none"><polyline class="ln" points="0,36 18,28 34,31 52,16 70,21 88,9 104,14 120,8"></polyline></svg><div class="dash-bars"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></div></div></div>';
		}
	}
}

/* -----------------------------------------------------------------
 * 7. Shortcode  [digifluss_portfolio]
 *    Sul tema Digifluss: testata con .df-kicker/.df-h2/.df-lead,
 *    CTA con .df-btn, opzionale wrap in .df-section/.df-container.
 *    Su altri temi: fallback con classi .dfp-* originali.
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_shortcode' ) ) {
	function dfp_shortcode( $atts ) {
		$a = shortcode_atts(
			array(
				'count'   => -1,
				'orderby' => 'menu_order date',
				'order'   => 'ASC',
				'kicker'  => __( 'Portfolio · Sviluppo web & software', 'digifluss-portfolio' ),
				'title'   => __( 'Progetti, non semplici siti.', 'digifluss-portfolio' ),
				'lead'    => __( 'Strategia, sviluppo e AI per trasformare strumenti isolati in ecosistemi digitali fluidi. Una selezione di casi recenti.', 'digifluss-portfolio' ),
				'all_url' => '', // opzionale: link "vedi tutti" (default: archivio CPT)
				'wrap'    => 'auto', // auto (avvolge in .df-section sul tema Digifluss) | section | bare
			),
			$atts,
			'digifluss_portfolio'
		);

		// Assicura asset disponibili anche se il shortcode è caricato dinamicamente.
		dfp_maybe_enqueue();

		$query = new WP_Query(
			array(
				'post_type'           => 'df_progetto',
				'posts_per_page'      => intval( $a['count'] ),
				'orderby'             => sanitize_text_field( $a['orderby'] ),
				'order'               => ( 'DESC' === strtoupper( $a['order'] ) ) ? 'DESC' : 'ASC',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return '';
		}

		$motifs = dfp_motifs();
		$cards  = '';
		$items  = array();
		$i      = 0;

		while ( $query->have_posts() ) {
			$query->the_post();
			$pid = get_the_ID();
			if ( ! dfp_current_locale_has_complete_translation( $pid ) ) {
				continue;
			}

			$title    = get_the_title();
			$category = get_post_meta( $pid, '_dfp_category', true );
			$year     = get_post_meta( $pid, '_dfp_year', true );
			$role     = get_post_meta( $pid, '_dfp_role', true );
			$stack    = array_values( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $pid, '_dfp_stack', true ) ) ) ) );
			// La card porta sempre al caso di studio interno; il link esterno
			// (_dfp_link) è il bottone "Vai al sito live" dentro quella pagina.
			$link = get_permalink( $pid );
			$motif = get_post_meta( $pid, '_dfp_motif', true );
			if ( ! isset( $motifs[ $motif ] ) ) { $motif = 'dashboard'; }
			$accent  = get_post_meta( $pid, '_dfp_accent', true );
			$accent2 = get_post_meta( $pid, '_dfp_accent2', true );
			if ( ! $accent ) { $accent = '#2ee7f2'; }
			if ( ! $accent2 ) { $accent2 = $accent; }
			$desc = wp_strip_all_tags( get_the_excerpt() );
			// Nelle lingue non italiane usa la traduzione, se esiste.
			$trc = dfp_translation( $pid );
			if ( $trc ) {
				if ( ! empty( $trc['category'] ) ) { $category = $trc['category']; }
				if ( ! empty( $trc['excerpt'] ) )  { $desc = wp_strip_all_tags( $trc['excerpt'] ); }
				if ( ! empty( $trc['role'] ) )     { $role = $trc['role']; }
			}
			$img  = get_the_post_thumbnail_url( $pid, 'large' );
			$idx  = str_pad( $i + 1, 2, '0', STR_PAD_LEFT );

			$items[] = array(
				'title' => $title,
				'cat'   => $category,
				'year'  => $year,
				'role'  => $role,
				'stack' => $stack,
				'desc'  => $desc,
				'link'  => $link,
			);

			// Con la copertina reale, il mockup della tipologia resta come
			// riquadro in miniatura sopra l'immagine (in alto a sinistra).
			$cover_inner = $img
				? '<div class="dfp-motif-mini" aria-hidden="true">' . dfp_motif_html( $motif ) . '</div>'
				: dfp_motif_html( $motif );
			$has_img     = $img ? ' has-image' : '';
			$img_style   = $img ? ' style="--img:url(\'' . esc_url( $img ) . '\')"' : '';
			$stack_json  = wp_json_encode( $stack );
			if ( false === $stack_json ) { $stack_json = '[]'; }
			/* translators: 1: indice del progetto, 2: titolo */
			$aria = sprintf( __( 'Progetto %1$d: %2$s', 'digifluss-portfolio' ), $i + 1, $title );

			$cards .= '<article class="dfp-card"'
				. ' style="--accent:' . esc_attr( $accent ) . '; --accent-2:' . esc_attr( $accent2 ) . '; --enter-delay:' . intval( $i * 70 ) . 'ms"'
				. ' role="button" tabindex="0"'
				. ' data-title="' . esc_attr( $title ) . '"'
				. ' data-cat="' . esc_attr( $category ) . '"'
				. ' data-year="' . esc_attr( $year ) . '"'
				. ' data-role="' . esc_attr( $role ) . '"'
				. ' data-stack="' . esc_attr( $stack_json ) . '"'
				. ' data-desc="' . esc_attr( $desc ) . '"'
				. ' data-link="' . esc_url( $link ) . '"'
				. ' aria-label="' . esc_attr( $aria ) . '">'
				. '<div class="dfp-cover' . $has_img . '"' . $img_style . '>'
				. '<div class="dfp-accent-glow"></div>'
				. '<div class="dfp-inner">'
				. $cover_inner
				. '<div class="dfp-shade"></div>'
				. '<div class="dfp-meta"><span class="dfp-index-badge">' . esc_html( $idx ) . '</span>'
				. '<div class="dfp-foot"><span class="dfp-name">' . esc_html( $title ) . '</span><span class="dfp-cat">' . esc_html( $category ) . '</span></div>'
				. '</div>'
				. '</div>'
				. '</div>'
				. '</article>';

			$i++;
		}
		wp_reset_postdata();

		$n = $i;
		if ( ! $n ) { return ''; }

		$dots = '';
		for ( $d = 0; $d < $n; $d++ ) {
			/* translators: %d: indice del progetto */
			$dot_label = sprintf( __( 'Vai al progetto %d', 'digifluss-portfolio' ), $d + 1 );
			$dots     .= '<button type="button" class="dfp-dot' . ( 0 === $d ? ' is-active' : '' ) . '" aria-label="' . esc_attr( $dot_label ) . '"></button>';
		}

		$first       = $items[0];
		$first_chips = '';
		foreach ( $first['stack'] as $s ) {
			$first_chips .= '<span class="dfp-chip">' . esc_html( $s ) . '</span>';
		}
		$first_meta = implode( '   ·   ', array_filter( array( $first['cat'], $first['year'], $first['role'] ) ) );

		// Wrap: auto = avvolge in .df-section/.df-container quando rileva il tema Digifluss.
		$wrap_mode = ( 'auto' === $a['wrap'] ) ? ( dfp_theme_active() ? 'section' : 'bare' ) : $a['wrap'];
		$themed    = ( 'section' === $wrap_mode ) || dfp_theme_active();

		$all_url = $a['all_url'] ? esc_url( $a['all_url'] ) : esc_url( (string) get_post_type_archive_link( 'df_progetto' ) );

		ob_start();
		?>
		<?php if ( 'section' === $wrap_mode ) : ?>
		<section class="df-section dfp-wrap" aria-label="<?php esc_attr_e( 'Portfolio', 'digifluss-portfolio' ); ?>">
			<div class="df-container">
		<?php endif; ?>

		<section class="dfp<?php echo $themed ? ' dfp--themed' : ''; ?>" aria-label="<?php esc_attr_e( 'Portfolio', 'digifluss-portfolio' ); ?>">
			<div class="dfp-head reveal">
				<div class="<?php echo $themed ? 'df-kicker' : 'dfp-kicker'; ?>"><span></span> <?php echo esc_html( $a['kicker'] ); ?></div>
				<h2 class="<?php echo $themed ? 'df-h2' : 'dfp-h2'; ?>"><?php echo esc_html( $a['title'] ); ?></h2>
				<p class="<?php echo $themed ? 'df-lead' : 'dfp-lead'; ?>"><?php echo esc_html( $a['lead'] ); ?></p>
			</div>

			<div class="dfp-carousel reveal" role="group" aria-roledescription="<?php esc_attr_e( 'carosello', 'digifluss-portfolio' ); ?>" aria-label="<?php esc_attr_e( 'Progetti del portfolio', 'digifluss-portfolio' ); ?>">
				<?php echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup costruito con escape per ogni valore dinamico. ?>
			</div>

			<div class="dfp-controls reveal">
				<div class="dfp-index"><b>01</b> / <?php echo esc_html( str_pad( $n, 2, '0', STR_PAD_LEFT ) ); ?></div>
				<div class="dfp-progress"><div class="dfp-fill" style="width:<?php echo esc_attr( ( 100 / max( 1, $n ) ) ); ?>%"></div></div>
				<div class="dfp-nav">
					<button type="button" class="dfp-prev" aria-label="<?php esc_attr_e( 'Progetto precedente', 'digifluss-portfolio' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M15 5l-7 7 7 7"/></svg></button>
					<button type="button" class="dfp-next" aria-label="<?php esc_attr_e( 'Progetto successivo', 'digifluss-portfolio' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9 5l7 7-7 7"/></svg></button>
				</div>
				<div class="dfp-dots"><?php echo $dots; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pulsanti con aria-label in escape. ?></div>
				<div class="dfp-hint"><?php esc_html_e( 'Trascina · ← →', 'digifluss-portfolio' ); ?></div>
			</div>

			<div class="dfp-panel reveal" aria-live="polite">
				<h3 class="dfp-title"><?php echo esc_html( $first['title'] ); ?></h3>
				<p class="dfp-metaline"><?php echo esc_html( $first_meta ); ?></p>
				<div class="dfp-stack"><?php echo $first_chips; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- chip con esc_html. ?></div>
				<p class="dfp-desc"><?php echo esc_html( $first['desc'] ); ?></p>
				<?php if ( $themed ) : ?>
					<a class="df-btn df-btn--ghost dfp-link" href="<?php echo esc_url( $first['link'] ); ?>"><?php esc_html_e( 'Vedi il caso di studio', 'digifluss-portfolio' ); ?> <span aria-hidden="true">&rarr;</span></a>
				<?php else : ?>
					<a class="dfp-link" href="<?php echo esc_url( $first['link'] ); ?>"><?php esc_html_e( 'Vedi il caso di studio', 'digifluss-portfolio' ); ?> <span aria-hidden="true">&rarr;</span></a>
				<?php endif; ?>
			</div>

			<?php if ( $all_url ) : ?>
				<div class="dfp-all reveal">
					<a class="<?php echo $themed ? 'inline-link' : 'dfp-link'; ?>" href="<?php echo esc_url( $all_url ); ?>"><?php esc_html_e( 'Vedi tutti i progetti', 'digifluss-portfolio' ); ?> <span aria-hidden="true">&rarr;</span></a>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( 'section' === $wrap_mode ) : ?>
			</div>
		</section>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}
	add_shortcode( 'digifluss_portfolio', 'dfp_shortcode' );
}

/* -----------------------------------------------------------------
 * 8. Template loader — usa i template del plugin per single/archive
 *    se il tema non ne fornisce di propri. Si integrano col tema
 *    perché chiamano get_header()/get_footer().
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_template_include' ) ) {
	function dfp_template_include( $template ) {
		if ( is_singular( 'df_progetto' ) ) {
			$theme_tpl = locate_template( array( 'single-df_progetto.php' ) );
			if ( ! $theme_tpl ) {
				$plugin_tpl = DIGIFLUSS_PORTFOLIO_PATH . 'templates/single-df_progetto.php';
				if ( file_exists( $plugin_tpl ) ) {
					return $plugin_tpl;
				}
			}
		}
		if ( is_post_type_archive( 'df_progetto' ) ) {
			$theme_tpl = locate_template( array( 'archive-df_progetto.php' ) );
			if ( ! $theme_tpl ) {
				$plugin_tpl = DIGIFLUSS_PORTFOLIO_PATH . 'templates/archive-df_progetto.php';
				if ( file_exists( $plugin_tpl ) ) {
					return $plugin_tpl;
				}
			}
		}
		return $template;
	}
	add_filter( 'template_include', 'dfp_template_include' );
}

/* -----------------------------------------------------------------
 * 9. Admin: colonne dedicate sulla lista Progetti
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_admin_columns' ) ) {
	function dfp_admin_columns( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['dfp_cat']  = __( 'Categoria', 'digifluss-portfolio' );
				$new['dfp_year'] = __( 'Anno', 'digifluss-portfolio' );
				$new['dfp_role'] = __( 'Obiettivo', 'digifluss-portfolio' );
			}
		}
		return $new;
	}
	add_filter( 'manage_df_progetto_posts_columns', 'dfp_admin_columns' );
}

if ( ! function_exists( 'dfp_admin_column_value' ) ) {
	function dfp_admin_column_value( $col, $pid ) {
		if ( 'dfp_cat' === $col )  { echo esc_html( get_post_meta( $pid, '_dfp_category', true ) ); }
		if ( 'dfp_year' === $col ) { echo esc_html( get_post_meta( $pid, '_dfp_year', true ) ); }
		if ( 'dfp_role' === $col ) { echo esc_html( get_post_meta( $pid, '_dfp_role', true ) ); }
	}
	add_action( 'manage_df_progetto_posts_custom_column', 'dfp_admin_column_value', 10, 2 );
}

/* -----------------------------------------------------------------
 * 10. Attivazione / disattivazione
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_activate' ) ) {
	function dfp_activate() {
		dfp_register_cpt();
		flush_rewrite_rules();
	}
	register_activation_hook( __FILE__, 'dfp_activate' );
}
if ( ! function_exists( 'dfp_deactivate' ) ) {
	function dfp_deactivate() {
		flush_rewrite_rules();
	}
	register_deactivation_hook( __FILE__, 'dfp_deactivate' );
}

/* -----------------------------------------------------------------
 * 12. SEO: JSON-LD per singolo progetto e archivio
 *     Article + BreadcrumbList sul case study, CollectionPage + ItemList
 *     sull'archivio. Emesso solo se nessun plugin SEO dedicato è attivo
 *     (stessa logica del tema Digifluss). Solo dati reali dei campi compilati.
 * ----------------------------------------------------------------- */
if ( ! function_exists( 'dfp_archive_label' ) ) {
	/**
	 * Etichetta dell'archivio ("Progetti") valida su ogni pagina: fuori
	 * dall'archivio post_type_archive_title() restituisce stringa vuota e
	 * Search Console segnala il breadcrumb senza "name".
	 */
	function dfp_archive_label() {
		$pto = get_post_type_object( 'df_progetto' );
		return $pto ? (string) $pto->labels->name : 'Progetti';
	}
}

if ( ! function_exists( 'dfp_seo_plugin_active' ) ) {
	function dfp_seo_plugin_active() {
		return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
	}
}

if ( ! function_exists( 'dfp_localized_meta' ) ) {
	/**
	 * Valore di un campo nel locale corrente. Nelle varianti straniere non
	 * ricade mai sull'italiano: una pagina incompleta viene esclusa prima.
	 */
	function dfp_localized_meta( $pid, $key, $tr ) {
		if ( is_array( $tr ) && ! empty( $tr[ $key ] ) && is_string( $tr[ $key ] ) ) {
			return $tr[ $key ];
		}
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		if ( 0 !== strpos( (string) $locale, 'it' ) ) {
			return '';
		}
		if ( 'excerpt' === $key ) {
			return (string) get_post_field( 'post_excerpt', $pid );
		}
		return (string) get_post_meta( $pid, '_dfp_' . $key, true );
	}
}

if ( ! function_exists( 'dfp_seo_head' ) ) {
	function dfp_seo_head() {
		if ( is_admin() || dfp_seo_plugin_active() ) {
			return;
		}

		$jsonld = static function ( $data ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		};
		$org = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
		);

		if ( is_singular( 'df_progetto' ) ) {
			$pid = get_queried_object_id();
			$tr  = dfp_translation( $pid );

			$desc = dfp_localized_meta( $pid, 'excerpt', $tr );
			if ( '' === trim( $desc ) ) {
				$desc = dfp_localized_meta( $pid, 'overview', $tr );
			}
			if ( '' === trim( $desc ) ) {
				$desc = dfp_localized_meta( $pid, 'challenge', $tr );
			}

			$images = array();
			if ( has_post_thumbnail( $pid ) ) {
				$images[] = get_the_post_thumbnail_url( $pid, 'large' );
			}
			foreach ( array_map( 'absint', explode( ',', (string) get_post_meta( $pid, '_dfp_gallery', true ) ) ) as $gid ) {
				if ( $gid && wp_attachment_is_image( $gid ) ) {
					$u = wp_get_attachment_image_url( $gid, 'large' );
					if ( $u ) {
						$images[] = $u;
					}
				}
			}

			$keywords = array();
			$keyword_sources = array(
				(string) get_post_meta( $pid, '_dfp_stack', true ),
				(string) get_post_meta( $pid, '_dfp_integrations', true ),
				dfp_localized_meta( $pid, 'tags', $tr ),
			);
			foreach ( $keyword_sources as $source ) {
				foreach ( explode( ',', $source ) as $piece ) {
					$piece = trim( $piece );
					if ( '' !== $piece ) {
						$keywords[] = $piece;
					}
				}
			}
			$keywords = array_values( array_unique( $keywords ) );

			$article = array(
				'@context'         => 'https://schema.org',
				'@type'            => 'Article',
				'headline'         => wp_strip_all_tags( get_the_title( $pid ) ),
				'mainEntityOfPage' => get_permalink( $pid ),
				'datePublished'    => get_the_date( 'c', $pid ),
				'dateModified'     => get_the_modified_date( 'c', $pid ),
				'author'           => $org,
				'publisher'        => $org,
				'inLanguage'       => str_replace( '_', '-', determine_locale() ),
			);
			$cs_title = dfp_localized_meta( $pid, 'cs_title', $tr );
			if ( '' !== trim( $cs_title ) ) {
				$article['alternativeHeadline'] = wp_strip_all_tags( $cs_title );
			}
			if ( '' !== trim( $desc ) ) {
				$article['description'] = wp_strip_all_tags( $desc );
			}
			if ( $images ) {
				$article['image'] = array_values( array_unique( $images ) );
			}
			if ( $keywords ) {
				$article['keywords'] = implode( ', ', $keywords );
			}
			$about = array_values( array_filter( array(
				dfp_localized_meta( $pid, 'product', $tr ),
				dfp_localized_meta( $pid, 'industry', $tr ),
			), 'strlen' ) );
			if ( $about ) {
				$article['about'] = 1 === count( $about ) ? $about[0] : $about;
			}
			$jsonld( $article );

			$archive = get_post_type_archive_link( 'df_progetto' );
			if ( $archive ) {
				$jsonld( array(
					'@context'        => 'https://schema.org',
					'@type'           => 'BreadcrumbList',
					'itemListElement' => array(
						array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ),
						array( '@type' => 'ListItem', 'position' => 2, 'name' => dfp_archive_label(), 'item' => $archive ),
						array( '@type' => 'ListItem', 'position' => 3, 'name' => wp_strip_all_tags( get_the_title( $pid ) ), 'item' => get_permalink( $pid ) ),
					),
				) );
			}
			return;
		}

		if ( is_post_type_archive( 'df_progetto' ) ) {
			$items    = array();
			$position = 0;
			$loop     = new WP_Query( array(
				'post_type'      => 'df_progetto',
				'posts_per_page' => 50,
				'no_found_rows'  => true,
			) );
			foreach ( $loop->posts as $p ) {
				if ( ! dfp_current_locale_has_complete_translation( $p->ID ) ) {
					continue;
				}
				$position++;
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'name'     => wp_strip_all_tags( get_the_title( $p ) ),
					'url'      => get_permalink( $p ),
				);
			}
			$jsonld( array(
				'@context'   => 'https://schema.org',
				'@type'      => 'CollectionPage',
				'name'       => dfp_archive_label(),
				'url'        => get_post_type_archive_link( 'df_progetto' ),
				'inLanguage' => str_replace( '_', '-', determine_locale() ),
				'publisher'  => $org,
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'itemListElement' => $items,
				),
			) );
			$jsonld( array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => array(
					array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ),
					array( '@type' => 'ListItem', 'position' => 2, 'name' => dfp_archive_label(), 'item' => get_post_type_archive_link( 'df_progetto' ) ),
				),
			) );
		}
	}
	add_action( 'wp_head', 'dfp_seo_head', 4 );
}

if ( ! function_exists( 'dfp_filter_meta_description' ) ) {
	/**
	 * Meta description localizzata per i progetti: il tema legge l'excerpt
	 * (italiano); qui, se esiste la traduzione del riassunto per il locale
	 * corrente, la si preferisce. Richiede il filtro `digifluss_meta_description`
	 * del tema Digifluss >= 2.10.1 (no-op su altri temi).
	 */
	function dfp_filter_meta_description( $desc ) {
		if ( ! is_singular( 'df_progetto' ) ) {
			return $desc;
		}
		$pid = get_queried_object_id();
		$tr  = dfp_translation( $pid );
		if ( dfp_current_locale_has_complete_translation( $pid ) && is_array( $tr ) && ! empty( $tr['excerpt'] ) && is_string( $tr['excerpt'] ) ) {
			$desc = wp_strip_all_tags( $tr['excerpt'] );
		}
		// Stesso limite di lunghezza del tema (le traduzioni allungano il testo).
		return function_exists( 'digifluss_trim_description' ) ? digifluss_trim_description( $desc ) : $desc;
	}
	add_filter( 'digifluss_meta_description', 'dfp_filter_meta_description' );
}

if ( ! function_exists( 'dfp_case_document_title' ) ) {
	/**
	 * Titolo SERP dei casi studio.
	 *
	 * Il nome del progetto da solo è un marchio: identico in tutte le lingue
	 * e muto su cosa sia il progetto ("Look And Feel Better – DigiFluss").
	 * Gli affianchiamo la categoria, che è tradotta, tagliandola per restare
	 * nei ~60 caratteri una volta aggiunto il nome del sito. Le categorie sono
	 * spesso composte ("Wellness E-commerce · Shopify Store"): se non ci sta
	 * intera si tiene la prima parte, quella che dice il settore.
	 */
	function dfp_case_document_title( $parts ) {
		if ( ! is_singular( 'df_progetto' ) ) {
			return $parts;
		}
		$pid  = (int) get_queried_object_id();
		$name = isset( $parts['title'] ) ? trim( (string) $parts['title'] ) : trim( (string) get_the_title( $pid ) );
		if ( '' === $name ) {
			return $parts;
		}
		$cat = (string) get_post_meta( $pid, '_dfp_category', true );
		$tr  = dfp_translation( $pid );
		if ( is_array( $tr ) && ! empty( $tr['category'] ) && is_string( $tr['category'] ) ) {
			$cat = $tr['category'];
		}
		$cat = trim( wp_strip_all_tags( $cat ) );
		if ( '' === $cat ) {
			return $parts; // progetto senza categoria: meglio il solo nome.
		}
		// 60 caratteri meno " – Nome del sito" e il separatore ": ".
		$budget = 60 - ( 3 + mb_strlen( (string) get_bloginfo( 'name' ) ) ) - mb_strlen( $name ) - 2;
		if ( mb_strlen( $cat ) > $budget ) {
			$first = preg_split( '/\s*[·|\/–—]\s*/u', $cat );
			$cat   = $first ? trim( $first[0] ) : $cat;
		}
		if ( $budget < 8 || mb_strlen( $cat ) > $budget ) {
			return $parts; // non ci sta nulla di sensato: nome da solo.
		}
		$parts['title'] = $name . ': ' . $cat;
		return $parts;
	}
	// Priorità 9 come la mappa SEO del tema: gira prima della traduzione
	// gettext del titolo, che sul composto non troverebbe corrispondenza.
	add_filter( 'document_title_parts', 'dfp_case_document_title', 9 );
}
