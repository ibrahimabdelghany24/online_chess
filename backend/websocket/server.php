<?php

require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

include "../db.php";
class GameServer implements MessageComponentInterface
{

  private array $clients = [];
  private array $queue = [];
  private array $rooms = [];

  public function __construct() {}

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
        'id' => $data['id']
      ];

      $match = $this->findMatch($player);
      // TODO: insert the data into the database only send the room id, url only
      if ($match) {
        $roomId = uniqid("");
        $color = rand(0, 1);
        $this->rooms[$roomId] = [
          'joined' => false,
          'time'          => $match[0]['time'],
          'inc'           => $match[0]['inc'],
          $match[0]['id'] => [
            'elo'  => $match[0]['elo'],
            'username' => $match[0]['username'],
            'color' => $color
          ],
          $match[1]['id'] => [
            'elo'  => $match[1]['elo'],
            'username' => $match[1]['username'],
            'color' => abs($color - 1)
          ]
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

        foreach ($this->rooms[$roomId] as $p) {
          if ($p['conn'] !== $conn) {
            $p['conn']->send(json_encode([
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
