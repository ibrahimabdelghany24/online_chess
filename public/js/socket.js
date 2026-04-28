const socket = new WebSocket("ws://localhost:8080");

document.querySelectorAll(".game-options a").forEach(el => {
  el.addEventListener("click", (e) => {
    e.preventDefault();
    const time = el.getAttribute("data-time");
    const inc = el.getAttribute("data-inc");
    const elo = el.getAttribute("data-elo");

    socket.send(JSON.stringify({
      type: "join_queue",
      time,
      inc,
      elo
    }));
    el.parentElement.querySelectorAll("a").forEach(a => {
      a.style.pointerEvents = "none";
      a.style.visibility = "hidden";
    });
    const msg = el.parentElement.querySelector(".msg");
    if (msg) {
      msg.style.visibility = "visible";
      msg.style.top = "50%";
    }
  });
});

socket.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log(data);
  if (data.type === "start_game") {
    window.location.href = data.url;
  }
}