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
  window.location.href = "index.php";
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
      } else if (data.status !== "solved") {
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
