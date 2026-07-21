<!--
  Template: addon/mini | Filename: mininext.tpl.php | Fileversion: 1.1.0
  Changelog: 1.1.0 - Team-Anzeige auf Wunsch umgebaut: Logo steht jetzt neben dem Namen statt
                     darüber ("TEAMNAME LOGO -:- LOGO TEAMNAME", Logos "schauen" zum Ergebnis in
                     der Mitte), analog zur Ergebnisse-Ansicht der normalen Besucherseite.
                     CSS-Selektor für die Logo-Größe generisch auf "img im Team-Bereich" gesetzt
                     statt auf die globale .team-logo-inline-Klasse (existiert auf dieser
                     eigenständigen Seite nicht). Gleiche Anpassung im "Vorheriges Spiel"-Block
  Changelog: 1.0.1
  Changelog: 1.0.1 - box-sizing:border-box + display:inline-block ergänzt (gleiche defensive
                     Absicherung wie beim Minitabelle-Template-Fix, siehe standard.tpl.php 1.1.0)
                     gegen abweichende CSS-Regeln auf der Zielseite
  Changelog: 1.0.0 - Initiale Version, angelehnt an das gleichnamige Template des alten LMO
                     (siehe template/mini/mininext.tpl.php dort, vom Nutzer als Referenz
                     bereitgestellt), aber mit eigenständigem, modernem CSS passend zum
                     LMOnext-Look. Läuft komplett unabhängig vom restlichen LMOnext-Stylesheet,
                     da dieses Widget auf fremden Webseiten eingebunden wird (kein Zugriff auf
                     die dortigen CSS-Variablen). Platzhalternamen bewusst identisch zur
                     Referenzvorlage gehalten, siehe addon/mini/lmo-mininext.php.
  Reines Markup + Platzhalter, kein PHP.
-->
<style>
.lmo-mininext{display:inline-block;box-sizing:border-box;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;font-size:.8rem;
  background:#fff;border:1px solid #e3e7ee;border-radius:8px;overflow:hidden;max-width:300px}
.lmo-mininext .lmo-mn-head{background:#153A8C;color:#fff;font-weight:700;padding:6px 10px}
.lmo-mininext .lmo-mn-countdown{text-align:center;padding:6px 8px 0;color:#697182;font-size:.72rem}
.lmo-mininext .lmo-mn-date{text-align:center;padding:2px 8px 6px;color:#9098a8;font-size:.72rem}
.lmo-mininext .lmo-mn-teams{display:flex;align-items:center;justify-content:space-between;gap:6px;padding:6px 8px}
.lmo-mininext .lmo-mn-team{flex:1;display:flex;align-items:center;gap:5px;font-weight:600;color:#1f2430;font-size:.74rem;min-width:0}
.lmo-mininext .lmo-mn-team span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lmo-mininext .lmo-mn-team-a{justify-content:flex-end;text-align:right}
.lmo-mininext .lmo-mn-team-b{justify-content:flex-start;text-align:left}
.lmo-mininext .lmo-mn-team img{height:22px;width:auto;object-fit:contain;vertical-align:middle;flex-shrink:0}
.lmo-mininext .lmo-mn-score{font-weight:700;font-size:1.1rem;color:#153A8C;white-space:nowrap;padding:0 4px;flex-shrink:0}
.lmo-mininext .lmo-mn-note{text-align:center;color:#9098a8;font-size:.7rem;padding:0 8px 6px}
.lmo-mininext .lmo-mn-sub{background:#f4f6fb;color:#697182;font-weight:700;padding:5px 10px;
  font-size:.72rem;text-transform:uppercase;letter-spacing:.02em}
.lmo-mininext .lmo-mn-tally{text-align:center;padding:6px 8px 2px;color:#1f2430;font-size:.78rem}
.lmo-mininext .lmo-mn-bar{display:flex;height:6px;margin:4px 10px 8px;border-radius:3px;overflow:hidden;background:#eef1f6}
.lmo-mininext .lmo-mn-bar-win{background:#22c55e}
.lmo-mininext .lmo-mn-bar-draw{background:#9098a8}
.lmo-mininext .lmo-mn-bar-lost{background:#ef4444}
.lmo-mininext ul{list-style:none;margin:0;padding:0 0 6px}
.lmo-mininext li{padding:3px 10px;font-size:.72rem;color:#4b5261;border-top:1px solid #f1f3f8;
  display:flex;justify-content:space-between;gap:6px}
.lmo-mininext li.win{color:#16a34a}
.lmo-mininext li.lost{color:#dc2626}
.lmo-mininext li.draw{color:#697182}
.lmo-mininext .lmo-mn-foot{font-size:.66rem;color:#9098a8;text-align:right;padding:4px 8px}
.lmo-mininext .lmo-mn-prev{border-top:1px solid #e3e7ee}
</style>
<div class="lmo-mininext">
  <div class="lmo-mn-head"><!--gameTxt--></div>
  <div class="lmo-mn-countdown"><!--countDown--></div>
  <div class="lmo-mn-date"><!--gameDate--> <!--gameTime--></div>
  <div class="lmo-mn-teams">
    <div class="lmo-mn-team lmo-mn-team-a"><span><!--homeName--></span><!--imgHomeBig--></div>
    <div class="lmo-mn-score"><!--homeTore--> : <!--guestTore--></div>
    <div class="lmo-mn-team lmo-mn-team-b"><!--imgGuestBig--><span><!--guestName--></span></div>
  </div>
  <div class="lmo-mn-note"><!--gameNote--></div>

  <div class="lmo-mn-sub"><!--matchesTxt--></div>
  <div class="lmo-mn-tally"><!--winCount--> <!--winTxt--> · <!--drawCount--> <!--drawTxt--> · <!--lostCount--> <!--lostTxt--></div>
  <div class="lmo-mn-bar">
    <div class="lmo-mn-bar-win" style="width:<!--winWidth-->px"></div>
    <div class="lmo-mn-bar-draw" style="width:<!--drawWidth-->px"></div>
    <div class="lmo-mn-bar-lost" style="width:<!--lostWidth-->px"></div>
  </div>
  <ul>
    <!-- BEGIN matches -->
    <li class="<!--class-->"><span><!--date--> <!--hTore-->:<!--gTore--> (<!--where-->)</span><span><!--matchingName--></span></li>
    <!-- END matches -->
  </ul>

  <!-- BEGIN previous -->
  <div class="lmo-mn-prev">
    <div class="lmo-mn-sub"><!--previous_gameTxt--></div>
    <div class="lmo-mn-date"><!--previous_gameDate--> <!--previous_gameTime--></div>
    <div class="lmo-mn-teams">
      <div class="lmo-mn-team lmo-mn-team-a"><span><!--previous_homeName--></span><!--previous_imgHomeSmall--></div>
      <div class="lmo-mn-score"><!--previous_hTore--> : <!--previous_gTore--></div>
      <div class="lmo-mn-team lmo-mn-team-b"><!--previous_imgGuestSmall--><span><!--previous_guestName--></span></div>
    </div>
  </div>
  <!-- END previous -->

  <div class="lmo-mn-foot"><!--ligaDatum--></div>
</div>
