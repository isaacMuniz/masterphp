<?php

class MySqlDataProvider extends DataProvider {
	function __construct($source) {
		$this->source = $source;
	}

	public function get_terms() {
		// we're passing the result of this function as a model to the view
		return $this->query('SELECT * FROM terms');
	}

	public function get_term($term) {
		$db = $this->connect();

		if ($db == null) {
			return; // not found
		}

		$sql = 'SELECT * FROM terms WHERE id = :id';
		$smt = $db->prepare($sql); //statement obj

		$smt->execute([
			':id' => $term,
		]);

		$data = $smt->fetchAll(PDO::FETCH_CLASS, 'GlossaryTerm');

		if (empty($data)) {
			return;
		}

		$smt = null;
		$db = null;

		return $data[0];
	}

	public function search_terms($search) {
		return $this->query(
			'SELECT * FROM terms WHERE term LIKE :search OR definition LIKE :search',
			[':search' => '%' . $search . '%']
		);
	}

	public function add_term($term, $definition) {
		$this->execute(
			'INSERT INTO terms (term, definition) VALUES (:term, :definition)',
			[
				':term' => $term,
				':definition' => $definition,
			]
		);
	}

	public function update_term($original_term, $new_term, $new_definition) {
		$this->execute(
			'UPDATE terms SET term = :term, definition = :definition WHERE id = :id',
			[
				':term' => $new_term,
				':definition' => $new_definition,
				':id' => $original_term
			]
		);
	}

	public function delete_term($term) {
		$this->execute('DELETE FROM terms WHERE id = :id', [':id' => $term]);
	}

	private function connect() {
		try {
			return new PDO($this->source, CONFIG['db_user'], CONFIG['db_password']);
		} catch (PDOException $e) {
			return null;
		}
	}

	private function query($sql, $sql_params = []) {
		$db = $this->connect(); // create database connection

		if ($db == null) { // check if we actually have a connection
			return [];
		}

		$query = null;

		if (empty($sql_params)) {
			$query = $db->query($sql);
		} else {
			$query = $db->prepare($sql);
			$query->execute($sql_params);
		}

		$data = $query->fetchAll(PDO::FETCH_CLASS, 'GlossaryTerm');

		$query = null;
		$db = null;

		return $data;
	}

	private function execute($sql, $sql_params) {
		$db = $this->connect();

		if ($db == null) {
			return;
		}

		$smt = $db->prepare($sql);

		$smt->execute($sql_params);

		$smt = null;
		$db = null;
	}
}
