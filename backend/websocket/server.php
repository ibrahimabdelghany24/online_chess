<?php

require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

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
            "test",
            "white",
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
            'url' => '../backend/api/game.php?game_id=' . $roomId
          ]));
        }
      } else {
        $this->queue[] = $player;
      }
    }

    if ($data['type'] === 'move') {
      $roomId = $data['room'];
      $FEN = $data["FEN"];
      $moves = $data["moves"];
      $turn = ($data["turn"] == "w") ? "white" : "black";
      $conn->room = $roomId;
      global $con;
      $stmt = $con->prepare("UPDATE matches SET board = ?, moves = ?, turn = ? WHERE id = ?");
      $stmt->execute([$FEN, $moves, $turn, $roomId]);
      if (isset($this->rooms[$roomId])) {
        foreach ($this->rooms[$roomId] as $player) {
          if ($player['conn'] !== $conn) {  // send to opponent only
            $player['conn']->send(json_encode([
              'type' => 'move',
              'move' => $data['move']
            ]));
          }
        }
      }
    }

    if ($data['type'] === 'join_room') {
      $roomId = $data['room'];
      if (!isset($this->rooms[$roomId])) {
        $this->rooms[$roomId] = [];
      }

      $this->rooms[$roomId][] = ["conn" => $conn];
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
        foreach ($this->rooms[$roomId] as $player) {
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
