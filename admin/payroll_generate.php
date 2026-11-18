<?php
	include 'includes/session.php';
	require_once __DIR__.'/includes/payroll_functions.php';

	function generateRow($from, $to, $conn){
		$contents = '';
	 	
		$total = 0;
		foreach (payroll_rows($conn, $from, $to) as $row) {
			$net = $row['net'];
			$total += $net;
			$contents .= '
			<tr>
				<td>'.h($row['lastname']).', '.h($row['firstname']).'</td>
				<td>'.h($row['employee_id']).'</td>
				<td align="right">'.number_format($net, 2).'</td>
			</tr>
			';
		}

		$contents .= '
			<tr>
				<td colspan="2" align="right"><b>Total</b></td>
				<td align="right"><b>'.number_format($total, 2).'</b></td>
			</tr>
		';
		return $contents;
	}
		
	try {
		list($from, $to) = payroll_range($_POST['date_range'] ?? '');
	} catch (InvalidArgumentException $error) {
		http_response_code(400);
		header('Content-Type: text/plain; charset=utf-8');
		exit($error->getMessage());
	}
	$from_title = date('M d, Y', strtotime($from));
	$to_title = date('M d, Y', strtotime($to));

	require_once('../tcpdf/tcpdf.php');  
    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);  
    $pdf->SetCreator(PDF_CREATOR);  
    $pdf->SetTitle('Payroll: '.$from_title.' - '.$to_title);  
    $pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);  
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));  
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
    $pdf->SetDefaultMonospacedFont('helvetica');  
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);  
    $pdf->SetMargins(PDF_MARGIN_LEFT, '10', PDF_MARGIN_RIGHT);  
    $pdf->setPrintHeader(false);  
    $pdf->setPrintFooter(false);  
    $pdf->SetAutoPageBreak(TRUE, 10);  
    $pdf->SetFont('helvetica', '', 11);  
    $pdf->AddPage();  
    $content = '';  
    $content .= '
      	<h2 align="center">Teelaunch - EMS</h2>
      	<h4 align="center">'.$from_title." - ".$to_title.'</h4>
      	<table border="1" cellspacing="0" cellpadding="3">  
           <tr>  
           		<th width="40%" align="center"><b>Employee Name</b></th>
                <th width="30%" align="center"><b>Employee ID</b></th>
				<th width="30%" align="center"><b>Net Pay</b></th> 
           </tr>  
      ';  
    $content .= generateRow($from, $to, $conn);
    $content .= '</table>';  
    $pdf->writeHTML($content);  
    $pdf->Output('payroll.pdf', 'I');

?>
