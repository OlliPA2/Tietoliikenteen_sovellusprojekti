<?php
// Tunnukset ladataan erillisestä tiedostosta, joka ei ole gitissä
require __DIR__ . "/config.php";

// Yhdistetään tietokantaan
$conn = new mysqli($servername, $username, $password, $dbname);

// Tarkistetaan yhteys
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Haetaan kaikki tiedot rawdata-taulusta
$sql = "SELECT * FROM rawdata ORDER BY id DESC";
$result = $conn->query($sql);

// Sarakkeiden nimet ja rivit talteen
$fields = $result->fetch_fields();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$conn->close();

// Selkokieliset otsikot sarakkeille (alkuperäinen nimi näkyy pienellä alla)
$labels = [
    "id"            => "#",
    "groupid"       => "Ryhmä",
    "sensorvalue_a" => "X",
    "sensorvalue_b" => "Y",
    "sensorvalue_c" => "Z",
    "sensorvalue_d" => "Suunta",
    "aika"          => "Aika",
];

$rowCount = count($rows);
$latest = ($rowCount > 0 && isset($rows[0]["aika"])) ? $rows[0]["aika"] : "–";
$updated = date("H:i:s");
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sensoridata ❄</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mountains+of+Christmas:wght@700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --night-1: #13284d;
            --night-2: #081428;
            --night-3: #040a16;
            --snow: #f4f8ff;
            --ink: #e9f0ff;
            --ink-dim: #9fb3d6;
            --red: #e23b4a;
            --red-deep: #a51d2d;
            --green: #2fae6a;
            --gold: #f6c453;
            --ice: #8fd3ff;
            --glass: rgba(16, 32, 64, 0.55);
            --glass-line: rgba(180, 210, 255, 0.18);
        }

        * { box-sizing: border-box; }

        html, body { margin: 0; min-height: 100%; }

        body {
            font-family: "Nunito", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1px 1px at 12% 18%, #fff8 50%, transparent 51%),
                radial-gradient(1px 1px at 78% 12%, #fff9 50%, transparent 51%),
                radial-gradient(1.5px 1.5px at 42% 8%, #fffa 50%, transparent 51%),
                radial-gradient(1px 1px at 88% 34%, #fff7 50%, transparent 51%),
                radial-gradient(1px 1px at 24% 42%, #fff6 50%, transparent 51%),
                radial-gradient(1.5px 1.5px at 62% 28%, #fff8 50%, transparent 51%),
                radial-gradient(ellipse 80% 50% at 50% -10%, #2a4f8a 0%, transparent 70%),
                linear-gradient(180deg, var(--night-1) 0%, var(--night-2) 55%, var(--night-3) 100%);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* ---------- Lumisade ---------- */
        #snow {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            transition: opacity 0.8s ease;
        }
        #snow.off { opacity: 0; }

        /* ---------- Jouluvalot ---------- */
        .lights {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            margin: 0;
            padding: 0 2vw;
            list-style: none;
            height: 64px;
            overflow: hidden;
        }
        .lights::before {               /* johto */
            content: "";
            position: absolute;
            left: -5%;
            right: -5%;
            top: -38px;
            height: 60px;
            border-bottom: 2px solid #1d2b1f;
            border-radius: 0 0 50% 50% / 0 0 100% 100%;
        }
        .lights li {
            position: relative;
            width: 12px;
            height: 22px;
            margin-top: calc(14px + var(--sag, 0px));
            border-radius: 50% 50% 50% 50% / 40% 40% 60% 60%;
            background: var(--c);
            box-shadow: 0 4px 18px 3px var(--c);
            animation: twinkle 2.4s ease-in-out infinite;
            animation-delay: calc(var(--i) * -0.37s);
        }
        .lights li::before {            /* kanta */
            content: "";
            position: absolute;
            top: -5px;
            left: 2px;
            width: 8px;
            height: 7px;
            border-radius: 2px;
            background: #3a463c;
        }
        .lights li:nth-child(4n+1) { --c: var(--red); }
        .lights li:nth-child(4n+2) { --c: var(--gold); }
        .lights li:nth-child(4n+3) { --c: var(--green); }
        .lights li:nth-child(4n+4) { --c: var(--ice); }

        @keyframes twinkle {
            0%, 100% { opacity: 1;    filter: brightness(1.15); }
            50%      { opacity: 0.35; filter: brightness(0.7); box-shadow: 0 4px 6px 0 var(--c); }
        }

        /* ---------- Sisältö ---------- */
        main {
            position: relative;
            z-index: 1;
            max-width: 1100px;
            margin: 0 auto;
            padding: 8px 16px 64px;
        }

        header {
            text-align: center;
            margin: 8px 0 36px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border: 1px solid var(--glass-line);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.04);
            color: var(--ink-dim);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        h1 {
            margin: 14px 0 6px;
            font-family: "Mountains of Christmas", "Nunito", cursive;
            font-size: clamp(3rem, 9vw, 5.6rem);
            line-height: 1;
            background: linear-gradient(180deg, #fff6d8 0%, var(--gold) 55%, #c98b1e 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 4px 24px rgba(246, 196, 83, 0.35));
        }
        h1 .flake {
            display: inline-block;
            font-size: 0.5em;
            vertical-align: middle;
            color: var(--snow);
            -webkit-text-fill-color: var(--snow);
            animation: spin 9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .subtitle {
            margin: 0;
            color: var(--ink-dim);
            font-size: 1.05rem;
        }

        /* ---------- Tilastot ---------- */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 40px;
        }
        .stat {
            position: relative;
            padding: 16px 18px;
            border: 1px solid var(--glass-line);
            border-radius: 16px;
            background: var(--glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .stat::after {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--accent);
        }
        .stat:nth-child(1) { --accent: var(--red); }
        .stat:nth-child(2) { --accent: var(--green); }
        .stat:nth-child(3) { --accent: var(--gold); }
        .stat-label {
            display: block;
            color: var(--ink-dim);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .stat-value {
            display: block;
            margin-top: 4px;
            font-size: 1.5rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .stat-value.small { font-size: 1.1rem; padding-top: 6px; }

        .toggle {
            all: unset;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border: 1px solid var(--glass-line);
            border-radius: 16px;
            background: var(--glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: border-color 0.2s, transform 0.2s;
        }
        .toggle:hover { border-color: rgba(180, 210, 255, 0.45); transform: translateY(-1px); }
        .toggle:focus-visible { outline: 2px solid var(--ice); outline-offset: 3px; }
        .switch {
            position: relative;
            flex: none;
            width: 46px;
            height: 26px;
            border-radius: 999px;
            background: #2a3b5c;
            transition: background 0.25s;
        }
        .switch::after {
            content: "❄";
            position: absolute;
            top: 3px; left: 3px;
            width: 20px; height: 20px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--snow);
            color: #2a3b5c;
            font-size: 12px;
            transition: transform 0.25s;
        }
        .toggle[aria-pressed="true"] .switch { background: var(--green); }
        .toggle[aria-pressed="true"] .switch::after { transform: translateX(20px); color: var(--green); }

        /* ---------- Taulukkokortti ---------- */
        .card {
            position: relative;
            border: 1px solid var(--glass-line);
            border-radius: 20px;
            background: var(--glass);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.6);
        }
        .card::before {                 /* lumikerros kortin päällä */
            content: "";
            position: absolute;
            left: -6px;
            right: -6px;
            top: -16px;
            height: 30px;
            border-radius: 14px 14px 6px 6px;
            background:
                radial-gradient(16px 14px at 50% 100%, var(--snow) 97%, transparent 100%) 0 0 / 30px 22px repeat-x,
                radial-gradient(11px 9px at 50% 100%, var(--snow) 97%, transparent 100%) 15px 4px / 30px 22px repeat-x,
                linear-gradient(var(--snow), #dfe9fb) 0 100% / 100% 10px no-repeat;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.35));
            z-index: 2;
            pointer-events: none;
        }
        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 26px 22px 14px;
        }
        .card-head h2 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
        }
        .live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--ink-dim);
            font-size: 0.85rem;
        }
        .live::before {
            content: "";
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 0 0 rgba(47, 174, 106, 0.7);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            70%  { box-shadow: 0 0 0 10px rgba(47, 174, 106, 0); }
            100% { box-shadow: 0 0 0 0 rgba(47, 174, 106, 0); }
        }

        .table-wrap {
            max-height: 70vh;
            overflow: auto;
            border-top: 1px solid var(--glass-line);
            border-radius: 0 0 20px 20px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-variant-numeric: tabular-nums;
        }

        th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 12px 16px;
            background: linear-gradient(180deg, var(--red) 0%, var(--red-deep) 100%);
            color: #fff;
            font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
            font-size: 0.68rem;
            font-weight: 400;
            letter-spacing: 0.02em;
            opacity: 1;
            text-align: center;
            white-space: nowrap;
        }
        th::before {                    /* selkokielinen otsikko */
            content: attr(data-label);
            display: block;
            font-family: "Nunito", system-ui, sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }
        th::after {                     /* karkkikeppiraita */
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 4px;
            background: repeating-linear-gradient(-45deg, #fff 0 6px, var(--red) 6px 12px);
        }
        th:first-child { border-top-left-radius: 0; }

        td {
            padding: 11px 16px;
            border-bottom: 1px solid rgba(180, 210, 255, 0.08);
            text-align: center;
            white-space: nowrap;
        }
        tbody tr { transition: background 0.2s; }
        tbody tr:nth-child(even) td { background: rgba(255, 255, 255, 0.03); }
        tbody tr:hover td {
            background: rgba(246, 196, 83, 0.1);
        }
        tbody tr:hover td:first-child { box-shadow: inset 3px 0 0 var(--gold); }
        tbody tr:last-child td { border-bottom: 0; }

        td.col-id { color: var(--ink-dim); font-weight: 700; }
        td.col-aika { color: var(--ice); }
        td.col-sensorvalue_d { color: var(--gold); font-weight: 700; }

        td.empty {
            padding: 48px 16px;
            color: var(--ink-dim);
            font-size: 1.05rem;
        }

        footer {
            margin-top: 36px;
            text-align: center;
            color: var(--ink-dim);
            font-size: 0.9rem;
        }
        footer strong {
            font-family: "Mountains of Christmas", cursive;
            font-size: 1.6rem;
            color: var(--snow);
            margin-right: 4px;
        }

        @media (max-width: 600px) {
            .lights li:nth-child(n+13) { display: none; }
            .card-head { flex-direction: column; align-items: flex-start; gap: 6px; }
            th, td { padding: 10px 12px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<canvas id="snow" aria-hidden="true"></canvas>

<ul class="lights" aria-hidden="true">
    <li style="--i:0;--sag:0px"></li><li style="--i:1;--sag:6px"></li><li style="--i:2;--sag:11px"></li>
    <li style="--i:3;--sag:15px"></li><li style="--i:4;--sag:18px"></li><li style="--i:5;--sag:20px"></li>
    <li style="--i:6;--sag:21px"></li><li style="--i:7;--sag:21px"></li><li style="--i:8;--sag:20px"></li>
    <li style="--i:9;--sag:18px"></li><li style="--i:10;--sag:15px"></li><li style="--i:11;--sag:11px"></li>
    <li style="--i:12;--sag:6px"></li><li style="--i:13;--sag:0px"></li>
</ul>

<main>
    <header>
        <span class="eyebrow">🎄 Joulun mittausasema</span>
        <h1><span class="flake">❄</span> Sensoridata <span class="flake">❄</span></h1>
        <p class="subtitle">Kiihtyvyysanturin mittaukset suoraan tietokannasta</p>
    </header>

    <section class="stats">
        <div class="stat">
            <span class="stat-label">Mittauksia</span>
            <span class="stat-value"><?= $rowCount ?></span>
        </div>
        <div class="stat">
            <span class="stat-label">Viimeisin mittaus</span>
            <span class="stat-value small"><?= htmlspecialchars((string)$latest) ?></span>
        </div>
        <div class="stat">
            <span class="stat-label">Sivu päivitetty</span>
            <span class="stat-value"><?= $updated ?></span>
        </div>
        <button class="toggle" id="snowToggle" type="button" aria-pressed="true">
            <span>
                <span class="stat-label">Lumisade</span>
                <span class="stat-value small" id="snowState">Päällä</span>
            </span>
            <span class="switch" aria-hidden="true"></span>
        </button>
    </section>

    <section class="card">
        <div class="card-head">
            <h2>Kaikki mittaukset</h2>
            <span class="live">Uusin ylimpänä</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <?php
                        // Sarakkeiden nimet: <th>-tekstinä alkuperäinen nimi (Python-ohjelma lukee tämän),
                        // selkokielinen otsikko näytetään CSS:llä data-label-attribuutista.
                        foreach ($fields as $field) {
                            $name = $field->name;
                            $label = $labels[$name] ?? $name;
                            echo '<th data-label="' . htmlspecialchars($label) . '">' . htmlspecialchars($name) . '</th>';
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($rowCount > 0) {
                        foreach ($rows as $row) {
                            echo "<tr>";
                            foreach ($row as $key => $value) {
                                echo '<td class="col-' . htmlspecialchars($key) . '">' . htmlspecialchars((string)$value) . "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td class="empty" colspan="' . count($fields) . '">Ei vielä mittauksia – lumiukko odottaa ☃</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer>
        <strong>Hyvää joulua!</strong> 🎅 Data haettu tietokannasta <?= htmlspecialchars($dbname) ?>
    </footer>
</main>

<script>
(function () {
    const canvas = document.getElementById("snow");
    const ctx = canvas.getContext("2d");
    const toggle = document.getElementById("snowToggle");
    const stateText = document.getElementById("snowState");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    let W = 0, H = 0, flakes = [], running = true, t = 0, rafId = null;

    function makeFlake(randomY) {
        const r = Math.pow(Math.random(), 2) * 3.2 + 0.6;      // enemmän pieniä hiutaleita
        return {
            x: Math.random() * W,
            y: randomY ? Math.random() * H : -10,
            r: r,
            vy: r * 0.35 + 0.35,                                // isot putoavat nopeammin
            amp: Math.random() * 1.2 + 0.3,
            phase: Math.random() * Math.PI * 2,
            o: Math.random() * 0.45 + 0.45
        };
    }

    function resize() {
        const dpr = window.devicePixelRatio || 1;
        W = window.innerWidth;
        H = window.innerHeight;
        canvas.width = W * dpr;
        canvas.height = H * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        const target = Math.min(350, Math.round((W * H) / 7000));
        while (flakes.length < target) flakes.push(makeFlake(true));
        flakes.length = target;
        if (reduceMotion) draw();
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);
        for (const f of flakes) {
            ctx.beginPath();
            ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
            ctx.fillStyle = "rgba(255,255,255," + f.o + ")";
            ctx.fill();
        }
    }

    function step() {
        t += 0.01;
        const wind = Math.sin(t * 0.7) * 0.6 + Math.sin(t * 0.23) * 0.4;   // tuuli vaihtelee hitaasti
        for (const f of flakes) {
            f.y += f.vy;
            f.x += wind * (f.r * 0.25) + Math.sin(t * 2 + f.phase) * f.amp * 0.3;
            if (f.y > H + 10) Object.assign(f, makeFlake(false));
            if (f.x > W + 10) f.x = -10;
            if (f.x < -10) f.x = W + 10;
        }
        draw();
        rafId = requestAnimationFrame(step);
    }

    function setRunning(on) {
        running = on;
        canvas.classList.toggle("off", !on);
        toggle.setAttribute("aria-pressed", String(on));
        stateText.textContent = on ? "Päällä" : "Pois";
        try { localStorage.setItem("snow", on ? "1" : "0"); } catch (e) {}
        cancelAnimationFrame(rafId);
        if (on && !reduceMotion && !document.hidden) rafId = requestAnimationFrame(step);
    }

    toggle.addEventListener("click", function () { setRunning(!running); });
    window.addEventListener("resize", resize);
    document.addEventListener("visibilitychange", function () {
        cancelAnimationFrame(rafId);
        if (!document.hidden && running && !reduceMotion) rafId = requestAnimationFrame(step);
    });

    let saved = "1";
    try { saved = localStorage.getItem("snow") ?? "1"; } catch (e) {}
    resize();
    setRunning(saved !== "0");
})();
</script>

</body>
</html>

