<?php
require_once '../config/dbConnect.php';
require('../fpdf/fpdf.php');

session_start();
if (!isset($_SESSION['Email_id']) || $_SESSION['user_type'] !== 'Delivery Agent') {
    die("Unauthorized Access!");
}

// Configuration
$email = $_SESSION['Email_id'];
$selectedMonth = isset($_POST['month']) ? $_POST['month'] : date('Y-m');
$reportDate = date('d M Y');
$outputFilename = "Delivery_Report_" . date('Y-m', strtotime($selectedMonth)) . ".pdf";
$logoPath = '../assets/partmartLogo.png';

// Database functions
function getAgentDetails($conn, $email) {
    $stmt = $conn->prepare("SELECT First_Name, Last_Name FROM user_detail_tbl WHERE Email_id = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $agent ? "{$agent['First_Name']} {$agent['Last_Name']}" : 'Unknown Agent';
}

function getOrderStats($conn, $email, $selectedMonth) {
    $stats = ['total_orders' => 0, 'monthly_orders' => 0];
    
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_orders FROM order_tbl WHERE Delivered_By = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stats['total_orders'] = $stmt->get_result()->fetch_assoc()['total_orders'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) AS monthly_orders FROM order_tbl 
                          WHERE Delivered_By = ? AND DATE_FORMAT(Delivery_Date, '%Y-%m') = ?");
    $stmt->bind_param("ss", $email, $selectedMonth);
    $stmt->execute();
    $stats['monthly_orders'] = $stmt->get_result()->fetch_assoc()['monthly_orders'];
    $stmt->close();
    
    return $stats;
}

function getOrderHistory($conn, $email, $selectedMonth) {
    $stmt = $conn->prepare("SELECT ot.Order_Id, ot.Delivery_Date, ud.First_Name, ud.Last_Name, 
                          ot.Delivery_Address, ot.Delivery_Pincode AS Pincode, ud.Phone_Number
                          FROM order_tbl ot JOIN user_detail_tbl ud ON ot.Email_Id = ud.Email_id
                          WHERE ot.Delivered_By = ? AND DATE_FORMAT(ot.Delivery_Date, '%Y-%m') = ?
                          ORDER BY ot.Delivery_Date DESC");
    $stmt->bind_param("ss", $email, $selectedMonth);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $history;
}

// Get data
$agentName = getAgentDetails($conn, $email);
$orderStats = getOrderStats($conn, $email, $selectedMonth);
$orderHistory = getOrderHistory($conn, $email, $selectedMonth);
$conn->close();

class DeliveryReportPDF extends FPDF {
    private $showHeader = true;
    
    function Header() {
        if ($this->showHeader || $this->PageNo() === 1) {
            $this->SetDrawColor(0, 0, 0);
            $this->Rect(5, 5, $this->w - 10, $this->h - 10);
            
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(0, 6, 'Generated On: ' . $GLOBALS['reportDate'], 0, 0, 'R');
            $this->Ln(10);
            
            $this->PageNo() === 1 ? $this->firstPageHeader() : $this->standardPageHeader();
            $this->tableHeader();
        }
    }
    
    
    // [Previous configuration and database functions remain the same until the firstPageHeader() method]
    
    private function firstPageHeader() {
        // Logo and text configuration with precise alignment
        $logoWidth = 20;  // Reduced logo width for better proportion
        $logoHeight = 15; // Reduced logo height
        $text = 'PartsMart';
        $this->SetFont('Arial', 'B', 20);
        $textWidth = $this->GetStringWidth($text);
        $spacing = 0; // Space between logo and text
        
        // Calculate starting X position to center the group
        $totalWidth = $logoWidth + $spacing + $textWidth;
        $startX = ($this->GetPageWidth() - $totalWidth) / 2;
        
        // Add logo if exists
        if (file_exists($GLOBALS['logoPath'])) {
            $this->Image($GLOBALS['logoPath'], $startX, 15, $logoWidth, $logoHeight);
        }
        
        // Draw text exactly beside the logo
        $this->SetXY($startX + $logoWidth + $spacing, 15);
        $this->Cell($textWidth, 15, $text, 0, 1);
        
        // Center-aligned header content
        $this->SetY(40); // Position after logo+title
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, 'Delivery Agent Report', 0, 1, 'L'); // Centered
        $this->Ln(6);
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, 'Agent: ' . $GLOBALS['agentName'], 0, 1, 'L'); // Centered
        $this->Ln(6);
        
        $this->Cell(0, 6, 'For: ' . date('F Y', strtotime($GLOBALS['selectedMonth'])), 0, 1, 'L'); // Centered
        $this->Ln(6);
        
        // Delivery statistics with underlined text and numbers
        $this->SetFont('Arial', 'BU', 12);
        $this->Cell(0, 6, 'All Time Deliveries: ' . number_format($GLOBALS['orderStats']['total_orders']), 0, 1, 'L'); // Centered
        $this->SetFont('Arial', 'B', 12);
        $this->Ln(6);
        
        $this->SetFont('Arial', 'BU', 12);
        $this->Cell(0, 6, 'Monthly Deliveries: ' . number_format($GLOBALS['orderStats']['monthly_orders']), 0, 1, 'L'); // Centered
        $this->SetFont('Arial', 'B', 12);
        $this->Ln(8);
    }
    
    // [Rest of the code remains exactly the same]
    
    private function tableHeader() {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(23, 8, 'SR. NO', 1, 0, 'C', true);
        $this->Cell(37, 8, 'Customer Name', 1, 0, 'C', true);
        $this->Cell(60, 8, 'Address', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Pincode', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Phone No', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Delivered In', 1, 1, 'C', true);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, 'Delivery tracking report - PartsMart', 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Generate PDF
$pdf = new DeliveryReportPDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

if (!empty($orderHistory)) {
    $index = 1;
    $fill = false;
    
    foreach ($orderHistory as $order) {
        $customerName = htmlspecialchars("{$order['First_Name']} {$order['Last_Name']}");
        $address = wordwrap(htmlspecialchars($order['Delivery_Address']), 25);
        $rowHeight = max(8, (substr_count($address, "\n") + 1) * 8);
        
        if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 30) {
            $pdf->AddPage();
        }
        
        $pdf->SetFillColor($fill ? 230 : 255);
        $fill = !$fill;
        
        $pdf->Cell(23, $rowHeight, $index++, 1, 0, 'C', true);
        $pdf->Cell(37, $rowHeight, $customerName, 1, 0, 'C', true);
        $pdf->MultiCell(60, 8, $address, 1, 'C', true);
        $pdf->SetXY($pdf->GetX() + 120, $pdf->GetY() - $rowHeight);
        $pdf->Cell(20, $rowHeight, htmlspecialchars($order['Pincode']), 1, 0, 'C', true);
        $pdf->Cell(25, $rowHeight, htmlspecialchars($order['Phone_Number']), 1, 0, 'C', true);
        $pdf->Cell(25, $rowHeight, date('d M Y', strtotime($order['Delivery_Date'])), 1, 1, 'C', true);
    }
} else {
    $pdf->Cell(0, 10, 'No orders found for the selected month.', 0, 1, 'C');
}

$pdf->Output('D', $outputFilename);
exit();