<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Solver.php';

// Create puzzle and do initial setup
$puzzle = [
    'rows' => 9, 'cols' => 9,
    'cells' => [
        ['row' => 0, 'col' => 0, 'type' => 'black'],
        ['row' => 0, 'col' => 1, 'type' => 'clue', 'clueRight' => null, 'clueDown' => 6],
        ['row' => 0, 'col' => 2, 'type' => 'clue', 'clueRight' => null, 'clueDown' => 7],
        ['row' => 0, 'col' => 3, 'type' => 'black'],
        ['row' => 0, 'col' => 4, 'type' => 'black'],
        ['row' => 0, 'col' => 5, 'type' => 'black'],
        ['row' => 0, 'col' => 6, 'type' => 'black'],
        ['row' => 0, 'col' => 7, 'type' => 'black'],
        ['row' => 0, 'col' => 8, 'type' => 'black'],
        ['row' => 1, 'col' => 0, 'type' => 'clue', 'clueRight' => 5, 'clueDown' => null],
        ['row' => 1, 'col' => 1, 'type' => 'white'],
        ['row' => 1, 'col' => 2, 'type' => 'white'],
        ['row' => 1, 'col' => 3, 'type' => 'black'],
        ['row' => 1, 'col' => 4, 'type' => 'black'],
        ['row' => 1, 'col' => 5, 'type' => 'black'],
        ['row' => 1, 'col' => 6, 'type' => 'black'],
        ['row' => 1, 'col' => 7, 'type' => 'black'],
        ['row' => 1, 'col' => 8, 'type' => 'black'],
        ['row' => 2, 'col' => 0, 'type' => 'clue', 'clueRight' => 8, 'clueDown' => null],
        ['row' => 2, 'col' => 1, 'type' => 'white'],
        ['row' => 2, 'col' => 2, 'type' => 'white'],
        ['row' => 2, 'col' => 3, 'type' => 'black'],
        ['row' => 2, 'col' => 4, 'type' => 'black'],
        ['row' => 2, 'col' => 5, 'type' => 'black'],
        ['row' => 2, 'col' => 6, 'type' => 'black'],
        ['row' => 2, 'col' => 7, 'type' => 'black'],
        ['row' => 2, 'col' => 8, 'type' => 'black'],
        ...array_merge(...array_map(fn($r) => array_map(fn($c) => ['row' => $r, 'col' => $c, 'type' => 'black'], range(0, 8)), range(3, 8)))
    ]
];

$combTable = new CombinationTable();
$board = Board::fromJson($puzzle, $combTable);

// Check initial board
echo "Initial board:\n";
for ($r = 0; $r < 3; $r++) {
    for ($c = 0; $c < 3; $c++) {
        if ($board->getCellType($r, $c) === 'white') {
            $cands = $board->getCandidates($r, $c);
            if (empty($cands)) {
                echo "({$r},{$c}): EMPTY\n";
            } else {
                echo "({$r},{$c}): [" . implode(',', $cands) . "]\n";
            }
        }
    }
}

// Make the assignment manually
echo "\n\nAssigning (1,1)=1 and removing from peers...\n";
$board->assign(1, 1, 1);

// Remove 1 from peers
$hRun = $board->getHorizontalRun(1, 1);
$vRun = $board->getVerticalRun(1, 1);
foreach ($hRun->getCells() as $cell) {
    if ($cell['row'] === 1 && $cell['col'] === 1) continue;
    $board->removeCandidate($cell['row'], $cell['col'], 1);
}
foreach ($vRun->getCells() as $cell) {
    if ($cell['row'] === 1 && $cell['col'] === 1) continue;
    $board->removeCandidate($cell['row'], $cell['col'], 1);
}

// Now check board
echo "\nBoard after assignment:\n";
for ($r = 0; $r < 3; $r++) {
    for ($c = 0; $c < 3; $c++) {
        if ($board->getCellType($r, $c) === 'white') {
            $assigned = $board->getAssigned($r, $c);
            if ($assigned !== null) {
                echo "({$r},{$c}) = " . $assigned . "\n";
            } else {
                $cands = $board->getCandidates($r, $c);
                if (empty($cands)) {
                    echo "({$r},{$c}): EMPTY ✗\n";
                } else {
                    echo "({$r},{$c}): [" . implode(',', $cands) . "]\n";
                }
            }
        }
    }
}

// Now trace combination elimination manually
echo "\n\n=== COMBINATION ELIMINATION TRACE ===\n";
$runs = $board->getRuns();
foreach ($runs as $i => $run) {
    echo "\nRun " . ($i + 1) . " (sum=" . $run->sum . "):\n";
    echo "  Before: ";
    foreach ($run->getCombinations() as $combo) {
        echo "[" . implode(',', $combo) . "] ";
    }
    echo "\n";

    // Collect cell candidates
    $cellCandidates = [];
    foreach ($run->getCells() as $pos => $coord) {
        $r = $coord['row'];
        $c = $coord['col'];
        $assigned = $board->getAssigned($r, $c);
        $cellCandidates[$pos] = $assigned !== null ? [$assigned] : $board->getCandidates($r, $c);
    }

    echo "  Cell candidates: ";
    foreach ($cellCandidates as $pos => $cands) {
        echo "pos$pos=[" . implode(',', $cands) . "] ";
    }
    echo "\n";

    // Prune
    $run->pruneByAllCellCandidates($cellCandidates);

    echo "  After: ";
    if (empty($run->getCombinations())) {
        echo "NONE ✗\n";
    } else {
        foreach ($run->getCombinations() as $combo) {
            echo "[" . implode(',', $combo) . "] ";
        }
        echo "\n";
    }
}
