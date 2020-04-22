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
            //echo $line;
            //var_dump($tabLine);
            //echo $tabLine[0];
            //break;
            //echo stripos($tabLine[0], 'round: ');
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
        $strTabMatch = print_r2($tabMatch);
        $strTabMatchScore = print_r2($tabMatchScore);
        $strTabMatchPlayer = print_r2($tabPlayer);
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
