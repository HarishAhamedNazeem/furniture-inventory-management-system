<?php
require_once('includes/load.php');
$products = join_product_table();
if ($_GET['type'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=products.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Description', 'Category', 'Quantity', 'Buy Price', 'Sale Price', 'Date']);
    foreach ($products as $product) {
        fputcsv($output, [
            $product['id'],
            $product['name'],
            isset($product['description']) ? $product['description'] : '',
            $product['categorie'],
            $product['quantity'],
            $product['buy_price'],
            $product['sale_price'],
            $product['date']
        ]);
    }
    fclose($output);
    exit;
} elseif ($_GET['type'] == 'pdf') {
    require('fpdf/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Products',0,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(15,10,'ID',1);
    $pdf->Cell(50,10,'Name',1);
    $pdf->Cell(60,10,'Description',1);
    $pdf->Cell(30,10,'Category',1);
    $pdf->Cell(20,10,'Qty',1);
    $pdf->Cell(30,10,'Buy Price',1);
    $pdf->Cell(30,10,'Sale Price',1);
    $pdf->Cell(40,10,'Date',1);
    $pdf->Ln();
    foreach ($products as $product) {
        $pdf->Cell(15,10,$product['id'],1);
        $pdf->Cell(50,10,$product['name'],1);
        $pdf->Cell(60,10,isset($product['description']) ? substr($product['description'], 0, 30) . '...' : '',1);
        $pdf->Cell(30,10,$product['categorie'],1);
        $pdf->Cell(20,10,$product['quantity'],1);
        $pdf->Cell(30,10,$product['buy_price'],1);
        $pdf->Cell(30,10,$product['sale_price'],1);
        $pdf->Cell(40,10,$product['date'],1);
        $pdf->Ln();
    }
    $pdf->Output('D', 'products.pdf');
    exit;
} 