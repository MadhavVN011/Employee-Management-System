<?php
	include 'includes/session.php';

	if(isset($_POST['edit'])){
		$id = $_POST['id'];
		$amount = $_POST['amount'];
		
		$sql = 'UPDATE cashadvance SET amount = ? WHERE id = ?';
		if(db_query($conn, $sql, array($amount, $id))){
			$_SESSION['success'] = 'Cash advance updated successfully';
		}
		else{
			$_SESSION['error'] = $conn->error;
		}
	}
	else{
		$_SESSION['error'] = 'Fill up edit form first';
	}

	header('location:cashadvance.php');

?>