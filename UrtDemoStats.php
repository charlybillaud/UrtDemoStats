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
                $tabRound[$lineRound]['victim'] = $tabLine[2];
                $tabRound[$lineRound]['killer'] = $tabLine[7];
                $tabRound[$lineRound]['weapon'] = $tabLine[10];
                $tabRound[$lineRound]['round'] = $nbRound;
            }
        }

        $tabPlayer = array();
        $round = 1;

        foreach ($tabRound as $lineRound) {
            $tabPlayer[$lineRound['killer']][$lineRound['round']]['NbRoundWinRedTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinRedTeam'];
            $tabPlayer[$lineRound['killer']][$lineRound['round']]['NbRoundWinBlueTeam'] = $tabMatchScore[$lineRound['round']]['NbRoundWinBlueTeam'];
            $tabPlayer[$lineRound['killer']][$lineRound['round']]['NbOfKill'] = $tabPlayer[$lineRound['killer']][$lineRound['round']]['NbOfKill'] + 1;
            //$tabByPlayer[]
        }

        fclose($fh);
        //$strTabMatch = print_r2($tabMatch);
        //$strTabMatchScore = print_r2($tabMatchScore);
        //$strTabMatchPlayer = print_r2($tabPlayer);
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
function sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("statsPlayer");
  switching = true;
  // Set the sorting direction to ascending:
  dir = "asc";
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first, which contains table headers): */
    for (i = 1; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc or desc: */
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch
      and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      // Each time a switch is done, increase this count by 1:
      switchcount ++;
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
</script>
<table id="statsPlayer">
<tr>
    <th onclick="sortTable(0)">Player</th>
    <th onclick="sortTable(1)">N°Round</th>
    <th onclick="sortTable(2)">Kills</th>
    <th onclick="sortTable(3)">Score (RED-BLUE)</th>
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
                }
            }
            $html .= "<tr><td>$player</td><td>$round</td><td>$kills</td><td>$NbRoundWinRedTeam - $NbRoundWinBlueTeam</td>";
        }
        $html .= '</tr>' . PHP_EOL;
    }
    $html .=  "</table></div>";
    return $html;
}
