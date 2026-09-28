=== Digifluss Portfolio ===
Contributors: digifluss
Tags: portfolio, carousel, custom post type, shortcode
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.11.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Portfolio a carosello e case study multilingua: Custom Post Type "Progetti" + shortcode [digifluss_portfolio]. Integrato col tema Digifluss (auto-detect): testata, bottoni e template singolo/archivio riusano i componenti del tema. Su altri temi mantiene fallback autonomi.

== Description ==

Aggiunge un tipo di contenuto "Progetti" e uno shortcode che mostra i progetti
in un carosello editoriale (swipe/drag, frecce, tastiera, dots) con copertine
mock-up d'interfaccia o immagine in evidenza. Ogni progetto può diventare un
case study completo, importato da un file Markdown e tradotto in cinque lingue.

* Custom Post Type "Progetti" con campi dedicati (categoria, anno, ruolo, stack, link, layout copertina, colori accento).
* Case study approfondito: hero dedicato, panoramica, "Il prodotto", design e identità visiva, capacità chiave, approfondimenti tecnici (sfida, soluzione, tecnologie), architettura, tag e tempi di realizzazione. I campi vuoti non compaiono in pagina.
* Import da file .md (formato PROJECT ANALYSIS / PORTFOLIO CASE STUDY): il file viene letto nel browser e precompila i campi; i valori "[da confermare]" vengono saltati e nulla è salvato finché non si preme Aggiorna.
* Traduzione AI in en/de/fr/es/pt, una lingua alla volta, con avanzamento e interruzione. Una lingua viene pubblicata solo se tutti i campi compilati in italiano hanno una traduzione completa.
* Galleria con didascalie e lightbox; le immagini affiancano le sezioni narrative.
* Dati strutturati: Article e BreadcrumbList sul singolo progetto, CollectionPage e ItemList sull'archivio (disattivati se è attivo un plugin SEO dedicato).
* Shortcode [digifluss_portfolio] con attributi: count, orderby, order, kicker, title, lead, all_url, wrap.
* CSS isolato sotto .dfp; sul tema Digifluss eredita i token (--df-*), altrove usa i fallback.
* Nessuna libreria esterna.

== Servizi esterni ==

Le funzioni AI (traduzione del case study e suggerimento dello stack) sono
facoltative e restano spente finché non si inserisce una chiave API Anthropic
in Portfolio > AI (stack), oppure la costante DFFA_AI_KEY in wp-config.php.
Quando sono in uso, il plugin invia a https://api.anthropic.com i testi del
case study da tradurre, oppure titolo, tipo, categoria e obiettivo del
progetto per il suggerimento dello stack. Le chiamate partono dal server, solo su richiesta
di un utente che può modificare i progetti; la chiave non viene mai esposta ai
visitatori del sito.

Termini: https://www.anthropic.com/legal/commercial-terms
Privacy: https://www.anthropic.com/legal/privacy

== Installation ==

1. WordPress → Plugin → Aggiungi nuovo → Carica plugin → carica lo zip e attiva.
2. In wp-admin apri il menu "Portfolio" e crea i progetti.
3. Inserisci [digifluss_portfolio] in una pagina o articolo.

L'ordine si regola con il campo "Ordine" (Attributi pagina) o con
[digifluss_portfolio orderby="date" order="DESC"].

== Frequently Asked Questions ==

= Le pagine dei progetti danno 404 =
Il plugin rigenera i permalink in attivazione. Se necessario, vai su
Impostazioni → Permalink e premi "Salva".

= Posso usarlo con un tema diverso da Digifluss? =
Sì. L'aspetto resta coerente grazie ai fallback nel CSS; i colori del brand
si possono comunque sovrascrivere definendo le variabili --df-* nel tuo tema.

== Changelog ==

= 1.11.7 =
* Traduzione AI con output strutturato: la risposta segue uno schema
  ricavato dai campi del progetto, quindi il JSON è sempre valido anche
  quando il testo cita frasi tra virgolette (prima alcune lingue venivano
  scartate con "Traduzione non valida").
* Il prompt di traduzione vieta il trattino lungo e il trattino medio,
  come le regole di scrittura dei testi italiani.
* Margini adatti ai case study lunghi: risposta fino a 16.000 token,
  timeout di 300 secondi; messaggi chiari se la risposta viene troncata o
  rifiutata.
* La costante DIGIFLUSS_PORTFOLIO_VERSION torna allineata alla versione
  del plugin (era ferma alla 1.11.3).

= 1.11.6 =
* Titolo SERP dei casi studio: accanto al nome del progetto compare la
  categoria, accorciata per restare intorno ai 60 caratteri col nome del
  sito. Senza categoria resta il solo nome.

= 1.11.5 =
* La copertina del case study, primo elemento visibile della pagina, è
  servita da WordPress con caricamento immediato, priorità alta, width e
  height dichiarati e srcset, invece che in lazy loading.

= 1.11.4 =
* Autoriparazione delle rewrite rules: se la regola dell'archivio progetti
  manca (regole rigenerate senza il plugin caricato), viene rigenerata alla
  prima richiesta invece di lasciare archivio e casi studio in 404.

= 1.11.3 =
* Una variante linguistica del case study viene pubblicata solo se tutti i
  campi italiani compilati hanno una traduzione completa.
* Card, metadati, keyword e schema ItemList non ricadono più su contenuti
  italiani nelle lingue estere.
* Il plugin comunica le lingue realmente disponibili a switcher, hreflang,
  sitemap e llms.txt del tema Digifluss.

= 1.11.2 =
* La meta description tradotta del progetto rispetta lo stesso limite di
  lunghezza del tema (circa 158 caratteri, tagliata a fine frase o parola).

= 1.11.1 =
* La copertina non si ripete più come prima immagine affiancata al testo:
  resta solo in apertura e le altre immagini scalano di una posizione.
* Breadcrumb dei dati strutturati: il secondo elemento ha sempre un nome,
  anche sul singolo progetto (segnalazione di Search Console).

= 1.11.0 =
* Dati strutturati: Article e BreadcrumbList sul singolo progetto, con i
  campi della lingua corrente; CollectionPage e ItemList sull'archivio.
  Disattivati se è attivo un plugin SEO dedicato.
* Meta description del progetto tradotta nelle lingue estere (filtro
  digifluss_meta_description del tema).

= 1.10.0 =
* Pannello traduzioni: scelta delle lingue, barra di avanzamento con
  contatore e cronometro, pulsante Interrompi che salva comunque la lingua
  in corso. Le lingue già tradotte partono deselezionate.
* Galleria: le immagini eliminate dalla libreria media non lasciano più
  riquadri vuoti in pagina; didascalia sotto ogni immagine (didascalia
  della libreria media, altrimenti testo alternativo, altrimenti nome file).

= 1.9.1 =
* La card attiva del carosello apre il case study (anche con Invio e
  Spazio); il link al sito live del cliente resta come pulsante nella
  pagina.
* Barra di controllo del carosello subito sotto le card.

= 1.9.0 =
* Traduzione AI del case study in en/de/fr/es/pt tramite l'API Anthropic:
  una chiamata per lingua, salvata a parte; la pagina mostra i campi
  tradotti secondo la lingua. Chiave nelle impostazioni del plugin o nella
  costante DFFA_AI_KEY.
* Galleria in admin riordinabile con il trascinamento; con la copertina
  impostata, la card mantiene il mockup in miniatura.

= 1.8.0 =
* Sezione "Design e identità visiva": introduzione e blocchi, con elenchi
  puntati; riconosciuta anche dall'import .md.
* Immagini della galleria affiancate alle sezioni narrative e lightbox.
* Corretto il salvataggio dei blocchi ripetibili: gli a capo e le
  virgolette non vengono più corrotti.

= 1.7.1 =
* Campo "Tempi di realizzazione", mostrato nella scheda laterale e
  riconosciuto dall'import .md.

= 1.7.0 =
* Import del case study da file .md (formato PROJECT ANALYSIS / PORTFOLIO
  CASE STUDY): il file viene letto nel browser e precompila i campi; i
  valori "[da confermare]" vengono saltati e nulla è salvato finché non si
  preme Aggiorna.

= 1.6.0 =
* Case study approfondito: hero dedicato, scheda laterale estesa,
  panoramica, "Il prodotto", capacità chiave, approfondimenti tecnici
  (sfida, soluzione, tecnologie), architettura e tag. I campi vuoti non
  compaiono e i progetti esistenti restano invariati.

= 1.5.0 =
* Campi caso studio aggiuntivi: "Cliente / Settore" e "Integrazioni"
  (chip nella sidebar del progetto). Nessuna migrazione richiesta:
  i meta esistenti restano invariati.
* Consolidati nella sorgente i miglioramenti della 1.4.0: campi
  Sfida / Approccio / Risultato, galleria immagini con media library,
  suggeritore stack AI opzionale (chiave solo lato server), carosello
  con flick e trackpad, nuovi layout copertina.

= 1.1.1 =
* Editor classico per "Progetti": Gutenberg disattivato solo su questo CPT,
  così il pannello "Dettagli progetto" appare subito sotto l'editor invece di
  finire collassato a piè di pagina. REST resta abilitata.

= 1.1.0 =
* Integrazione col tema Digifluss (auto-detect): lo shortcode si avvolge in
  .df-section/.df-container, testata con .df-kicker/.df-h2/.df-lead, CTA con
  .df-btn. Niente più sezione "isolata" sul tema.
* Nuovi template inclusi per il singolo progetto (single-df_progetto.php) e
  per l'archivio (archive-df_progetto.php): riusano .split-section,
  .sticky-block, .rich-text, .cta-block e i bottoni del tema.
* Reveal-on-scroll del tema agganciato a testata, carosello, pannello e
  controlli (entrano in scena come le altre sezioni del tema).
* Link "Vedi tutti i progetti" in fondo allo shortcode (punta all'archivio
  CPT di default; configurabile con all_url="...").
* Nuovo attributo wrap="auto|section|bare" per controllare l'integrazione.
* Asset registrati globalmente ma fatti enqueue solo dove servono
  (pagina con shortcode, archivio o singolo progetto).
* Admin: colonne Categoria / Anno / Ruolo nella lista Progetti.

= 1.0.0 =
* Prima versione: CPT Progetti, meta box, shortcode carosello, fallback CSS.
