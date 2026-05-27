# Kakuro Solver

A web-based Kakuro puzzle solver. You define the grid layout in the browser, enter the clue numbers, and the backend solves the puzzle using constraint propagation — no brute force.

---

## Requirements

- PHP 8.1 or later (uses named arguments and `readonly` properties)
- Any modern web browser

---

## Running the App

1. Open a terminal in the project directory.
2. Start the built-in PHP development server:

   ```bash
   php -S localhost:8000
   ```

3. Open your browser at:

   ```
   http://localhost:8000/index.php
   ```

---

## How to Use

### Step 1 — Draw the Grid (`index.php`)

Click each cell to cycle through its three types:

| Type  | Appearance        | Meaning                          |
|-------|-------------------|----------------------------------|
| White | Light background  | A cell to be filled with a digit |
| Clue  | Dark + diagonal   | Holds the across/down clue sums  |
| Black | Dark solid        | A blocked cell                   |

Click **Next: Enter Clues** when the grid structure is complete.

> The grid state is saved in the browser's `sessionStorage`, so you can navigate back and forward without losing your work.

### Step 2 — Enter Clues (`clues.php`)

Each clue cell shows two small number inputs:

- **Top-right** — the *across* sum (sum of white cells to the right)
- **Bottom-left** — the *down* sum (sum of white cells below)

Leave a field at `0` if there is no run in that direction (e.g. the cell is on the top edge and has no cells below it).

Click **Solve** when all clue numbers are entered.

### Step 3 — View the Solution (`solution.php`)

The page sends the puzzle to the PHP solver and displays the result. If the puzzle has a unique solution the digits are filled in. If no solution exists an error message is shown with a link back to editing.

---

## Project Structure

```
Web-Kakuro/
  index.php          # Step 1: grid structure editor
  clues.php          # Step 2: clue number entry
  solution.php       # Step 3: solution display
  solve.php          # API endpoint (POST JSON → JSON response)

  src/
    CombinationTable.php   # Precomputes valid digit combos for every (sum, length)
    Run.php                # One horizontal or vertical run; owns its live combos
    Board.php              # Full grid state: cell types, candidates, assigned digits
    Solver.php             # Constraint propagation + MRV backtracking

  js/
    grid-editor.js    # Step 1 interactivity
    clue-editor.js    # Step 2 rendering and validation
    solution.js       # Step 3 API call and rendering

  css/
    style.css         # All styles

  tests/
    test-*.php        # Manual/debug solver test scripts
```

---

## How the Solver Works

The solver uses **constraint propagation** as its primary strategy, falling back to backtracking only when it gets stuck. Most published Kakuro puzzles are solved purely by propagation.

### 1. Combination precomputation

Before solving, `CombinationTable` generates every subset of {1–9} grouped by their sum and size. For example, the only way to fill a 2-cell run summing to 3 is `{1, 2}`. This table drives all constraint reasoning.

### 2. Candidate initialisation

Each white cell's initial candidates are the intersection of:
- all digits that appear in any valid combination for its **horizontal** run
- all digits that appear in any valid combination for its **vertical** run

### 3. Constraint propagation loop

The solver applies four techniques repeatedly until nothing changes:

| Technique | What it does |
|-----------|-------------|
| **Naked singles** | A cell with one candidate must be that digit. Remove it from peers. |
| **Hidden singles** | A digit that can only go in one cell of a run must go there. |
| **Naked pairs/triples** | N cells sharing exactly N candidates lock those digits out of other cells in the run. |
| **Combination elimination** | Prune run combinations that conflict with current candidates; remove candidates no longer supported by any combination. |

### 4. MRV backtracking (fallback)

If propagation stalls and the board is incomplete, the solver picks the unassigned cell with the **fewest remaining candidates** (minimum remaining values), takes a snapshot of the board, guesses each candidate in turn, and recurses. The snapshot is restored if a contradiction is reached.

---

## API Reference

`solve.php` accepts `POST` requests with a JSON body and returns JSON.

### Request

```json
{
  "rows": 9,
  "cols": 9,
  "cells": [
    { "row": 0, "col": 0, "type": "black" },
    { "row": 0, "col": 1, "type": "clue", "clueRight": 16, "clueDown": null },
    { "row": 1, "col": 1, "type": "white" }
  ]
}
```

`type` is `"black"`, `"clue"`, or `"white"`. Clue cells include `clueRight` and/or `clueDown` (integers or `null`).

### Success response

```json
{
  "status": "solved",
  "solution": [
    [null, null, 7, 9, ...],
    ...
  ]
}
```

`solution` is a 2-D array. White cells contain their digit; all other cells are `null`.

### Error responses

```json
{ "status": "unsolvable", "message": "No solution exists for this puzzle." }
{ "status": "error",      "message": "Impossible clue: sum 50 for 3 cells at (0,1) going horizontal." }
```

---

## Running Test Scripts

Solver test/debug scripts are stored in `tests/`.

Run any script from the project root, for example:

```bash
php tests/test-assign.php
php tests/test-propagate.php
```
