<!--
  Partial: standings_row.tpl.php | Fileversion: 1.3.0
  Eine Zeile in der Liga-Tabelle
  Changelog: 1.3.0 - Platzhalter "Logo" von "Team" getrennt (eigenes <span> mit fester Breite),
                     damit die Teamnamen untereinander bündig ausgerichtet bleiben, auch wenn
                     die Logos unterschiedlich breit sind (siehe .st-team-logo-wrap CSS)
  Changelog: 1.2.0 - Neuer Platzhalter "RowStyle" für die farbige Rand-Markierung (Champions
                     League/Europa League/Relegation/Abstieg usw., siehe Admin → Liga-
                     Einstellungen → Tabelle → Tabellenmarkierungen)
  Changelog: 1.1.0 - TeamClass ergänzt, damit die Lieblingsmannschaft (favTeam-Einstellung)
                      fett hervorgehoben werden kann
  Changelog: 1.0.0 - Initiale Version
-->
<tr<!--RowStyle-->>
  <td class="st-platz"><!--Platz--></td>
  <td class="st-team<!--TeamClass-->"><!--Logo--><!--Team--></td>
  <td class="st-num"><!--Sp--></td>
  <td class="st-num"><!--S--></td>
  <td class="st-num"><!--U--></td>
  <td class="st-num"><!--N--></td>
  <td class="st-num"><!--Tore--></td>
  <td class="st-num<!--DiffClass-->"><!--Diff--></td>
  <td class="st-pkt"><!--Pkt--></td>
</tr>
