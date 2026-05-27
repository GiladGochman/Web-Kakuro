<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';

/**
 * Ultra-detailed diagnostic: trace constraint propagation step-by-step
 */

// Debug puzzle 2 data
$puzzle = [
    'rows' => 9,
    'cols' => 9,
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

echo "=== DETAILED CONSTRAINT PROPAGATION TRACE ===\n\n";

$combTable = new CombinationTable();
$board = Board::fromJson($puzzle, $combTable);

echo "Initial board state:\n";
echo "White cells and their candidates:\n";
for ($r = 0; $r < 3; $r++) {
    for ($c = 0; $c < 3; $c++) {
        if ($board->getCellType($r, $c) === 'white') {
            $candidates = $board->getCandidates($r, $c);
            $assigned = $board->getAssigned($r, $c);
            $value = $assigned !== null ? $assigned : implode(',', $candidates);
            echo "  ({$r},{$c}): [$value]\n";
        }
    }
}
echo "\n";

// Manually check combination elimination for Run 2
echo "ANALYZING RUN 2 (Sum=8, cells (2,1) and (2,2)):\n";
$runs = $board->getRuns();
$run2 = $runs[1]; // Run 2

echo "Current combinations in Run 2:\n";
foreach ($run2->getCombinations() as $combo) {
    echo "  [" . implode(', ', $combo) . "]\n";
}

echo "\nCell candidates:\n";
$cellCandidates = [];
foreach ($run2->getCells() as $pos => $coord) {
    $r = $coord['row'];
    $c = $coord['col'];
    $candidates = $board->getCandidates($r, $c);
    $cellCandidates[$pos] = $candidates;
    echo "  Position {$pos} ({$r},{$c}): [" . implode(', ', $candidates) . "]\n";
}

echo "\nChecking which combos are valid given cell candidates:\n";
$validCombos = 0;
foreach ($run2->getCombinations() as $combo) {
    $isValid = true;
    foreach ($cellCandidates as $pos => $candidates) {
        if (!in_array($combo[$pos], $candidates, true)) {
            $isValid = false;
            echo "  " . json_encode($combo) . " INVALID: position {$pos} needs " . $combo[$pos] . " but cell only has [" . implode(',', $candidates) . "]\n";
            break;
        }
    }
    if ($isValid) {
        echo "  " . json_encode($combo) . " VALID\n";
        $validCombos++;
    }
}

echo "\nResult: {$validCombos} valid combos remaining\n";

if ($validCombos === 0) {
    echo "\n✗ RUN 2 HAS NO VALID COMBINATIONS - THIS CAUSES THE CONTRADICTION!\n";
}

echo "\n=== Analysis of why Run 2 fails ===\n";
echo "Cell (2,1) has candidates: [" . implode(', ', $cellCandidates[0]) . "]\n";
echo "Cell (2,2) has candidates: [" . implode(', ', $cellCandidates[1]) . "]\n";
echo "\nFor sum=8 with 2 cells, valid mathematical combinations are:\n";
echo "  (1,7), (2,6), (3,5)\n";
echo "\nBut we need BOTH values in the cell's candidates:\n";
echo "  [1,7]: 1 in [" . implode(',', $cellCandidates[0]) . "]? " . (in_array(1, $cellCandidates[0]) ? 'YES' : 'NO') .
        ", 7 in [" . implode(',', $cellCandidates[1]) . "]? " . (in_array(7, $cellCandidates[1]) ? 'YES' : 'NO') . "\n";
echo "  [2,6]: 2 in [" . implode(',', $cellCandidates[0]) . "]? " . (in_array(2, $cellCandidates[0]) ? 'YES' : 'NO') .
        ", 6 in [" . implode(',', $cellCandidates[1]) . "]? " . (in_array(6, $cellCandidates[1]) ? 'YES' : 'NO') . "\n";
echo "  [3,5]: 3 in [" . implode(',', $cellCandidates[0]) . "]? " . (in_array(3, $cellCandidates[0]) ? 'YES' : 'NO') .
        ", 5 in [" . implode(',', $cellCandidates[1]) . "]? " . (in_array(5, $cellCandidates[1]) ? 'YES' : 'NO') . "\n";
