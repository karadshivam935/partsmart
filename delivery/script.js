
const profileLink = document.getElementById('profile-link');
const ordersLink = document.getElementById('orders-link');
const logoutLink = document.getElementById('logout-link');
const iframe = document.getElementById('content-frame');

// Add event listeners for each link to load the corresponding content

profileLink.addEventListener('click', () => {
    iframe.src = "login-manage.html";  // Load Profile page
});

ordersLink.addEventListener('click', () => {
    iframe.src = "OrderStatus.html";  // Load Orders page
});





logoutLink.addEventListener('click', () => {
    // Implement logout functionality here
    console.log("Logging out...");
});