<?php
session_start();

// Include the database connection file
require '../config/dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['Email_id'])) {
  header('Location: login.php'); // Redirect to login if not logged in
  exit;
}

$email = $_SESSION['Email_id'];

// Handle Review Submission, Editing, and Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = $_POST['order_id'];
    $productId = $_POST['product_id'];
    $reviewText = $_POST['review_text'];

    if ($action === 'submit') {
      // Fetch the maximum Review_Id from the review_tbl
      $sqlMaxId = "SELECT MAX(Review_Id) AS max_id FROM review_tbl";
      $stmtMaxId = $conn->prepare($sqlMaxId);
      $stmtMaxId->execute();
      $resultMaxId = $stmtMaxId->get_result();
      $rowMaxId = $resultMaxId->fetch_assoc();
      $nextReviewId = $rowMaxId['max_id'] + 1; // Increment the maximum Review_Id by 1

      // Insert new review with the calculated Review_Id
      $sql = "INSERT INTO review_tbl (Review_Id, Email_Id, Product_Id, Review_Text, Review_Date) VALUES (?, ?, ?, ?, NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("isis", $nextReviewId, $email, $productId, $reviewText);
      $stmt->execute();
    } elseif ($action === 'edit') {
      // Update existing review
      $sql = "UPDATE review_tbl SET Review_Text = ?, Review_Date = NOW() WHERE Email_Id = ? AND Product_Id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssi", $reviewText, $email, $productId);
      $stmt->execute();
    } elseif ($action === 'delete') {
      // Delete review
      $sql = "DELETE FROM review_tbl WHERE Email_Id = ? AND Product_Id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $email, $productId);
      $stmt->execute();
    }
  }
}

// Fetch the user's orders from the database
$sql = "
    SELECT o.Order_Id, o.Order_Date, o.Total_Amount, o.Order_Status, 
           o.Delivery_Address, o.Delivery_Pincode, o.Delivery_Date
    FROM order_tbl o
    WHERE o.Email_Id = ?
    ORDER BY o.Order_Date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
  $orderId = $row['Order_Id'];

  // Fetch order items for this order
  $sqlItems = "
        SELECT p.Product_Name, od.Qty, od.Unit_Price, od.Sub_Total, p.Product_Id
        FROM order_detail_tbl od
        JOIN product_details_tbl pd ON od.Pdt_Det_Id = pd.Pdt_Det_Id
        JOIN product_tbl p ON pd.Product_Id = p.Product_Id
        WHERE od.Order_Id = ?
    ";
  $stmtItems = $conn->prepare($sqlItems);
  $stmtItems->bind_param("i", $orderId);
  $stmtItems->execute();
  $resultItems = $stmtItems->get_result();

  $items = [];
  while ($itemRow = $resultItems->fetch_assoc()) {
    // Fetch reviews for each product in the order
    $sqlReview = "SELECT Review_Text, Review_Date FROM review_tbl WHERE Email_Id = ? AND Product_Id = ?";
    $stmtReview = $conn->prepare($sqlReview);
    $stmtReview->bind_param("si", $email, $itemRow['Product_Id']);
    $stmtReview->execute();
    $resultReview = $stmtReview->get_result();
    $review = $resultReview->fetch_assoc();

    $itemRow['review'] = $review;
    $items[] = $itemRow;
  }

  // Add items to the order
  $row['items'] = $items;

  // Determine if the order can be canceled
  $row['can_cancel'] = ($row['Order_Status'] === 'Processing');

  // Calculate expected delivery date if not delivered
  if ($row['Order_Status'] !== 'Delivered') {
    $orderDate = new DateTime($row['Order_Date']);
    $orderDate->modify('+3 days');
    $row['Expected_Delivery_Date'] = $orderDate->format('Y-m-d');
  } else {
    $row['Expected_Delivery_Date'] = $row['Delivery_Date'];
  }

  // Add the order to the list
  $orders[] = $row;
}

// Pagination
$orders_per_page = 2;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$total_pages = ceil(count($orders) / $orders_per_page);
$start_index = ($current_page - 1) * $orders_per_page;
$current_orders = array_slice($orders, $start_index, $orders_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body,
    html {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      overflow-x: hidden;
      font-family: Poppins, sans-serif;
    }

    body::-webkit-scrollbar {
      display: none
    }

    .orders-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .order-card {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      background-color: white;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #e0e0e0;
    }

    .order-status {
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 500;
    }

    .status-delivered {
      background-color: #e8f5e9;
      color: #2e7d32;
    }

    .status-processing {
      background-color: #fff3e0;
      color: #ef6c00;
    }

    .status-processed {
      background-color: #e3f2fd;
      color: #1565c0;
    }

    .status-cancelled {
      background-color: #ffebee;
      color: #c62828;
    }

    .order-items {
      margin: 15px 0;
    }

    .item-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .pagination {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
    }

    .pagination a {
      padding: 8px 12px;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      text-decoration: none;
      color: #373737;
    }

    .pagination a.active {
      background-color: #373737;
      color: white;
    }

    .button {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: background-color 0.3s;
    }

    .button-primary {
      background-color: #373737;
      color: white;
    }

    .button-danger {
      background-color: #dc3545;
      color: white;
    }

    .review-section {
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid #e0e0e0;
    }

    .review-form textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      margin-bottom: 10px;
      font-family: 'Poppins', sans-serif;
    }

    .review-content {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 4px;
      margin-top: 10px;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }

    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 90%;
      max-width: 400px;
      text-align: center;
    }

    .modal-buttons {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
    }
  </style>
  <link rel="stylesheet" href="../styles/navbar.css">
  <link rel="stylesheet" href="../styles/footer.css" />
</head>

<body>
  <?php include('header.php') ?>
  <div class="orders-container">
    <h1 style="margin-bottom: 30px;">My Orders</h1>

    <?php if (empty($current_orders)): ?>
      <div class="empty-cart">
        <p>No orders found.</p>
        <a href="products.php" class="checkout-btn">Continue Shopping</a>
      </div>
    <?php else: ?>
      <?php foreach ($current_orders as $order): ?>
        <div class="order-card" id="order-<?php echo $order['Order_Id']; ?>">
          <div class="order-header">
            <div>
              <p>Ordered on: <?php echo date('d M Y', strtotime($order['Order_Date'])); ?></p>
            </div>
            <span class="order-status status-<?php echo strtolower($order['Order_Status']); ?>"
              id="status-<?php echo $order['Order_Id']; ?>">
              <?php echo $order['Order_Status']; ?>
            </span>
          </div>

          <div class="order-items">
            <?php foreach ($order['items'] as $item): ?>
              <div class="item-row">
                <div>
                  <p><?php echo $item['Product_Name']; ?></p>
                  <small>Qty: <?php echo $item['Qty']; ?></small>
                </div>
                <div>₹<?php echo number_format($item['Sub_Total'], 2); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div style="margin-top: 15px;">
            <p><strong>Delivery Address:</strong></p>
            <p><?php echo $order['Delivery_Address']; ?> - <?php echo $order['Delivery_Pincode']; ?></p>
            <p id="delivery-date-<?php echo $order['Order_Id']; ?>">
              <?php if ($order['Order_Status'] === 'Delivered'): ?>
                <strong>Delivered on:</strong> <?php echo date('d M Y', strtotime($order['Delivery_Date'])); ?>
              <?php else: ?>
                <strong>Expected Delivery:</strong> <?php echo date('d M Y', strtotime($order['Expected_Delivery_Date'])); ?>
              <?php endif; ?>
            </p>
          </div>

          <div style="margin-top: 15px; display: flex; gap: 10px;">
            <button class="button button-primary" onclick="downloadInvoice(<?php echo $order['Order_Id']; ?>)">
              Download Invoice
            </button>

            <?php if ($order['can_cancel']): ?>
              <button class="button button-danger" id="cancel-btn-<?php echo $order['Order_Id']; ?>"
                onclick="showCancelConfirmation(<?php echo $order['Order_Id']; ?>)">
                Cancel Order
              </button>
            <?php endif; ?>
          </div>

          <?php if ($order['Order_Status'] === 'Delivered'): ?>
            <div class="review-section" id="review-section-<?php echo $order['Order_Id']; ?>">
              <h4>Product Review</h4>
              <?php foreach ($order['items'] as $item): ?>
                <?php if (isset($item['review'])): ?>
                  <div class="review-content" id="review-content-<?php echo $item['Product_Id']; ?>">
                    <p><?php echo $item['review']['Review_Text']; ?></p>
                    <small>Reviewed on: <?php echo date('d M Y', strtotime($item['review']['Review_Date'])); ?></small>
                    <div style="margin-top: 10px;">
                      <button class="button button-primary" onclick="editReview(<?php echo $item['Product_Id']; ?>)">
                        Edit Review
                      </button>
                      <button class="button button-danger" onclick="deleteReview(<?php echo $item['Product_Id']; ?>)">
                        Delete Review
                      </button>
                    </div>
                  </div>
                  <div class="review-form" id="review-form-<?php echo $item['Product_Id']; ?>" style="display: none;">
                    <textarea placeholder="Share your experience..." rows="3"
                      id="review-text-<?php echo $item['Product_Id']; ?>"></textarea>
                    <button class="button button-primary" onclick="submitReview(<?php echo $item['Product_Id']; ?>)">
                      Submit Review
                    </button>
                  </div>
                <?php else: ?>
                  <div class="review-form" id="review-form-<?php echo $item['Product_Id']; ?>">
                    <textarea placeholder="Share your experience..." rows="3"
                      id="review-text-<?php echo $item['Product_Id']; ?>"></textarea>
                    <button class="button button-primary" onclick="submitReview(<?php echo $item['Product_Id']; ?>)">
                      Submit Review
                    </button>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?php echo $i; ?>" <?php if ($i === $current_page)
               echo 'class="active"'; ?>>
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Cancel Confirmation Modal -->
  <div id="cancelModal" class="modal">
    <div class="modal-content">
      <h3>Cancel Order</h3>
      <p>Are you sure you want to cancel this order?</p>
      <div class="modal-buttons">
        <button class="button button-danger" id="confirmCancel">Yes, Cancel Order</button>
        <button class="button button-primary" onclick="closeCancelModal()">No, Keep Order</button>
      </div>
    </div>
  </div>
  <?php include 'footer.php' ?>

  <script>
    let currentCancelOrderId = null;

    function downloadInvoice(orderId) {
      alert('Downloading invoice for Order #' + orderId);
      // Add actual invoice download logic here
    }

    function showCancelConfirmation(orderId) {
      currentCancelOrderId = orderId;
      document.getElementById('cancelModal').style.display = 'block';
      document.getElementById('confirmCancel').onclick = function () {
        cancelOrder(orderId);
      };
    }

    function closeCancelModal() {
      document.getElementById('cancelModal').style.display = 'none';
      currentCancelOrderId = null;
    }

    function cancelOrder(orderId) {
      // Update status to cancelled
      const statusElement = document.getElementById('status-' + orderId);
      statusElement.textContent = 'Cancelled';
      statusElement.className = 'order-status status-cancelled';

      // Hide cancel button
      const cancelButton = document.getElementById('cancel-btn-' + orderId);
      if (cancelButton) {
        cancelButton.style.display = 'none';
      }

      // Close modal
      closeCancelModal();
    }

    function submitReview(productId) {
      const reviewText = document.getElementById('review-text-' + productId).value.trim();
      if (reviewText) {
        const formData = new FormData();
        formData.append('action', 'submit');
        formData.append('order_id', currentCancelOrderId);
        formData.append('product_id', productId);
        formData.append('review_text', reviewText);

        fetch(window.location.href, {
          method: 'POST',
          body: formData
        }).then(response => {
          if (response.ok) {
            location.reload(); // Reload the page to reflect the new review
          }
        });
      }
    }

    function editReview(productId) {
      // Get the review text from the review content div
      const reviewContent = document.getElementById('review-content-' + productId);
      const reviewText = reviewContent.querySelector('p').textContent;

      // Show review form
      const reviewForm = document.getElementById('review-form-' + productId);
      reviewForm.style.display = 'block';
      document.getElementById('review-text-' + productId).value = reviewText;

      // Hide review content
      reviewContent.style.display = 'none';
    }

    function deleteReview(productId) {
      if (confirm('Are you sure you want to delete this review?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('order_id', currentCancelOrderId);
        formData.append('product_id', productId);

        fetch(window.location.href, {
          method: 'POST',
          body: formData
        }).then(response => {
          if (response.ok) {
            location.reload(); // Reload the page to reflect the deleted review
          }
        });
      }
    }
    const reviewText = document.getElementById('review-text-' + productId).value.trim();
    if (reviewText) {
      const formData = new FormData();
      formData.append('action', 'submit');
      formData.append('order_id', currentCancelOrderId);
      formData.append('product_id', productId);
      formData.append('review_text', reviewText);

      fetch(window.location.href, {
        method: 'POST',
        body: formData
      }).then(response => {
        if (response.ok) {
          location.reload(); // Reload the page to reflect the new review
        }
      });
    }




    // Close modal when clicking outside
    window.onclick = function (event) {
      const modal = document.getElementById('cancelModal');
      if (event.target === modal) {
        closeCancelModal();
      }
    }
  </script>
  <script src="../scripts/navbar.js"></script>
</body>



</html>