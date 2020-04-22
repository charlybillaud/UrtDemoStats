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
    $lineRound = 0;
    if (($fileIn === '.') || ($fileIn === '..') || ($fileIn === '.gitkeep')) {
        continue;
    }
    $fileIn = $folderOutDemoTxt . $fileIn;
    if ($fh = fopen($fileIn, 'r')) {
        $nbRound = 1;
        while (!feof($fh)) {
            $line = fgets($fh);
            // Remplace les charactères illisibles par un espace
            $line = preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $line);
            $tabLine = array();
            $tabLine = explode(' ', $line);

            // New Round
            if (($tabLine[0] === 'round:') && ($tabLine[2] === 'status=start')) {
                $nbRound++;
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
                //$tabRound[$lineRound]['victim'] = $tabLine[2];
                $tabRound[$lineRound]['shooter'] = $tabLine[7];
                //$tabRound[$lineRound]['kill'] += $tabRound[$lineRound]['kill'];
                //$tabRound[$lineRound]['weapon'] = $tabLine[10];
                $tabRound[$lineRound]['is_kill'] = 1;
                $tabRound[$lineRound]['round'] = $nbRound;
                $tabRound[$lineRound]['damage'] = 0;
            }
            if ($tabLine[0] === 'hit:') {
                //$tabRound[$lineRound]['victim'] = $tabLine[2];
                $tabRound[$lineRound]['shooter'] = $tabLine[2];
                $tabRound[$lineRound]['is_kill'] = 0;
                $tabRound[$lineRound]['round'] = $nbRound;
                $tabRound[$lineRound]['damage'] = $tabLine[7];
            }
        }

        $tabPlayer = array();
        $round = 1;

        foreach ($tabRound as $lineRound) {
            $tabPlayer[$lineRound['shooter']][$lineRound['round']]['NbRoundWinRedTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinRedTeam'];
            $tabPlayer[$lineRound['shooter']][$lineRound['round']]['NbRoundWinBlueTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinBlueTeam'];
            $tabPlayer[$lineRound['shooter']][$lineRound['round']]['NbOfKill'] += $lineRound['is_kill'];
            $tabPlayer[$lineRound['shooter']][$lineRound['round']]['Damage'] += $lineRound['damage'];
        }


        fclose($fh);
        //$strTabMatch = print_r2($tabMatch);
        //$strTabMatchScore = print_r2($tabMatchScore);
        //echo print_r2($tabPlayer);
        $strTabMatchPlayer = build_table($tabPlayer);
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
    <th onclick="sortTable('tableStatsPlayer',2)">Kills</th>
    <th onclick="sortTable('tableStatsPlayer',3)">Damage</th>
    <th onclick="sortTable('tableStatsPlayer',4)">Score (RED-BLUE)</th>
</tr>
HTML;

    foreach ($array as $player => $values) {
        foreach ($values as $round => $datasRound) {
            foreach ($datasRound as $dataRound => $value) {
                if ($dataRound === 'NbRoundWinRedTeam') {
                    $NbRoundWinRedTeam = $value;
                } elseif ($dataRound === 'NbRoundWinBlueTeam') {
                    $NbRoundWinBlueTeam = $value;
                } elseif ($dataRound === 'NbOfKill') {
                    $kills = $value;
                } else if ($dataRound === 'Damage') {
                    $damage = $value;
                }
            }
            $html .= "<tr><td>$player</td><td>$round</td><td>$kills</td><td>$damage</td><td>$NbRoundWinRedTeam - $NbRoundWinBlueTeam</td>";
        }
        $html .= '</tr>' . PHP_EOL;
    }
    $html .=  "</table></div>";
    return $html;
}
