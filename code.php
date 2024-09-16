<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist und die richtige Rolle hat
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Lehrling_ohne_Schule') {
    header('Location: lehrling_login.php');
    exit;
}


// Verbindung zur Datenbank herstellen
include('db.php');

// Provisionen definieren
$commission_rates = [
    'A1 TV' => [
        'A1 Xplore TV Home' => 0,
        'A1 Xplore TV S Streaming' => 0,
        'A1 Xplore TV M Streaming' => 2
    ],
    'Sonstiges' => [
        'BRK' => 0,
        'Urlaub' => 0
    ],
    'BBI' => [
        'A1 Internet 50' => 20,
        'A1 Internet 100' => 20,
        'A1 Internet 150' => 20,
        'A1 Internet 300' => 20,
        'A1 Glasfaser Internet 250' => 20,
        'A1 Glasfaser Internet 500' => 20,
        'A1 Glasfaser Internet 1000' => 20
    ],
    'NetCube' => [
        'A1 Cube 30' => 0,
        'A1 Cube 50' => 0,
        'A1 Cube 100' => 0,
        'A1 Cube 150' => 0,
        'A1 Cube 300' => 0,
        'A1 Cube 500' => 0,
        'A1 Cube Xcite/Xcite+' => 0,
        'A1 Flex Cube 100' => 0,
        'A1 SIMply Data L' => 0
    ],
    'Voice' => [
        'A1 SIMply Xcite' => 0,
        'A1 SIMply S' => 0,
        'A1 SIMply M' => 0,
        'A1 SIMply L' => 0,
        'A1 Xcite' => 0,
        'A1 Mobil S' => 0,
        'A1 Mobil M' => 0,
        'A1 Mobil L' => 0,
        'A1 Mobil XL' => 0,
        'A1 Mobil Unlimited' => 0,
        'A1 SIMply Family' => 0,
        'A1 Kids SIMply' => 0,
        'A1 Kids' => 0,
        'Kids Watch' => 0,
        'RBM SIMpur' => 0,
        'RBM mit HW' => 0
    ],
    'KuBi' => [
        'NEXT' => 0,
        'LUP Mobil Voice' => 3,
        'M4S Mobil Voice' => 2,
        'TUP Mobil Voice' => 5,
        'TUP NetCube' => 0,
        'CR Mobil' => 0,
        'CR NetCube' => 0,
        'LUP Fixed' => 3,
        'TUP Fixed' => 5,
        'M4S Fixed' => 2,
        'Bandbreiten Upgrade 40/50' => 0,
        'Bandbreiten Upgrade 80/100' => 0,
        'Bandbreiten Upgrade 150/200' => 0,
        'Bandbreiten Upgrade 300' => 0,
        'Bandbreiten Upgrade 500/700/1000' => 0,
        'TUP Internet Power' => 0,
        'TUP TV – A1 TV Upgrades' => 0,
        'A1 Premium WLAN Box' => 0,
        'Get Wings' => 0,
        'B.free Wertkarten Umsteiger' => 0,
        'Übersiedlung' => 0
    ],
    'Expanded Portfolio' => [
        'A1 Handygarantie' => 0,
        'A1 Virenschutz' => 0,
        'Onlineschutz' => 0,
        'Sicher Shoppen' => 0,
        'Sicher Surfen' => 0,
        'Canal+' => 3,
        'Netflix' => 0,
        'A1 TV Optionen' => 0,
        'A1 Eintauschbonus' => 0,
        'IP Voice' => 0
    ]
];

// Monat und Jahr abfragen
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n'); // Aktueller Monat als Standard
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y'); // Aktuelles Jahr als Standard

$commission_summary = [];
$total_provision_all = 0;
$total_quantity_all = 0;
$total_quantity_bbi = 0;
$total_quantity_a1_tv = 0;
$total_quantity_kubi_tup = 0;
$total_quantity_kubi_lup = 0;
$total_quantity_kubi_m4s = 0;
$total_quantity_virenschutz_onlineschutz = 0;
$total_quantity_sichersurfen_sichershoppen = 0;
$total_quantity_expanded = 0;

// Berechnung der Provisionen und Stückzahlen
$qUser = $_SESSION['username'];
$month_padded = str_pad($selected_month, 2, '0', STR_PAD_LEFT);

$stmt = $conn->prepare("
    SELECT category, subcategory, quantity
    FROM entries
    WHERE qUser = ? AND YEAR(date) = ? AND MONTH(date) = ?
");
$stmt->bind_param("sss", $qUser, $selected_year, $month_padded);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $category = $row['category'];
    $subcategory = $row['subcategory'];
    $quantity = $row['quantity'];

    if (isset($commission_rates[$category][$subcategory])) {
        $commission_per_unit = $commission_rates[$category][$subcategory];
        $total_commission = $commission_per_unit * $quantity;

        if (!isset($commission_summary[$category][$subcategory])) {
            $commission_summary[$category][$subcategory] = [
                'total_provision' => 0,
                'total_quantity' => 0
            ];
        }

        $commission_summary[$category][$subcategory]['total_provision'] += $total_commission;
        $commission_summary[$category][$subcategory]['total_quantity'] += $quantity;

        if ($category === 'BBI') {
            $total_quantity_bbi += $quantity;
        } elseif ($category === 'A1 TV') {
            $total_quantity_a1_tv += $quantity;
        }elseif ($category === 'Expanded Portfolio') {
            if($subcategory === 'Canal+'){
                $total_quantity_expanded += $quantity;
            } elseif ($subcategory === 'A1 Virenschutz' || $subcategory === 'Onlineschutz'){
                $total_quantity_virenschutz_onlineschutz += $quantity;
            } elseif($subcategory === 'Sicher Surfen' || $subcategory === 'Sicher Shoppen'){
                $total_quantity_sichersurfen_sichershoppen += $quantity;
            }
        } elseif ($category === 'KuBi') {
            if ($subcategory === 'TUP Fixed' || $subcategory === 'TUP Mobil Voice') {
                $total_quantity_kubi_tup += $quantity;
            } elseif ($subcategory === 'LUP Fixed' || $subcategory === 'LUP Mobil Voice') {
                $total_quantity_kubi_lup += $quantity;
            } elseif ($subcategory === 'M4S Fixed' || $subcategory === 'M4S Mobil Voice') {
                $total_quantity_kubi_m4s += $quantity;
            }
        }

        $total_provision_all += $total_commission;
        $total_quantity_all += $quantity;
    }
}

$stmt->close();
$conn->close();

// Überprüfen, ob die Ziele erreicht wurden
$goal_bbi_reached = $total_quantity_bbi >= 11;
$goal_a1_tv_reached = $total_quantity_a1_tv >= 4;
$goal_kubi_tup_reached = $total_quantity_kubi_tup >= 12;
$goal_kubi_lup_reached = $total_quantity_kubi_lup >= 18;
$goal_kubi_m4s_reached = $total_quantity_kubi_m4s >= 6;
$goal_expanded_reached = $total_quantity_expanded >= 4;
$goal_virenschutz_onlineschutz_reached = $total_quantity_virenschutz_onlineschutz >= 30;
$goal_sichersurfen_sichershoppen_reached = $total_quantity_sichersurfen_sichershoppen >= 10;

$goal_reached = $total_quantity_all >= 132 && $goal_bbi_reached && $goal_a1_tv_reached && 
$goal_kubi_tup_reached && $goal_kubi_lup_reached && $goal_kubi_m4s_reached && $goal_expanded_reached 
&& $goal_virenschutz_onlineschutz_reached && $goal_sichersurfen_sichershoppen_reached;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkaufsstatistik und Provision</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <style>
        .red { color: red; }
        .green { color: green; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Verkaufsstatistik und Provision</h1>
            <nav>
                <a href="lehrling_ohne_schule_dashboard.php" class="nav-button">Zurück zum Dashboard</a>
                <a href="logout.php" class="nav-button logout">Logout</a>
            </nav>
        </header>
        
        <main>
            <section>
                <h2>Gesamtprovision und Stückzahl</h2>
                <?php
                // Berechnung der Gesamtprovision nur für Produkte, die das Ziel erreicht haben
                $total_provision_with_goals_reached = 0;
                foreach ($commission_summary as $category => $subcategories) {
                    foreach ($subcategories as $subcategory => $data) {
                        if (is_goal_reached($category, $subcategory)) {
                            $total_provision_with_goals_reached += $data['total_provision'];
                        }
                    }
                }
                
                // Bestimmung der Farbe für die Gesamtstückzahl
                $total_quantity_color = $total_quantity_all >= 132 ? 'green' : 'red';
                ?>
                <p>Gesamtprovision aller Verkaufseinträge: <strong class="green"><?php echo number_format($total_provision_with_goals_reached, 2, ',', '.'); ?> €</strong></p>
                <p>Gesamtstückzahl aller Verkaufseinträge: <strong class="<?php echo $total_quantity_color; ?>"><?php echo number_format($total_quantity_all); ?>Stk. / 132 Stk.</strong></p>
            </section>

            <section>
                <h2>Verkaufte Produkte und Provisionen</h2>
                <form method="get" action="">
                    <label for="month">Monat:</label>
                    <select name="month" id="month">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i == $selected_month) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <label for="year">Jahr:</label>
                    <select name="year" id="year">
                        <?php for ($i = date('Y') - 10; $i <= date('Y'); $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i == $selected_year) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="nav-button logout">Filter anwenden</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Kategorie</th>
                            <th>Unterkategorie</th>
                            <th>Gesamtprovision</th>
                            <th>Stückzahl</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Hilfsfunktion zur Überprüfung, ob    das Ziel erreicht wurde
                        function is_goal_reached($category, $subcategory) {
                            global $total_quantity_bbi, $total_quantity_a1_tv, $total_quantity_kubi_tup, $total_quantity_kubi_lup, $total_quantity_kubi_m4s, 
                            $total_quantity_expanded, $total_quantity_virenschutz_onlineschutz;
                            switch ($category) {
                                case 'BBI':
                                    return $total_quantity_bbi >= 11;
                                case 'A1 TV':
                                    if ($subcategory === 'A1 Xplore TV M Streaming') {
                                        return $total_quantity_a1_tv >= 4;
                                    }
                                    break;
                                case 'KuBi':
                                    if ($subcategory === 'TUP Fixed' || $subcategory === 'TUP Mobil Voice') {
                                        return $total_quantity_kubi_tup >= 12;
                                    } elseif ($subcategory === 'LUP Fixed' || $subcategory === 'LUP Mobil Voice') {
                                        return $total_quantity_kubi_lup >= 18;
                                    } elseif ($subcategory === 'M4S Fixed' || $subcategory === 'M4S Mobil Voice') {
                                        return $total_quantity_kubi_m4s >= 6;
                                    } 
                                    break;
                                case 'Expanded Portfolio':
                                    if ($subcategory === 'Canal+') {
                                        return $total_quantity_expanded >= 4;
                                    } elseif($subcategory === 'A1 Virenschutz' || $subcategory === 'Onlineschutz'){
                                        return $total_quantity_virenschutz_onlineschutz >= 30;
                                    } elseif($subcategory === 'Sicher Surfen' || $subcategory === 'Sicher Shoppen'){
                                        return $total_quantity_sichersurfen_sichershoppen >= 10;
                                    }
                                    break;
                            }
                            return false;
                        }

                        
                        ?>
                        <?php 
                        // Beispiel für die Provisionssätze
                        $commission_rates = [
                            'BBI' => 20, // Provision pro Stück für BBI
                            // Weitere Kategorien und deren Provisionen hier
                        ];

                        // Durchlaufen der $commission_summary für die BBI-Kategorie
                        foreach ($commission_summary as $category => $subcategories): ?>
                            <?php if ($category === 'BBI'): ?>
                                <?php 
                                // Initialisieren der Variablen für die aggregierte Provision und Stückzahl
                                $total_provision_bbi = 0;
                                $total_quantity_bbi = 0;
                                
                                // Provisionsrate für BBI
                                $provision_rate_bbi = isset($commission_rates['BBI']) ? $commission_rates['BBI'] : 0;

                                // Iterieren über die Unterkategorien
                                foreach ($subcategories as $subcategory => $data): ?>
                                    <?php 
                                    // Summieren der Stückzahlen
                                    $total_quantity_bbi += $data['total_quantity'];
                                    ?>
                                <?php endforeach; ?>

                                <?php 
                                // Berechnen der Gesamtprovision für BBI
                                $total_provision_bbi = $total_quantity_bbi * $provision_rate_bbi;
                                
                                // Überprüfen, ob das kombinierte Ziel für BBI erreicht ist
                                $goal_reached_for_bbi = $total_quantity_bbi >= 11;
                                ?>

                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>-</td>
                                    <td>
                                        <?php if ($goal_reached_for_bbi): ?>
                                            <span class="green"><?php echo number_format($total_provision_bbi, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_bbi, 0, ',', '.'); ?> / 11</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_bbi); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php 
                        // Beispiel für die Provisionssätze
                        $commission_rates = [
                            'KuBi_TUP' => 5, // Provision pro Stück für TUP Fixed und TUP Mobil Voice
                            'KuBi_LUP' => 3,  // Provision pro Stück für LUP Fixed und LUP Mobil Voice
                            'KuBi_M4S' => 2  // Provision pro Stück für M4S Fixed und M4S Mobil Voice
                            // Weitere Kategorien und deren Provisionen hier
                        ];

                        // Durchlaufen der $commission_summary für die KuBi-Kategorie
                        foreach ($commission_summary as $category => $subcategories): ?>
                            <?php if ($category === 'KuBi'): ?>
                                <?php 
                                // Initialisieren der Variablen für die aggregierte Provision und Stückzahl für jede Gruppe
                                $total_provision_kubi_tup = 0;
                                $total_quantity_kubi_tup = 0;
                                
                                $total_provision_kubi_lup = 0;
                                $total_quantity_kubi_lup = 0;
                                
                                $total_provision_kubi_m4s = 0;
                                $total_quantity_kubi_m4s = 0;

                                // Provisionsraten für jede Gruppe
                                $provision_rate_kubi_tup = isset($commission_rates['KuBi_TUP']) ? $commission_rates['KuBi_TUP'] : 0;
                                $provision_rate_kubi_lup = isset($commission_rates['KuBi_LUP']) ? $commission_rates['KuBi_LUP'] : 0;
                                $provision_rate_kubi_m4s = isset($commission_rates['KuBi_M4S']) ? $commission_rates['KuBi_M4S'] : 0;

                                // Iterieren über die Unterkategorien
                                foreach ($subcategories as $subcategory => $data): ?>
                                    <?php 
                                    // Summieren der Provision und Stückzahl für jede Gruppe
                                    if ($subcategory === 'TUP Fixed' || $subcategory === 'TUP Mobil Voice') {
                                        $total_quantity_kubi_tup += $data['total_quantity'];
                                    } elseif ($subcategory === 'LUP Fixed' || $subcategory === 'LUP Mobil Voice') {
                                        $total_quantity_kubi_lup += $data['total_quantity'];
                                    } elseif ($subcategory === 'M4S Fixed' || $subcategory === 'M4S Mobil Voice') {
                                        $total_quantity_kubi_m4s += $data['total_quantity'];
                                    }
                                    ?>
                                <?php endforeach; ?>

                                <?php 
                                // Berechnen der Gesamtprovision für jede Gruppe
                                $total_provision_kubi_tup = $total_quantity_kubi_tup * $provision_rate_kubi_tup;
                                $total_provision_kubi_lup = $total_quantity_kubi_lup * $provision_rate_kubi_lup;
                                $total_provision_kubi_m4s = $total_quantity_kubi_m4s * $provision_rate_kubi_m4s;
                                
                                // Überprüfen, ob das kombinierte Ziel für jede Gruppe erreicht ist
                                $goal_reached_for_kubi_tup = $total_quantity_kubi_tup >= 12;
                                $goal_reached_for_kubi_lup = $total_quantity_kubi_lup >= 18;
                                $goal_reached_for_kubi_m4s = $total_quantity_kubi_m4s >= 6;
                                ?>

                                <!-- Zeilen für jede Gruppe in der Tabelle -->
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>TUP</td>
                                    <td>
                                        <?php if ($goal_reached_for_kubi_tup): ?>
                                            <span class="green"><?php echo number_format($total_provision_kubi_tup, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_kubi_tup, 0, ',', '.'); ?> / 12</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_kubi_tup); ?></td>
                                </tr>
                                
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>LUP</td>
                                    <td>
                                        <?php if ($goal_reached_for_kubi_lup): ?>
                                            <span class="green"><?php echo number_format($total_provision_kubi_lup, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_kubi_lup, 0, ',', '.'); ?> / 18</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_kubi_lup); ?></td>
                                </tr>
                                
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>M4S</td>
                                    <td>
                                        <?php if ($goal_reached_for_kubi_m4s): ?>
                                            <span class="green"><?php echo number_format($total_provision_kubi_m4s, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_kubi_m4s, 0, ',', '.'); ?> / 6</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_kubi_m4s); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php 
                        // Beispiel für die Provisionssätze
                        $commission_rates = [
                            'A1_TV' => 2 // Provision pro Stück für A1 Xplore TV M Streaming
                            // Weitere Kategorien und deren Provisionen hier
                        ];

                        // Durchlaufen der $commission_summary für die A1 TV-Kategorie
                        foreach ($commission_summary as $category => $subcategories): ?>
                            <?php if ($category === 'A1 TV'): ?>
                                <?php 
                                // Initialisieren der Variablen für die Provision und Stückzahl
                                $total_provision_a1_tv = 0;
                                $total_quantity_a1_tv = 0;


                                // Provisionsrate für die Unterkategorie
                                $provision_rate_a1_tv = isset($commission_rates['A1_TV']) ? $commission_rates['A1_TV'] : 0;

                                // Iterieren über die Unterkategorien
                                foreach ($subcategories as $subcategory => $data): ?>
                                    <?php 
                                    // Summieren der Provision und Stückzahl
                                    if ($subcategory === 'A1 Xplore TV M Streaming') {
                                        $total_quantity_a1_tv += $data['total_quantity'];
                                    }
                                    ?>
                                <?php endforeach; ?>

                                <?php 
                                // Berechnen der Gesamtprovision
                                $total_provision_a1_tv = $total_quantity_a1_tv * $provision_rate_a1_tv;

                                // Überprüfen, ob das Ziel erreicht ist
                                $goal_reached_for_a1_tv = $total_quantity_a1_tv >= 4;
                                ?>

                                <!-- Zeile für die A1 TV Unterkategorie in der Tabelle -->
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>A1 Xplore TV M Streaming</td>
                                    <td>
                                        <?php if ($goal_reached_for_a1_tv): ?>
                                            <span class="green"><?php echo number_format($total_provision_a1_tv, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_a1_tv, 0, ',', '.'); ?> / 4</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_a1_tv); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php 
                        // Beispiel für die Provisionssätze
                        $commission_rates = [
                            'Canal+' => 5, // Provision pro Stück für TUP Fixed und TUP Mobil Voice
                            'KuBi_Viren_Online' => 0,  // Provision pro Stück für LUP Fixed und LUP Mobil Voice
                            'KuBi_Sicher_Surfen_Shoppen' => 0  // Provision pro Stück für M4S Fixed und M4S Mobil Voice
                            // Weitere Kategorien und deren Provisionen hier
                        ];

                        // Durchlaufen der $commission_summary für die KuBi-Kategorie
                        foreach ($commission_summary as $category => $subcategories): ?>
                            <?php if ($category === 'Expanded Portfolio'): ?>
                                <?php 
                                // Initialisieren der Variablen für die aggregierte Provision und Stückzahl für jede Gruppe
                                $total_provision_expanded = 0;
                                $total_quantity_expanded = 0;
                                
                                $total_provision_viren_online = 0;
                                $total_quantity_virenschutz_onlineschutz = 0;
                                
                                $total_provision_sicher_surfen_shoppen = 0;
                                $total_quantity_sichersurfen_sichershoppen = 0;

                                // Provisionsraten für jede Gruppe
                                $provision_rate_expanded = isset($commission_rates['Canal+']) ? $commission_rates['Canal+'] : 0;
                                $provision_rate_viren_online = isset($commission_rates['KuBi_Viren_Online']) ? $commission_rates['KuBi_Viren_Online'] : 0;
                                $provision_rate_sicher_surfen_shoppen = isset($commission_rates['KuBi_Sicher_Surfen_Shoppen']) ? $commission_rates['KuBi_Sicher_Surfen_Shoppen'] : 0;

                                // Iterieren über die Unterkategorien
                                foreach ($subcategories as $subcategory => $data): ?>
                                    <?php 
                                    // Summieren der Provision und Stückzahl für jede Gruppe
                                    if ($subcategory === 'Canal+') {
                                        $total_quantity_expanded += $data['total_quantity'];
                                    } elseif ($subcategory === 'A1 Virenschutz' || $subcategory === 'Onlineschutz') {
                                        $total_quantity_virenschutz_onlineschutz += $data['total_quantity'];
                                    } elseif ($subcategory === 'Sicher Surfen' || $subcategory === 'Sicher Shoppen') {
                                        $total_quantity_sichersurfen_sichershoppen += $data['total_quantity'];
                                    }
                                    ?>
                                <?php endforeach; ?>

                                <?php 
                                // Berechnen der Gesamtprovision für jede Gruppe
                                $total_provision_expanded = $total_quantity_expanded * $provision_rate_expanded;
                                $total_provision_viren_online = $total_quantity_virenschutz_onlineschutz * $provision_rate_viren_online;
                                $total_provision_sicher_surfen_shoppen = $total_quantity_sichersurfen_sichershoppen * $provision_rate_sicher_surfen_shoppen;
                                
                                // Überprüfen, ob das kombinierte Ziel für jede Gruppe erreicht ist
                                $goal_reached_for_expanded = $total_quantity_expanded >= 5;
                                $goal_reached_for_viren_online = $total_quantity_virenschutz_onlineschutz >= 30;
                                $goal_reached_for_sicher_surfen_shoppen = $total_quantity_sichersurfen_sichershoppen >= 10;
                                ?>

                                <!-- Zeilen für jede Gruppe in der Tabelle -->
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>Canal+</td>
                                    <td>
                                        <?php if ($goal_reached_for_expanded): ?>
                                            <span class="green"><?php echo number_format($total_provision_expanded, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_expanded, 0, ',', '.'); ?> / 5</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_expanded); ?></td>
                                </tr>
                                
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>A1 Virenschutz + Onlineschutz</td>
                                    <td>
                                        <?php if ($goal_reached_for_viren_online): ?>
                                            <span class="green"><?php echo number_format($total_provision_viren_online, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_virenschutz_onlineschutz, 0, ',', '.'); ?> / 30</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_virenschutz_onlineschutz); ?></td>
                                </tr>

                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>Sicher Surfen + Sicher Shoppen</td>
                                    <td>
                                        <?php if ($goal_reached_for_sicher_surfen_shoppen): ?>
                                            <span class="green"><?php echo number_format($total_provision_sicher_surfen_shoppen, 2, ',', '.'); ?> €</span>
                                        <?php else: ?>
                                            <span class="red">Nicht Erreicht <?php echo number_format($total_quantity_sichersurfen_sichershoppen, 0, ',', '.'); ?> / 10</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($total_quantity_sichersurfen_sichershoppen); ?></td>
                                </tr>
                                
                                <!-- Hier bitte einfach von oben kopieren und alle Werte umtauschen -->
                            <?php endif; ?>
                        <?php endforeach; ?>



                        
                        <!-- Gesamtzeilen -->
                        <tr>
                            <td colspan="2"><strong class="red">Gesamt</strong></td>
                            <td>
                                <strong class="<?php echo $goal_reached ? 'green' : 'red'; ?>">
                                    <?php echo number_format($total_provision_with_goals_reached, 2, ',', '.'); ?> €
                                </strong>
                            </td>
                            <td>
                                <strong class="<?php echo $total_quantity_color; ?>">
                                    <?php echo number_format($total_quantity_all); ?> Stk. 
                                </strong>
                            </td>
                        </tr>
                        <thead>
                            <tr>
                                <th>Kategorie</th>
                                <th>Unterkategorie</th>
                                <th>Gesamtstückzahl</th>
                                <th></th>
                            </tr>
                        </thead>    
                        <tr>
                            <td><strong>BBI</strong></td>
                            <td>Gesamt</td>
                            <td>
                                <?php if ($total_quantity_bbi >= 18): ?>
                                    <span class="green"><?php echo number_format($total_quantity_bbi, 0, ',', '.'); ?> / 11</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_bbi, 0, ',', '.'); ?> / 11</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>A1 TV</strong></td>
                            <td>A1 Xplore TV M Streaming</td>
                            <td>
                                <?php if ($total_quantity_a1_tv >= 5): ?>
                                    <span class="green"><?php echo number_format($total_quantity_a1_tv, 0, ',', '.'); ?> / 4</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_a1_tv, 0, ',', '.'); ?> / 4</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>KuBi</strong></td>
                            <td>TUP Fixed + TUP Mobil Voice</td>
                            <td>
                                <?php if ($total_quantity_kubi_tup >= 20): ?>
                                    <span class="green"><?php echo number_format($total_quantity_kubi_tup, 0, ',', '.'); ?> / 12</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_kubi_tup, 0, ',', '.'); ?> / 12</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>KuBi</strong></td>
                            <td>LUP Fixed + LUP Mobil Voice</td>
                            <td>
                                <?php if ($total_quantity_kubi_lup >= 30): ?>
                                    <span class="green"><?php echo number_format($total_quantity_kubi_lup, 0, ',', '.'); ?> / 18</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_kubi_lup, 0, ',', '.'); ?> / 18</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>KuBi</strong></td>
                            <td>M4S Fixed + M4S Mobil Voice</td>
                            <td>
                                <?php if ($total_quantity_kubi_m4s >= 10): ?>
                                    <span class="green"><?php echo number_format($total_quantity_kubi_m4s, 0, ',', '.'); ?> / 6</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_kubi_m4s, 0, ',', '.'); ?> / 6</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>Expanded Portfolio</strong></td>
                            <td>Canal+</td>
                            <td>
                                <?php if ($total_quantity_expanded >= 5): ?>
                                    <span class="green"><?php echo number_format($total_quantity_expanded, 0, ',', '.'); ?> / 4</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_expanded, 0, ',', '.'); ?> / 4</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>Expanded Portfolio</strong></td>
                            <td>A1 Virenschutz + Onlineschutz<span class="red"> => mind. 30 Stk.</span></td>
                            <td>
                                <?php if ($total_quantity_virenschutz_onlineschutz >= 30): ?>
                                    <span class="green"><?php echo number_format($total_quantity_virenschutz_onlineschutz, 0, ',', '.'); ?> / 30</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_virenschutz_onlineschutz, 0, ',', '.'); ?> / 30</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>Expanded Portfolio</strong></td>
                            <td>Sicher Surfen + Sicher Shoppen<span class="red"> => mind. 10 Stk.</span></td>
                            <td>
                                <?php if ($total_quantity_sichersurfen_sichershoppen >= 10): ?>
                                    <span class="green"><?php echo number_format($total_quantity_sichersurfen_sichershoppen, 0, ',', '.'); ?> / 10</span>
                                <?php else: ?>
                                    <span class="red"><?php echo number_format($total_quantity_sichersurfen_sichershoppen, 0, ',', '.'); ?> / 10</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                        
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
