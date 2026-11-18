<?php
	include 'includes/session.php';
	require_once __DIR__.'/includes/payroll_functions.php';
	
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
    $pdf->SetTitle('Payslip: '.$from_title.' - '.$to_title);  
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
    $contents = '';

	foreach (payroll_rows($conn, $from, $to) as $row) {
		$deduction = $row['deduction'];
		$cashadvance = $row['cashadvance'];
		$net = $row['net'];

		$contents .= '
			<h2 align="center">Teelaunch - EMS</h2>
			<h4 align="center">'.$from_title." - ".$to_title.'</h4>
			<table cellspacing="0" cellpadding="3">  
    	       	<tr>  
            		<td width="25%" align="right">Employee Name: </td>
                    <td width="25%"><b>'.h($row['firstname'])." ".h($row['lastname']).'</b></td>
				 	<td width="25%" align="right">Rate per Hour: </td>
                 	<td width="25%" align="right">'.number_format($row['rate'], 2).'</td>
    	    	</tr>
    	    	<tr>
    	    		<td width="25%" align="right">Employee ID: </td>
                    <td width="25%">'.h($row['employee_id']).'</td>
				 	<td width="25%" align="right">Total Hours: </td>
				 	<td width="25%" align="right">'.number_format($row['total_hr'], 2).'</td> 
    	    	</tr>
    	    	<tr> 
    	    		<td></td> 
    	    		<td></td>
                    <td width="25%" align="right"><b>Gross Pay (incl. overtime): </b></td>
                    <td width="25%" align="right"><b>'.number_format($row['gross'], 2).'</b></td>
    	    	</tr>
                <tr>
                    <td></td><td></td>
                    <td width="25%" align="right">Overtime Pay: </td>
                    <td width="25%" align="right">'.number_format($row['overtime_pay'], 2).'</td>
                </tr>
    	    	<tr> 
    	    		<td></td> 
    	    		<td></td>
				 	<td width="25%" align="right">Deduction: </td>
				 	<td width="25%" align="right">'.number_format($deduction, 2).'</td> 
    	    	</tr>
    	    	<tr> 
    	    		<td></td> 
    	    		<td></td>
				 	<td width="25%" align="right">Cash Advance: </td>
				 	<td width="25%" align="right">'.number_format($cashadvance, 2).'</td> 
    	    	</tr>
    	    	<tr> 
    	    		<td></td> 
    	    		<td></td>
				 	<td width="25%" align="right"><b>Total Deduction:</b></td>
                    <td width="25%" align="right"><b>'.number_format($row['total_deduction'], 2).'</b></td>
    	    	</tr>
    	    	<tr> 
    	    		<td></td> 
    	    		<td></td>
				 	<td width="25%" align="right"><b>Net Pay:</b></td>
				 	<td width="25%" align="right"><b>'.number_format($net, 2).'</b></td> 
    	    	</tr>
    	    </table>
    	    <br><hr>
		';
	}
    $pdf->writeHTML($contents);  
    $pdf->Output('payslip.pdf', 'I');

?>
