<?php

require_once __DIR__ . '/CombinationTable.php';
require_once __DIR__ . '/Run.php';

/**
 * Represents the full Kakuro board: cell types, candidate digit sets, assigned
 * values, and all Run objects.
 *
 * Cell types:
 *   'black' — blocked cell, never filled
 *   'clue'  — contains one or two clue numbers (right-sum, down-sum)
 *   'white' — must be filled with a digit 1–9
 *
 * The board is constructed from the JSON payload sent by the frontend, and is
 * cloned during backtracking so the solver can restore state on contradiction.
 */
class Board
{
    private int $rows;
    private int $cols;

    /** @var array<int, array<int, string>> $cellTypes[row][col] = 'black'|'clue'|'white' */
    private array $cellTypes = [];

    /** @var array<int, array<int, int|null>> $assigned[row][col] = digit or null */
    private array $assigned = [];

    /** @var array<int, array<int, array<int, bool>>> $candidates[row][col] = {digit => true} */
    private array $candidates = [];

    /** @var list<Run> */
    private array $runs = [];

    /**
     * Maps each white cell to its horizontal and vertical Run.
     * @var array<int, array<int, array{h: Run|null, v: Run|null}>>
     */
    private array $cellRuns = [];

    private function __construct(int $rows, int $cols)
    {
        $this->rows = $rows;
        $this->cols = $cols;
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    /**
     * Builds a Board from the JSON payload sent by the frontend.
     *
     * Expected payload shape:
     * {
     *   "rows": int,
     *   "cols": int,
     *   "cells": [
     *     { "row": int, "col": int, "type": "black"|"clue"|"white",
     *       "clueRight": int|null, "clueDown": int|null }
     *   ]
     * }
     *
     * @throws InvalidArgumentException on invalid input
     */
    public static function fromJson(array $data, CombinationTable $combTable): self
    {
        $rows  = (int) ($data['rows'] ?? 0);
        $cols  = (int) ($data['cols'] ?? 0);

        if ($rows < 2 || $cols < 2) {
            throw new InvalidArgumentException("Board must be at least 2×2.");
        }

        $board = new self($rows, $cols);

        // Index raw cell data for easy lookup.
        $cellData = [];
        foreach ($data['cells'] as $cell) {
            $cellData[$cell['row']][$cell['col']] = $cell;
        }

        // Store cell types and clue values.
        $clues = []; // $clues[row][col] = ['right' => int|null, 'down' => int|null]
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $cell = $cellData[$r][$c] ?? ['type' => 'black'];
                $type = $cell['type'];
                $board->cellTypes[$r][$c] = $type;
                $board->assigned[$r][$c]  = null;

                if ($type === 'clue') {
                    $clues[$r][$c] = [
                        'right' => isset($cell['clueRight']) ? (int) $cell['clueRight'] : null,
                        'down'  => isset($cell['clueDown'])  ? (int) $cell['clueDown']  : null,
                    ];
                }
            }
        }

        // Build runs and initialise candidate sets.
        $board->buildRuns($clues, $combTable);
        $board->initCandidates();

        return $board;
    }

    /**
     * Scans the grid to find all horizontal and vertical runs, creating Run
     * objects with their initial combination lists from the combination table.
     *
     * @throws InvalidArgumentException if a run's (sum, length) pair is impossible
     */
    private function buildRuns(array $clues, CombinationTable $combTable): void
    {
        // Horizontal runs: scan each row left-to-right.
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                if ($this->cellTypes[$r][$c] !== 'clue') {
                    continue;
                }
                $sum = $clues[$r][$c]['right'] ?? null;
                if ($sum === null || $sum === 0) {
                    continue;
                }

                $cells = [];
                for ($nc = $c + 1; $nc < $this->cols && $this->cellTypes[$r][$nc] === 'white'; $nc++) {
                    $cells[] = ['row' => $r, 'col' => $nc];
                }

                $run = $this->makeRun($sum, $cells, $combTable, 'horizontal', $r, $c);
                $this->runs[] = $run;
                foreach ($cells as $pos => $coord) {
                    $this->cellRuns[$coord['row']][$coord['col']]['h'] = $run;
                }
            }
        }

        // Vertical runs: scan each column top-to-bottom.
        for ($c = 0; $c < $this->cols; $c++) {
            for ($r = 0; $r < $this->rows; $r++) {
                if ($this->cellTypes[$r][$c] !== 'clue') {
                    continue;
                }
                $sum = $clues[$r][$c]['down'] ?? null;
                if ($sum === null || $sum === 0) {
                    continue;
                }

                $cells = [];
                for ($nr = $r + 1; $nr < $this->rows && $this->cellTypes[$nr][$c] === 'white'; $nr++) {
                    $cells[] = ['row' => $nr, 'col' => $c];
                }

                $run = $this->makeRun($sum, $cells, $combTable, 'vertical', $r, $c);
                $this->runs[] = $run;
                foreach ($cells as $pos => $coord) {
                    $this->cellRuns[$coord['row']][$coord['col']]['v'] = $run;
                }
            }
        }
    }

    private function makeRun(
        int $sum,
        array $cells,
        CombinationTable $combTable,
        string $direction,
        int $clueRow,
        int $clueCol
    ): Run {
        $length = count($cells);

        if ($length < 2 || $length > 9) {
            throw new InvalidArgumentException(
                "Invalid run length $length at clue ($clueRow,$clueCol) going $direction."
            );
        }

        $combos = $combTable->getCombinations($sum, $length);
        if (empty($combos)) {
            throw new InvalidArgumentException(
                "Impossible clue: sum $sum for $length cells at ($clueRow,$clueCol) going $direction."
            );
        }

        return new Run($sum, $cells, $combos);
    }

    /**
     * Sets each white cell's initial candidates to the intersection of its
     * horizontal run's digit union and its vertical run's digit union.
     */
    private function initCandidates(): void
    {
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                if ($this->cellTypes[$r][$c] !== 'white') {
                    continue;
                }

                $hRun = $this->cellRuns[$r][$c]['h'] ?? null;
                $vRun = $this->cellRuns[$r][$c]['v'] ?? null;

                $hDigits = $hRun ? array_flip($hRun->getCandidateUnion()) : array_flip(range(1, 9));
                $vDigits = $vRun ? array_flip($vRun->getCandidateUnion()) : array_flip(range(1, 9));

                // Intersection: digit must be possible in both runs.
                $intersection = array_intersect_key($hDigits, $vDigits);
                $this->candidates[$r][$c] = array_fill_keys(array_keys($intersection), true);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getRows(): int { return $this->rows; }
    public function getCols(): int { return $this->cols; }

    public function getCellType(int $row, int $col): string
    {
        return $this->cellTypes[$row][$col] ?? 'black';
    }

    /** @return list<int> */
    public function getCandidates(int $row, int $col): array
    {
        return array_keys($this->candidates[$row][$col] ?? []);
    }

    public function getAssigned(int $row, int $col): ?int
    {
        return $this->assigned[$row][$col];
    }

    /** @return list<Run> */
    public function getRuns(): array
    {
        return $this->runs;
    }

    public function getHorizontalRun(int $row, int $col): ?Run
    {
        return $this->cellRuns[$row][$col]['h'] ?? null;
    }

    public function getVerticalRun(int $row, int $col): ?Run
    {
        return $this->cellRuns[$row][$col]['v'] ?? null;
    }

    /** Returns all unassigned white cells as [[row, col], ...]. */
    public function getUnassignedCells(): array
    {
        $cells = [];
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                if ($this->cellTypes[$r][$c] === 'white' && $this->assigned[$r][$c] === null) {
                    $cells[] = [$r, $c];
                }
            }
        }
        return $cells;
    }

    public function isComplete(): bool
    {
        return empty($this->getUnassignedCells());
    }

    // -------------------------------------------------------------------------
    // Mutations
    // -------------------------------------------------------------------------

    /**
     * Assigns a digit to a cell and removes it from that cell's candidates.
     * Does NOT propagate to peers — the Solver handles propagation.
     */
    public function assign(int $row, int $col, int $digit): void
    {
        $this->assigned[$row][$col]   = $digit;
        $this->candidates[$row][$col] = [$digit => true];
    }

    /**
     * Removes a digit from a cell's candidate set.
     * Returns true if the candidate was present (i.e., something changed).
     */
    public function removeCandidate(int $row, int $col, int $digit): bool
    {
        if (!isset($this->candidates[$row][$col][$digit])) {
            return false;
        }
        unset($this->candidates[$row][$col][$digit]);
        return true;
    }

    public function candidateCount(int $row, int $col): int
    {
        return count($this->candidates[$row][$col] ?? []);
    }

    // -------------------------------------------------------------------------
    // Snapshot for backtracking
    // -------------------------------------------------------------------------

    /**
     * Returns a deep clone of this board so the solver can restore it if a
     * backtracking guess leads to a contradiction.
     */
    public function snapshot(): self
    {
        $copy = new self($this->rows, $this->cols);
        $copy->cellTypes = $this->cellTypes;
        $copy->assigned  = $this->assigned;
        $copy->candidates = array_map(
            fn($row) => array_map(fn($cell) => $cell, $row),
            $this->candidates
        );

        // Deep-clone runs and rebuild cellRuns index pointing at the clones.
        $runMap = new SplObjectStorage();
        foreach ($this->runs as $run) {
            $clonedRun = clone $run;
            $copy->runs[] = $clonedRun;
            $runMap[$run] = $clonedRun;
        }

        foreach ($this->cellRuns as $r => $cols) {
            foreach ($cols as $c => $refs) {
                $copy->cellRuns[$r][$c] = [
                    'h' => isset($refs['h']) ? $runMap[$refs['h']] : null,
                    'v' => isset($refs['v']) ? $runMap[$refs['v']] : null,
                ];
            }
        }

        return $copy;
    }

    // -------------------------------------------------------------------------
    // Solution export
    // -------------------------------------------------------------------------

    /**
     * Returns a 2-D array where white cells have their assigned digit and
     * all other cells are null. This is the format returned by the API.
     */
    public function toSolutionGrid(): array
    {
        $grid = [];
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                $grid[$r][$c] = ($this->cellTypes[$r][$c] === 'white')
                    ? $this->assigned[$r][$c]
                    : null;
            }
        }
        return $grid;
    }
}
