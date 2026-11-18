<?php 
	include 'includes/session.php';

	if(isset($_POST['id'])){
		$id = $_POST['id'];
		$sql = 'SELECT * FROM position WHERE id = ?';
		$query = db_query($conn, $sql, array($id));
		$row = $query->fetch_assoc();

		echo json_encode($row);
	}
?>