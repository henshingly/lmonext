<!DOCTYPE html>
<html lang="<!--HtmlLang-->">
<head>
<!--
  Template: dark | Filename: layout.tpl.php | Fileversion: 1.2.8
  Changelog: 1.2.8 - CSS für den neuen "Spielfrei"-Hinweis (spielfrei_note.tpl.php, fällt für
                     dieses Template auf das gleichnamige Partial von "default" zurück) ergänzt
  Changelog: 1.2.7 - .team-logo-inline margin-left ergänzt, siehe template/default/layout.tpl.php 1.15.4
  Changelog: 1.2.6
  Changelog: 1.2.6 - .st-team-logo-wrap ergänzt, siehe template/default/layout.tpl.php 1.15.3
  Changelog: 1.2.5
  Changelog: 1.2.5 - .team-logo-inline: max-width-Grenze entfernt, siehe
                     template/default/layout.tpl.php 1.15.2
  Changelog: 1.2.4
  Changelog: 1.2.4 - .team-logo-inline: feste Höhe + automatische Breite statt fixem Quadrat,
                     siehe template/default/layout.tpl.php 1.15.1
  Changelog: 1.2.3
  Changelog: 1.2.3 - CSS für .team-logo-inline ergänzt, siehe template/default/layout.tpl.php 1.15.0
  Changelog: 1.2.2
  Changelog: 1.2.2 - Basis-CSS für farbige Tabellenmarkierungen ergänzt, siehe
                     template/default/layout.tpl.php 1.14.9
  Changelog: 1.2.1 - CSS für responsive Lang-/Kurzform im Teamvergleich-Modal ergänzt, siehe
                     template/default/layout.tpl.php 1.14.8
  Changelog: 1.2.0 - Header komplett entfernt (kein Logo/Schriftzug mehr). Sprachauswahl in den
                     Footer verschoben, direkt über der "LMOnext {Version}"-Zeile, gleiche
                     dezente Optik wie das Template-Auswahl-Dropdown dort
  Changelog: 1.1.0 - Bild-Logo im Header entfernt, ersetzt durch einen Text-Schriftzug
                     ("LMOnext" in Akzentfarbe/Textfarbe), analog zum Colored-Template
  Changelog: 1.0.0 - Initiale Version: drittes Frontend-Template ("Dark"), komplett
                     eigenstaendiges CSS. Ueberwiegend dunkles Design (dunkler Seitenhintergrund,
                     dunkle Karten/Tabellen, helle Schrift, kraeftiges Hellblau als Akzent).
                     Kopfzeile nutzt bewusst einen etwas helleren Dunkelton als der Seiten-
                     hintergrund (var(--surface) statt var(--bg)), damit das (unveraenderte)
                     Bild-Logo lesbar bleibt. Fusszeile inhaltlich unveraendert wie im
                     Default-Template. home.tpl.php/liga.tpl.php/alle Partials werden nicht
                     dupliziert - sie enthalten kein CSS/Logo und fallen automatisch auf
                     template/default/ zurueck (siehe loadTemplateFile() in template_engine.php)
-->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><!--Titel--></title>
<link rel="shortcut icon" href="assets/favicon/favicon.ico">
<link rel="apple-touch-icon" sizes="57x57" href="assets/favicon/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="assets/favicon/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="assets/favicon/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="assets/favicon/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="assets/favicon/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="assets/favicon/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="assets/favicon/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="assets/favicon/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192" href="assets/favicon/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="assets/favicon/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
<link rel="manifest" href="assets/favicon/manifest.json">
<meta name="msapplication-TileColor" content="#38bdf8">
<meta name="msapplication-TileImage" content="assets/favicon/ms-icon-144x144.png">
<meta name="msapplication-config" content="assets/favicon/browserconfig.xml">
<meta name="theme-color" content="#38bdf8">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#12141c;--surface:#1b1e29;--surface2:#232838;--border:#323952;
  --text:#e7e9ee;--muted:#8b93a7;--accent:#38bdf8;
  --green:#4ade80;--yellow:#fbbf24;
  --radius:10px;--font:'Segoe UI',system-ui,-apple-system,sans-serif;
}
body{font-family:var(--font);background:var(--bg);color:var(--text);line-height:1.5;min-height:100vh}
a{color:inherit}
.team-logo-inline{height:18px;width:auto;vertical-align:middle;margin-right:5px;margin-left:5px;border-radius:3px}
.st-team-logo-wrap{display:inline-block;min-width:26px;text-align:center;vertical-align:middle}

main{max-width:920px;margin:0 auto;padding:28px 20px 60px}

.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:20px 24px;margin-bottom:20px}
.card h2{font-size:1.02rem;font-weight:600;margin-bottom:14px;color:var(--text)}

.liga-list{list-style:none}
.liga-list li{border-bottom:1px solid var(--border)}
.liga-list li:last-child{border-bottom:none}

a.liga-link{display:flex;align-items:center;gap:10px;padding:11px 4px;text-decoration:none;color:var(--text)}
a.liga-link:hover{color:var(--accent)}
a.liga-link .liga-name{font-weight:500}

.chip{display:inline-block;font-size:.72rem;padding:2px 9px;border-radius:20px;font-weight:600;white-space:nowrap}
.chip-blue{background:#173547;color:var(--accent)}
.chip-yellow{background:#3a2f14;color:var(--yellow)}

details.archiv-folder{border-bottom:1px solid var(--border)}
details.archiv-folder:last-child{border-bottom:none}
details.archiv-folder summary{cursor:pointer;padding:11px 4px;font-weight:500;list-style:none;
  display:flex;align-items:center;gap:8px;user-select:none}
details.archiv-folder summary::-webkit-details-marker{display:none}
details.archiv-folder summary .arrow{transition:transform .15s;display:inline-block;color:var(--muted);font-size:.72rem}
details.archiv-folder[open]>summary .arrow{transform:rotate(90deg)}
details.archiv-folder .folder-desc{color:var(--muted);font-size:.82rem;font-weight:400}
.folder-content{padding:2px 0 8px 22px}
.folder-content.folder-empty{color:var(--muted);font-size:.85rem;padding-left:22px}

.empty-msg{color:var(--muted);font-size:.9rem}

.back-link{display:inline-block;color:var(--muted);text-decoration:none;font-size:.85rem;margin-bottom:16px}
.back-link:hover{color:var(--accent)}

.liga-title{font-size:1.4rem;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.liga-subtitle{font-size:1.05rem;font-weight:600;color:var(--text);margin-bottom:14px}

.spieltag-picker{display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:.88rem}
.spieltag-picker select{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:7px 12px;font-size:.88rem;color:var(--text);font-family:var(--font);cursor:pointer}

.spieltag-heading{font-size:1rem;font-weight:600;margin-bottom:10px}

.table-scroll{overflow-x:auto;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:10px}
table.results-table{width:100%;border-collapse:collapse;font-size:.88rem;background:var(--surface)}
table.results-table thead th{background:var(--surface2);color:#fff;text-align:left;padding:10px 14px;
  font-weight:600;font-size:.83rem;white-space:nowrap}
table.results-table thead th.col-ergebnis{text-align:center}
table.results-table thead th.col-heim{text-align:right}
table.results-table tbody tr:nth-child(even){background:var(--bg)}
table.results-table td{padding:9px 14px;border-top:1px solid var(--border)}
table.results-table td.col-datum{color:var(--muted);white-space:nowrap;font-size:.83rem}
table.results-table td.col-heim{text-align:right}
table.results-table td.col-gast{text-align:left}
table.results-table td.col-ergebnis{text-align:center;font-weight:700;white-space:nowrap}
table.results-table td.col-ergebnis.ergebnis-offen{color:var(--muted);font-weight:500}
table.results-table td.col-vergleich,table.results-table thead th.col-vergleich{width:1%;text-align:center;padding-left:6px;padding-right:10px}

.h2h-icon{background:none;border:none;padding:4px;margin:0;color:var(--muted);cursor:pointer;
  border-radius:6px;display:inline-flex;align-items:center;justify-content:center;line-height:0}
.h2h-icon:hover{color:var(--accent);background:#173547}

.h2h-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;
  justify-content:center;padding:20px;z-index:1000}
.h2h-overlay[hidden]{display:none}
.h2h-modal{background:var(--surface);border-radius:var(--radius);max-width:560px;width:100%;
  max-height:85vh;overflow-y:auto;padding:24px 26px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.55)}
.h2h-close{position:absolute;top:14px;right:14px;background:none;border:none;font-size:1.4rem;
  line-height:1;color:var(--muted);cursor:pointer;padding:4px}
.h2h-close:hover{color:var(--text)}
.h2h-title{margin:0 34px 16px 0;font-size:1.1rem;color:var(--text)}
.h2h-record{display:flex;gap:8px;margin-bottom:18px}
.h2h-chip{flex:1;text-align:center;padding:8px 6px;border-radius:8px;font-weight:700;font-size:.95rem;
  background:var(--bg);color:var(--text)}
.h2h-chip-a,.h2h-chip-b{display:flex;flex-direction:column;gap:3px}
.h2h-chip-label{font-weight:600;font-size:.72rem;color:var(--muted);white-space:normal;line-height:1.2}
.h2h-chip-num{font-size:1.15rem;font-weight:700}
.h2h-chip-draw{font-weight:600;font-size:.82rem;color:var(--muted)}
.h2h-list{display:flex;flex-direction:column;gap:2px}
.h2h-match-row{padding:9px 0;border-top:1px solid var(--border)}
.h2h-match-row:first-child{border-top:none}
.h2h-match-meta{font-size:.75rem;color:var(--muted);margin-bottom:4px;text-decoration:none;display:inline-block}
.h2h-rd-short{display:none}
@media (max-width:480px){.h2h-rd-long{display:none}.h2h-rd-short{display:inline}}
.h2h-match-meta:hover{color:var(--accent);text-decoration:underline}
.h2h-match-teams{display:flex;align-items:center;gap:10px;font-size:.9rem}
.h2h-match-team{flex:1;color:var(--muted)}
.h2h-match-team:first-child{text-align:right}
.h2h-match-team.h2h-winner{color:var(--text);font-weight:700}
.h2h-match-score{flex:none;font-weight:700;color:var(--text);min-width:44px;text-align:center}
.h2h-empty{color:var(--muted);font-size:.88rem}

.spieltag-stats{font-size:.82rem;color:var(--muted);margin-bottom:20px}

.pdf-export-row{text-align:right;margin-top:14px}
.btn-pdf-export{display:inline-flex;align-items:center;gap:7px;background:#173547;color:var(--accent);
  border:1px solid #2a4258;padding:8px 16px;border-radius:var(--radius);font-size:.85rem;font-weight:700;
  text-decoration:none;transition:background .15s ease,color .15s ease,border-color .15s ease}
.btn-pdf-export:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.spieltag-stats .stats-heading{font-weight:600;color:var(--text);margin-bottom:3px}
.spielfrei-note{font-size:.85rem;color:var(--muted);margin:-8px 0 16px;padding:8px 12px;
  background:var(--bg);border-radius:var(--radius);border:1px dashed var(--border)}
.spielfrei-note strong{color:var(--text)}

.tabs-bar{display:flex;gap:0;flex-wrap:wrap;margin-bottom:16px;border-bottom:1px solid var(--border)}
.tab-item{padding:9px 16px;font-size:.86rem;text-decoration:none;color:var(--muted);
  border-bottom:2px solid transparent;margin-bottom:-1px}
.tab-item:hover{color:var(--text)}
.tab-item-active{color:var(--accent);font-weight:600;border-bottom-color:var(--accent)}

.card p{margin-bottom:10px;font-size:.9rem;line-height:1.55}
.card p:last-child{margin-bottom:0}
.info-copyright{color:var(--muted);font-size:.85rem}
.info-license{color:var(--muted);font-size:.8rem;margin-top:14px}
.info-links{font-size:.85rem;color:var(--muted)}
.info-links a{color:var(--accent);text-decoration:none}
.info-links a:hover{text-decoration:underline}

.cal-nav{display:flex;align-items:center;gap:14px;margin-bottom:14px;font-size:.9rem}
.cal-nav a{text-decoration:none;color:var(--accent)}
.cal-monthyear{font-weight:600;flex:1;text-align:center}
.cal-today-link{font-size:.8rem;color:var(--muted) !important}
table.cal-table{width:100%;border-collapse:collapse;table-layout:fixed}
table.cal-table th{background:var(--surface2);color:#fff;padding:6px 4px;font-size:.75rem;font-weight:600}
table.cal-table td.cal-day{border:1px solid var(--border);vertical-align:top;height:56px;padding:3px 4px;font-size:.78rem}
table.cal-table td.cal-empty{background:var(--bg);border:1px solid var(--border)}
.cal-daynum{color:var(--muted);font-size:.72rem;margin-bottom:2px}
td.cal-today .cal-daynum{color:var(--accent);font-weight:700}
a.cal-entry{display:inline-block;background:#173547;color:var(--accent);border-radius:8px;
  padding:1px 5px;font-size:.7rem;text-decoration:none;margin:0 2px 2px 0}

.bracket-scroll{overflow-x:auto;padding-bottom:6px}
.bracket{display:flex;gap:22px;min-width:min-content}
.bracket-round{display:flex;flex-direction:column;min-width:170px}
.bracket-round-heading{font-size:.78rem;font-weight:600;color:var(--muted);text-align:center;
  margin-bottom:10px;text-transform:uppercase;letter-spacing:.03em}
.bracket-round-pairings{display:flex;flex-direction:column;justify-content:space-around;flex:1}
.bracket-pairing{background:var(--bg);border:1px solid var(--border);border-radius:8px;
  padding:8px 10px;min-height:64px;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center}
.bracket-team{padding:1px 0;font-size:.83rem;line-height:1.35}
.bracket-team-name{display:inline-block}
.bracket-score{margin-top:3px;font-size:.78rem;font-weight:700;color:var(--accent);text-align:center}
.bracket-compare{display:flex;justify-content:center;margin-top:2px}
.bracket-kickoff{margin-top:2px;font-size:.68rem;color:var(--muted);text-align:center}

table.standings-table{width:100%;border-collapse:collapse;font-size:.87rem;background:var(--surface)}
table.standings-table tbody tr{border-left:4px solid transparent}
table.standings-table thead th{background:var(--surface2);color:#fff;padding:9px 10px;font-weight:600;font-size:.8rem;white-space:nowrap}
table.standings-table tbody tr:nth-child(even){background:var(--bg)}
table.standings-table td{padding:8px 10px;border-top:1px solid var(--border)}
.st-platz{width:1%;text-align:center;color:var(--muted);font-weight:600}
.st-team{font-weight:500;white-space:nowrap}
.st-num{text-align:center;width:1%;white-space:nowrap}
.st-pkt{text-align:center;font-weight:700;color:var(--accent);width:1%;white-space:nowrap}
.st-num.diff-pos{color:var(--green)}
.st-num.diff-neg{color:#f87171}

.schedule-wrap{display:flex;gap:0;padding:0;overflow:hidden}
.schedule-sidebar{flex:0 0 170px;display:flex;flex-direction:column;border-right:1px solid var(--border);max-height:520px;overflow-y:auto}
.team-sidebar-item{padding:8px 12px;text-align:left;font-size:.82rem;font-weight:600;color:var(--accent);
  text-decoration:none;border-bottom:1px solid var(--border)}
.team-sidebar-item:hover{background:var(--bg)}
.team-sidebar-item.team-sidebar-active{background:var(--accent);color:#fff}
.schedule-content{flex:1;padding:20px 24px;min-width:0}
.schedule-content .empty-msg{margin:0}
.col-nr{color:var(--muted);width:1%;white-space:nowrap;text-align:center}
.col-vs{color:var(--muted);width:1%;text-align:center}
.col-heim,.col-gast{text-align:left}
.col-heim.schedule-own,.col-gast.schedule-own{font-weight:700;color:var(--text)}
.st-team.fav-team{font-weight:700}

table.kreuz-table{border-collapse:collapse;font-size:.78rem;background:var(--surface);white-space:nowrap}
table.kreuz-table th, table.kreuz-table td{border:1px solid var(--border);padding:5px 8px}
table.kreuz-table thead th{background:var(--surface2);color:#fff;font-weight:600;text-align:center}
.kz-corner{background:var(--surface2)}
.kz-rowlabel{background:var(--surface2);color:#fff;font-weight:600;text-align:left;position:sticky;left:0}
.kz-cell{text-align:center;color:var(--text)}
.kz-cell.kz-diag{background:var(--bg)}
.kz-col.kz-fav,.kz-rowlabel.kz-fav{background:var(--accent)}
.kz-col:hover,.kz-rowlabel:hover{background:#2c5273}
.kz-cell.kz-fav-row,.kz-cell.kz-fav-col{background:#173547}
.kz-cell.kz-diag.kz-fav-row,.kz-cell.kz-diag.kz-fav-col{background:#204a63}

.fk-chart-wrap{position:relative;width:100%;min-height:420px}

.ligastat-picker{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.ligastat-picker select{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);
  padding:7px 12px;font-size:.88rem;color:var(--text);font-family:var(--font);cursor:pointer;flex:1;min-width:180px}
.ligastat-compare{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:14px}
@media (max-width:640px){.ligastat-compare{grid-template-columns:1fr}}
.ligastat-box{border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;background:var(--bg)}
.ligastat-box h3{font-size:.98rem;margin-bottom:10px}
table.ligastat-kv{width:100%;font-size:.83rem;border-collapse:collapse}
table.ligastat-kv td{padding:5px 4px;border-top:1px solid var(--border);vertical-align:top}
table.ligastat-kv td:first-child{color:var(--muted);width:46%}
.ligastat-chances{font-size:.92rem;margin:14px 0}
table.ligastat-overall{width:100%;border-collapse:collapse;font-size:.83rem;margin:12px 0}
table.ligastat-overall th{background:var(--surface2);color:#fff;padding:7px 10px;text-align:left;font-weight:600;font-size:.78rem}
table.ligastat-overall td{padding:7px 10px;border-top:1px solid var(--border)}
.ligastat-remaining-eval{margin-top:14px}

footer.site{text-align:center;color:var(--muted);font-size:.8rem;padding:24px 20px}
footer.site .template-switch,footer.site .lang-switch{display:inline-block}
footer.site .template-switch select,footer.site .lang-switch select{background:transparent;border:none;color:var(--muted);
  font-size:.8rem;font-family:inherit;padding:0;cursor:pointer;text-decoration:underline;text-underline-offset:2px}
footer.site .template-switch select:hover,footer.site .lang-switch select:hover{color:var(--accent)}
</style>
</head>
<body>

<main>
<!--Hauptteil-->
</main>

<footer class="site"><!--Sprachauswahl--><br>LMOnext <!--Version--><br><!--TemplateZeile--><br><!--Berechnungszeit--></footer>

</body>
</html>
