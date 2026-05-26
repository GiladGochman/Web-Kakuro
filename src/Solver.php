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

    public function __construct(Board $board)
    {
        $this->board = $board;
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
    // Top-level recursive solver
    // -------------------------------------------------------------------------

    private function solveBoard(Board $board): ?Board
    {
        if (!$this->propagate($board)) {
            return null; // Contradiction found during propagation.
        }

        if ($board->isComplete()) {
            return $board;
        }

        // Propagation stalled — try backtracking on the most-constrained cell.
        [$row, $col] = $this->pickMRVCell($board);
        $candidates  = $board->getCandidates($row, $col);

        foreach ($candidates as $digit) {
            $snapshot = $board->snapshot();
            $snapshot->assign($row, $col, $digit);

            if (!$this->removeDigitFromPeers($snapshot, $row, $col, $digit)) {
                continue; // Immediate contradiction — try next digit.
            }

            $result = $this->solveBoard($snapshot);
            if ($result !== null) {
                return $result;
            }
        }

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
        do {
            $changed = false;

            if (!$this->applyCombinationElimination($board, $changed)) return false;
            if (!$this->applyNakedSingles($board, $changed))           return false;
            if (!$this->applyHiddenSingles($board, $changed))          return false;
            if (!$this->applyNakedPairsTriples($board, $changed))      return false;

        } while ($changed);

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
        foreach ($board->getUnassignedCells() as [$row, $col]) {
            if ($board->candidateCount($row, $col) !== 1) {
                continue;
            }

            $digit = $board->getCandidates($row, $col)[0];
            $board->assign($row, $col, $digit);
            $changed = true;

            if (!$this->removeDigitFromPeers($board, $row, $col, $digit)) {
                return false;
            }
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
        foreach ($board->getRuns() as $run) {
            foreach (range(1, 9) as $digit) {
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

                    $board->assign($row, $col, $digit);
                    $changed = true;

                    if (!$this->removeDigitFromPeers($board, $row, $col, $digit)) {
                        return false;
                    }
                }
            }
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

                    foreach ($unassigned as $coord) {
                        ['row' => $r, 'col' => $c] = $coord;
                        if (in_array("$r,$c", $groupCoords, true)) continue;

                        foreach (array_keys($unionDigits) as $digit) {
                            if ($board->removeCandidate($r, $c, $digit)) {
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
        foreach ($board->getRuns() as $run) {
            // Step A: collect each cell's effective candidates (assigned = single value).
            $cellCandidates = [];
            foreach ($run->getCells() as $pos => $coord) {
                ['row' => $r, 'col' => $c] = $coord;
                $assigned = $board->getAssigned($r, $c);
                $cellCandidates[$pos] = $assigned !== null ? [$assigned] : $board->getCandidates($r, $c);
            }

            // Step B: prune combos that can't fill any cell.
            $run->pruneByAllCellCandidates($cellCandidates);

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
                        $changed = true;
                    }
                }

                if ($board->candidateCount($r, $c) === 0) {
                    return false;
                }
            }
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

        foreach ($runs as $run) {
            foreach ($run->getCells() as $coord) {
                ['row' => $r, 'col' => $c] = $coord;
                if ($r === $row && $c === $col) continue;
                if ($board->getAssigned($r, $c) !== null) continue;

                $board->removeCandidate($r, $c, $digit);

                if ($board->candidateCount($r, $c) === 0) {
                    return false;
                }
            }
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

        return [$bestRow, $bestCol];
    }
}
