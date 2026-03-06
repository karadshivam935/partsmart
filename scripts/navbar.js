document.addEventListener("DOMContentLoaded", function () {
  var searchForm = document.getElementById("search-form");
  var searchButton = document.getElementById("search-button");

  searchButton.addEventListener("click", function (event) {
    if (!isLoggedIn) {
      event.preventDefault(); // Prevent the form from submitting
      alert("Please log in to search for products.");
      window.location.href = "login.php"; // Redirect to login page
    }
  });
});
