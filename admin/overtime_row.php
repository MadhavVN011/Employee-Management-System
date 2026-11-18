<?php 
	include 'includes/session.php';

	if(isset($_POST['id'])){
		$id = $_POST['id'];
		$sql = 'SELECT *, overtime.id AS otid FROM overtime LEFT JOIN employees on employees.id=overtime.employee_id WHERE overtime.id=?';
		$query = db_query($conn, $sql, array($id));
		$row = $query->fetch_assoc();

		echo json_encode($row);
	}
?>