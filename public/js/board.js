let ws = new WebSocket('ws://localhost:8080');
let whiteTime = TIME * 60 * 1000;
let blackTime = TIME * 60 * 1000;
let inc = INC * 1000;
let turn = "w"
let lastSync = Date.now();
let blackClock = document.querySelector('.black.timer');
let whiteClock = document.querySelector('.white.timer');
let myColor = PLAYER_COLOR === "white" ? "w" : "b";

const PIECES = {
  wK: '♔', wQ: '♕', wR: '♖', wB: '♗', wN: '♘', wP: '♙',
  bK: '♚', bQ: '♛', bR: '♜', bB: '♝', bN: '♞', bP: '♟'
};

const FILES = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
const RANKS = ['8', '7', '6', '5', '4', '3', '2', '1'];

let chess = new Chess();
let selected = null;
let hints = [];
let flipped = false;

function squareName(fi, ri) {
  const f = flipped ? FILES[7 - fi] : FILES[fi];
  const r = flipped ? RANKS[7 - ri] : RANKS[ri];
  return f + r;
}

function renderBoard() {
  const board = document.getElementById('board');
  board.innerHTML = '';

  for (let ri = 0; ri < 8; ri++) {
    for (let fi = 0; fi < 8; fi++) {
      const sq = squareName(fi, ri);
      const piece = chess.get(sq);
      const isLight = (fi + ri) % 2 === 0;
      const div = document.createElement('div');
      div.className = 'sq ' + (isLight ? 'light' : 'dark');
      div.dataset.sq = sq;
      if (piece) {
        div.textContent = PIECES[piece.color + piece.type.toUpperCase()];
        if (hints.includes(sq)) div.classList.add('hint', 'has-piece');
      } else {
        if (hints.includes(sq)) div.classList.add('hint');
      }
      if (selected === sq) div.classList.add('selected');
      div.addEventListener('click', () => onSquareClick(sq));
      board.appendChild(div);
    }
  }

  const rankLabels = document.getElementById('rank-labels');
  rankLabels.innerHTML = '';
  for (let ri = 0; ri < 8; ri++) {
    const r = flipped ? RANKS[7 - ri] : RANKS[ri];
    const d = document.createElement('div');
    d.className = 'rank-label';
    d.textContent = r;
    rankLabels.appendChild(d);
  }

  const fileLabels = document.getElementById('file-labels');
  fileLabels.innerHTML = '';
  for (let fi = 0; fi < 8; fi++) {
    const f = flipped ? FILES[7 - fi] : FILES[fi];
    const d = document.createElement('div');
    d.className = 'file-label';
    d.textContent = f;
    fileLabels.appendChild(d);
  }

  const status = document.getElementById('status');
  if (chess.in_checkmate()) {
    status.textContent = (chess.turn() === 'w' ? 'Black' : 'White') + ' wins by checkmate!';
  } else if (chess.in_draw()) {
    status.textContent = 'Draw!';
  } else if (chess.in_check()) {
    status.textContent = (chess.turn() === 'w' ? 'White' : 'Black') + ' is in check!';
  } else {
    status.textContent = (chess.turn() === 'w' ? 'White' : 'Black') + ' to move';
  }
}

function onSquareClick(sq) {
  if (chess.game_over()) return;
  if (chess.turn() === 'w' && PLAYER_COLOR !== 'white') return;
  if (chess.turn() === 'b' && PLAYER_COLOR !== 'black') return;
  const piece = chess.get(sq);

  if (selected) {
    if (hints.includes(sq)) {
      const move = chess.move({ from: selected, to: sq, promotion: 'q' });
      if (move) {
        ws.send(JSON.stringify({
          type: 'move',
          room: ROOM_ID,
          move: { from: selected, to: sq, promotion: 'q' },
          w_time: whiteTime,
          b_time: blackTime
        }));
      }
      selected = null;
      hints = [];
    } else if (piece && piece.color === chess.turn()) {
      selected = sq;
      hints = chess.moves({ square: sq, verbose: true }).map(m => m.to);
    } else {
      selected = null;
      hints = [];
    }
  } else {
    if (piece && piece.color === chess.turn()) {
      selected = sq;
      hints = chess.moves({ square: sq, verbose: true }).map(m => m.to);
    }
  }
  renderBoard();
}

function flipBoard() {
  flipped = !flipped;
  selected = null;
  hints = [];
  renderBoard();
}

function formateTime(time) {
  const mins = Math.max(0, Math.floor(time / 1000 / 60));
  const sec = Math.max(0, Math.floor(time / 1000) % 60);
  return `${mins}:${sec.toString().padStart(2, "0")}`
}


function rematch() {
  ws.send(JSON.stringify({
    type: 'rematch',
    room: ROOM_ID,
    id: PLAYER_ID
  }));
}

function newGame() {
  document.getElementById("rematch").disabled = true;
  document.getElementById("new-game").disabled = true;
  sessionStorage.setItem("autoPlay", "true");
  window.location.href = "../public/homepage.php?time=" + TIME + "&inc=" + INC + "&type=" + TYPE;
};

const countDown = setInterval(() => {
  const now = Date.now();
  const elapsed = now - lastSync;

  let w = whiteTime;
  let b = blackTime;

  if (turn === "w") {
    w = whiteTime - elapsed;
  } else {
    b = blackTime - elapsed;
  }

  if (blackTime <= 0 || whiteTime <= 0) {
    clearInterval(countDown);
    return;
  } else {
    whiteClock.innerHTML = formateTime(w);
    blackClock.innerHTML = formateTime(b);
  }
}, 100)



function showPopUp(data) {
  const modal = document.getElementById("gameOverModal");
  const result = document.getElementById("resultText");
  const loser = document.getElementById("loserText");

  const youWin = data.winner === myColor;

  result.innerText = youWin ? "You Win!" : "You Lose";
  loser.innerText = youWin ? "Opponent lost " + data.reason : "You lost " + data.reason;

  modal.style.display = "flex";
  document.querySelector('.exit').addEventListener("click", () => {
    modal.style.display = "none";
  });
}


ws.onmessage = (event) => {
  const data = JSON.parse(event.data);

  if (data.type === "game_update") {
    whiteTime = data.white;
    blackTime = data.black;
    turn = data.turn;
    lastSync = Date.now();
    chess.load(data.FEN);
    renderBoard();
  }

  if (data.type === "timeout") {
    clearInterval(countDown);
    showPopUp(data);
  }

  if (data.type === "ask_rematch") {
    document.getElementById("rematch").style.display = "none";
    document.getElementById("new-game").style.display = "none";
    document.getElementById("accept").style.display = "inline-block";
    document.getElementById("decline").style.display = "inline-block";

    document.getElementById("accept").onclick = () => {
      ws.send(JSON.stringify({
        type: "rematch_response",
        room: ROOM_ID,
        id: PLAYER_ID,
        accept: "true"
      }));
    }

    document.getElementById("decline").onclick = () => {
      ws.send(JSON.stringify({
        type: "rematch_response",
        room: ROOM_ID,
        id: PLAYER_ID,
        accept: "false"
      }));
      document.getElementById("rematch").style.display = "inline-block";
      document.getElementById("new-game").style.display = "inline-block";
      document.getElementById("accept").style.display = "none";
      document.getElementById("decline").style.display = "none";
    }
  }

  if (data.type === "rematch_declined") {
    document.querySelector(".message").innerText = "Opponent declined the rematch.";
    document.getElementById("rematch").disabled = true;
  }

  if (data.type === "start_game") {
    window.location.href = data.url;
  }
};


ws.onopen = () => {
  ws.send(JSON.stringify({
    type: 'join_room',
    room: ROOM_ID,
    id: PLAYER_ID
  }))
}


if (PLAYER_COLOR == "black") {
  flipBoard()
}

renderBoard();