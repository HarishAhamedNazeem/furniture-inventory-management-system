<?php
require_once('includes/load.php');
$suppliers = find_all('suppliers');
if ($_GET['type'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=suppliers.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Contact Person', 'Phone', 'Email', 'Address', 'Status', 'Date']);
    foreach ($suppliers as $supplier) {
        fputcsv($output, [
            $supplier['id'],
            $supplier['name'],
            $supplier['contact_person'],
            $supplier['phone'],
            $supplier['email'],
            $supplier['address'],
            $supplier['status'] == 1 ? 'Active' : 'Inactive',
            $supplier['date']
        ]);
    }
    fclose($output);
    exit;
} elseif ($_GET['type'] == 'pdf') {
    require('fpdf/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Suppliers',0,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(10,10,'ID',1);
    $pdf->Cell(40,10,'Name',1);
    $pdf->Cell(40,10,'Contact Person',1);
    $pdf->Cell(30,10,'Phone',1);
    $pdf->Cell(40,10,'Email',1);
    $pdf->Cell(50,10,'Address',1);
    $pdf->Cell(20,10,'Status',1);
    $pdf->Cell(30,10,'Date',1);
    $pdf->Ln();
    foreach ($suppliers as $supplier) {
        $pdf->Cell(10,10,$supplier['id'],1);
        $pdf->Cell(40,10,$supplier['name'],1);
        $pdf->Cell(40,10,$supplier['contact_person'],1);
        $pdf->Cell(30,10,$supplier['phone'],1);
        $pdf->Cell(40,10,$supplier['email'],1);
        $pdf->Cell(50,10,$supplier['address'],1);
        $pdf->Cell(20,10,($supplier['status'] == 1 ? 'Active' : 'Inactive'),1);
        $pdf->Cell(30,10,$supplier['date'],1);
        $pdf->Ln();
    }
    $pdf->Output('D', 'suppliers.pdf');
    exit;
} 