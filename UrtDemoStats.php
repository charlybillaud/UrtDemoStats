<?php

$folderIn = 'demo_in/';
$folderOutDemoTxt = 'demo_out/demo_to_txt/';
$folderOut = 'demo_out/';

$files = scandir($folderIn);
foreach ($files as $fileIn) {
    if (($fileIn === '.') || ($fileIn === '..') || ($fileIn === '.gitkeep')) {
        continue;
    }
    // Just used to log correctly
    $fileInLog = $folderIn . $fileIn;
    // Concert to UNIX path for use gdemo.exe
    $fileIn = './' . $folderIn . $fileIn;
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
    $tabPlayer = array();
    $tabPlayerStats = array();
    $tabIndivualScore = array();
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
                }
            }

            if (($nbRound === 1) && ($lineRound === 1)) {
                foreach ($tabPlayer as $player) {
                    $tabIndivualScore[$nbRound][$player]['kill'] = 0;
                    $tabIndivualScore[$nbRound][$player]['death'] = 0;
                }
            }

            // New Round
            if (($tabLine[0] === 'round:') && ($tabLine[2] === 'status=start')) {
                $nbRound++;
                foreach ($tabPlayer as $player) {
                    $tabIndivualScore[$nbRound][$player]['kill'] = $tabIndivualScore[$nbRound - 1][$player]['kill'];
                    $tabIndivualScore[$nbRound][$player]['death'] = $tabIndivualScore[$nbRound - 1][$player]['death'];
                }
            }
            $lineRound++;

            if (($tabLine[0] === 'round:') && ($tabLine[2] === 'status=end')) {
                if (stripos($tabLine[3], 'Red') != false) {
                    if ($nbRound === 1) {
                        $tabMatchScore[$nbRound]['NbRoundWinRedTeam'] = 1;
                        $tabMatchScore[$nbRound]['NbRoundWinBlueTeam'] = 0;
                    } else {
                        $tabMatchScore[$nbRound]['NbRoundWinRedTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinRedTeam'] + 1;
                        $tabMatchScore[$nbRound]['NbRoundWinBlueTeam'] = $tabMatchScore[$nbRound - 1]['NbRoundWinBlueTeam'];
                    }
                } elseif (stripos($tabLine[3], 'Blue') != false) {
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
                $tabRound[$lineRound]['shooter'] = $tabLine[7];
                $tabRound[$lineRound]['is_kill'] = 1;
                $tabRound[$lineRound]['round'] = $nbRound;
                $tabRound[$lineRound]['damage'] = 0;
                $tabRound[$lineRound]['hit'] = 0;
                $tabRound[$lineRound]['miss'] = 0;
                $tabIndivualScore[$nbRound][$tabLine[7]]['kill'] += 1;
                $tabIndivualScore[$nbRound][$tabLine[2]]['death'] += 1;
                $tabIndivualScore[$nbRound][$tabLine[2]]['kill'] += 0;
                $tabIndivualScore[$nbRound][$tabLine[7]]['death'] += 0;
            }

            if ($tabLine[0] === 'hit:') {
                //$tabRound[$lineRound]['victim'] = $tabLine[2];
                $tabRound[$lineRound]['shooter'] = $tabLine[2];
                $tabRound[$lineRound]['is_kill'] = 0;
                $tabRound[$lineRound]['round'] = $nbRound;
                $tabRound[$lineRound]['damage'] = $tabLine[7];
                $tabRound[$lineRound]['hit'] = 1;
                $tabRound[$lineRound]['miss'] = 0;
            }
            if ($tabLine[0] === 'miss:') {
                //$tabRound[$lineRound]['victim'] = $tabLine[2];
                $tabRound[$lineRound]['shooter'] = $tabLine[2];
                $tabRound[$lineRound]['is_kill'] = 0;
                $tabRound[$lineRound]['round'] = $nbRound;
                $tabRound[$lineRound]['damage'] = 0;
                $tabRound[$lineRound]['hit'] = 0;
                $tabRound[$lineRound]['miss'] = 1;
            }
        }


        $round = 1;

        foreach ($tabRound as $lineRound) {
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['NbRoundWinRedTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinRedTeam'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['NbRoundWinBlueTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinBlueTeam'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['NbOfKill'] += $lineRound['is_kill'];


            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['Damage'] += $lineRound['damage'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['Hit'] += $lineRound['hit'];
            $tabPlayerStats[$lineRound['shooter']][$lineRound['round']]['Miss'] += $lineRound['miss'];

            foreach ($tabPlayer as $player) {
                $tabPlayerStats[$player][$lineRound['round']]['Kill'] = $tabIndivualScore[$lineRound['round']][$player]['kill'];
                $tabPlayerStats[$player][$lineRound['round']]['Death'] = $tabIndivualScore[$lineRound['round']][$player]['death'];
            }
        }


        fclose($fh);
        //$strTabMatch = print_r2($tabMatch);
        //$strTabMatchScore = print_r2($tabMatchScore);
        //echo print_r2($tabPlayerStats);
        $strTabMatchPlayer = build_table($tabPlayerStats);
        $fileOutHtml = $folderOut . basename($fileIn, '.txt') . '.html';
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

function build_table($array)
{
    $html = <<<HTML
<style>
#statsPlayer {
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
    }

    #statsPlayer td, #statsPlayer th {
    border: 1px solid #ddd;
    padding: 8px;
    }

    th {
    cursor: pointer;
    }

    #statsPlayer tr:nth-child(even){background-color: #f2f2f2;}

    #statsPlayer tr:hover {background-color: #ddd;}

    #statsPlayer th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #4CAF50;
    color: white;
    }
</style>

<script>
function sortTable(tableClass, n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;

  table = document.getElementsByClassName(tableClass)[0];
  switching = true;
  dir = "asc";
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
          if (switchcount == 0 && dir == "asc") {
              dir = "desc";
              switching = true;
          }
      }
   }
}
</script>
<table id="statsPlayer" class="tableStatsPlayer">
<tr>
    <th onclick="sortTable('tableStatsPlayer',0)">Player</th>
    <th onclick="sortTable('tableStatsPlayer',1)">N°Round</th>
    <th onclick="sortTable('tableStatsPlayer',2)">Kill</th>
    <th onclick="sortTable('tableStatsPlayer',3)">Damage</th>
    <th onclick="sortTable('tableStatsPlayer',4)">Accuracy (%)</th>
    <th onclick="sortTable('tableStatsPlayer',5)">Player Score (K-D)</th>
    <th onclick="sortTable('tableStatsPlayer',6)">Team Score (RED-BLUE)</th>
</tr>
HTML;

    foreach ($array as $player => $values) {
        foreach ($values as $round => $datasRound) {
            // If there are only kill and death then don't write a line.
            //if (count($datasRound) != 2) {
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
            $html .= "<tr><td>$player</td><td>$round</td><td>$NbOfKill</td><td>$damage</td><td>$accuracy</td><td>$kill-$death</td><td>$NbRoundWinRedTeam - $NbRoundWinBlueTeam</td>";
            //}
        }
        $html .= '</tr>' . PHP_EOL;
    }
    $html .=  "</table></div>";
    return $html;
}
