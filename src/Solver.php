<?php

require_once __DIR__ . '/Board.php';

/**
 * Solves a Kakuro board using constraint propagation followed by backtracking.
 *
 * The solver applies four constraint techniques in a loop before ever guessing:
 *
 *   1. Naked singles      — a cell with one candidate must be that digit.
 *   2. Hidden singles     — a digit possible in only one cell of a run must go there.
 *   3. Naked pairs/triples — N cells sharing exactly N candidates lock those digits.
 *   4. Combination elim.  — prune invalid combos from runs, then shrink cell candidates.
 *
 * Only when all four techniques stop making progress does the solver fall back to
 * backtracking. It picks the most-constrained cell (minimum remaining candidates)
 * and tries each digit in turn, recursing on a snapshot copy of the board.
 */
class Solver
{
    private Board $board;
    /** @var callable|null Callback fired when a cell is assigned: fn(int $row, int $col, int $digit) */
    private $onAssign = null;

    public function __construct(Board $board)
    {
        $this->board = $board;
    }

    /**
     * Sets a callback to be invoked each time a cell is assigned during solving.
     * Callback signature: fn(int $row, int $col, int $digit): void
     */
    public function setOnAssignCallback(callable $cb): void
    {
        $this->onAssign = $cb;
    }

    /**
     * Attempts to solve the board. Returns the completed board on success,
     * or null if the puzzle has no solution.
     */
    public function solve(): ?Board
    {
        return $this->solveBoard($this->board);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Assigns a cell and fires the callback if set and if on main board. */
    private function assignCell(Board $board, int $row, int $col, int $digit): void
    {
        $board->assign($row, $col, $digit);
        if ($this->onAssign && $board === $this->board) {
            ($this->onAssign)($row, $col, $digit);
        }
    }

    // -------------------------------------------------------------------------
    // Top-level recursive solver
    // -------------------------------------------------------------------------

    private function solveBoard(Board $board): ?Board
    {
        $this->log("=== solveBoard() called ===");

        if (!$this->propagate($board)) {
            $this->log("CONTRADICTION: propagation failed");
            return null; // Contradiction found during propagation.
        }

        if ($board->isComplete()) {
            $this->log("✓ SOLVED! Board is complete");
            return $board;
        }

        // Propagation stalled — try backtracking on the most-constrained cell.
        [$row, $col] = $this->pickMRVCell($board);
        $candidates  = $board->getCandidates($row, $col);

        $this->log("BACKTRACKING: trying cell ($row,$col) with candidates: " . implode(',', $candidates));

        foreach ($candidates as $digit) {
            $this->log("  └─ GUESS: ($row,$col) = $digit");
            $snapshot = $board->snapshot();
            $this->assignCell($snapshot, $row, $col, $digit);

            if (!$this->removeDigitFromPeers($snapshot, $row, $col, $digit)) {
                $this->log("    └─ GUESS REJECTED: immediate peer conflict");
                continue; // Immediate contradiction — try next digit.
            }

            $this->log("    └─ GUESS ACCEPTED: recursing...");
            $result = $this->solveBoard($snapshot);
            if ($result !== null) {
                return $result;
            }
            $this->log("    └─ GUESS FAILED: recursion returned null");
        }

        $this->log("DEAD END: all guesses failed");
        return null; // All guesses led to contradictions.
    }

    // -------------------------------------------------------------------------
    // Constraint propagation
    // -------------------------------------------------------------------------

    /**
     * Runs all constraint techniques in a loop until no further progress is made.
     * Returns false if a contradiction is detected (empty candidate set).
     */
    private function propagate(Board $board): bool
    {
        $this->log("  propagate() START");
        $iteration = 0;

        do {
            $changed = false;
            $iteration++;
            $this->log("  ┌─ PROPAGATION PASS $iteration");

            if (!$this->applyCombinationElimination($board, $changed)) {
                $this->log("  │  └─ FAILED: Combination elimination");
                return false;
            }
            if (!$this->applyNakedSingles($board, $changed)) {
                $this->log("  │  └─ FAILED: Naked singles");
                return false;
            }
            if (!$this->applyHiddenSingles($board, $changed)) {
                $this->log("  │  └─ FAILED: Hidden singles");
                return false;
            }
            if (!$this->applyNakedPairsTriples($board, $changed)) {
                $this->log("  │  └─ FAILED: Naked pairs/triples");
                return false;
            }

            $this->log("  └─ PASS $iteration DONE, changed=$changed");

        } while ($changed);

        $this->log("  propagate() END (fixed point reached)");
        return true;
    }

    // -------------------------------------------------------------------------
    // Technique 1: Naked singles
    // -------------------------------------------------------------------------

    /**
     * If a cell has exactly one candidate, that must be its value. Assigns the
     * digit and removes it from all peers in the same runs.
     */
    private function applyNakedSingles(Board $board, bool &$changed): bool
    {
        $count = 0;
        foreach ($board->getUnassignedCells() as [$row, $col]) {
            if ($board->candidateCount($row, $col) !== 1) {
                continue;
            }

            $digit = $board->getCandidates($row, $col)[0];
            $count++;
            $this->log("    │  NAKED SINGLE: ($row,$col) must be $digit");
            $this->assignCell($board, $row, $col, $digit);
            $changed = true;

            if (!$this->removeDigitFromPeers($board, $row, $col, $digit)) {
                return false;
            }
        }
        if ($count > 0) {
            $this->log("    │  Applied $count naked singles");
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Technique 2: Hidden singles
    // -------------------------------------------------------------------------

    /**
     * For each run, if a digit can only go in one cell, it must go there.
     */
    private function applyHiddenSingles(Board $board, bool &$changed): bool
    {
        $count = 0;
        foreach ($board->getRuns() as $run) {
            foreach (range(1, 9) as $digit) {
                $digitAlreadyAssignedInRun = false;
                foreach ($run->getCells() as $coord) {
                    ['row' => $r, 'col' => $c] = $coord;
                    if ($board->getAssigned($r, $c) === $digit) {
                        $digitAlreadyAssignedInRun = true;
                        break;
                    }
                }
                if ($digitAlreadyAssignedInRun) {
                    // The run already contains this digit, so it is not missing.
                    continue;
                }

                $possibleCells = [];

                foreach ($run->getCells() as $coord) {
                    ['row' => $r, 'col' => $c] = $coord;
                    if ($board->getAssigned($r, $c) !== null) continue;
                    if (in_array($digit, $board->getCandidates($r, $c), true)) {
                        $possibleCells[] = [$r, $c];
                    }
                }

                if (count($possibleCells) === 0) {
                    // Digit required by this run but cannot go anywhere — contradiction.
                    if ($this->runRequiresDigit($run, $digit)) {
                        return false;
                    }
                    continue;
                }

                if (count($possibleCells) === 1) {
                    [$row, $col] = $possibleCells[0];
                    if ($board->getAssigned($row, $col) !== null) continue;

                    // With unordered combos, only force the assignment if every
                    // remaining combo requires this digit — otherwise the run may
                    // simply not use it at all.
                    if (!$this->runRequiresDigit($run, $digit)) continue;

                    $count++;
                    $this->log("    │  HIDDEN SINGLE: digit $digit must go to ($row,$col)");
                    $this->assignCell($board, $row, $col, $digit);
                    $changed = true;

                    if (!$this->removeDigitFromPeers($board, $row, $col, $digit)) {
                        return false;
                    }
                }
            }
        }
        if ($count > 0) {
            $this->log("    │  Applied $count hidden singles");
        }

        return true;
    }

    /** Returns true if every remaining combination in the run contains $digit. */
    private function runRequiresDigit(Run $run, int $digit): bool
    {
        foreach ($run->getCombinations() as $combo) {
            if (!in_array($digit, $combo, true)) return false;
        }
        return !empty($run->getCombinations());
    }

    // -------------------------------------------------------------------------
    // Technique 3: Naked pairs and triples
    // -------------------------------------------------------------------------

    /**
     * Finds groups of N unassigned cells in a run whose combined candidates
     * contain exactly N digits. Those digits cannot appear in any other cell
     * of the same run.
     *
     * Handles N = 2 (pairs) and N = 3 (triples).
     */
    private function applyNakedPairsTriples(Board $board, bool &$changed): bool
    {
        foreach ($board->getRuns() as $run) {
            $unassigned = array_values(array_filter(
                $run->getCells(),
                fn($coord) => $board->getAssigned($coord['row'], $coord['col']) === null
            ));

            $count = count($unassigned);

            foreach ([2, 3] as $groupSize) {
                if ($count < $groupSize + 1) continue; // Need at least one "other" cell.

                foreach ($this->combinations($unassigned, $groupSize) as $group) {
                    $unionDigits = [];
                    foreach ($group as $coord) {
                        foreach ($board->getCandidates($coord['row'], $coord['col']) as $d) {
                            $unionDigits[$d] = true;
                        }
                    }

                    if (count($unionDigits) !== $groupSize) continue;

                    // Found a naked group — remove these digits from all other cells in the run.
                    $groupCoords = array_map(fn($c) => "{$c['row']},{$c['col']}", $group);
                    $groupStr = implode(';', $groupCoords);
                    $digitsStr = implode(',', array_keys($unionDigits));
                    $this->log("    │  NAKED " . ($groupSize === 2 ? 'PAIR' : 'TRIPLE') . ": cells [$groupStr] lock digits [$digitsStr]");

                    foreach ($unassigned as $coord) {
                        ['row' => $r, 'col' => $c] = $coord;
                        if (in_array("$r,$c", $groupCoords, true)) continue;

                        foreach (array_keys($unionDigits) as $digit) {
                            if ($board->removeCandidate($r, $c, $digit)) {
                                $this->log("      └─ Removed $digit from ($r,$c)");
                                $changed = true;
                            }
                            if ($board->candidateCount($r, $c) === 0) {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /** Generates all size-$k subsets of $items. */
    private function combinations(array $items, int $k): array
    {
        if ($k === 0) return [[]];
        if (empty($items)) return [];

        $first = array_shift($items);
        $withFirst    = array_map(fn($c) => array_merge([$first], $c), $this->combinations($items, $k - 1));
        $withoutFirst = $this->combinations($items, $k);

        return array_merge($withFirst, $withoutFirst);
    }

    // -------------------------------------------------------------------------
    // Technique 4: Combination elimination
    // -------------------------------------------------------------------------

    /**
     * Synchronises each Run's combination list with the current candidate sets:
     *   - Removes combos that place a digit not in a cell's candidates.
     *   - After pruning, removes candidates no longer supported by any combo.
     */
    private function applyCombinationElimination(Board $board, bool &$changed): bool
    {
        $combosRemoved = 0;
        $candidatesRemoved = 0;

        foreach ($board->getRuns() as $run) {
            // Step A: collect each cell's effective candidates (assigned = single value).
            $cellCandidates = [];
            foreach ($run->getCells() as $pos => $coord) {
                ['row' => $r, 'col' => $c] = $coord;
                $assigned = $board->getAssigned($r, $c);
                $cellCandidates[$pos] = $assigned !== null ? [$assigned] : $board->getCandidates($r, $c);
            }

            // Step B: prune combos that can't fill any cell.
            $before = count($run->getCombinations());
            $run->pruneByAllCellCandidates($cellCandidates);
            $after = count($run->getCombinations());
            if ($before > $after) {
                $removed = $before - $after;
                $combosRemoved += $removed;
                $this->log("    │  COMBO ELIM: removed $removed combos (was $before, now $after)");
            }

            if ($run->hasNoCombinations()) {
                return false;
            }

            // Step C: remove candidates not supported by any remaining combo.
            foreach ($run->getCells() as $pos => $coord) {
                ['row' => $r, 'col' => $c] = $coord;
                if ($board->getAssigned($r, $c) !== null) continue;

                $supported = $run->getSupportedDigitsForCell($cellCandidates[$pos]);

                foreach ($board->getCandidates($r, $c) as $digit) {
                    if (!in_array($digit, $supported, true)) {
                        $board->removeCandidate($r, $c, $digit);
                        $candidatesRemoved++;
                        $this->log("      └─ Removed unsupported digit $digit from ($r,$c)");
                        $changed = true;
                    }
                }

                if ($board->candidateCount($r, $c) === 0) {
                    return false;
                }
            }
        }

        if ($combosRemoved > 0 || $candidatesRemoved > 0) {
            $this->log("    │  Applied combination elimination: $combosRemoved combos, $candidatesRemoved candidates removed");
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Peer removal helper
    // -------------------------------------------------------------------------

    /**
     * Removes $digit from all other unassigned cells in the same horizontal
     * and vertical runs as ($row, $col). Returns false on contradiction.
     */
    private function removeDigitFromPeers(Board $board, int $row, int $col, int $digit): bool
    {
        $runs = array_filter([
            $board->getHorizontalRun($row, $col),
            $board->getVerticalRun($row, $col),
        ]);

        $removed = 0;
        foreach ($runs as $run) {
            foreach ($run->getCells() as $coord) {
                ['row' => $r, 'col' => $c] = $coord;
                if ($r === $row && $c === $col) continue;
                if ($board->getAssigned($r, $c) !== null) continue;

                if ($board->removeCandidate($r, $c, $digit)) {
                    $removed++;
                }

                if ($board->candidateCount($r, $c) === 0) {
                    return false;
                }
            }
        }

        if ($removed > 0) {
            $this->log("      └─ Peer removal: removed $digit from $removed peer cells");
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // MRV cell selection
    // -------------------------------------------------------------------------

    /**
     * Picks the unassigned cell with the fewest remaining candidates (minimum
     * remaining values heuristic). Ties are broken by choosing the cell whose
     * runs have the most constraints (most combos already eliminated), but a
     * simple min-candidate count is sufficient for most puzzles.
     *
     * @return array{0: int, 1: int} [row, col]
     */
    private function pickMRVCell(Board $board): array
    {
        $bestRow   = -1;
        $bestCol   = -1;
        $bestCount = PHP_INT_MAX;

        foreach ($board->getUnassignedCells() as [$row, $col]) {
            $count = $board->candidateCount($row, $col);
            if ($count < $bestCount) {
                $bestCount = $count;
                $bestRow   = $row;
                $bestCol   = $col;
            }
        }

        $this->log("MRV: selected cell ($bestRow,$bestCol) with $bestCount candidates");
        return [$bestRow, $bestCol];
    }

    // -------------------------------------------------------------------------
    // Logging helper
    // -------------------------------------------------------------------------

    private function log(string $message): void
    {
        error_log($message);
    }
}
