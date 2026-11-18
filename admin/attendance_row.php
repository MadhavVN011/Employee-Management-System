<?php 
	include 'includes/session.php';

	if(isset($_POST['id'])){
		$id = $_POST['id'];
		$sql = 'SELECT *, attendance.id as attid FROM attendance LEFT JOIN employees ON employees.id=attendance.employee_id WHERE attendance.id = ?';
		$query = db_query($conn, $sql, array($id));
		$row = $query->fetch_assoc();

		echo json_encode($row);
	}
?>