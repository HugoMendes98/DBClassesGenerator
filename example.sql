DROP DATABASE IF EXISTS example;
CREATE DATABASE example;

USE example;

CREATE TABLE tblDefaultPage (
	_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE tblUser (
	_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,

	defaultPage_id INT,

	FOREIGN KEY (defaultPage_id)
	REFERENCES tblDefaultPage(_id),

	username VARCHAR(50) NOT NULL UNIQUE,
	password VARCHAR(240) NOT NULL,
	isEnabled BOOLEAN DEFAULT FALSE,

	email VARCHAR(100) NOT NULL,
	token VARCHAR(100) UNIQUE NOT NULL,

	firstname VARCHAR(60) DEFAULT "george",
	lastname VARCHAR(60),
	phone VARCHAR(15),

	creationTime DATETIME DEFAULT NOW(),
	modificationTime DATETIME ON UPDATE NOW(),
	lastTime DATE DEFAULT NULL
);

CREATE TABLE tblGroup (
	_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE tblUsrGrp (
	_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	user_id INT NOT NULL,
	group_id INT NOT NULL,

	UNIQUE (user_id, group_id),

	FOREIGN KEY (user_id)
	REFERENCES tblUser(_id),

	FOREIGN KEY (group_id)
	REFERENCES tblGroup(_id)
);

INSERT INTO tblDefaultPage (name) VALUES ('asdasd'), ('sdsd');

INSERT INTO tblUser (username, password, token, email, defaultPage_id) VALUES ('admin', 'password', '', '', null), ('admin2', 'password', '2', '', 1);
INSERT INTO tblGroup (name) VALUES ('admin'), ('grp');

INSERT INTO tblUsrGrp (user_id, group_id) VALUES (1,1), (1,2), (2,1);