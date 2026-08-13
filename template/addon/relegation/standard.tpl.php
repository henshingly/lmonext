<style>
.rl-wrap{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;color:#1f2430;max-width:1024px;margin:0 auto}
.rl-title{font-size:1.15rem;font-weight:700;margin:0 0 12px;text-align:center}
.rl-row{display:flex;gap:8px;margin:0 0 4px}
.rl-box{flex:1;min-width:0;background:#31363f;border-radius:8px;overflow:hidden;text-align:center}
.rl-box-header{background:#4b5160;color:#e7eaf0;font-size:.78rem;font-weight:700;padding:6px 4px;letter-spacing:.02em}
.rl-box-target .rl-box-header{font-weight:800}
.rl-box-body{padding:14px 8px 10px}
.rl-box .rl-logo-slot{height:44px;display:flex;align-items:center;justify-content:center;position:relative}
.rl-box .st-team-logo-wrap img{height:40px;width:auto;max-width:56px;object-fit:contain}
.rl-arrow{position:absolute;top:-2px;right:6px;font-size:.85rem;font-weight:800}
.rl-arrow-down{color:#ef4444}
.rl-arrow-up{color:#22c55e}
.rl-team{color:#f3f5f8;font-size:.86rem;font-weight:600;margin:6px 0 2px;padding:0 2px;word-break:break-word}
.rl-pkt{color:#a7adb8;font-size:.76rem;margin:0 0 4px}
.rl-banner{margin:10px 0;padding:12px 16px;border-radius:6px;text-align:center;font-weight:700;font-size:.92rem;color:#fff;letter-spacing:.01em}
.rl-banner-ab{background:#dc2626}
.rl-banner-auf{background:#16a34a}
.rl-liga-caption{text-align:center;font-size:.76rem;color:#8b93a0;margin:2px 0 8px;text-transform:uppercase;letter-spacing:.04em}
</style>
<div class="rl-wrap">
  <h2 class="rl-title">{LIGA_OBEN_NAME} &harr; {LIGA_UNTEN_NAME}</h2>

  <p class="rl-liga-caption">{LIGA_OBEN_NAME}</p>
  <div class="rl-row">
    <!-- BEGIN BOX_OBEN -->
    <div class="rl-box{BOX_CLASS}">
      <div class="rl-box-header" style="{HEADER_STYLE}">{PLATZ_LABEL} {PLATZ}</div>
      <div class="rl-box-body">
        <div class="rl-logo-slot">{LOGO}{ARROW}</div>
        <div class="rl-team">{TEAM}</div>
        <div class="rl-pkt">{PKT} Punkte</div>
      </div>
    </div>
    <!-- END BOX_OBEN -->
  </div>

  <div class="rl-banner rl-banner-ab">{BANNER_ABSTIEG}</div>
  <div class="rl-banner rl-banner-auf">{BANNER_AUFSTIEG}</div>

  <p class="rl-liga-caption">{LIGA_UNTEN_NAME}</p>
  <div class="rl-row">
    <!-- BEGIN BOX_UNTEN -->
    <div class="rl-box{BOX_CLASS}">
      <div class="rl-box-header" style="{HEADER_STYLE}">{PLATZ_LABEL} {PLATZ}</div>
      <div class="rl-box-body">
        <div class="rl-logo-slot">{LOGO}{ARROW}</div>
        <div class="rl-team">{TEAM}</div>
        <div class="rl-pkt">{PKT} Punkte</div>
      </div>
    </div>
    <!-- END BOX_UNTEN -->
  </div>

  {COPYRIGHT}
</div>
