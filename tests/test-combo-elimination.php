<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';

// Manually trace constraint propagation
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

$combTable = new CombinationTable();
$board = Board::fromJson($puzzle, $combTable);

echo "=== COMBINATION ELIMINATION TEST ===\n\n";

$runs = $board->getRuns();
foreach ($runs as $i => $run) {
    echo "Run " . ($i + 1) . " before combination elimination:\n";
    foreach ($run->getCombinations() as $combo) {
        echo "  [" . implode(", ", $combo) . "]\n";
    }

    // Get cell candidates
    $cellCandidates = [];
    foreach ($run->getCells() as $pos => $coord) {
        $r = $coord['row'];
        $c = $coord['col'];
        $cellCandidates[$pos] = $board->getCandidates($r, $c);
    }

    echo "  Cell candidates:\n";
    foreach ($cellCandidates as $pos => $cands) {
        echo "    Pos {$pos}: [" . implode(", ", $cands) . "]\n";
    }

    // Apply combination elimination
    $run->pruneByAllCellCandidates($cellCandidates);

    echo "  After pruning:\n";
    if (empty($run->getCombinations())) {
        echo "    ✗ NO COMBOS LEFT - UNSOLVABLE!\n";
        exit(1);
    }
    foreach ($run->getCombinations() as $combo) {
        echo "    [" . implode(", ", $combo) . "]\n";
    }

    // Get supported digits
    echo "  Supported digits per cell:\n";
    foreach ($run->getCells() as $pos => $coord) {
        $r = $coord['row'];
        $c = $coord['col'];
        $supported = $run->getSupportedDigitsForCell($cellCandidates[$pos]);
        echo "    Pos {$pos} ({$r},{$c}): [" . implode(", ", $supported) . "]\n";
    }

    echo "\n";
}

echo "✓ All runs have valid combinations.\n";
