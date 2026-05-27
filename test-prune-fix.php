<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Run.php';

// Quick test of the fixed pruning
$run = new Run(8, [['row' => 2, 'col' => 1], ['row' => 2, 'col' => 2]],
               [[1, 7], [2, 6], [3, 5]]);

echo "Run 2 combos before pruning:\n";
foreach ($run->getCombinations() as $combo) {
    echo "  [" . implode(", ", $combo) . "]\n";
}

$cellCandidates = [
    [1, 2, 5],        // Cell (2,1)
    [1, 2, 6, 3, 5]   // Cell (2,2)
];

echo "\nCell candidates:\n";
echo "  Cell 0: [" . implode(", ", $cellCandidates[0]) . "]\n";
echo "  Cell 1: [" . implode(", ", $cellCandidates[1]) . "]\n";

echo "\nPruning with fixed logic...\n";
$run->pruneByAllCellCandidates($cellCandidates);

echo "\nCombo [3,5] check:\n";
echo "  Can 3 be in cell 0? " . (in_array(3, $cellCandidates[0]) ? "YES" : "NO") . "\n";
echo "  Can 5 be in cell 1? " . (in_array(5, $cellCandidates[1]) ? "YES" : "NO") . "\n";
echo "  Can 5 be in cell 0? " . (in_array(5, $cellCandidates[0]) ? "YES" : "NO") . "\n";
echo "  Can 3 be in cell 1? " . (in_array(3, $cellCandidates[1]) ? "YES" : "NO") . "\n";
echo "  => Assignment (5 → cell 0, 3 → cell 1) VALID\n";

echo "\nRun 2 combos after pruning:\n";
foreach ($run->getCombinations() as $combo) {
    echo "  [" . implode(", ", $combo) . "]\n";
}

$count = count($run->getCombinations());
echo "\nTotal combos remaining: {$count}\n";
echo "(Expected: 2 — [3,5] valid, [1,7] invalid because 7 missing)\n";
$hasThreeFive = false;
foreach ($run->getCombinations() as $combo) {
    if ($combo === [3, 5]) {
        $hasThreeFive = true;
        break;
    }
}
if ($hasThreeFive) {
    echo "✓ Fixed! Combo [3,5] is now valid.\n";
} else {
    echo "✗ Fix didn't work - combo [3,5] still pruned.\n";
}
