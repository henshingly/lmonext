<?php
/**
 * Project: LMOnext
 * Filename: handler_wizard.php
 * Fileversion: 1.2.1
 * Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.2.0 - Flash-Meldungen über t() übersetzt
 * Changelog: 1.1.2 - Team-Limit von 64 auf 128 erhöht
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Liga-Wizard (Neu erstellen, Vorlage)
 * Vorlage anwenden → Session füllen und zu Schritt 1b springen
 */
if ($action === 'apply_template' && isset($_GET['tpl'])) {
    requireLogin();
    $tplKey = $_GET['tpl'];
    $tpl    = LIGA_TEMPLATES[$tplKey] ?? null;
    if (!$tpl) { flash(t('wiz_flash_unknown_template'), 'error'); redirect('?action=create_liga&step=1'); }
    $teams = [];
    for ($i = 0; $i < $tpl['teams_count']; $i++) {
        $name   = $tpl['team_defaults'][$i] ?? sprintf('Team %02d', $i + 1);
        $mittel = $tpl['team_mittel'][$i]   ?? '';
        $teams[] = ['name' => $name, 'mittel' => $mittel, 'kurz' => ''];
    }
    $_SESSION['wiz'] = [
        'name'        => '',
        'type'        => $tpl['type'],
        'team_count'  => $tpl['teams_count'],
        'round_count' => $tpl['rounds'] ?? 0,
        'matches'     => $tpl['matches'] ?? 0,
        'teams'       => $teams,
        'spieltage'   => [],
        'tpl_key'     => $tplKey,
        'tpl_options' => $tpl['options'] ?? [],
        'round_names' => $tpl['round_names'] ?? [],
        'round_modi'  => $tpl['round_modi']  ?? [],
    ];
    redirect('?action=create_liga&step=1b');
}

// ── Wizard-Routing ────────────────────────────────────────────────────────────
if ($action === 'create_liga') {
    requireLogin();
    $step = $_GET['step'] ?? '1';

    // Schritt 1b: Name vergeben nach Vorlagen-Auswahl
    if ($step === '1b' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $wiz  = $_SESSION['wiz'] ?? null;
        $name = trim($_POST['liga_name'] ?? '');
        if (!$wiz || $name === '') { flash(t('wiz_flash_name_required'), 'error'); redirect('?action=create_liga&step=1'); }
        $_SESSION['wiz']['name'] = $name;
        redirect('?action=create_liga&step=2');
    }

    $step = (int)$step;
    if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name    = trim($_POST['liga_name'] ?? '');
        $type    = (int)($_POST['liga_type'] ?? 0);
        $teamCnt = max(2, min(128, (int)($_POST['team_count'] ?? 2)));
        $rndCnt  = ($type === 1) ? max(1, (int)($_POST['round_count'] ?? 1)) : 0;
        if ($name === '') { flash(t('wiz_flash_name_required'), 'error'); redirect('?action=create_liga&step=1'); }
        $_SESSION['wiz'] = ['name'=>$name,'type'=>$type,'team_count'=>$teamCnt,'round_count'=>$rndCnt,'teams'=>[],'spieltage'=>[]];
        redirect('?action=create_liga&step=2');
    }
    if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $wiz = $_SESSION['wiz'] ?? null;
        if (!$wiz) { redirect('?action=create_liga&step=1'); }
        $teams = [];
        for ($i = 0; $i < $wiz['team_count']; $i++) {
            $n = trim($_POST["team_name_{$i}"] ?? '');
            $k = trim($_POST["team_kurz_{$i}"] ?? '');
            $m = trim($_POST["team_mittel_{$i}"] ?? '');
            if ($n === '') { flash(t('wiz_flash_team_name_missing', ['n' => $i+1]), 'error'); redirect('?action=create_liga&step=2'); }
            $teams[] = ['name'=>$n,'kurz'=>$k,'mittel'=>$m];
        }
        $_SESSION['wiz']['teams'] = $teams;
        if ($wiz['type'] === 0) {
            $_SESSION['wiz']['spieltage'] = generateRoundRobin(range(0, count($teams) - 1));
        }
        redirect('?action=create_liga&step=3');
    }
    if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $wiz = $_SESSION['wiz'] ?? null;
        if (!$wiz) { redirect('?action=create_liga&step=1'); }
        if ($wiz['type'] === 1) {
            $teamCount = count($wiz['teams']);
            $nRounds   = (int)$wiz['round_count'];
            $isUefa24  = ($teamCount === 24);
            $spieltage = [];

            // Paaranzahl pro Runde berechnen
            // Standard: R1=n/2, R2=n/4, ... Finale=1
            // UEFA-24: R1=8, R2=8, R3=4, R4=2, R5=1
            $paarCount = [];
            if ($isUefa24) {
                $paarCount = [1=>8, 2=>8, 3=>4, 4=>2, 5=>1];
            } else {
                for ($r = 1; $r <= $nRounds; $r++) {
                    $paarCount[$r] = (int)($teamCount / pow(2, $r));
                }
            }

            for ($r = 1; $r <= $nRounds; $r++) {
                $mod = (int)($_POST["rnd_{$r}_modus"] ?? KO_MODUS_DEFAULT);
                if (!isset(KO_MODUS[$mod])) { $mod = KO_MODUS_DEFAULT; }

                // Explizit eingetragene Paarungen aus Formular lesen
                $pairs = [];
                $cnt   = (int)($_POST["rnd_{$r}_count"] ?? 0);
                for ($p = 0; $p < $cnt; $p++) {
                    $hh = (int)($_POST["rnd_{$r}_heim_{$p}"] ?? -1);
                    $gg = (int)($_POST["rnd_{$r}_gast_{$p}"] ?? -1);
                    if ($hh >= 0 && $gg >= 0 && $hh !== $gg) { $pairs[] = [$hh, $gg]; }
                }

                // Wenn keine Paarungen manuell eingegeben: Dummy-Paarungen erzeugen
                // Runde 1: echte Teams (paarweise); spätere Runden: nur Dummies
                if (empty($pairs)) {
                    $nPaare = $paarCount[$r] ?? 1;
                    if ($r === 1) {
                        // Runde 1: Teams paarweise zuordnen (1 vs 2, 3 vs 4, ...)
                        for ($p = 0; $p < $nPaare; $p++) {
                            $hIdx = $p * 2;
                            $gIdx = $p * 2 + 1;
                            if (isset($wiz['teams'][$hIdx]) && isset($wiz['teams'][$gIdx])) {
                                $pairs[] = [$hIdx, $gIdx];
                            } else {
                                $pairs[] = [-1, -1]; // Dummy
                            }
                        }
                    } else {
                        // Folgerunden: nur Dummy-Paarungen (Teams stehen noch nicht fest)
                        for ($p = 0; $p < $nPaare; $p++) {
                            $pairs[] = [-1, -1]; // -1 = Dummy (wird in createLigaInDB zu ___)
                        }
                    }
                }

                $spieltage[$r] = ['pairs' => $pairs, 'modus' => $mod];
            }
            $_SESSION['wiz']['spieltage'] = $spieltage;
        }
        $wiz    = $_SESSION['wiz'];
        // Vorlagen-Optionen mit übernehmen
        if (!empty($wiz['tpl_options'])) {
            $wiz['options'] = array_merge($wiz['tpl_options'], $wiz['options'] ?? []);
        }
        $result = createLigaInDB($wiz['name'], $wiz['type'], $wiz['teams'], $wiz['spieltage'], $wiz['options'] ?? []);
        unset($_SESSION['wiz']);
        flash($result['msg'], $result['ok'] ? 'success' : 'error');
        redirect($result['ok'] ? '?action=liga_detail&id='.$result['liga_id'] : '?action=create_liga&step=1');
    }
}

