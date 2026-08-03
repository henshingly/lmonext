<!--
  Partial: standings_view.tpl.php | Fileversion: 1.2.0
  Rahmen um die Liga-Tabelle
  Changelog: 1.2.0 - Neuer Platzhalter "Fussnoten" für die Strafpunkte-Begründungen im
                     Wikipedia-Stil ("(1) Begründungstext" unter der Tabelle, siehe
                     renderStrafFootnotes() in src/Liga/StandingsTrait.php)
  Changelog: 1.1.0 - Wertungshinweis-Zeile entfernt
  Changelog: 1.0.0 - Initiale Version
-->
<div class="card">
  <div class="table-scroll">
    <table class="standings-table">
      <thead>
        <tr>
          <th class="st-platz"><!--ColPlatz--></th>
          <th class="st-team"><!--ColTeam--></th>
          <th class="st-num"><!--ColSp--></th>
          <th class="st-num"><!--ColS--></th>
          <th class="st-num"><!--ColU--></th>
          <th class="st-num"><!--ColN--></th>
          <th class="st-num"><!--ColTore--></th>
          <th class="st-num"><!--ColDiff--></th>
          <th class="st-pkt"><!--ColPkt--></th>
        </tr>
      </thead>
      <tbody>
<!--Rows-->
      </tbody>
    </table>
  </div>
  <!--Fussnoten-->
</div>
