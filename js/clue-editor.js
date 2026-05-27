/**
 * Step 2: Clue editor.
 *
 * Reads the grid layout from sessionStorage, renders the Kakuro grid with number
 * inputs on clue cells, validates the entered clues, then builds the full puzzle
 * definition and navigates to solution.php.
 */

const grid = document.getElementById("grid");

let layout = loadLayout();
if (!layout) {
  // Auto-load debug layout as default
  layout = buildDebugLayout();
  sessionStorage.setItem("kakuro_grid", JSON.stringify(layout));
}

renderGrid(layout);
autoFillDebugClues();

document.getElementById("back-btn").addEventListener("click", () => {
  window.location.href = "index.php";
});

document.getElementById("solve-btn").addEventListener("click", () => {
  const puzzle = buildPuzzle(layout);
  if (!puzzle) return; // Validation failed; errors shown inline.

  sessionStorage.setItem("kakuro_puzzle", JSON.stringify(puzzle));
  window.location.href = "solution.php";
});

// -----------------------------------------------------------------------------
// Grid rendering
// -----------------------------------------------------------------------------

function renderGrid(layout) {
  const rows = layout.length;
  const cols = layout[0].length;

  for (let r = 0; r < rows; r++) {
    const tr = document.createElement("tr");

    for (let c = 0; c < cols; c++) {
      const type = layout[r][c];
      const td = document.createElement("td");
      td.className = `cell cell--${type}`;
      td.dataset.row = r;
      td.dataset.col = c;
      td.dataset.type = type;

      if (type === "clue") {
        td.appendChild(buildClueInputs(r, c));
      }

      tr.appendChild(td);
    }

    grid.appendChild(tr);
  }
}

/**
 * Builds the two number inputs for a clue cell, styled as a diagonal split.
 * The "across" input sits in the upper-right, "down" in the lower-left,
 * matching how Kakuro clue cells look in a printed puzzle.
 */
function buildClueInputs(row, col) {
  const wrapper = document.createElement("div");
  wrapper.className = "clue-cell";

  const acrossInput = document.createElement("input");
  acrossInput.type = "number";
  acrossInput.min = "0";
  acrossInput.max = "45";
  acrossInput.value = "0";
  acrossInput.className = "clue-input clue-input--across";
  acrossInput.id = `across_${row}_${col}`;
  acrossInput.title = "Across sum";
  acrossInput.setAttribute(
    "aria-label",
    `Across clue at row ${row}, col ${col}`,
  );

  const downInput = document.createElement("input");
  downInput.type = "number";
  downInput.min = "0";
  downInput.max = "45";
  downInput.value = "0";
  downInput.className = "clue-input clue-input--down";
  downInput.id = `down_${row}_${col}`;
  downInput.title = "Down sum";
  downInput.setAttribute("aria-label", `Down clue at row ${row}, col ${col}`);

  wrapper.appendChild(acrossInput);
  wrapper.appendChild(downInput);
  return wrapper;
}

// -----------------------------------------------------------------------------
// Puzzle serialisation and validation
// -----------------------------------------------------------------------------

/**
 * Builds the puzzle JSON expected by solve.php.
 * Returns null and shows an alert if any validation fails.
 */
function buildPuzzle(layout) {
  const rows = layout.length;
  const cols = layout[0].length;
  const cells = [];

  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
      const type = layout[r][c];

      if (type === "black" || type === "white") {
        cells.push({ row: r, col: c, type });
        continue;
      }

      // Clue cell — read and validate the two inputs.
      const acrossRaw =
        document.getElementById(`across_${r}_${c}`)?.value ?? "0";
      const downRaw = document.getElementById(`down_${r}_${c}`)?.value ?? "0";
      const across = parseInt(acrossRaw, 10);
      const down = parseInt(downRaw, 10);

      if (isNaN(across) || isNaN(down)) {
        alert(`Invalid clue value at row ${r + 1}, column ${c + 1}.`);
        return null;
      }

      cells.push({
        row: r,
        col: c,
        type: "clue",
        clueRight: across > 0 ? across : null,
        clueDown: down > 0 ? down : null,
      });
    }
  }

  if (!hasAtLeastOneClue(cells)) {
    alert("Please enter at least one clue number before solving.");
    return null;
  }

  return { rows, cols, cells };
}

function hasAtLeastOneClue(cells) {
  return cells.some((c) => c.clueRight != null || c.clueDown != null);
}

// -----------------------------------------------------------------------------
// Session helpers
// -----------------------------------------------------------------------------

function loadLayout() {
  const saved = sessionStorage.getItem("kakuro_grid");
  if (!saved) return null;

  try {
    return JSON.parse(saved);
  } catch {
    return null;
  }
}

function buildDebugLayout() {
  return [
    ["black", "clue", "clue", "clue", "black", "black", "black", "black"],
    ["clue", "white", "white", "white", "black", "black", "black", "black"],
    ["clue", "white", "white", "white", "black", "black", "black", "black"],
    ["clue", "white", "white", "white", "black", "black", "black", "black"],
    ["black", "black", "black", "black", "black", "black", "black", "black"],
    ["black", "black", "black", "black", "black", "black", "black", "black"],
    ["black", "black", "black", "black", "black", "black", "black", "black"],
    ["black", "black", "black", "black", "black", "black", "black", "black"],
  ];
}

function autoFillDebugClues() {
  const clues = {
    "0_1": { across: 0, down: 6 },
    "0_2": { across: 0, down: 7 },
    "0_3": { across: 0, down: 9 },
    "1_0": { across: 6, down: 0 },
    "2_0": { across: 7, down: 0 },
    "3_0": { across: 9, down: 0 },
  };

  for (const [key, values] of Object.entries(clues)) {
    const acrossInput = document.getElementById(`across_${key}`);
    const downInput = document.getElementById(`down_${key}`);
    if (acrossInput) acrossInput.value = values.across || "0";
    if (downInput) downInput.value = values.down || "0";
  }
}
