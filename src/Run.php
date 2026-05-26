<?php

/**
 * Represents one contiguous sequence of white cells sharing a single clue number.
 *
 * Each horizontal or vertical group of white cells between two non-white cells is
 * one Run. The Run owns its current list of valid combinations — combinations are
 * pruned as the solver learns more about individual cells, which in turn lets the
 * solver eliminate candidate digits from cells.
 */
class Run
{
    public int $sum;

    /** @var list<array{row: int, col: int}> Ordered cell coordinates in this run. */
    public array $cells;

    /** @var list<list<int>> Currently valid digit combinations. Pruned during solving. */
    private array $combinations;

    /**
     * @param list<array{row: int, col: int}> $cells
     * @param list<list<int>>                 $combinations
     */
    public function __construct(int $sum, array $cells, array $combinations)
    {
        $this->sum          = $sum;
        $this->cells        = $cells;
        $this->combinations = $combinations;
    }

    /** Returns all currently valid combinations. */
    public function getCombinations(): array
    {
        return $this->combinations;
    }

    /**
     * Returns the union of all digits that appear in at least one valid combination.
     * Used to derive candidate sets for cells.
     *
     * @return list<int>
     */
    public function getCandidateUnion(): array
    {
        $union = [];
        foreach ($this->combinations as $combo) {
            foreach ($combo as $digit) {
                $union[$digit] = true;
            }
        }
        return array_keys($union);
    }

    /**
     * Removes combos that cannot fill the run given each cell's current candidates.
     * A combo is invalid if any cell has no digit from the combo in its candidates.
     * $cellCandidates is indexed by position (same order as $this->cells).
     */
    public function pruneByAllCellCandidates(array $cellCandidates): void
    {
        $this->combinations = array_values(array_filter(
            $this->combinations,
            function (array $combo) use ($cellCandidates): bool {
                foreach ($cellCandidates as $candidates) {
                    if (empty(array_intersect($combo, $candidates))) {
                        return false;
                    }
                }
                return true;
            }
        ));
    }

    /**
     * Returns the digits that are supported at a given cell: digits that appear in
     * any remaining combo AND are in that cell's own candidates.
     */
    public function getSupportedDigitsForCell(array $cellCandidates): array
    {
        $supported = [];
        $candidateSet = array_flip($cellCandidates);
        foreach ($this->combinations as $combo) {
            foreach ($combo as $digit) {
                if (isset($candidateSet[$digit])) {
                    $supported[$digit] = true;
                }
            }
        }
        return array_keys($supported);
    }

    public function hasNoCombinations(): bool
    {
        return empty($this->combinations);
    }

    public function getCells(): array
    {
        return $this->cells;
    }

    public function getCellCount(): int
    {
        return count($this->cells);
    }
}
