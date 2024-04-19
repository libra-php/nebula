CREATE TABLE IF NOT EXISTS sessions (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	request_uri MEDIUMTEXT,
	ip INT UNSIGNED,
	user_id INT UNSIGNED,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	FOREIGN KEY (user_id) REFERENCES users (id)
)
