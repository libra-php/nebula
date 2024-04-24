CREATE TABLE IF NOT EXISTS modules (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL,
	path VARCHAR(255),
	class_name VARCHAR(255),
	sql_table VARCHAR(255),
	primary_key VARCHAR(255),
	item_order TINYINT NOT NULL DEFAULT 0,
	max_permission_level TINYINT,
	parent_module_id INT UNSIGNED,
	updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	FOREIGN KEY (parent_module_id) REFERENCES modules (id)
)
