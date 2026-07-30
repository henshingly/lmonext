<!--
  Template: default | Filename: liga.tpl.php | Fileversion: 2.1.0
  Changelog: 2.1.0 - "ZurueckLinkBlock" (vollständiger HTML-Block statt nur Text) ersetzt die
                     bisher fest verankerte Verlinkung, damit sich der Link über die neue
                     globale Einstellung "Übersicht-Link anzeigen?" komplett ausblenden lässt
                     (siehe renderBackLinkBlock() in liga.php)
  Inhalt der Liga-Detailseite. Reines Markup + Platzhalter, kein PHP.
  Werte kommen vom Root-Controller liga.php. "TabsBar" und "ViewInhalt" sind
  bereits fertige HTML-Blöcke – ViewInhalt enthält je nach gewähltem Reiter
  (Kalender/Ergebnisse/Spielpläne/Info) unterschiedlichen Inhalt.
-->
<!--ZurueckLinkBlock-->

<h1 class="liga-title">
  <!--LigaName-->
  <span class="chip <!--TypChipClass-->"><!--TypLabel--></span>
</h1>

<!--TabsBar-->

<!--ViewInhalt-->

