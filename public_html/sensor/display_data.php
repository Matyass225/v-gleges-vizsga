<?php
// Adatbázis kapcsolat beállításai
$servername = "localhost";
$username   = "subiczm";
$password   = "Jelszo123!";
$dbname     = "subiczm_db";

// Kapcsolódás az adatbázishoz
$conn = new mysqli($servername, $username, $password, $dbname);

// Kapcsolódási hiba ellenőrzése
if ($conn->connect_error) {
    die("Kapcsolódási hiba: " . $conn->connect_error);
}

// Adatok lekérése
$sql = "SELECT temperature, humidity, created_at FROM measurements ORDER BY created_at ASC";
$result = $conn->query($sql);

$temperatures = [];
$humidity = [];
$timestamps = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $temperatures[] = (float)$row['temperature'];
        $humidity[]     = (float)$row['humidity'];
        $timestamps[]   = $row['created_at'];
    }
}

$conn->close();

$count        = count($temperatures);
$latest_temp  = $count ? $temperatures[$count-1] : 0;
$avg_temp     = $count ? round(array_sum($temperatures)/$count, 1) : 0;
$max_temp     = $count ? max($temperatures) : 0;
$min_temp     = $count ? min($temperatures) : 0;
$latest_hum   = $count ? $humidity[$count-1] : 0;
$avg_hum      = $count ? round(array_sum($humidity)/$count, 1) : 0;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Szenzor Műszerfal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@300;400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ── RESET ── */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { font-size:16px; }

:root {
    --blue-deep:  #0a1931;
    --blue-mid:   #0d2347;
    --blue-vivid: #2b58f7;
    --blue-light: #4a7fff;
    --accent-hot: #ff4d4d;
    --accent-cold:#4af0ff;
    --white:      #ffffff;
    --white-dim:  rgba(255,255,255,0.65);
    --white-faint:rgba(255,255,255,0.12);
    --white-ghost:rgba(255,255,255,0.05);
    --purple:     #a78bfa;
    --panel-bg:   rgba(11,24,56,0.72);
    --panel-border:rgba(255,255,255,0.08);
    --text:       #b8cce8;
    --muted:      #4a6090;
    --green:      #2ae87a;
    --red:        #e84b4b;
}

body {
    background: radial-gradient(circle at 50% 40%, #2b58f7 0%, #0a1931 100%);
    background-attachment: fixed;
    color: var(--white);
    font-family: 'Space Grotesk', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

body::before {
    content:'';
    position:fixed; inset:0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events:none; z-index:0; opacity:.6;
}

.wrap {
    position: relative; z-index: 1;
    max-width: 1400px; margin: 0 auto;
    padding: 28px 28px 48px;
}

/* ── HEADER ── */
header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 32px;
    animation: slideDown .7s cubic-bezier(.16,1,.3,1) both;
}
@keyframes slideDown { from{opacity:0;transform:translateY(-18px);} to{opacity:1;transform:translateY(0);} }

.brand { display:flex; align-items:center; gap:14px; }
.brand-icon {
    width:46px; height:46px; border-radius:13px;
    background: linear-gradient(145deg, rgba(43,88,247,0.4), rgba(10,25,49,0.9));
    border: 1px solid rgba(43,88,247,0.5);
    display:flex; align-items:center; justify-content:center; font-size:22px;
    box-shadow: 0 0 24px rgba(43,88,247,0.35), inset 0 1px 0 rgba(255,255,255,0.1);
    animation: iconGlow 3s ease-in-out infinite;
}
@keyframes iconGlow {
    0%,100%{ box-shadow: 0 0 20px rgba(43,88,247,0.3); }
    50%    { box-shadow: 0 0 44px rgba(43,88,247,0.6); }
}
.brand h1 { font-size:1.2rem; font-weight:700; letter-spacing:-.02em; }
.brand p  { font-family:'JetBrains Mono',monospace; font-size:.55rem; color:var(--muted); letter-spacing:.15em; text-transform:uppercase; margin-top:3px; }

.header-right { display:flex; align-items:center; gap:14px; }

/* ── CLOCK ── */
.clock-widget {
    background: var(--panel-bg);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--panel-border);
    border-radius:16px; padding:14px 22px;
    display:flex; flex-direction:column; align-items:center; min-width:185px;
    position:relative; overflow:hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,0.06);
}
.clock-widget::before {
    content:''; position:absolute; top:0; left:0; right:0; height:1px;
    background: linear-gradient(90deg, transparent, rgba(43,88,247,0.7), transparent);
}
.clock-time {
    font-family:'JetBrains Mono',monospace;
    font-size:1.7rem; font-weight:600; letter-spacing:.06em; line-height:1;
}
.clock-time .col { color: var(--blue-light); animation:colonBlink 1s step-end infinite; }
@keyframes colonBlink{0%,100%{opacity:1}50%{opacity:.15}}
.clock-date { font-family:'JetBrains Mono',monospace; font-size:.55rem; color:var(--muted); letter-spacing:.12em; text-transform:uppercase; margin-top:7px; }

/* ── BADGES ── */
.badges { display:flex; flex-direction:column; align-items:flex-end; gap:9px; }
.badge-live {
    display:flex; align-items:center; gap:8px;
    border-radius:999px; padding:6px 14px;
    font-family:'JetBrains Mono',monospace; font-size:.58rem; letter-spacing:.1em;
    backdrop-filter: blur(8px);
    transition: all .4s ease;
}
.badge-live.ok  { background:rgba(42,232,122,0.07); border:1px solid rgba(42,232,122,0.22); color:var(--green); }
.badge-live.err { background:rgba(232,75,75,0.07);  border:1px solid rgba(232,75,75,0.22);  color:var(--red); }
.live-dot { width:6px; height:6px; border-radius:50%; }
.badge-live.ok  .live-dot { background:var(--green); animation:livePulse 1.4s ease-in-out infinite; }
.badge-live.err .live-dot { background:var(--red);   animation:errPulse .6s ease-in-out infinite; }
@keyframes livePulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(42,232,122,.4);}50%{opacity:.7;box-shadow:0 0 0 5px rgba(42,232,122,0);}}
@keyframes errPulse{0%,100%{opacity:1;}50%{opacity:.3;}}
.badge-refresh {
    display:flex; align-items:center; gap:8px;
    font-family:'JetBrains Mono',monospace; font-size:.58rem; color:var(--muted); letter-spacing:.08em;
}
.ring-svg { width:26px; height:26px; transform:rotate(-90deg); }
.ring-bg  { fill:none; stroke:rgba(255,255,255,0.1); stroke-width:2.5; }
.ring-prog{ fill:none; stroke:var(--blue-light); stroke-width:2.5; stroke-linecap:round; stroke-dasharray:56.5; stroke-dashoffset:0; transition: stroke-dashoffset 1s linear; }

/* ── MAIN GRID ── */
.main-grid {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 20px;
    margin-bottom: 20px;
    align-items: start;
}
@media(max-width:900px){ .main-grid{ grid-template-columns:1fr; } }

/* ── GAUGE PANEL ── */
.gauge-panel {
    background: var(--panel-bg);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--panel-border);
    border-radius: 24px;
    padding: 32px 24px 28px;
    display: flex; flex-direction: column; align-items: center;
    position: relative; overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,0.07);
    animation: riseUp .7s .1s cubic-bezier(.16,1,.3,1) both;
}
.gauge-panel::before {
    content:''; position:absolute; top:0; left:0; right:0; height:1px;
    background: linear-gradient(90deg, transparent, rgba(43,88,247,0.8), transparent);
}
.gauge-panel::after {
    content:''; position:absolute;
    width:260px; height:260px; border-radius:50%;
    top:50%; left:50%; transform:translate(-50%,-42%);
    background: radial-gradient(circle, rgba(43,88,247,0.18) 0%, transparent 70%);
    pointer-events:none;
}

.gauge-lbl {
    font-family:'JetBrains Mono',monospace; font-size:.6rem;
    color:var(--muted); letter-spacing:.15em; text-transform:uppercase;
    margin-bottom:16px;
}

.gauge-container {
    position: relative; width: 280px; height: 280px;
    margin-bottom: 0;
}
.gauge-svg {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%; overflow: visible;
}
.gauge-tick-labels {
    position:absolute; inset:0; pointer-events:none;
}
.tick-lbl {
    position:absolute; font-family:'JetBrains Mono',monospace;
    font-size:.55rem; color:rgba(255,255,255,0.28); transform:translate(-50%,-50%);
    letter-spacing:0;
}
.gauge-center {
    position:absolute;
    top: 100px;
    left: 50%; transform: translateX(-50%);
    text-align:center; pointer-events:none;
    white-space: nowrap;
}
.gauge-value {
    font-size:3.8rem; font-weight:300; line-height:1;
    color: var(--white);
    text-shadow: 0 0 40px rgba(255,255,255,0.3), 0 0 80px rgba(43,88,247,0.5);
    letter-spacing:-.04em;
    transition: all .6s ease;
}
.gauge-value sup { font-size:1.5rem; vertical-align:super; font-weight:300; }
.gauge-sublbl {
    font-family:'JetBrains Mono',monospace; font-size:.58rem;
    color:var(--muted); letter-spacing:.12em; text-transform:uppercase;
    margin-top:6px;
}

/* ── STATS BELOW GAUGE ── */
.gauge-stats {
    display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;
    width:100%; margin-top:8px;
}
.g-stat {
    background: rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:12px; padding:12px 10px;
    text-align:center;
    transition: transform .2s, background .2s;
}
.g-stat:hover { transform:translateY(-2px); background:rgba(255,255,255,0.07); }
.g-stat-lbl { font-family:'JetBrains Mono',monospace; font-size:.5rem; color:var(--muted); letter-spacing:.1em; text-transform:uppercase; margin-bottom:5px; }
.g-stat-val { font-size:1.15rem; font-weight:600; color:var(--white); letter-spacing:-.02em; }
.g-stat-val .unit { font-size:.65rem; font-weight:400; color:var(--muted); }

/* ── RIGHT COLUMN ── */
.right-col { display:flex; flex-direction:column; gap:14px; }

/* ── HUMIDITY WIDGET ── */
.press-panel {
    background: var(--panel-bg);
    backdrop-filter: blur(20px);
    border:1px solid var(--panel-border);
    border-radius:20px; padding:24px 24px 20px;
    position:relative; overflow:hidden;
    box-shadow: 0 12px 40px rgba(0,0,0,.35);
    animation: riseUp .7s .2s cubic-bezier(.16,1,.3,1) both;
}
.press-panel::before {
    content:''; position:absolute; top:0; left:0; right:0; height:1px;
    background: linear-gradient(90deg, transparent, rgba(167,139,250,0.7), transparent);
}
.press-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.press-title-row { display:flex; align-items:center; gap:10px; }
.pdot { width:8px; height:8px; border-radius:50%; background:var(--purple); box-shadow:0 0 8px rgba(167,139,250,0.5); }
.press-title { font-size:.95rem; font-weight:600; }
.press-tag {
    font-family:'JetBrains Mono',monospace; font-size:.52rem; color:var(--muted);
    letter-spacing:.1em; text-transform:uppercase;
    background:rgba(255,255,255,0.03); border:1px solid var(--panel-border);
    border-radius:6px; padding:4px 10px;
}
.press-vals { display:flex; gap:24px; margin-bottom:14px; }
.press-main { font-size:2.4rem; font-weight:700; letter-spacing:-.03em; color:var(--purple); }
.press-main .unit { font-size:.9rem; font-weight:400; color:var(--muted); margin-left:4px; }
.press-avg  { display:flex; flex-direction:column; justify-content:center; }
.press-avg-lbl { font-family:'JetBrains Mono',monospace; font-size:.5rem; color:var(--muted); letter-spacing:.1em; text-transform:uppercase; }
.press-avg-val { font-size:1.15rem; font-weight:600; color:rgba(167,139,250,0.7); }

/* ── CHARTS ── */
.chart-card {
    background: var(--panel-bg);
    backdrop-filter: blur(16px);
    border:1px solid var(--panel-border);
    border-radius:20px; padding:24px 24px 18px;
    position:relative; overflow:hidden;
    box-shadow: 0 12px 40px rgba(0,0,0,.35);
    animation: riseUp .7s cubic-bezier(.16,1,.3,1) both;
}
.chart-card:nth-child(1){ animation-delay:.3s; }
.chart-card:nth-child(2){ animation-delay:.4s; }
.chart-card.ct::before {
    content:''; position:absolute; left:0; top:18%; bottom:18%; width:2px;
    background:linear-gradient(180deg,transparent,var(--blue-light),transparent);
    border-radius:999px; opacity:.7;
}
.chart-card.cp::before {
    content:''; position:absolute; left:0; top:18%; bottom:18%; width:2px;
    background:linear-gradient(180deg,transparent,var(--purple),transparent);
    border-radius:999px; opacity:.7;
}
.chart-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
.chart-head-left { display:flex; align-items:center; gap:10px; }
.cdot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.cdot.t { background:var(--blue-light); box-shadow:0 0 8px rgba(74,127,255,0.5); }
.cdot.p { background:var(--purple);     box-shadow:0 0 8px rgba(167,139,250,0.5); }
.chart-title { font-size:.95rem; font-weight:600; }
.chart-tag {
    font-family:'JetBrains Mono',monospace; font-size:.52rem; color:var(--muted);
    letter-spacing:.1em; text-transform:uppercase;
    background:rgba(255,255,255,0.03); border:1px solid var(--panel-border);
    border-radius:6px; padding:4px 10px;
}
.chart-wrap { position:relative; height:200px; }

/* ── FOOTER ── */
footer {
    text-align:center; margin-top:28px;
    font-family:'JetBrains Mono',monospace; font-size:.55rem; color:var(--muted);
    letter-spacing:.1em;
    display:flex; align-items:center; justify-content:center; gap:14px;
    animation: riseUp .6s .55s cubic-bezier(.16,1,.3,1) both;
}
footer span { color:rgba(255,255,255,0.4); }
.sep { color:rgba(255,255,255,0.15); }

/* ── ANIMATIONS ── */
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes flashVal{0%{opacity:.2}100%{opacity:1}}
.flash{ animation:flashVal .5s ease both; }
.stat-unit { font-size:.75rem; font-weight:400; color:var(--muted); margin-left:2px; }
</style>
</head>
<body>
<div class="wrap">

    <!-- HEADER -->
    <header>
        <div class="brand">
            <div class="brand-icon">🌡️</div>
            <div>
                <h1>Szenzor Műszerfal</h1>
                <p>Élő monitoring &middot; v3.1</p>
            </div>
        </div>
        <div class="header-right">
            <div class="clock-widget">
                <div class="clock-time" id="clockTime">--<span class="col">:</span>--<span class="col">:</span>--</div>
                <div class="clock-date" id="clockDate">--- -- ----</div>
            </div>
            <div class="badges">
                <div class="badge-live ok" id="statusBadge">
                    <div class="live-dot"></div>
                    <span id="statusTxt">ADATOK BETÖLTVE</span>
                </div>
                <div class="badge-refresh">
                    <svg class="ring-svg" viewBox="0 0 22 22">
                        <circle class="ring-bg"   cx="11" cy="11" r="9"/>
                        <circle class="ring-prog" id="ringProg" cx="11" cy="11" r="9"/>
                    </svg>
                    <span id="countdownTxt">30</span>s
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN GRID -->
    <div class="main-grid">

        <!-- GAUGE PANEL -->
        <div class="gauge-panel">
            <div class="gauge-lbl">Hőmérséklet szenzor</div>
            <div class="gauge-container" id="gaugeContainer">
                <div class="gauge-tick-labels" id="tickLabels"></div>
                <svg class="gauge-svg" viewBox="0 0 280 280" id="gaugeSvg">
                    <defs>
                        <linearGradient id="arcGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%"   stop-color="#4af0ff"/>
                            <stop offset="40%"  stop-color="#4a7fff"/>
                            <stop offset="70%"  stop-color="#2b58f7"/>
                            <stop offset="100%" stop-color="#ff4d4d"/>
                        </linearGradient>
                        <filter id="arcGlow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="4" result="blur"/>
                            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                        <filter id="handleGlow" x="-100%" y="-100%" width="300%" height="300%">
                            <feGaussianBlur stdDeviation="5" result="blur"/>
                            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <path id="arcBase" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="10" stroke-linecap="round"/>
                    <path id="arcProg" fill="none" stroke="url(#arcGrad)" stroke-width="10" stroke-linecap="round" filter="url(#arcGlow)"/>
                    <circle id="arcHandle" r="7" fill="white" filter="url(#handleGlow)"/>
                    <circle id="arcHandleInner" r="3" fill="rgba(43,88,247,0.9)"/>
                </svg>
                <div class="gauge-center">
                    <div class="gauge-value" id="gaugeValue"><?php echo $latest_temp; ?><sup>°C</sup></div>
                    <div class="gauge-sublbl">Jelenlegi hőmérséklet</div>
                </div>
            </div>
            <div class="gauge-stats">
                <div class="g-stat">
                    <div class="g-stat-lbl">Átlag</div>
                    <div class="g-stat-val" id="gs-avg"><?php echo $avg_temp; ?><span class="unit"> °C</span></div>
                </div>
                <div class="g-stat">
                    <div class="g-stat-lbl">Max</div>
                    <div class="g-stat-val" id="gs-max"><?php echo $max_temp; ?><span class="unit"> °C</span></div>
                </div>
                <div class="g-stat">
                    <div class="g-stat-lbl">Min</div>
                    <div class="g-stat-val" id="gs-min"><?php echo $min_temp; ?><span class="unit"> °C</span></div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="right-col">

            <!-- HUMIDITY WIDGET -->
            <div class="press-panel">
                <div class="press-header">
                    <div class="press-title-row">
                        <div class="pdot"></div>
                        <span class="press-title">Páratartalom</span>
                    </div>
                    <span class="press-tag">% · VALÓS IDŐ</span>
                </div>
                <div class="press-vals">
                    <div>
                        <div style="font-family:'JetBrains Mono',monospace;font-size:.5rem;color:var(--muted);letter-spacing:.12em;text-transform:uppercase;margin-bottom:4px;">Jelenlegi páratartalom</div>
                        <div class="press-main"><?php echo $latest_hum; ?><span class="unit"> %</span></div>
                    </div>
                    <div class="press-avg">
                        <div class="press-avg-lbl">Átlag</div>
                        <div class="press-avg-val"><?php echo $avg_hum; ?> <span style="font-size:.65rem;font-weight:400;color:var(--muted);">%</span></div>
                    </div>
                </div>
                <div style="background:rgba(255,255,255,0.04);border-radius:4px;height:4px;overflow:hidden;">
                    <div style="height:100%;width:<?php echo min(100, $latest_hum); ?>%;background:linear-gradient(90deg,rgba(167,139,250,0.5),var(--purple));border-radius:4px;transition:width 1s ease;"></div>
                </div>
            </div>

            <!-- TEMP CHART -->
            <div class="chart-card ct">
                <div class="chart-head">
                    <div class="chart-head-left">
                        <div class="cdot t"></div>
                        <span class="chart-title">Hőmérséklet idősora</span>
                    </div>
                    <span class="chart-tag">CELSIUS · IDŐSOR</span>
                </div>
                <div class="chart-wrap"><canvas id="tempChart"></canvas></div>
            </div>

            <!-- HUMIDITY CHART -->
            <div class="chart-card cp">
                <div class="chart-head">
                    <div class="chart-head-left">
                        <div class="cdot p"></div>
                        <span class="chart-title">Páratartalom idősora</span>
                    </div>
                    <span class="chart-tag">% · IDŐSOR</span>
                </div>
                <div class="chart-wrap"><canvas id="pressChart"></canvas></div>
            </div>

        </div>
    </div>

    <footer>
        <span>SZENZOR MONITORING RENDSZER</span>
        <span class="sep">·</span>
        <span id="f-date"></span>
        <span class="sep">·</span>
        <span><?php echo $count; ?></span> ADATPONT
        <span class="sep">·</span>
        Frissítve: <span id="f-updated">--:--:--</span>
    </footer>
</div>

<script>
// ── PHP ADATOK ────────────────────────────────────────────────
const phpTimestamps    = <?php echo json_encode($timestamps); ?>;
const phpTemperatures  = <?php echo json_encode($temperatures); ?>;
const phpHumidity      = <?php echo json_encode($humidity); ?>;

// ── CLOCK ─────────────────────────────────────────────────────
const DAYS   = ['Vasárnap','Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat'];
const MONTHS = ['Jan','Feb','Már','Ápr','Máj','Jún','Júl','Aug','Sze','Okt','Nov','Dec'];
function pad(n){ return String(n).padStart(2,'0'); }

function updateClock(){
    const n = new Date();
    document.getElementById('clockTime').innerHTML =
        pad(n.getHours())+'<span class="col">:</span>'+
        pad(n.getMinutes())+'<span class="col">:</span>'+
        pad(n.getSeconds());
    document.getElementById('clockDate').textContent =
        DAYS[n.getDay()]+' · '+n.getDate()+' '+MONTHS[n.getMonth()]+' '+n.getFullYear();
    document.getElementById('f-updated').textContent =
        pad(n.getHours())+':'+pad(n.getMinutes())+':'+pad(n.getSeconds());
    document.getElementById('f-date').textContent = n.toLocaleDateString('hu-HU');
}
updateClock();
setInterval(updateClock, 1000);

// ── COUNTDOWN RING ─────────────────────────────────────────────
const INTERVAL = 2000;
const CIRCUM   = 56.5;
const ring     = document.getElementById('ringProg');
const cntTxt   = document.getElementById('countdownTxt');
let ringStart  = Date.now();

function updateRing(){
    const elapsed   = (Date.now() - ringStart) % INTERVAL;
    const remaining = Math.ceil((INTERVAL - elapsed) / 1000);
    ring.style.strokeDashoffset = CIRCUM * (elapsed / INTERVAL);
    cntTxt.textContent = remaining > 0 ? remaining : 1;
}
setInterval(updateRing, 200);

// ── GAUGE ──────────────────────────────────────────────────────
const CX = 140, CY = 155, R = 110;
const ARC_START = 225;
const ARC_SWEEP = 270;
const TEMP_MIN = -10;
const TEMP_MAX = 50;

function degToRad(d){ return d * Math.PI / 180; }
function polarToXY(angleDeg, r){
    const rad = degToRad(angleDeg);
    return { x: CX + r * Math.cos(rad), y: CY + r * Math.sin(rad) };
}
function buildArcPath(startDeg, sweepDeg, r){
    const s = polarToXY(startDeg, r);
    const endDeg = startDeg + sweepDeg;
    const e = polarToXY(endDeg, r);
    const large = sweepDeg > 180 ? 1 : 0;
    return `M ${s.x} ${s.y} A ${r} ${r} 0 ${large} 1 ${e.x} ${e.y}`;
}

const arcBase       = document.getElementById('arcBase');
const arcProg       = document.getElementById('arcProg');
const arcHandle     = document.getElementById('arcHandle');
const arcHandleInner= document.getElementById('arcHandleInner');

arcBase.setAttribute('d', buildArcPath(ARC_START, ARC_SWEEP, R));

// Tick labels
const tickLabels = document.getElementById('tickLabels');
const TICKS = [-10, 0, 10, 20, 30, 40, 50];
TICKS.forEach(t => {
    const frac = (t - TEMP_MIN) / (TEMP_MAX - TEMP_MIN);
    const angleDeg = ARC_START + frac * ARC_SWEEP;
    const pos = polarToXY(angleDeg, R + 22);
    const pctX = (pos.x / 280) * 100;
    const pctY = (pos.y / 280) * 100;
    const el = document.createElement('div');
    el.className = 'tick-lbl';
    el.textContent = t;
    el.style.left = pctX + '%';
    el.style.top  = pctY + '%';
    tickLabels.appendChild(el);
});

function updateGauge(temp){
    const clamped  = Math.min(TEMP_MAX, Math.max(TEMP_MIN, temp));
    const frac     = (clamped - TEMP_MIN) / (TEMP_MAX - TEMP_MIN);
    const sweepProg= frac * ARC_SWEEP;
    if(sweepProg > 1){
        arcProg.setAttribute('d', buildArcPath(ARC_START, sweepProg, R));
        arcProg.style.display = '';
    } else {
        arcProg.style.display = 'none';
    }
    const endAngle = ARC_START + sweepProg;
    const hp = polarToXY(endAngle, R);
    arcHandle.setAttribute('cx', hp.x);
    arcHandle.setAttribute('cy', hp.y);
    arcHandleInner.setAttribute('cx', hp.x);
    arcHandleInner.setAttribute('cy', hp.y);
    document.getElementById('gaugeValue').innerHTML = temp + '<sup>°C</sup>';
}

// Gauge inicializálás PHP adattal
const latestTemp = <?php echo $latest_temp; ?>;
updateGauge(latestTemp);

// ── CHART INIT ─────────────────────────────────────────────────
const baseOpts = {
    responsive:true, maintainAspectRatio:false,
    interaction:{ mode:'index', intersect:false },
    animation:{ duration:800, easing:'easeInOutSine' },
    plugins:{
        legend:{ display:false },
        tooltip:{
            backgroundColor:'rgba(10,25,49,0.95)',
            borderColor:'rgba(255,255,255,0.08)', borderWidth:1,
            titleColor:'#4a6090', bodyColor:'#e8f0ff',
            titleFont:{ family:'JetBrains Mono', size:10 },
            bodyFont:{ family:'Space Grotesk', size:14, weight:'700' },
            padding:14, cornerRadius:12,
        }
    },
    scales:{
        x:{
            ticks:{ color:'rgba(255,255,255,0.2)', font:{family:'JetBrains Mono',size:8}, maxTicksLimit:8, maxRotation:0 },
            grid:{ color:'rgba(255,255,255,0.02)' },
            border:{ color:'rgba(255,255,255,0.04)' }
        },
        y:{
            ticks:{ color:'rgba(255,255,255,0.2)', font:{family:'JetBrains Mono',size:8} },
            grid:{ color:'rgba(255,255,255,0.025)' },
            border:{ color:'rgba(255,255,255,0.04)', dash:[4,4] }
        }
    }
};

const tCtx = document.getElementById('tempChart').getContext('2d');
const pCtx = document.getElementById('pressChart').getContext('2d');

function makeTempGrad(ctx){
    const g = ctx.createLinearGradient(0,0,0,200);
    g.addColorStop(0,   'rgba(74,127,255,0.35)');
    g.addColorStop(0.6, 'rgba(74,127,255,0.07)');
    g.addColorStop(1,   'rgba(74,127,255,0)');
    return g;
}
function makeHumGrad(ctx){
    const g = ctx.createLinearGradient(0,0,0,200);
    g.addColorStop(0,   'rgba(167,139,250,0.28)');
    g.addColorStop(0.6, 'rgba(167,139,250,0.05)');
    g.addColorStop(1,   'rgba(167,139,250,0)');
    return g;
}

const tempChart = new Chart(tCtx, {
    type:'line',
    data:{ labels: phpTimestamps, datasets:[{
        label:'Hőmérséklet (°C)', data: phpTemperatures,
        borderColor:'#4a7fff', backgroundColor: makeTempGrad(tCtx),
        borderWidth:2.5, fill:true, tension:0.45,
        pointRadius:0, pointHoverRadius:4,
        pointHoverBackgroundColor:'#4a7fff',
    }]},
    options: baseOpts
});

const humChart = new Chart(pCtx, {
    type:'line',
    data:{ labels: phpTimestamps, datasets:[{
        label:'Páratartalom (%)', data: phpHumidity,
        borderColor:'#a78bfa', backgroundColor: makeHumGrad(pCtx),
        borderWidth:2.5, fill:true, tension:0.45,
        pointRadius:0, pointHoverRadius:4,
        pointHoverBackgroundColor:'#a78bfa',
    }]},
    options:{...baseOpts, scales:{...baseOpts.scales, y:{...baseOpts.scales.y, min:0, max:100}}}
});

// ── AUTO REFRESH 30mp-enként ────────────────────────────────────
setTimeout(() => { location.reload(); }, INTERVAL);
</script>
</body>
</html>
