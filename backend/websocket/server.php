<?php

require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
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
        $this->queue[] = $player;
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
        $white_con = ($room[0]['id'] == $row["white"])? $room[0]['conn'] : $room[1]['conn'];
        $black_con = ($room[0]['id'] == $row["black"])? $room[0]['conn'] : $room[1]['conn'];

        $room["game"] = [
          "chess" => new Chess(),
          "white_con" => $white_con,
          "black_con" => $black_con,
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

      // if ($clock["white"] <= 0) {
      //   return;
      // }

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
      if (!isset($this->rooms[$roomId])) {
        $this->rooms[$roomId] = [];
      }
      $this->rooms[$roomId][] = ["conn" => $conn, "id" => $data['id']];
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

// Run the server
$server = IoServer::factory(
  new HttpServer(
    new WsServer(
      new GameServer()
    )
  ),
  8080
);

echo "WebSocket running on ws://localhost:8080\n";

$server->run();
