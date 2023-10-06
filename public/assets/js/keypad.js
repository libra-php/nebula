// Keypad 
if (typeof keypad_key === 'undefined') {
  let keypad_key = document.querySelectorAll("#keypad > button");
  keypad_key.forEach((key) => {
    key.addEventListener("click", (e) => {
      const keyPress = (e) => {
        var input = document.getElementById("code");
        if (input.value.length < 6) {
          input.value = input.value + e.target.value;
        }
      };
      const bsPress = (e) => {
        var input = document.getElementById("code");
        if (input.value.length > 0) {
          input.value = input.value.slice(0, -1);
        }
      };
      if (key.value === "bs") {
        bsPress(e);
      } else if (key.value === "ent") {
        const button = document.getElementById("two-factor-submit");
        button.click();
      } else {
        keyPress(e);
      }
      const target = e.currentTarget;
      const classlist = target.classList;
      classlist.add("active");
      setTimeout(() => {
        classlist.remove("active");
      }, 100);
    });
  });
}
