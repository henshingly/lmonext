<!--
  Partial: spieltag_picker.tpl.php | Fileversion: 1.0.0
  Dropdown zur Spieltag-/Rundenauswahl
  Changelog: 1.0.0 - Initiale Version
-->
<div class="spieltag-picker">
  <label for="spieltag-select"><!--PickerLabel--></label>
  <select id="spieltag-select" onchange="location.href='liga.php?id=<!--LigaId-->&amp;nr='+this.value">
<!--Optionen-->
  </select>
</div>
