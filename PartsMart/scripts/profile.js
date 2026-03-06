document.addEventListener('DOMContentLoaded', function () {
  // Form validation
  const profileForm = document.getElementById('profileForm');

  profileForm.addEventListener('submit', function (e) {
    e.preventDefault();

    // Get form values
    const phone = document.getElementById('phone').value;
    const pincode = document.getElementById('pincode').value;

    // Validate phone number (Indian format)
    const phoneRegex = /^[6-9]\d{9}$/;
    if (!phoneRegex.test(phone)) {
      alert('Please enter a valid Indian phone number');
      return;
    }

    // Validate Ahmedabad pincode (380001 to 380099)
    const pincodeRegex = /^3800[0-9][0-9]$/;
    if (!pincodeRegex.test(pincode)) {
      alert('Please enter a valid Ahmedabad pincode');
      return;
    }

    // If validation passes, submit the form
    this.submit();
  });
});

function openReview(orderId) {
  // Add your review functionality here
  alert('Review functionality for order: ' + orderId);
}

