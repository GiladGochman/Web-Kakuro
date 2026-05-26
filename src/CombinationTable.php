<?php

/**
 * Precomputes every valid set of distinct digits (1–9) grouped by their sum and count.
 *
 * In Kakuro, each run of white cells must be filled with distinct digits 1–9 that
 * add up to the run's clue number. Knowing all valid combinations upfront lets the
 * solver reason about which digits are possible in a run without re-deriving them.
 *
 * Table layout: $table[sum][length] = [ [d1, d2, ...], ... ]
 * Sums range from 3 (1+2) to 45 (1+2+…+9).
 * Lengths range from 2 to 9.
 */
class CombinationTable
{
    /** @var array<int, array<int, list<list<int>>>> */
    private array $table = [];

    public function __construct()
    {
        $this->build();
    }

    /**
     * Returns all valid combinations for a run with the given sum and cell count.
     * Returns an empty array if the (sum, length) pair is impossible.
     *
     * @return list<list<int>>
     */
    public function getCombinations(int $sum, int $length): array
    {
        return $this->table[$sum][$length] ?? [];
    }

    /**
     * Generates combinations recursively: choose $length distinct digits from
     * {$min..9} that add up to $remaining, accumulating into $current.
     */
    private function build(): void
    {
        $this->generate([], 1, 0, 0);
    }

    private function generate(array $current, int $min, int $sum, int $length): void
    {
        if ($length >= 2) {
            $this->table[$sum][$length][] = $current;
        }

        if ($length === 9 || $min > 9) {
            return;
        }

        for ($digit = $min; $digit <= 9; $digit++) {
            $this->generate(
                array_merge($current, [$digit]),
                $digit + 1,
                $sum + $digit,
                $length + 1
            );
        }
    }
}
