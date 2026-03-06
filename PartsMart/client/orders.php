<?php
session_start();

// Include the database connection file
require '../config/dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['Email_id'])) {
  header('Location: login.php');
  exit;
}

$email = $_SESSION['Email_id'];

// Handle Review CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  $orderId = (int) $_POST['order_id'];
  $productId = (int) $_POST['product_id'];
  $reviewText = trim($_POST['review_text']);

  // Validate review text length
  if (strlen($reviewText) > 255) {
    echo "<script>alert('Review text must not exceed 255 characters.');</script>";
    exit;
  }

  if ($action === 'submit') {
    // Check if the product is part of a delivered order for this user
    $sqlCheck = "
            SELECT COUNT(*) 
            FROM order_tbl o
            JOIN order_detail_tbl od ON o.Order_Id = od.Order_Id
            JOIN product_details_tbl pd ON od.Pdt_Det_Id = pd.Pdt_Det_Id
            WHERE o.Email_Id = ? AND pd.Product_Id = ? AND o.Order_Status = 'Delivered'
        ";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("si", $email, $productId);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($count == 0) {
      echo "<script>alert('You can only review products from delivered orders.');</script>";
      exit;
    }

    // Check if a review already exists
    $sqlExists = "SELECT Review_Id FROM review_tbl WHERE Email_Id = ? AND Product_Id = ?";
    $stmtExists = $conn->prepare($sqlExists);
    $stmtExists->bind_param("si", $email, $productId);
    $stmtExists->execute();
    $resultExists = $stmtExists->get_result();
    if ($resultExists->num_rows > 0) {
      echo "<script>alert('You have already reviewed this product.');</script>";
      exit;
    }
    $stmtExists->close();

    // Get next Review_Id
    $sqlMaxId = "SELECT MAX(Review_Id) AS max_id FROM review_tbl";
    $stmtMaxId = $conn->prepare($sqlMaxId);
    $stmtMaxId->execute();
    $resultMaxId = $stmtMaxId->get_result();
    $rowMaxId = $resultMaxId->fetch_assoc();
    $nextReviewId = ($rowMaxId['max_id'] ?? 0) + 1;
    $stmtMaxId->close();

    // Insert new review
    $sql = "INSERT INTO review_tbl (Review_Id, Email_Id, Product_Id, Review_Text, Review_Date) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $nextReviewId, $email, $productId, $reviewText);
    if ($stmt->execute()) {
      echo "<script>alert('Review submitted successfully!'); location.reload();</script>";
    } else {
      echo "<script>alert('Error submitting review: " . $stmt->error . "');</script>";
    }
    $stmt->close();
  } elseif ($action === 'edit') {
    $sql = "UPDATE review_tbl SET Review_Text = ?, Review_Date = NOW() WHERE Email_Id = ? AND Product_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $reviewText, $email, $productId);
    if ($stmt->execute()) {
      echo "<script>alert('Review updated successfully!'); location.reload();</script>";
    } else {
      echo "<script>alert('Error updating review: " . $stmt->error . "');</script>";
    }
    $stmt->close();
  } elseif ($action === 'delete') {
    $sql = "DELETE FROM review_tbl WHERE Email_Id = ? AND Product_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $productId);
    if ($stmt->execute()) {
      echo "<script>alert('Review deleted successfully!'); location.reload();</script>";
    } else {
      echo "<script>alert('Error deleting review: " . $stmt->error . "');</script>";
    }
    $stmt->close();
  }
  exit; // Stop further processing after review actions
}

// Handle Date Filter
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$dateFilterApplied = !empty($startDate) && !empty($endDate);

// Fetch the user's orders
$sql = "
    SELECT o.Order_Id, o.Order_Date, o.Total_Amount, o.Order_Status, 
           o.Delivery_Address, o.Delivery_Pincode, o.Delivery_Date
    FROM order_tbl o
    WHERE o.Email_Id = ?
";
if ($dateFilterApplied) {
  $sql .= " AND o.Order_Date BETWEEN ? AND ?";
}
$sql .= " ORDER BY o.Order_Date DESC";

$stmt = $conn->prepare($sql);
if ($dateFilterApplied) {
  $stmt->bind_param("sss", $email, $startDate, $endDate);
} else {
  $stmt->bind_param("s", $email);
}
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
  $orderId = $row['Order_Id'];

  // Fetch order items
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
    // Fetch review for this product
    $sqlReview = "SELECT Review_Text, Review_Date FROM review_tbl WHERE Email_Id = ? AND Product_Id = ?";
    $stmtReview = $conn->prepare($sqlReview);
    $stmtReview->bind_param("si", $email, $itemRow['Product_Id']);
    $stmtReview->execute();
    $resultReview = $stmtReview->get_result();
    $review = $resultReview->fetch_assoc();
    $stmtReview->close();

    $itemRow['review'] = $review;
    $items[] = $itemRow;
  }
  $stmtItems->close();

  $row['items'] = $items;
  $row['can_cancel'] = ($row['Order_Status'] === 'Processing');

  if ($row['Order_Status'] !== 'Delivered') {
    $orderDate = new DateTime($row['Order_Date']);
    $orderDate->modify('+3 days');
    $row['Expected_Delivery_Date'] = $orderDate->format('Y-m-d');
  } else {
    $row['Expected_Delivery_Date'] = $row['Delivery_Date'];
  }

  $orders[] = $row;
}

// Limit to 2 most recent orders initially if no filter
if (!$dateFilterApplied && count($orders) > 2) {
  $orders = array_slice($orders, 0, 2);
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
      display: none;
    }

    .orders-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
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

    .status-out-for-delivery {
      background-color: #fff9c4;
      color: #f9a825;
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
      resize: vertical;
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

    .filter-form {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .filter-form label {
      font-weight: 500;
    }

    .filter-form input[type="date"] {
      padding: 8px;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
    }

    .filter-form button {
      padding: 8px 16px;
      background-color: #373737;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
  </style>
  <link rel="stylesheet" href="../styles/navbar.css">
  <link rel="stylesheet" href="../styles/footer.css" />
</head>

<body>
  <?php include('header.php') ?>
  <div class="orders-container">
    <h1 style="margin-bottom: 20px; margin-top: 75px;">My Orders</h1>

    <!-- Date Filter Form -->
    <form class="filter-form" method="GET">
      <label for="start_date">Start Date:</label>
      <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
      <label for="end_date">End Date:</label>
      <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
      <button type="submit">Filter</button>
    </form>

    <?php if (empty($current_orders)): ?>
      <div class="empty-cart">
        <p>No orders found<?php echo $dateFilterApplied ? ' for the selected date range' : ''; ?>.</p>
        <a href="products.php" class="checkout-btn">Continue Shopping</a>
      </div>
    <?php else: ?>
      <?php foreach ($current_orders as $order): ?>
        <div class="order-card" id="order-<?php echo $order['Order_Id']; ?>">
          <div class="order-header">
            <div>
              <p>Ordered on: <?php echo date('d M Y', strtotime($order['Order_Date'])); ?></p>
            </div>
            <span class="order-status status-<?php echo strtolower(str_replace(' ', '-', $order['Order_Status'])); ?>"
              id="status-<?php echo $order['Order_Id']; ?>">
              <?php echo $order['Order_Status']; ?>
            </span>
          </div>

          <div class="order-items">
            <?php foreach ($order['items'] as $item): ?>
              <div class="item-row">
                <div>
                  <p><?php echo htmlspecialchars($item['Product_Name']); ?></p>
                  <small>Qty: <?php echo $item['Qty']; ?></small>
                </div>
                <div>₹<?php echo number_format($item['Sub_Total'], 2); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div style="margin-top: 15px;">
            <p><strong>Delivery Address:</strong></p>
            <p><?php echo htmlspecialchars($order['Delivery_Address']); ?> - <?php echo $order['Delivery_Pincode']; ?></p>
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
              <h4>Product Reviews</h4>
              <?php foreach ($order['items'] as $item): ?>
                <div style="margin-bottom: 20px;">
                  <p><strong><?php echo htmlspecialchars($item['Product_Name']); ?></strong></p>
                  <?php if (isset($item['review'])): ?>
                    <div class="review-content" id="review-content-<?php echo $item['Product_Id']; ?>">
                      <p><?php echo htmlspecialchars($item['review']['Review_Text']); ?></p>
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
                      <textarea placeholder="Edit your review..." rows="3"
                        id="review-text-<?php echo $item['Product_Id']; ?>"><?php echo htmlspecialchars($item['review']['Review_Text']); ?></textarea>
                      <button class="button button-primary"
                        onclick="saveEditedReview(<?php echo $item['Product_Id']; ?>, <?php echo $order['Order_Id']; ?>)">
                        Save Changes
                      </button>
                    </div>
                  <?php else: ?>
                    <div class="review-form" id="review-form-<?php echo $item['Product_Id']; ?>">
                      <textarea placeholder="Share your experience..." rows="3"
                        id="review-text-<?php echo $item['Product_Id']; ?>"></textarea>
                      <button class="button button-primary"
                        onclick="submitReview(<?php echo $item['Product_Id']; ?>, <?php echo $order['Order_Id']; ?>)">
                        Submit Review
                      </button>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?php echo $i; ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>"
            <?php if ($i === $current_page)
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
      fetch(`generate_invoice.php?order_id=${orderId}`, {
        credentials: 'same-origin' // Ensure session cookies are sent
      })
        .then(response => {
          if (!response.ok) {
            if (response.status === 403) {
              throw new Error('Access denied: You are not authorized to download this invoice.');
            } else {
              throw new Error(`Failed to download invoice (Status: ${response.status})`);
            }
          }
          // Check if the response is actually a PDF
          const contentType = response.headers.get('Content-Type');
          if (!contentType || !contentType.includes('application/pdf')) {
            throw new Error('Invalid response: Expected a PDF file.');
          }
          return response.blob();
        })
        .then(blob => {
          // Verify blob size to ensure it’s not empty
          if (blob.size === 0) {
            throw new Error('Received an empty PDF file.');
          }
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = `invoice_${orderId}.pdf`;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          console.error('Download error:', error);
          alert(error.message || 'An error occurred while downloading the invoice.');
        });
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
      const statusElement = document.getElementById('status-' + orderId);
      statusElement.textContent = 'Cancelled';
      statusElement.className = 'order-status status-cancelled';

      const cancelButton = document.getElementById('cancel-btn-' + orderId);
      if (cancelButton) {
        cancelButton.style.display = 'none';
      }

      closeCancelModal();
    }

    function submitReview(productId, orderId) {
      const reviewText = document.getElementById('review-text-' + productId).value.trim();
      if (reviewText) {
        const formData = new FormData();
        formData.append('action', 'submit');
        formData.append('order_id', orderId);
        formData.append('product_id', productId);
        formData.append('review_text', reviewText);

        fetch(window.location.href, {
          method: 'POST',
          body: formData
        }).then(response => {
          if (response.ok) {
            location.reload();
          }
        });
      } else {
        alert('Please enter a review before submitting.');
      }
    }

    function editReview(productId) {
      const reviewContent = document.getElementById('review-content-' + productId);
      const reviewForm = document.getElementById('review-form-' + productId);
      reviewContent.style.display = 'none';
      reviewForm.style.display = 'block';
    }

    function saveEditedReview(productId, orderId) {
      const reviewText = document.getElementById('review-text-' + productId).value.trim();
      if (reviewText) {
        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('order_id', orderId);
        formData.append('product_id', productId);
        formData.append('review_text', reviewText);

        fetch(window.location.href, {
          method: 'POST',
          body: formData
        }).then(response => {
          if (response.ok) {
            location.reload();
          }
        });
      } else {
        alert('Please enter a review before saving.');
      }
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
            location.reload();
          }
        });
      }
    }

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