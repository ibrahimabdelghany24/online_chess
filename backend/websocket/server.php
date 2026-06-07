<?php

require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;

use Ryanhs\Chess\Chess;

include __DIR__ . "/../db.php";
class GameServer implements MessageComponentInterface
{

  private array $clients = [];
  private array $queue = [];
  private array $rooms = [];

  // When a new player connects
  public function onOpen(ConnectionInterface $conn)
  {
    $this->clients[spl_object_id($conn)] = $conn;
  }

  // When a message is received from a player
  public function onMessage(ConnectionInterface $conn, $msg)
  {

    $data = json_decode($msg, true);
    echo "========================\n";
    echo "\n";
    print_r($data);
    echo "\n";
    foreach ($this->rooms as $i => $value) {
      echo $i . "\n";
    }
    echo "=========================\n";
    if (!$data || !isset($data['type'])) return;


    // push to queue
    if ($data['type'] === 'join_queue') {
      $player = [
        'conn' => $conn,
        'elo'  => $data['elo'],
        'time' => $data['time'],
        'inc'  => $data['inc'],
        'username' => $data['username'],
        'id' => $data['id'],
        'type' => $data['game_type']
      ];
      $match = $this->findMatch($player);
      // TODO: insert the data into the database only send the room id, url only
      if ($match) {
        $player1 = $match[0];
        $player2 = $match[1];
        $color = rand(0, 1);
        global $con;
        $stmt = $con->prepare(
          "INSERT INTO matches(black, white, type, time, inc, board, turn, moves, status, date)
          VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, now())"
        );
        $stmt->execute(
          [
            ($color) ? $player1["id"] : $player2["id"],
            ($color) ? $player2["id"] : $player1["id"],
            $player1["type"],
            $player1["time"],
            $player1["inc"],
            "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1",
            "w",
            "",
            "waiting"
          ]
        );

        $roomId = $con->lastInsertId();

        $this->rooms[$roomId] = [
          $player1,
          $player2,
        ];
        foreach ($match as $p) {
          $p['conn']->room = $roomId;
          $p['conn']->send(json_encode([
            'type' => 'start_game',
            'room' => $roomId,
            'url' => '../backend/game.php?game_id=' . $roomId
          ]));
        }
      } else {
        $this->queue[$player['id']] = $player;
      }
    }
    // recieve a move
    if ($data['type'] === 'move') {
      global $con;
      $roomId = $data['room'];
      $move = $data["move"];
      $conn->room = $roomId;
      // if it is the first move
      if (!isset($this->rooms[$roomId]["game"])) {
        $stmt = $con->prepare("SELECT time, inc, white, black FROM matches WHERE id = ? LIMIT 1");
        $stmt->execute([$roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $room = &$this->rooms[$roomId];
        $white_con = ($room[0]['id'] == $row["white"]) ? $room[0]['conn'] : $room[1]['conn'];
        $black_con = ($room[0]['id'] == $row["black"]) ? $room[0]['conn'] : $room[1]['conn'];
        $room["game"] = [
          "chess" => new Chess(),
          "white_con" => $white_con,
          "black_con" => $black_con,
          "state" => "started",
          "clock" => [
            "white" => $row["time"] * 60 * 1000,
            "black" => $row["time"] * 60 * 1000,
            "inc" => $row["inc"] * 1000,
            "turn" => "w",
            "last_update" => microtime(true) * 1000
          ]
        ];
      }
      $game = &$this->rooms[$roomId]["game"]["chess"];
      $clock = &$this->rooms[$roomId]["game"]["clock"];
      if (!$game->move($move)) {
        return;
      }

      $now = microtime(true) * 1000;
      $elapsed = $now - $clock["last_update"];
      // subtract time from player who moved
      if ($clock['turn'] === "w") {
        $clock['white'] -= $elapsed;
        $clock['white'] += $clock['inc']; // increment
        $clock['white'] = max(0, $clock['white']);
      } else {
        $clock['black'] -= $elapsed;
        $clock['black'] += $clock['inc'];
        $clock['black'] = max(0, $clock['black']);
      }
      // switch turn
      $clock['turn'] = ($clock['turn'] === "w") ? "b" : "w";
      $clock['last_update'] = $now;
      $FEN = $game->fen();
      $moves = implode(",", $game->history());
      $turn = $game->turn();
      if (isset($this->rooms[$roomId])) {
        for ($i = 0; $i < 2; $i++) {
          $player = $this->rooms[$roomId][$i];
          $player["conn"]->send(json_encode([
            "type" => "game_update",
            "move" => $move,
            "FEN" => $FEN,
            "white" => $clock['white'],
            "black" => $clock['black'],
            "turn" => $clock['turn'],
            "server_time" => microtime(true) * 1000,
          ]));
        }
      }
      $stmt = $con->prepare("UPDATE matches SET board = ?, moves = ?, turn = ? WHERE id = ?");
      $stmt->execute([$FEN, $moves, $turn, $roomId]);
    }

    if ($data['type'] === 'join_room') {
      $roomId = $data['room'];
      $conn->room = $roomId;
      if (!isset($this->rooms[$roomId])) {
        $this->rooms[$roomId] = [];
      }
      $this->rooms[$roomId][] = ["conn" => $conn, "id" => $data['id']];
    }

    if ($data["type"] == "rematch") {
      $roomID = $data["room"];
      $room = &$this->rooms[$roomID];
      $other_player = ($room[0]["conn"] == $conn) ? $room[1]["conn"] : $room[0]["conn"];
      $other_player->send(json_encode([
        "type" => "ask_rematch",
        "id" => $data["id"]
      ])
      );
    }

    if ($data["type"] == "rematch_response") {
      // TODO: have to update the database and create a new id for new game and unset the old game data in the room
      $roomID = $data["room"];
      $room = &$this->rooms[$roomID];
      if ($data["response"] == "accept") {
        $other_player = ($room[0]["conn"] == $conn) ? $room[1]["conn"] : $room[0]["conn"];
        $other_player->send(json_encode([
          "type" => "rematch_accepted",
          "id" => $data["id"]
        ]));
        // reset the game state
        $room["game"]["chess"] = new Chess();
        $room["game"]["state"] = "started";
        // reset the clock
        $room["game"]["clock"]["white"] = 5 * 60 * 1000; // TODO: get from database
        $room["game"]["clock"]["black"] = 5 * 60 * 1000; // TODO: get from database
        $room["game"]["clock"]["turn"] = "w";
        $room["game"]["clock"]["last_update"] = microtime(true) * 1000;
      } else {
        $other_player = ($room[0]["conn"] == $conn) ? $room[1]["conn"] : $room[0]["conn"];
        $other_player->send(json_encode([
          "type" => "rematch_declined",
          "id" => $data["id"]
        ]));
      }
    }
  }


  public function checkTimeouts()
  {
    $now = microtime(true) * 1000;

    foreach ($this->rooms as $roomId => &$room) {

      if (!isset($room["game"])) continue;

      $clock = &$room["game"]["clock"];

      $elapsed = $now - $clock["last_update"];

      $white = $clock["white"];
      $black = $clock["black"];

      // simulate ONLY current side's ticking time
      if ($clock["turn"] === "w") {
        $white -= $elapsed;
      } else {
        $black -= $elapsed;
      }

      if ($white <= 0 || $black <= 0) {

        $winner = ($white <= 0) ? "b" : "w";

        for ($i = 0; $i < 2; $i++) {
          $player = $room[$i];
          if (!isset($player["conn"]) || $room["game"]["state"] == "finished") continue;

          $player["conn"]->send(json_encode([
            "type" => "timeout",
            "winner" => $winner,
            "reason" => "On Time"
          ]));
        }
        $room["game"]["state"] = "finished";
      }
    }
  }

  // When a player disconnects
  public function onClose(ConnectionInterface $conn)
  {
    // remove from queue
    foreach ($this->queue as $i => $p) {
      if ($p['conn'] === $conn) {
        unset($this->queue[$i]);
      }
    }
    // remove from room
    if (isset($conn->room)) {
      $roomId = $conn->room;
      if (isset($this->rooms[$roomId]) && $this->rooms[$roomId]) {
        foreach ($this->rooms[$roomId] as $i => $player) {
          if ($i == "game") continue;
          if ($player["conn"] !== $conn) {
            $player["conn"]->send(json_encode([
              'type' => 'opponent_left'
            ]));
          }
        }
        echo "roomID: " . $roomId . " is deleted\n";
        unset($this->rooms[$roomId]);
      }
    }
    unset($this->clients[spl_object_id($conn)]);
  }

  public function onError(ConnectionInterface $conn, \Exception $e)
  {
    $conn->close();
  }

  // Look for a match in the queue
  private function findMatch(array $newPlayer)
  {
    foreach ($this->queue as $i => $p) {
      if ($p['id'] == $newPlayer['id']) continue;
      if (
        abs($p['elo'] - $newPlayer['elo']) <= 50 &&
        $p['time'] == $newPlayer['time'] &&
        $p['inc']  == $newPlayer['inc']
      ) {
        unset($this->queue[$i]);
        return [$p, $newPlayer];
      }
    }
    return null;
  }
}

$loop = Loop::get();

$gameServer = new GameServer();

$loop->addPeriodicTimer(0.1, function () use ($gameServer) {
  $gameServer->checkTimeouts();
});

$socket = new React\Socket\SocketServer('0.0.0.0:8080', [], $loop);

// Run the server
$server = new IoServer(
  new HttpServer(
    new WsServer($gameServer)
  ),
  $socket,
  $loop
);
echo "WebSocket running on ws://localhost:8080\n";

$loop->run();
