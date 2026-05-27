/**
 * Step 3: Real-time solution display.
 *
 * Streams the solve process in real-time: POST to solve.php and listen for
 * newline-delimited JSON events as cells are assigned.
 */

const spinner = document.getElementById("spinner");
const gridWrapper = document.getElementById("grid-wrapper");
const gridEl = document.getElementById("grid");
const actionsEl = document.getElementById("actions");
const statusArea = document.getElementById("status-area");

const puzzle = loadPuzzle();
if (!puzzle) {
  // Auto-load debug puzzle as default
  const debugPuzzle = buildDebugPuzzle();
  sessionStorage.setItem("kakuro_puzzle", JSON.stringify(debugPuzzle));
  location.reload();
}

// Render the grid skeleton immediately (empty white cells, clue cells, black cells)
renderGridSkeleton(puzzle);
gridWrapper.hidden = false;

// Start solving and listening for real-time updates
solvePuzzle(puzzle);

document.getElementById("back-btn").addEventListener("click", () => {
  window.location.href = "clues.php";
});

document.getElementById("new-btn").addEventListener("click", () => {
  sessionStorage.removeItem("kakuro_grid");
  sessionStorage.removeItem("kakuro_puzzle");
  window.location.href = "index.php";
});

// ============================================================================
// Real-time streaming solver
// ============================================================================

async function solvePuzzle(puzzle) {
  try {
    const response = await fetch("solve.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(puzzle),
    });

    if (!response.ok) {
      throw new Error("Server returned " + response.status);
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = "";

    while (true) {
      const { done, value } = await reader.read();
      buffer += decoder.decode(value, { stream: !done });

      // Process complete lines from the buffer
      while (true) {
        const newlineIdx = buffer.indexOf("\n");
        if (newlineIdx === -1) break;

        const line = buffer.substring(0, newlineIdx).trim();
        buffer = buffer.substring(newlineIdx + 1);

        if (!line) continue; // Skip empty lines

        try {
          processStreamEvent(line);
        } catch (err) {
          console.error("Error processing event:", err, line);
        }
      }

      if (done) break;
    }

    spinner.hidden = true;
    actionsEl.hidden = false;
  } catch (err) {
    spinner.hidden = true;
    showError("Could not reach the server: " + err.message);
  }
}

/**
 * Processes a single SSE event line (e.g., "event: assign\ndata: {...}\n\n")
 */
function processStreamEvent(line) {
  if (line.startsWith("event: ")) {
    const eventType = line.substring(7).trim();
    // We'll handle this in the next line if it's "data:"
    window.currentEventType = eventType;
  } else if (line.startsWith("data: ")) {
    const eventType = window.currentEventType || "unknown";
    const data = JSON.parse(line.substring(6));

    if (eventType === "assign") {
      updateCell(data.row, data.col, data.digit);
    } else if (eventType === "solved") {
      if (data.status === "unsolvable") {
        showError("No solution exists for this puzzle.");
      } else if (data.status === "solved") {
        showSuccess("Puzzle solved! ✓");
      } else {
        showError(data.message || "An error occurred.");
      }
    } else if (eventType === "error") {
      showError(data.message || "An error occurred.");
    }
  }
}

// ============================================================================
// Grid rendering
// ============================================================================

/**
 * Renders the grid structure once, with empty placeholders for white cells.
 * As assignments come in, updateCell() fills them.
 */
function renderGridSkeleton(puzzle) {
  const { rows, cols, cells } = puzzle;

  // Index cell definitions for quick lookup
  const cellMap = {};
  for (const cell of cells) {
    cellMap[`${cell.row},${cell.col}`] = cell;
  }

  for (let r = 0; r < rows; r++) {
    const tr = document.createElement("tr");

    for (let c = 0; c < cols; c++) {
      const def = cellMap[`${r},${c}`] ?? { type: "black" };
      const td = document.createElement("td");
      td.className = `cell cell--${def.type}`;
      td.dataset.type = def.type;
      td.id = `cell-${r}-${c}`;

      if (def.type === "clue") {
        td.appendChild(buildClueDisplay(def.clueRight, def.clueDown));
      } else if (def.type === "white") {
        td.textContent = "";
        td.classList.add("cell--solved");
      }

      tr.appendChild(td);
    }

    gridEl.appendChild(tr);
  }
}

/**
 * Updates a single cell with its solved digit. Called in real-time as
 * assignments happen on the backend.
 */
function updateCell(row, col, digit) {
  const cell = document.getElementById(`cell-${row}-${col}`);
  if (cell) {
    cell.textContent = digit;
    // Add a brief pulse animation
    cell.classList.remove("cell--assigned");
    void cell.offsetWidth; // Trigger reflow to restart animation
    cell.classList.add("cell--assigned");
  }
}

/**
 * Builds a clue cell display with diagonal split and both clue numbers.
 */
function buildClueDisplay(acrossSum, downSum) {
  const wrapper = document.createElement("div");
  wrapper.className = "clue-cell";

  const acrossSpan = document.createElement("span");
  acrossSpan.className = "clue-value clue-value--across";
  acrossSpan.textContent = acrossSum ?? "";

  const downSpan = document.createElement("span");
  downSpan.className = "clue-value clue-value--down";
  downSpan.textContent = downSum ?? "";

  wrapper.appendChild(acrossSpan);
  wrapper.appendChild(downSpan);
  return wrapper;
}

// ============================================================================
// Error display
// ============================================================================

function showError(message) {
  const errorBox = document.createElement("div");
  errorBox.className = "error-box";
  errorBox.textContent = message;
  statusArea.appendChild(errorBox);
}

function showSuccess(message) {
  const successBox = document.createElement("div");
  successBox.className = "success-box";
  successBox.textContent = message;
  statusArea.appendChild(successBox);
}

// ============================================================================
// Session helper
// ============================================================================

function loadPuzzle() {
  const saved = sessionStorage.getItem("kakuro_puzzle");
  if (!saved) return null;

  try {
    return JSON.parse(saved);
  } catch {
    return null;
  }
}

function buildDebugPuzzle() {
  return {
    rows: 8,
    cols: 8,
    cells: [
      { row: 0, col: 0, type: "black" },
      { row: 0, col: 1, type: "clue", clueRight: null, clueDown: 6 },
      { row: 0, col: 2, type: "clue", clueRight: null, clueDown: 7 },
      { row: 0, col: 3, type: "clue", clueRight: null, clueDown: 9 },
      { row: 0, col: 4, type: "black" },
      { row: 0, col: 5, type: "black" },
      { row: 0, col: 6, type: "black" },
      { row: 0, col: 7, type: "black" },
      { row: 1, col: 0, type: "clue", clueRight: 6, clueDown: null },
      { row: 1, col: 1, type: "white" },
      { row: 1, col: 2, type: "white" },
      { row: 1, col: 3, type: "white" },
      { row: 1, col: 4, type: "black" },
      { row: 1, col: 5, type: "black" },
      { row: 1, col: 6, type: "black" },
      { row: 1, col: 7, type: "black" },
      { row: 2, col: 0, type: "clue", clueRight: 7, clueDown: null },
      { row: 2, col: 1, type: "white" },
      { row: 2, col: 2, type: "white" },
      { row: 2, col: 3, type: "white" },
      { row: 2, col: 4, type: "black" },
      { row: 2, col: 5, type: "black" },
      { row: 2, col: 6, type: "black" },
      { row: 2, col: 7, type: "black" },
      { row: 3, col: 0, type: "clue", clueRight: 9, clueDown: null },
      { row: 3, col: 1, type: "white" },
      { row: 3, col: 2, type: "white" },
      { row: 3, col: 3, type: "white" },
      { row: 3, col: 4, type: "black" },
      { row: 3, col: 5, type: "black" },
      { row: 3, col: 6, type: "black" },
      { row: 3, col: 7, type: "black" },
      { row: 4, col: 0, type: "black" },
      { row: 4, col: 1, type: "black" },
      { row: 4, col: 2, type: "black" },
      { row: 4, col: 3, type: "black" },
      { row: 4, col: 4, type: "black" },
      { row: 4, col: 5, type: "black" },
      { row: 4, col: 6, type: "black" },
      { row: 4, col: 7, type: "black" },
      { row: 5, col: 0, type: "black" },
      { row: 5, col: 1, type: "black" },
      { row: 5, col: 2, type: "black" },
      { row: 5, col: 3, type: "black" },
      { row: 5, col: 4, type: "black" },
      { row: 5, col: 5, type: "black" },
      { row: 5, col: 6, type: "black" },
      { row: 5, col: 7, type: "black" },
      { row: 6, col: 0, type: "black" },
      { row: 6, col: 1, type: "black" },
      { row: 6, col: 2, type: "black" },
      { row: 6, col: 3, type: "black" },
      { row: 6, col: 4, type: "black" },
      { row: 6, col: 5, type: "black" },
      { row: 6, col: 6, type: "black" },
      { row: 6, col: 7, type: "black" },
      { row: 7, col: 0, type: "black" },
      { row: 7, col: 1, type: "black" },
      { row: 7, col: 2, type: "black" },
      { row: 7, col: 3, type: "black" },
      { row: 7, col: 4, type: "black" },
      { row: 7, col: 5, type: "black" },
      { row: 7, col: 6, type: "black" },
      { row: 7, col: 7, type: "black" },
    ],
  };
}
