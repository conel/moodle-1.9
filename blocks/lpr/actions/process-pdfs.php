<?php

    require_once('D:\moodle\config.php');
    // include the pdf export class
    require_once('D:\moodle\blocks\lpr\models\block_lpr_pdf_export.php');

    // instantiate the pdf_export class
    $pdf = new PdfExporter();
    $pdf->generatePDFs();
?>