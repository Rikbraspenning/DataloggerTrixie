<?php
// Database connectie-instellingen
$host = "localhost";
$user = "logger";
$password = "paswoord";
$database = "temperatures";

// Hoeveel uur terug voor de Live tabel
$hours = 24;

// Controleer of we JSON data moeten terugsturen (voor de grafieken)
if (isset($_GET['action']) && $_GET['action'] === 'getdata') {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT dateandtime, temperature, humidity FROM temperaturedata ORDER BY dateandtime ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Maak connectie voor de tabel data
$connectdb = mysqli_connect($host, $user, $password, $database)
    or die("Cannot reach database");

// SQL commando voor Live tabel
$sql = "SELECT * FROM temperaturedata WHERE dateandtime >= (NOW() - INTERVAL $hours HOUR) ORDER BY dateandtime DESC";
$temperatures = mysqli_query($connectdb, $sql);

// SQL voor dashboard statistieken (gemiddelde, min, max per week)
$sqlStats = "SELECT 
    YEAR(dateandtime) as jaar,
    WEEK(dateandtime, 1) as weeknummer,
    MIN(dateandtime) as week_start,
    MAX(dateandtime) as week_end,
    ROUND(AVG(temperature), 1) as gem_temp,
    ROUND(MIN(temperature), 1) as min_temp,
    ROUND(MAX(temperature), 1) as max_temp,
    ROUND(AVG(humidity), 1) as gem_vocht,
    ROUND(MIN(humidity), 1) as min_vocht,
    ROUND(MAX(humidity), 1) as max_vocht,
    COUNT(*) as metingen
FROM temperaturedata
GROUP BY YEAR(dateandtime), WEEK(dateandtime, 1)
ORDER BY jaar DESC, weeknummer DESC";

$weekStats = mysqli_query($connectdb, $sqlStats);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temperatuur- en Vochtigheidslogger</title>
    <link rel="icon" href="icon.png">
    <link rel="stylesheet" href="Styles.css">
</head>
<body>
    <nav>
        <div class="nav-tabs">
            <button class="tab-btn active" onclick="switchTab('home')">Home</button>
            <button class="tab-btn" onclick="switchTab('live')">Live</button>
            <button class="tab-btn" onclick="switchTab('dag')">Dag</button>
            <button class="tab-btn" onclick="switchTab('week')">Week</button>
            <button class="tab-btn" onclick="switchTab('maand')">Maand</button>
            <button class="tab-btn" onclick="switchTab('alltime')">All Time</button>
        </div>
    </nav>

    <div class="content">
        
        <!-- Home Tab -->
        <div id="home" class="tab-content active">
            <div class="hero">
                <h1>Temperatuur &amp;<br>Vochtigheids&shy;logger</h1>
                <p>Rik Braspenning — Lokaal L009</p>
            </div>

            <div class="card-grid">
                <div class="card" style="cursor:default;">
                    <h3>Wat</h3>
                    <p>In lokaal L009 bouwen we met een Raspberry Pi 3 een temperatuur- en vochtigheidslogger. Hiervoor gebruiken we een DHT22-sensor, die zowel de temperatuur als de relatieve luchtvochtigheid meet. De Raspberry Pi leest deze waarden elke 15 minuten uit en slaat ze op, zodat we het klimaat in het lokaal kunnen opvolgen en analyseren.</p>
                </div>
                <div class="card" style="cursor:default;">
                    <h3>Hardware</h3>
                    <p>Raspberry Pi 3 &mdash; DHT22 sensor &mdash; Meet-interval: 15 min &mdash; Opslag: MySQL database. De DHT22 heeft een nauwkeurigheid van ±0.5°C voor temperatuur en ±2–5% voor relatieve luchtvochtigheid.</p>
                </div>
            </div>

            <!-- Dashboard -->
            <div class="dashboard">
                <h2 class="section-title">Wekelijks Overzicht</h2>
                <div class="week-table-wrapper">
                    <div class="week-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Jaar</th>
                                    <th>Week</th>
                                    <th>Periode</th>
                                    <th>Metingen</th>
                                    <th>Gem. Temp</th>
                                    <th>Min Temp</th>
                                    <th>Max Temp</th>
                                    <th>Gem. Vocht</th>
                                    <th>Min Vocht</th>
                                    <th>Max Vocht</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($stat = mysqli_fetch_assoc($weekStats)) {
                                    $weekStart = date('d/m', strtotime($stat['week_start']));
                                    $weekEnd   = date('d/m', strtotime($stat['week_end']));
                                    echo "<tr>";
                                    echo "<td>{$stat['jaar']}</td>";
                                    echo "<td>W{$stat['weeknummer']}</td>";
                                    echo "<td>{$weekStart} — {$weekEnd}</td>";
                                    echo "<td>{$stat['metingen']}</td>";
                                    echo "<td><span class='temp-badge temp-avg'>{$stat['gem_temp']} °C</span></td>";
                                    echo "<td><span class='temp-badge temp-low'>{$stat['min_temp']} °C</span></td>";
                                    echo "<td><span class='temp-badge temp-high'>{$stat['max_temp']} °C</span></td>";
                                    echo "<td><span class='temp-badge temp-avg'>{$stat['gem_vocht']} %</span></td>";
                                    echo "<td><span class='temp-badge temp-low'>{$stat['min_vocht']} %</span></td>";
                                    echo "<td><span class='temp-badge temp-high'>{$stat['max_vocht']} %</span></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Tab -->
        <div id="live" class="tab-content">
            <h2 class="section-title">Live Data</h2>
            <div class="table-wrapper">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Datum &amp; Tijd</th>
                                <th>Sensor</th>
                                <th>Temperatuur</th>
                                <th>Vochtigheid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($temperature = mysqli_fetch_assoc($temperatures)) {
                                echo "<tr>";
                                echo "<td>{$temperature['dateandtime']}</td>";
                                echo "<td>{$temperature['sensor']}</td>";
                                echo "<td><span class='temp-badge temp-high'>{$temperature['temperature']} °C</span></td>";
                                echo "<td><span class='temp-badge temp-low'>{$temperature['humidity']} %</span></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- All Time Tab -->
        <div id="alltime" class="tab-content">
            <h2 class="section-title">All Time Data</h2>
            <div class="chart-container">
                <div class="loading" id="chart-loading">Data laden...</div>
                <svg id="chart-svg" class="chart-svg" viewBox="0 0 800 500" preserveAspectRatio="xMinYMin meet" style="display:none;"></svg>
            </div>
        </div>

        <!-- Dag Tab -->
        <div id="dag" class="tab-content">
            <h2 class="section-title">Dag</h2>
            <div class="card-grid">
                <div class="card" onclick="openModal('/Templogger/Raspi15DagTemperatuur.png', 'Temperatuur — Dag')">
                    <h3>Temperatuur</h3>
                    <img src="/Templogger/Raspi15DagTemperatuur.png" alt="Temperatuur grafiek" style="width:100%;height:auto;">
                </div>
                <div class="card" onclick="openModal('/Templogger/Raspi15DagVochtigheid.png', 'Vochtigheid — Dag')">
                    <h3>Vochtigheid</h3>
                    <img src="/Templogger/Raspi15DagVochtigheid.png" alt="Vochtigheid grafiek" style="width:100%;height:auto;">
                </div>
            </div>
        </div>

        <!-- Week Tab -->
        <div id="week" class="tab-content">
            <h2 class="section-title">Week</h2>
            <div class="card-grid">
                <div class="card" onclick="openModal('/Templogger/Raspi15WeekTemperatuur.png', 'Temperatuur — Week')">
                    <h3>Temperatuur</h3>
                    <img src="/Templogger/Raspi15WeekTemperatuur.png" alt="Temperatuur grafiek" style="width:100%;height:auto;">
                </div>
                <div class="card" onclick="openModal('/Templogger/Raspi15WeekVochtigheid.png', 'Vochtigheid — Week')">
                    <h3>Vochtigheid</h3>
                    <img src="/Templogger/Raspi15WeekVochtigheid.png" alt="Vochtigheid grafiek" style="width:100%;height:auto;">
                </div>
            </div>
        </div>

        <!-- Maand Tab -->
        <div id="maand" class="tab-content">
            <h2 class="section-title">Maand</h2>
            <div class="card-grid">
                <div class="card" onclick="openModal('/Templogger/Raspi15MaandTemperatuur.png', 'Temperatuur — Maand')">
                    <h3>Temperatuur</h3>
                    <img src="/Templogger/Raspi15MaandTemperatuur.png" alt="Temperatuur grafiek" style="width:100%;height:auto;">
                </div>
                <div class="card" onclick="openModal('/Templogger/Raspi15MaandVochtigheid.png', 'Vochtigheid — Maand')">
                    <h3>Vochtigheid</h3>
                    <img src="/Templogger/Raspi15MaandVochtigheid.png" alt="Vochtigheid grafiek" style="width:100%;height:auto;">
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="modal-close">&times;</span>
        <div class="modal-title" id="modalTitle"></div>
        <img class="modal-content" id="modalImage" alt="">
    </div>

    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');

            if (tabId === 'alltime' && !window.chartLoaded) loadChart();
        }

        function loadChart() {
            fetch('data.php')
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('chart-loading').textContent = 'Error: ' + data.error;
                        return;
                    }

                    document.getElementById('chart-loading').style.display = 'none';
                    const svgEl = document.getElementById('chart-svg');
                    svgEl.style.display = 'block';

                    const svg    = d3.select('#chart-svg'),
                          margin = {top: 40, right: 70, bottom: 55, left: 65},
                          width  = 800 - margin.left - margin.right,
                          height = 500 - margin.top - margin.bottom,
                          g      = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`);

                    const parseTime = d3.timeParse('%Y-%m-%d %H:%M:%S');
                    data.forEach(d => {
                        d.dateandtime = parseTime(d.dateandtime);
                        d.temperature = +d.temperature;
                        d.humidity    = +d.humidity;
                    });

                    const x     = d3.scaleTime().range([0, width]),
                          yTemp = d3.scaleLinear().range([height, 0]),
                          yHum  = d3.scaleLinear().range([height, 0]);

                    x.domain(d3.extent(data, d => d.dateandtime));
                    yTemp.domain([d3.min(data, d => d.temperature) - 1, d3.max(data, d => d.temperature) + 1]);
                    yHum.domain([d3.min(data, d => d.humidity) - 5,    d3.max(data, d => d.humidity) + 5]);

                    const lineTemp = d3.line().x(d => x(d.dateandtime)).y(d => yTemp(d.temperature));
                    const lineHum  = d3.line().x(d => x(d.dateandtime)).y(d => yHum(d.humidity));

                    // Gridlines
                    g.append('g').attr('class','grid').attr('transform',`translate(0,${height})`)
                     .call(d3.axisBottom(x).ticks(10).tickSize(-height).tickFormat(''));
                    g.append('g').attr('class','grid')
                     .call(d3.axisLeft(yTemp).ticks(10).tickSize(-width).tickFormat(''));

                    // X Axis
                    g.append('g').attr('transform',`translate(0,${height})`)
                     .call(d3.axisBottom(x).ticks(8))
                     .selectAll('text').style('fill','#9a9a9a').style('font-family','IBM Plex Mono').style('font-size','10px');

                    g.append('g').attr('transform',`translate(0,${height})`)
                     .call(d3.axisBottom(x).ticks(8).tickFormat(''))
                     .select('.domain').style('stroke','#3a3a3a');

                    // Y Axis left
                    g.append('g').call(d3.axisLeft(yTemp))
                     .selectAll('text').style('fill','#9a9a9a').style('font-family','IBM Plex Mono').style('font-size','10px');

                    // Y Axis right
                    g.append('g').attr('transform',`translate(${width},0)`).call(d3.axisRight(yHum))
                     .selectAll('text').style('fill','#9a9a9a').style('font-family','IBM Plex Mono').style('font-size','10px');

                    // Axis labels
                    g.append('text').attr('class','axis-label')
                     .attr('x', width/2).attr('y', height + 42)
                     .style('text-anchor','middle').text('TIJD');

                    g.append('text').attr('class','axis-label')
                     .attr('transform','rotate(-90)').attr('x',-height/2).attr('y',-48)
                     .style('text-anchor','middle').text('TEMPERATUUR (°C)');

                    g.append('text').attr('class','axis-label')
                     .attr('transform','rotate(-90)').attr('x', height/2).attr('y', width + 58)
                     .style('text-anchor','middle').text('LUCHTVOCHTIGHEID (%)');

                    // Lines
                    g.append('path').datum(data).attr('fill','none')
                     .attr('stroke','#e05555').attr('stroke-width',1.8).attr('d', lineTemp);
                    g.append('path').datum(data).attr('fill','none')
                     .attr('stroke','#3dbdb5').attr('stroke-width',1.8).attr('d', lineHum);

                    // Legend
                    const legend = svg.append('g').attr('transform',`translate(${margin.left + 10},${margin.top + 10})`);
                    legend.append('rect').attr('width',160).attr('height',60)
                     .attr('fill','rgba(29,31,33,0.9)').attr('stroke','rgba(255,255,255,0.12)');

                    legend.append('line').attr('x1',12).attr('y1',22).attr('x2',32).attr('y2',22)
                     .attr('stroke','#e05555').attr('stroke-width',2);
                    legend.append('text').attr('x',38).attr('y',26)
                     .attr('fill','#9a9a9a').style('font-family','IBM Plex Mono').style('font-size','11px')
                     .text('Temperatuur (°C)');

                    legend.append('line').attr('x1',12).attr('y1',42).attr('x2',32).attr('y2',42)
                     .attr('stroke','#3dbdb5').attr('stroke-width',2);
                    legend.append('text').attr('x',38).attr('y',46)
                     .attr('fill','#9a9a9a').style('font-family','IBM Plex Mono').style('font-size','11px')
                     .text('Vochtigheid (%)');

                    window.chartLoaded = true;
                })
                .catch(err => {
                    document.getElementById('chart-loading').textContent = 'Error loading data';
                    console.error(err);
                });
        }

        function openModal(src, title) {
            const modal = document.getElementById('imageModal');
            document.getElementById('modalImage').src   = src;
            document.getElementById('modalTitle').textContent = title;
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    </script>
</body>
</html>

<?php
mysqli_close($connectdb);
?>
