<?php
	include 'includes/session.php';

	if(isset($_POST['delete'])){
		$id = $_POST['id'];
		$sql = 'DELETE FROM overtime WHERE id = ?';
		if(db_query($conn, $sql, array($id))){
			$_SESSION['success'] = 'Overtime deleted successfully';
		}
		else{
			$_SESSION['error'] = $conn->error;
		}
	}
	else{
		$_SESSION['error'] = 'Select item to delete first';
	}

	header('location: overtime.php');
	
?>