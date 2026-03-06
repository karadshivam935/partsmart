document.addEventListener("DOMContentLoaded", function () {
    const profileLink = document.getElementById("profile-link");
    const ordersLink = document.getElementById("orders-link");
    const logoutLink = document.getElementById("logout-link");
    const iframe = document.getElementById("content-frame");

    if (!iframe) {
        console.error("No iframe found. Check if it's added to delivery.php.");
        return;
    }

    profileLink.addEventListener("click", (event) => {
        event.preventDefault(); // Prevent default page reload
        iframe.src = "login-manage.php";
    });

    ordersLink.addEventListener("click", (event) => {
        event.preventDefault();
        iframe.src = "OrderStatus.php";
    });

    logoutLink.addEventListener("click", (event) => {
        event.preventDefault();
        console.log("Logging out...");
        window.location.href = "login.php"; // Redirect to login page
    });
});
