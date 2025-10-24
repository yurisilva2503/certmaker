const dropdownMenu = document.getElementById("dropdown-user-menu");
const dropdownToggle = document.querySelector(".user-menu > .dropdown-toggle");

dropdownToggle.addEventListener("hide.bs.dropdown", function (event) {
  const mouseNoMenu = dropdownMenu.matches(":hover");
  if (mouseNoMenu) {
    event.preventDefault();
  }
});
