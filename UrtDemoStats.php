<?php

$folderIn = 'demo_in/';
$folderOutDemoTxt = 'demo_out/demo_to_txt/';
$folderOut = 'demo_out/';

$files = array();
foreach (glob($folderIn . '*.urtdemo') as $file) {
    $filesIn[] = $file;
}

if (empty($filesIn)) {
    echo 'No demo inside folder demo_in' . PHP_EOL;
    exit(0);
}

foreach ($filesIn as $fileIn) {
    if (($fileIn === '.') || ($fileIn === '..') || ($fileIn === '.gitkeep')) {
        continue;
    }
    // Just used to log correctly
    $fileInLog = $fileIn;
    // Concert to UNIX path for use gdemo.exe
    $fileIn = './' . $fileIn;
    $fileIn = str_replace('\\', '/', $fileIn);
    $fileOut = $folderOutDemoTxt . basename($fileIn, ".urtdemo") . '.txt';
    echo 'Convert "' . $fileInLog . '" to "' . $fileOut . '"' . PHP_EOL;
    // Call gdemo.exe to convert demo in .txt
    exec(".\binary\gdemo.exe -i \"$fileIn\" -a > \"$fileOut\"");
}

$files = scandir($folderOutDemoTxt);

foreach ($files as $fileIn) {
    // Init all array
    $tabMatchScore = array();
    $tabRound = array();
    $tabRoundClutch = array();
    $tabPlayer = array();
    $tabPlayerTeam = array();
    $tabPlayerStats = array();
    $tabIndivualScore = array();
    $tabStatsMatch = array();
    $playerClutching = '';
    $teamClutching = '';
    $lineRound = 0;
    if (($fileIn === '.') || ($fileIn === '..') || ($fileIn === '.gitkeep')) {
        continue;
    }
    $fileIn = $folderOutDemoTxt . $fileIn;
    if ($fh = fopen($fileIn, 'r')) {
        $nbRound = 1;
        while (!feof($fh)) {
            $line = fgets($fh);
            // Replace non UTF8 character by space
            $line = preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $line);
            $tabLine = array();
            $tabLine = explode(' ', $line);

            if ($tabLine[0] === 'info:') {
                $teamInfo = explode('=', $tabLine[3]);
                if ($teamInfo[1] != 'spectator') {
                    $tabPlayer[] = $tabLine[2];
                    $tabPlayerTeam[$tabLine[2]] = $teamInfo[1];
                }
            }

            // Start of match
            if ($tabLine[0] === 'serverinfo:') {
                $tabRoundClutch = array();
                foreach ($tabPlayer as $player) {
                    $tabIndivualScore[$nbRound][$player]['kill'] = 0;
                    $tabIndivualScore[$nbRound][$player]['death'] = 0;
                    $tabRoundClutch[$tabPlayerTeam[$player]][$player] = 1;
                    $tabStatsMatch[$player]['damage'] = 0;
                    $tabStatsMatch[$player]['hit'] = 0;
                    $tabStatsMatch[$player]['miss'] = 0;
                    $tabStatsMatch[$player]['kills'] = 0;
                    $tabStatsMatch[$player]['deaths'] = 0;
                }
            }

            // New Round
            if (($tabLine[0] === 'round:') && ($tabLine[2] === 'status=start')) {
                $nbRound++;
                $tabRoundClutch = array();
                $playerClutching = '';
                $teamClutching = '';
                foreach ($tabPlayer as $player) {
                    $tabIndivualScore[$nbRound][$player]['kill'] = $tabIndivualScore[$nbRound - 1][$player]['kill'];
                    $tabIndivualScore[$nbRound][$player]['death'] = $tabIndivualScore[$nbRound - 1][$player]['death'];
                    $tabRoundClutch[$tabPlayerTeam[$player]][$player] = 1;
                }
            }
            $lineRound++;

            if (($tabLine[0] === 'round:') && ($tabLine[2] === 'status=end')) {
                if (stripos($tabLine[3], 'Red') != false) {
                    if ($teamClutching === 'red') {
                        $tabRound[$lineRound]['shooter'] = $playerClutching;
                        $tabRound[$lineRound]['is_kill'] = 0;
                        $tabRound[$lineRound]['round'] = $nbRound;
                        $tabRound[$lineRound]['damage'] = 0;
                        $tabRound[$lineRound]['hit'] = 0;
                        $tabRound[$lineRound]['miss'] = 0;
                        $tabRound[$lineRound]['clutch'] = 1;
                    }
                    if ($nbRound === 1) {
                        $tabMatchScore[$nbRound]['NbRoundWinRedTeam'] = 1;
                        $tabMatchScore[$nbRound]['NbRoundWinBlueTeam'] = 0;
                    } else {
                        $tabMatchScore[$nbRound]['NbRoundWinRedTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinRedTeam'] + 1;
                        $tabMatchScore[$nbRound]['NbRoundWinBlueTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinBlueTeam'];
                    }
                } elseif (stripos($tabLine[3], 'Blue') != false) {
                    if ($teamClutching === 'blue') {
                        $tabRound[$lineRound]['shooter'] = $playerClutching;
                        $tabRound[$lineRound]['is_kill'] = 0;
                        $tabRound[$lineRound]['round'] = $nbRound;
                        $tabRound[$lineRound]['damage'] = 0;
                        $tabRound[$lineRound]['hit'] = 0;
                        $tabRound[$lineRound]['miss'] = 0;
                        $tabRound[$lineRound]['clutch'] = 1;
                    }
                    if ($nbRound === 1) {
                        $tabMatchScore[$nbRound]['NbRoundWinRedTeam'] = 0;
                        $tabMatchScore[$nbRound]['NbRoundWinBlueTeam'] = 1;
                    } else {
                        $tabMatchScore[$nbRound]['NbRoundWinBlueTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinBlueTeam'] + 1;
                        $tabMatchScore[$nbRound]['NbRoundWinRedTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinRedTeam'];
                    }
                } else {
                    // DRAW
                    $tabMatchScore[$nbRound]['NbRoundWinRedTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinRedTeam'];
                    $tabMatchScore[$nbRound]['NbRoundWinBlueTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinBlueTeam'];
                }
            }


            if ($tabLine[0] === 'kill:') {
                $teamShooter = $tabPlayerTeam[$tabLine[7]];
                $teamTarget = $tabPlayerTeam[$tabLine[2]];
                unset($tabRoundClutch[$teamTarget][$tabLine[2]]);
                if ((count($tabRoundClutch[$teamTarget]) === 1) && (count($tabRoundClutch[$teamShooter]) >= 2)) {
                    $tabPlayerClutching = $tabRoundClutch[$teamTarget];
                    foreach ($tabPlayerClutching as $key => $value) {
                        $playerClutching = $key;
                        $teamClutching = $teamTarget;
                    }
                }
                if ((count($tabRoundClutch[$teamShooter]) === 1) && (count($tabRoundClutch[$teamTarget]) >= 2)) {
                    $tabPlayerClutching = $tabRoundClutch[$teamShooter];
                    foreach ($tabPlayerClutching as $key => $value) {
                        $playerClutching = $key;
                        $teamClutching = $teamShooter;
                    }
                }
                if ($teamShooter != $teamTarget) {
                    $tabRound[$lineRound]['shooter'] = $tabLine[7];
                    $tabRound[$lineRound]['is_kill'] = 1;
                    $tabRound[$lineRound]['round'] = $nbRound;
                    $tabRound[$lineRound]['damage'] = 0;
                    $tabRound[$lineRound]['hit'] = 0;
                    $tabRound[$lineRound]['miss'] = 0;
                    $tabRound[$lineRound]['clutch'] = 0;
                    $tabIndivualScore[$nbRound][$tabLine[7]]['kill'] += 1;
                    $tabIndivualScore[$nbRound][$tabLine[2]]['death'] += 1;
                    $tabIndivualScore[$nbRound][$tabLine[2]]['kill'] += 0;
                    $tabIndivualScore[$nbRound][$tabLine[7]]['death'] += 0;
                } else {
                    $tabIndivualScore[$nbRound][$tabLine[7]]['kill'] -= 1;
                    $tabIndivualScore[$nbRound][$tabLine[2]]['death'] += 1;
                    $tabIndivualScore[$nbRound][$tabLine[2]]['kill'] += 0;
                    $tabIndivualScore[$nbRound][$tabLine[7]]['death'] += 0;
                }
            }

            if ($tabLine[0] === 'hit:') {
                $teamShooter = $tabPlayerTeam[$tabLine[2]];
                $teamTarget = $tabPlayerTeam[$tabLine[5]];
                if ($teamShooter != $teamTarget) {
                    $tabRound[$lineRound]['shooter'] = $tabLine[2];
                    $tabRound[$lineRound]['is_kill'] = 0;
                    $tabRound[$lineRound]['round'] = $nbRound;
                    $tabRound[$lineRound]['damage'] = $tabLine[7];
                    $tabRound[$lineRound]['hit'] = 1;
                    $tabRound[$lineRound]['miss'] = 0;
                    $tabRound[$lineRound]['clutch'] = 0;
                    $tabStatsMatch[$tabLine[2]]['damage'] += $tabLine[7];
                    $tabStatsMatch[$tabLine[2]]['hit'] += 1;
                }
            }

            if ($tabLine[0] === 'miss:') {
                //$tabRound[$lineRound]['victim'] = $tabLine[2];
                $tabRound[$lineRound]['shooter'] = $tabLine[2];
                $tabRound[$lineRound]['is_kill'] = 0;
                $tabRound[$lineRound]['round'] = $nbRound;
                $tabRound[$lineRound]['damage'] = 0;
                $tabRound[$lineRound]['hit'] = 0;
                $tabRound[$lineRound]['miss'] = 1;
                $tabRound[$lineRound]['clutch'] = 0;
                $tabStatsMatch[$tabLine[2]]['miss'] += 1;
            }


            if (($tabLine[0] === 'warmup:') && (preg_match('/^secs=[0-9]/', $tabLine[2]))) {
                // Match reset
                // Init all array
                $tabMatchScore = array();
                $tabRound = array();
                $tabRoundClutch = array();
                $tabPlayerStats = array();
                $tabIndivualScore = array();
                $tabStatsMatch = array();
                $playerClutching = '';
                $teamClutching = '';
                $lineRound = 0;
                $nbRound = 1;
                foreach ($tabPlayer as $player) {
                    $tabIndivualScore[$nbRound][$player]['kill'] = 0;
                    $tabIndivualScore[$nbRound][$player]['death'] = 0;
                    $tabRoundClutch[$tabPlayerTeam[$player]][$player] = 1;
                    $tabStatsMatch[$player]['damage'] = 0;
                    $tabStatsMatch[$player]['hit'] = 0;
                    $tabStatsMatch[$player]['miss'] = 0;
                    $tabStatsMatch[$player]['kills'] = 0;
                    $tabStatsMatch[$player]['deaths'] = 0;
                }
            }
        }

        foreach ($tabRound as $lineRound) {

            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['NbOfKill'] += $lineRound['is_kill'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['Damage'] += $lineRound['damage'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['Hit'] += $lineRound['hit'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['Miss'] += $lineRound['miss'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['Clutch'] += $lineRound['clutch'];

            foreach ($tabPlayer as $player) {
                $tabPlayerStats[$player][$lineRound['round']]['NbRoundWinRedTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinRedTeam'];
                $tabPlayerStats[$player][$lineRound['round']]['NbRoundWinBlueTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinBlueTeam'];
                $tabPlayerStats[$player][$lineRound['round']]['Kill'] = $tabIndivualScore[$lineRound['round']][$player]['kill'];
                $tabPlayerStats[$player][$lineRound['round']]['Death'] = $tabIndivualScore[$lineRound['round']][$player]['death'];
                $tabStatsMatch[$player]['kills'] = $tabPlayerStats[$player][$lineRound['round']]['Kill'];
                $tabStatsMatch[$player]['deaths'] = $tabPlayerStats[$player][$lineRound['round']]['Death'];
            }
        }


        fclose($fh);
        //$strTabMatch = print_r2($tabMatch);
        //print_r($tabStatsMatch);
        $fileOutHtml = $folderOut . basename($fileIn, '.txt') . '.html';
        echo 'Generate HTML stats file : ' . $fileOutHtml . PHP_EOL;
        $strTabMatchPlayer = build_table($tabPlayerStats, $tabStatsMatch);
        file_put_contents($fileOutHtml, $strTabMatchPlayer);
    }
}

function print_r2($val)
{
    $str = '<pre>';
    $str .= print_r($val, true);
    $str .= '</pre>';
    return $str;
}

function build_table($arrayStatsRound, $arrayStatsMatch)
{
    $html .= <<<HTML
<style>
.tabStats {
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
    }

.tabStats td, .tabStats th {
border: 1px solid #ddd;
padding: 8px;
}

th {
cursor: pointer;
}

.tabStats tr:nth-child(even){background-color: #f2f2f2;}

.tabStats tr:hover {background-color: #ddd;}

.tabStats th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #4CAF50;
    color: white;
}

#statsPlayer {
  margin-bottom: 50px; /* or whatever */
}
</style>

<script>
function sortTable(tableId, n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;

  table = document.getElementById(tableId);
  switching = true;
  dir = "desc";
  while (switching) {
      switching = false;
      rows = table.getElementsByTagName("TR");
      for (i = 1; i < (rows.length - 1); i++) {
          shouldSwitch = false;
          x = rows[i].getElementsByTagName("TD")[n];
          y = rows[i + 1].getElementsByTagName("TD")[n];
          var xContent = (isNaN(x.innerHTML))
              ? (x.innerHTML.toLowerCase() === '-')
                    ? 0 : x.innerHTML.toLowerCase()
              : parseFloat(x.innerHTML);
          var yContent = (isNaN(y.innerHTML))
              ? (y.innerHTML.toLowerCase() === '-')
                    ? 0 : y.innerHTML.toLowerCase()
              : parseFloat(y.innerHTML);
          if (dir == "asc") {
              if (xContent > yContent) {
                  shouldSwitch= true;
                  break;
              }
          } else if (dir == "desc") {
              if (xContent < yContent) {
                  shouldSwitch= true;
                  break;
              }
          }
      }
      if (shouldSwitch) {
          rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
          switching = true;
          switchcount ++;
      } else {
          if (switchcount == 0 && dir == "desc") {
              dir = "asc";
              switching = true;
          }
      }
   }
}
</script>

<table id="statsPlayer" class="tabStats">
<tr>
    <th onclick="sortTable('statsPlayer',0)">Player</th>
    <th onclick="sortTable('statsPlayer',1)">Damage</th>
    <th onclick="sortTable('statsPlayer',2)">Accuracy (%)</th>
    <th onclick="sortTable('statsPlayer',3)">Kill</th>
    <th onclick="sortTable('statsPlayer',4)">Death</th>
    <th onclick="sortTable('statsPlayer',5)">K/D</th>
</tr>
HTML;

    foreach ($arrayStatsMatch as $player => $values) {
        $damage = 0;
        $hit = 0;
        $miss = 0;
        $kills = 0;
        $deaths = 0;
        $accuracy = 0;
        foreach ($values as $key => $value) {
            if ($key === 'damage') {
                $damage = $value;
            } elseif ($key === 'hit') {
                $hit = $value;
            } elseif ($key === 'miss') {
                $miss = $value;
            } elseif ($key === 'kills') {
                $kills = $value;
            } elseif ($key === 'deaths') {
                $deaths = $value;
            }
        }
        if (($miss === 0) && ($hit === 0)) {
            $accuracy = '-';
        } else if ($miss === 0) {
            $accuracy = 100;
        } else if ($hit === 0) {
            $accuracy = 0;
        } else {
            $accuracy = $hit / ($miss + $hit) * 100;
            $accuracy = intval($accuracy);
        }
        if ($kills === null) {
            $kd = '-';
        } else if ($deaths === null) {
            $kd = '-';
        } else if (($kills === 0) && ($deaths === 0)) {
            $kd = '-';
        } else if ($deaths === 0) {
            $kd = 1;
        } else if ($kills === 0) {
            $kd = 0;
        } else {
            $kd = $kills / $deaths;
        }
        $kd = round($kd, 2);
        $html .= "<tr><td>$player</td><td>$damage</td><td>$accuracy</td><td>$kills</td><td>$deaths</td><td>$kd</td></tr>" . PHP_EOL;
    }
    $html .=  "</table></div>";

    $html .= <<<HTML
<table id="statsPlayerRound" class="tabStats">
<tr>
    <th onclick="sortTable('statsPlayerRound',0)">Player</th>
    <th onclick="sortTable('statsPlayerRound',1)">N°Round</th>
    <th onclick="sortTable('statsPlayerRound',2)">Kill</th>
    <th onclick="sortTable('statsPlayerRound',3)">Clutch</th>
    <th onclick="sortTable('statsPlayerRound',4)">Damage</th>
    <th onclick="sortTable('statsPlayerRound',5)">Accuracy (%)</th>
    <th onclick="sortTable('statsPlayerRound',6)">Player Score (K-D)</th>
    <th onclick="sortTable('statsPlayerRound',7)">Team Score (RED-BLUE)</th>
</tr>
HTML;

    foreach ($arrayStatsRound as $player => $values) {
        foreach ($values as $round => $datasRound) {
            // If there are only kill and death then don't write a line.
            //if (count($datasRound) != 2) {
            $NbOfKill = 0;
            $damage = 0;
            $clutch = 0;
            $hit = 0;
            $miss = 0;
            $kill = 0;
            $death = 0;
            $accuracy = 0;
            foreach ($datasRound as $dataRound => $value) {
                if ($dataRound === 'NbRoundWinRedTeam') {
                    $NbRoundWinRedTeam = $value;
                } elseif ($dataRound === 'NbRoundWinBlueTeam') {
                    $NbRoundWinBlueTeam = $value;
                } elseif ($dataRound === 'NbOfKill') {
                    $NbOfKill = $value;
                } else if ($dataRound === 'Damage') {
                    $damage = $value;
                } else if ($dataRound === 'Hit') {
                    $hit = $value;
                } else if ($dataRound === 'Miss') {
                    $miss = $value;
                } else if ($dataRound === 'Kill') {
                    $kill = $value;
                } else if ($dataRound === 'Death') {
                    $death = $value;
                } else if ($dataRound === 'Clutch') {
                    $clutch = $value;
                }
            }
            if (($miss === 0) && ($hit === 0)) {
                $accuracy = '-';
            } else if ($miss === 0) {
                $accuracy = 100;
            } else if ($hit === 0) {
                $accuracy = 0;
            } else {
                $accuracy = $hit / ($miss + $hit) * 100;
                $accuracy = intval($accuracy);
            }
            $html .= "<tr><td>$player</td><td>$round</td><td>$NbOfKill</td><td>$clutch</td><td>$damage</td><td>$accuracy</td><td>$kill-$death</td><td>$NbRoundWinRedTeam - $NbRoundWinBlueTeam</td>";
            //}
        }
        $html .= '</tr>' . PHP_EOL;
    }
    $html .=  "</table></div>";
    return $html;
}
