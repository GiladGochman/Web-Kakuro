<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Run.php';

// Manually test combination elimination after assignment
$run1 = new Run(5, [['row' => 1, 'col' => 1], ['row' => 1, 'col' => 2]],
                [[1, 4], [2, 3]]);

echo "Run 1 (horizontal, sum=5) after (1,1)=1:\n";
echo "Combos before elimination:\n";
foreach ($run1->getCombinations() as $combo) {
    echo "  [" . implode(", ", $combo) . "]\n";
}

// After assigning (1,1)=1, the cell candidates are:
// Cell 0 (1,1): [1] (assigned)
// Cell 1 (1,2): [4, 2, 3]
$cellCandidates = [
    [1],           // (1,1) assigned to 1
    [4, 2, 3]      // (1,2) can be 4, 2, or 3
];

echo "\nCell candidates:\n";
echo "  Cell 0: [" . implode(", ", $cellCandidates[0]) . "]\n";
echo "  Cell 1: [" . implode(", ", $cellCandidates[1]) . "]\n";

echo "\nChecking combo validity:\n";
$reflection = new ReflectionClass('Run');
$canAssignCombo = $reflection->getMethod('canAssignCombo');

foreach ($run1->getCombinations() as $combo) {
    $isValid = $canAssignCombo->invoke($run1, $combo, $cellCandidates);
    echo "  [" . implode(", ", $combo) . "]: " . ($isValid ? "VALID" : "INVALID") . "\n";
    // Manually check
    if ($combo === [1, 4]) {
        echo "    - Can assign 1 to cell 0? Cell 0 has [1] - YES\n";
        echo "    - Can assign 4 to cell 1? Cell 1 has [4,2,3] - YES\n";
        echo "    - Or: assign 4 to cell 0? Cell 0 has [1] - NO\n";
    } elseif ($combo === [2, 3]) {
        echo "    - Can assign 2 to cell 0? Cell 0 has [1] - NO\n";
        echo "    - Can assign 2 to cell 1? Cell 1 has [4,2,3] - YES, then assign 3? Cell 1? - NO (one cell left)\n";
        echo "    - Or: assign 3 to cell 0? Cell 0 has [1] - NO\n";
    }
}

echo "\n\nApplying pruning...\n";
$run1->pruneByAllCellCandidates($cellCandidates);

echo "Combos after elimination:\n";
if (empty($run1->getCombinations())) {
    echo "  NONE - RUN HAS NO VALID COMBOS!\n";
} else {
    foreach ($run1->getCombinations() as $combo) {
        echo "  [" . implode(", ", $combo) . "]\n";
    }
}
