function registrationSuccess() {
  let proceed = confirm(
    "Your account was successfully created!\n\nWould you like to continue to login?",
  );

  if (proceed) {
    window.location.href = "login.php";
  } else {
    window.location.href = "register.php";
  }
}

//otp
document.addEventListener("DOMContentLoaded", () => {
  const fields = document.querySelectorAll(".otp-field");
  const finalInput = document.getElementById("final-otp");
  const form = document.getElementById("otp-form");

  if (form) {
    fields.forEach((field, index) => {
      field.addEventListener("input", (e) => {
        if (e.target.value && index < fields.length - 1) {
          fields[index + 1].focus();
        }
      });

      field.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !e.target.value && index > 0) {
          fields[index - 1].focus();
        }
      });
    });

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      let combinedCode = "";
      fields.forEach((f) => {
        combinedCode += f.value;
      });
      finalInput.value = combinedCode;
      form.submit();
    });
  }
});

//inactvity
let inactivityTimer;

function resetTimer() {
  clearTimeout(inactivityTimer);
  inactivityTimer = setTimeout(showInactivityModal, 900000);
}

function showInactivityModal() {
  let stay = confirm(
    "Are you still there? You've been inactive for a while. \n\nClick OK to stay, or Cancel to sign out.",
  );

  if (!stay) {
    window.location.href = "../auth/logout.php?reason=inactive";
  }
}

window.onload = resetTimer;
document.onmousemove = resetTimer;
document.onkeypress = resetTimer;
