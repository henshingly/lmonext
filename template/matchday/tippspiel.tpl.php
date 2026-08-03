<!--
  Template: matchday | Filename: tippspiel.tpl.php | Fileversion: 1.1.0
  Changelog: 1.1.0 - Neuer Platzhalter "ZurueckLinkBlock" ergänzt (siehe home.php 2.3.1)
  Changelog: 1.0.0 - Initiale Version (eigenständiges Template, siehe layout.tpl.php).
                     Tippspiel läuft jetzt als eigene Seite innerhalb des Templates
                     (?view=tippspiel auf home.php), analog zu liga.tpl.php. Kein separater
                     "TabsBar"-Platzhalter - siehe default/tippspiel.tpl.php für die Begründung.
  Inhalt der Tippspiel-Seite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom
  Root-Controller home.php über renderTippspielView(). "ZurueckLinkBlock" kommt von
  renderBackLinkBlock() (dieselbe Funktion wie bei liga.tpl.php).
-->
<!--ZurueckLinkBlock-->

<h1><!--Titel--></h1>

<!--ViewInhalt-->
