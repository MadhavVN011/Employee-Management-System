<?php 
	include 'includes/session.php';

	if(isset($_POST['id'])){
		$id = $_POST['id'];
		$sql = 'SELECT *, cashadvance.id AS caid FROM cashadvance LEFT JOIN employees on employees.id=cashadvance.employee_id WHERE cashadvance.id=?';
		$query = db_query($conn, $sql, array($id));
		$row = $query->fetch_assoc();

		echo json_encode($row);
	}
?>