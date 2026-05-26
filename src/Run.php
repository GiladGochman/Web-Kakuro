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
     * Removes any combination that does NOT contain $digit at position $position.
     * Used when a cell is assigned a value and that value must appear there.
     */
    public function keepOnlyCombosWithDigitAtPosition(int $digit, int $position): void
    {
        $this->combinations = array_values(array_filter(
            $this->combinations,
            fn(array $combo) => ($combo[$position] ?? null) === $digit
        ));
    }

    /**
     * Removes any combination that contains $digit at position $position.
     * Used when a digit is eliminated from a specific cell in this run.
     */
    public function removeCombosWithDigitAtPosition(int $digit, int $position): void
    {
        $this->combinations = array_values(array_filter(
            $this->combinations,
            fn(array $combo) => ($combo[$position] ?? null) !== $digit
        ));
    }

    /**
     * Removes any combination that places a digit NOT in $allowedDigits at $position.
     * Used to synchronise combinations with a cell's current candidate set.
     */
    public function restrictPositionToCandidates(int $position, array $allowedDigits): void
    {
        $allowed = array_flip($allowedDigits);
        $this->combinations = array_values(array_filter(
            $this->combinations,
            fn(array $combo) => isset($allowed[$combo[$position] ?? -1])
        ));
    }

    public function hasNoCombinations(): bool
    {
        return empty($this->combinations);
    }

    public function getCellCount(): int
    {
        return count($this->cells);
    }
}
