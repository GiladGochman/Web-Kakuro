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

echo "Initial board candidates:\n";
for ($r = 0; $r < 3; $r++) {
    for ($c = 0; $c < 3; $c++) {
        if ($board->getCellType($r, $c) === 'white') {
            $cands = $board->getCandidates($r, $c);
            echo "({$r},{$c}): [" . implode(',', $cands) . "]\n";
        }
    }
}

// Try a manual solve: assign (1,1)=1 and see if propagate() still works
echo "\n\nManually testing: assign (1,1)=1\n";
$board->assign(1, 1, 1);
echo "After assigning (1,1)=1:\n";
echo "Candidates for (1,1): [" . implode(',', $board->getCandidates(1, 1)) . "]\n";

// Check what needs to be removed from peers
echo "\nRemoving 1 from peers of (1,1):\n";
$hRun = $board->getHorizontalRun(1, 1);
$vRun = $board->getVerticalRun(1, 1);
echo "Horizontal run cells: ";
foreach ($hRun->getCells() as $cell) {
    echo "({$cell['row']},{$cell['col']}) ";
}
echo "\n";
echo "Vertical run cells: ";
foreach ($vRun->getCells() as $cell) {
    echo "({$cell['row']},{$cell['col']}) ";
}
echo "\n";

// Manually remove from peers
foreach ($hRun->getCells() as $cell) {
    if ($cell['row'] === 1 && $cell['col'] === 1) continue;
    $board->removeCandidate($cell['row'], $cell['col'], 1);
}
foreach ($vRun->getCells() as $cell) {
    if ($cell['row'] === 1 && $cell['col'] === 1) continue;
    $board->removeCandidate($cell['row'], $cell['col'], 1);
}

echo "\nAfter removing 1 from peers:\n";
for ($r = 0; $r < 3; $r++) {
    for ($c = 0; $c < 3; $c++) {
        if ($board->getCellType($r, $c) === 'white') {
            $cands = $board->getCandidates($r, $c);
            if (empty($cands)) {
                echo "({$r},{$c}): EMPTY ✗\n";
            } else {
                echo "({$r},{$c}): [" . implode(',', $cands) . "]\n";
            }
        }
    }
}

// Now try propagate
echo "\n\nCalling propagate() after (1,1)=1 assignment...\n";
$solver = new Solver($board);
$reflection = new ReflectionClass('Solver');
$propagate = $reflection->getMethod('propagate');

$result = $propagate->invoke($solver, $board);
echo ($result ? "✓ Propagate succeeded\n" : "✗ Propagate failed\n");

if ($result) {
    echo "\nFinal candidates after propagate:\n";
    for ($r = 0; $r < 3; $r++) {
        for ($c = 0; $c < 3; $c++) {
            if ($board->getCellType($r, $c) === 'white') {
                $assigned = $board->getAssigned($r, $c);
                if ($assigned !== null) {
                    echo "({$r},{$c}) = " . $assigned . "\n";
                } else {
                    $cands = $board->getCandidates($r, $c);
                    echo "({$r},{$c}): [" . implode(',', $cands) . "]\n";
                }
            }
        }
    }
}
