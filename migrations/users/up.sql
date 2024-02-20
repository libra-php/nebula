CREATE TABLE IF NOT EXISTS users (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_type INT UNSIGNED NOT NULL DEFAULT 2,
	name VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL,
	password BINARY(96) NOT NULL,
	login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	UNIQUE (email),
	PRIMARY KEY (id),
	FOREIGN KEY (user_type) REFERENCES user_types (id)
);
