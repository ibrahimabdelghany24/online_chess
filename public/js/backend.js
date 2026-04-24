const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const passwordInput2 = document.getElementById("password2");
const regex = /^[a-zA-Z0-9_]*$/;


async function checkUser(username) {
  const response = await fetch("../backend/api/check_user.php?username=" + username);
  const data = await response.json();
  return data.exists;
}

usernameInput.addEventListener("input", async () => {
  exists = await checkUser(usernameInput.value);
  if (exists) {
    usernameInput.setCustomValidity("Username is already taken.");

  } else if (usernameInput.value.length < 3) {
    usernameInput.setCustomValidity("Username must be at least 3 characters long.");

  } else if (!regex.test(usernameInput.value)) {
    usernameInput.setCustomValidity("Username can only contain letters, numbers, and underscores.");

  } else {
    usernameInput.setCustomValidity("");
  }
  usernameInput.reportValidity();
});

passwordInput.addEventListener("input", () => {
  if (passwordInput.value.length < 8) {
    passwordInput.setCustomValidity("Password must be at least 8 characters long.");
  } else {
    passwordInput.setCustomValidity("");
  }
  passwordInput.reportValidity();
});

passwordInput2.addEventListener("input", () => {
  if (passwordInput2.value !== passwordInput.value) {
    passwordInput2.setCustomValidity("Passwords do not match.");
  } else {
    passwordInput2.setCustomValidity("");
  }
});