<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Solver.php';

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

$solver = new Solver($board);

echo "Testing propagate()...\n";

// Use Reflection to call solveBoard directly to see what propagate returns
$reflection = new ReflectionClass('Solver');
$propagate = $reflection->getMethod('propagate');

echo "\nCalling propagate() on fresh board...\n";
$result = $propagate->invoke($solver, $board);

if ($result === false) {
    echo "✗ Propagate returned FALSE (unsolvable)\n";
} else {
    echo "✓ Propagate succeeded!\n";
}

echo "\n\nNow testing full solve()...\n";

$combTable2 = new CombinationTable();
$board2 = Board::fromJson($puzzle, $combTable2);
$solver2 = new Solver($board2);

$solveResult = $solver2->solve();
if ($solveResult === null) {
    echo "✗ solve() returned NULL (unsolvable)\n";
} else {
    echo "✓ solve() found a solution!\n";
    echo "\nSolution:\n";
    for ($r = 0; $r < 3; $r++) {
        for ($c = 0; $c < 3; $c++) {
            if ($board2->getCellType($r, $c) === 'white') {
                $val = $solveResult->getAssigned($r, $c);
                echo "({$r},{$c}) = " . $val . "\n";
            }
        }
    }
}
