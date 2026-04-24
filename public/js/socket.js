const socket = new WebSocket("ws://localhost:8080/game");

// when message received
socket.onopen = () => {
  socket.send("hello");
};

socket.onmessage = (event) => {
  console.log("Received:", event.data);

  // update chess board here

};