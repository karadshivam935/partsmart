
let currentProductId = null;


function handleAddToCart(productId, productName, isLoggedIn, minOrderQty, stockQty) {
  if (!isLoggedIn) {
    showLoginAlert();
  } else {
    openCartDialog(productId, productName, minOrderQty, stockQty);
  }
}

function openCartDialog(productId, productName, minOrderQty, stockQty) {
  currentProductId = productId;
  // Store minimum order quantity and stock quantity
  window.currentMinOrderQty = minOrderQty;
  window.currentStockQty = stockQty;

  document.getElementById('dialogOverlay').style.display = 'block';
  document.getElementById('cartDialog').style.display = 'block';
  document.getElementById('quantity').value = minOrderQty; // Default to min order qty
}

function showLoginAlert() {
  document.getElementById('loginAlert').style.display = 'block';
  document.getElementById('dialogOverlay').style.display = 'block';
}

function redirectToLogin() {
  // Store the current page URL to redirect back after login
  window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
}


function closeCartDialog() {
  document.getElementById('dialogOverlay').style.display = 'none';
  document.getElementById('cartDialog').style.display = 'none';
  document.getElementById('loginAlert').style.display = 'none';
}

function addToCart() {
  const quantity = parseInt(document.getElementById('quantity').value);

  // Check if the quantity is valid
  if (quantity < window.currentMinOrderQty) {
    alert(`Minimum order quantity is ${window.currentMinOrderQty}`);
    return;
  }

  if (quantity > window.currentStockQty) {
    alert(`Maximum stock available is ${window.currentStockQty}`);
    return;
  }

  // Static handling of add to cart
  alert(`Added ${quantity} item(s) to cart successfully!`);
  closeCartDialog();
}

// Close dialogs when clicking overlay
document.getElementById('dialogOverlay').addEventListener('click', closeCartDialog);

// Close dialogs when pressing ESC key
document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    closeCartDialog();
  }
});

// Prevent dialog close when clicking inside the dialog
document.getElementById('cartDialog').addEventListener('click', function (event) {
  event.stopPropagation();
});

document.getElementById('loginAlert').addEventListener('click', function (event) {
  event.stopPropagation();
});

// Initialize selected filter values
document.addEventListener('DOMContentLoaded', function () {
  const urlParams = new URLSearchParams(window.location.search);

  // Set category
  const category = urlParams.get('category');
  if (category) {
    document.querySelector('select[name="category"]').value = category;
  }

  // Set material
  const material = urlParams.get('material');
  if (material) {
    document.querySelector('select[name="material"]').value = material;
  }

  // Set price range
  const minPrice = urlParams.get('min_price');
  if (minPrice) {
    document.querySelector('input[name="min_price"]').value = minPrice;
  }

  const maxPrice = urlParams.get('max_price');
  if (maxPrice) {
    document.querySelector('input[name="max_price"]').value = maxPrice;
  }
});
