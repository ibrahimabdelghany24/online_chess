const socket = new WebSocket("ws://localhost:8080");


async function getData(username) {
  const res = await fetch("../backend/api/check_user.php?username=" + username);
  return await res.json();
}


document.querySelectorAll(".game-options a").forEach(el => {
  el.addEventListener("click", (e) => {
    e.preventDefault();
    const time = el.getAttribute("data-time");
    const inc = el.getAttribute("data-inc");
    const eloType = el.getAttribute("data-elo");
    getData(username).then(data => {
      console.log(data);
      socket.send(JSON.stringify({
        type: "join_queue",
        username: data.username,
        id: data.id,
        time: time,
        inc: inc,
        elo: data[eloType + "_rating"],
      }));
    });
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