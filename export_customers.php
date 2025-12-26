<?php
require_once('includes/load.php');
require_once('includes/db.php');

// Get search query and customer type if exists
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$customer_type = isset($_GET['customer_type']) ? $_GET['customer_type'] : 'online';

// Build SQL query based on customer type using existing tables
if ($customer_type === 'walkin') {
    // For walk-in customers, get data directly from physical_sales table
    $sql = "SELECT DISTINCT 
            ps.customer_id as id,
            ps.customer_name as name,
            ps.customer_email as email,
            ps.customer_phone as contact
            FROM physical_sales ps 
            WHERE ps.customer_name IS NOT NULL AND ps.customer_name != ''";
} else {
    // For online customers, get data directly from customers table
    $sql = "SELECT c.id, c.name, c.email, c.phone, c.address 
            FROM customers c 
            INNER JOIN orders o ON c.id = o.customer_id
            GROUP BY c.id";
}

// Add search filters based on customer type
$where = [];
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    if ($customer_type === 'walkin') {
        $where[] = "(ps.customer_name LIKE '%$search_esc%' 
                  OR ps.customer_email LIKE '%$search_esc%' 
                  OR ps.customer_phone LIKE '%$search_esc%')";
    } else {
        $where[] = "(c.name LIKE '%$search_esc%' 
                  OR c.email LIKE '%$search_esc%' 
                  OR c.phone LIKE '%$search_esc%' 
                  OR c.address LIKE '%$search_esc%')";
    }
}

if (!empty($where)) {
    $sql .= ' AND ' . implode(' AND ', $where);
}

$sql .= " ORDER BY name ASC";
$result = $conn->query($sql);

if ($_GET['type'] == 'csv') {
    $filename = $customer_type === 'walkin' ? 'walkin_customers.csv' : 'online_customers.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);
    $output = fopen('php://output', 'w');
    
    // CSV headers based on customer type
    if ($customer_type === 'walkin') {
        fputcsv($output, ['ID', 'Name', 'Email', 'Contact']);
    } else {
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Address']);
    }
    
    if ($result && $result->num_rows > 0) {
        while ($customer = $result->fetch_assoc()) {
            if ($customer_type === 'walkin') {
                fputcsv($output, [
                    $customer['id'] ?? 'N/A',
                    $customer['name'],
                    $customer['email'] ?? 'N/A',
                    $customer['contact'] ?? 'N/A'
                ]);
            } else {
                fputcsv($output, [
                    $customer['id'],
                    $customer['name'],
                    $customer['email'],
                    $customer['phone'] ?? 'N/A',
                    $customer['address'] ?? 'N/A'
                ]);
            }
        }
    }
    fclose($output);
    exit;
} elseif ($_GET['type'] == 'pdf') {
    require('fpdf/fpdf.php');
    $pdf = new FPDF('L'); // Landscape orientation for better table display
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    
    $title = $customer_type === 'walkin' ? 'Walk-in Customers Report' : 'Online Customers Report';
    $pdf->Cell(0,10,$title,0,1,'C');
    $pdf->Ln(5);
    
    // Add filter info if applicable
    if (!empty($search)) {
        $pdf->SetFont('Arial','',10);
        $filter_text = 'Filtered by: Search: ' . $search;
        $pdf->Cell(0,8,$filter_text,0,1);
        $pdf->Ln(2);
    }
    
    $pdf->SetFont('Arial','',10);
    
    // Table headers based on customer type
    if ($customer_type === 'walkin') {
        $pdf->Cell(20,10,'ID',1);
        $pdf->Cell(60,10,'Name',1);
        $pdf->Cell(80,10,'Email',1);
        $pdf->Cell(50,10,'Contact',1);
        $pdf->Ln();
        
        // Table data for walk-in customers
        if ($result && $result->num_rows > 0) {
            while ($customer = $result->fetch_assoc()) {
                $pdf->Cell(20,10,$customer['id'] ?? 'N/A',1);
                $pdf->Cell(60,10,substr($customer['name'], 0, 25),1);
                $pdf->Cell(80,10,substr($customer['email'] ?? 'N/A', 0, 30),1);
                $pdf->Cell(50,10,substr($customer['contact'] ?? 'N/A', 0, 18),1);
                $pdf->Ln();
            }
        } else {
            $pdf->Cell(0,10,'No walk-in customers found.',1,1,'C');
        }
    } else {
        $pdf->Cell(20,10,'ID',1);
        $pdf->Cell(50,10,'Name',1);
        $pdf->Cell(70,10,'Email',1);
        $pdf->Cell(40,10,'Phone',1);
        $pdf->Cell(60,10,'Address',1);
        $pdf->Ln();
        
        // Table data for online customers
        if ($result && $result->num_rows > 0) {
            while ($customer = $result->fetch_assoc()) {
                $pdf->Cell(20,10,$customer['id'],1);
                $pdf->Cell(50,10,substr($customer['name'], 0, 20),1);
                $pdf->Cell(70,10,substr($customer['email'], 0, 25),1);
                $pdf->Cell(40,10,substr($customer['phone'] ?? 'N/A', 0, 15),1);
                $pdf->Cell(60,10,substr($customer['address'] ?? 'N/A', 0, 22),1);
                $pdf->Ln();
            }
        } else {
            $pdf->Cell(0,10,'No online customers found.',1,1,'C');
        }
    }
    
    $filename = $customer_type === 'walkin' ? 'walkin_customers.pdf' : 'online_customers.pdf';
    $pdf->Output('D', $filename);
    exit;
}
?>
