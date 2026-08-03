<!--
  Template: default | Filename: tippspiel.tpl.php | Fileversion: 1.0.0
  Changelog: 1.0.0 - Initiale Version: Tippspiel läuft jetzt als eigene Seite innerhalb des
                     Templates (?view=tippspiel auf home.php), analog zu liga.tpl.php. Anders
                     als bei liga.tpl.php gibt es hier KEINEN separaten "TabsBar"-Platzhalter -
                     die Tab-Leiste ist bereits Teil von "ViewInhalt" (siehe
                     renderTippspielUserBar()/renderTippspielTabsBar() in
                     addon/tipp/view_tippspiel_frontend.php), da sie nur bei den drei
                     eingeloggt-erforderlichen Ansichten erscheint, nicht bei Login/Registrierung.
  Inhalt der Tippspiel-Seite. Reines Markup + Platzhalter, kein PHP. Werte kommen vom
  Root-Controller home.php über renderTippspielView().
-->
<h1><!--Titel--></h1>

<!--ViewInhalt-->
