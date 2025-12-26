<?php
require_once('includes/load.php');
$categories = find_all('categories');
if ($_GET['type'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=categories.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name']);
    foreach ($categories as $cat) {
        fputcsv($output, [$cat['id'], $cat['name']]);
    }
    fclose($output);
    exit;
} elseif ($_GET['type'] == 'pdf') {
    require('fpdf/fpdf.php');
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Categories',0,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(20,10,'ID',1);
    $pdf->Cell(80,10,'Name',1);
    $pdf->Ln();
    foreach ($categories as $cat) {
        $pdf->Cell(20,10,$cat['id'],1);
        $pdf->Cell(80,10,$cat['name'],1);
        $pdf->Ln();
    }
    $pdf->Output('D', 'categories.pdf');
    exit;
} 