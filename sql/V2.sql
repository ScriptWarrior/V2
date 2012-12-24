-- podstawowy SQL V2, zawiera systemowe obiekty bazy danych (tabele, widoki, triggery itd), tworzace kontener na uzytkownikow i system uprawnien
-- SQL dla standardowych modulow, takich jak np. konto, regulamin, newsy, bedzie dorzucany w oddzielnych plikach SQL
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT, -- copy of id, needed by the model architecture (entities XML)
  email_addr VARCHAR(100) UNIQUE, -- email bedzie jednoczesnie loginem, bedzie mozna go zmienic
  user_pass VARCHAR(40), -- hash sha1
  active TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,	-- data zalozenia konta
  last_logged_at TIMESTAMP,
  username VARCHAR(50) character set utf8 collate utf8_unicode_ci DEFAULT "",-- imie, nazwisko/pseudonim widoczny na stronie, opcjonalne
  user_avatar VARCHAR(32) DEFAULT '',
  user_info TEXT character set utf8 collate utf8_unicode_ci  DEFAULT ''
) ENGINE=InnoDB;

CREATE INDEX user_index USING BTREE ON user(id);

DROP TABLE IF EXISTS `account_activation_token`;
CREATE TABLE `account_activation_token` (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNIQUE,
	token VARCHAR(30) UNIQUE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_groups_membership (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  group_id INT NOT NULL
) ENGINE=InnoDB;
CREATE INDEX user_groups_membership_index USING BTREE ON user_groups_membership(id);
CREATE INDEX user_groups_membership_index2 USING BTREE ON user_groups_membership(user_id);
CREATE INDEX user_groups_membership_index3 USING BTREE ON user_groups_membership(group_id);

DROP TABLE IF EXISTS acl;
CREATE TABLE acl (
        `aid` INT PRIMARY KEY AUTO_INCREMENT,
        `mod_name` VARCHAR(20) NOT NULL,
        `action` VARCHAR(30) NOT NULL,
        `schema_name` VARCHAR(50) DEFAULT '',
        `logging_system` TINYINT DEFAULT 1,
        `acl_res_id` VARCHAR(30) DEFAULT 0, -- FK to any ACL-ed table
        `acl_uid` INT DEFAULT 0,
        `acl_gid` INT DEFAULT 0,
        `csrf_protect` TINYINT DEFAULT 1 
) ENGINE=InnoDB;

CREATE INDEX acl_index USING BTREE ON acl(aid);
CREATE INDEX acl_mod_name_index USING HASH ON acl(mod_name);
CREATE INDEX acl_action_index USING HASH ON acl(action);

DROP TABLE IF EXISTS auth_log;
CREATE TABLE auth_log (
        lid BIGINT PRIMARY KEY AUTO_INCREMENT,
        module_name VARCHAR(20),
        action VARCHAR(30),
        res_id INT DEFAULT 0,
        usr_id INT DEFAULT 0,
        operation_status TINYINT DEFAULT 1, -- 1 oznacza powodzenie, info o tym bedziemy miec od razu z systemu autoryzacji
        time_stamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_addr VARCHAR(31) DEFAULT ''
) ENGINE=InnoDB;

CREATE INDEX auth_log_index USING BTREE ON auth_log(lid);
CREATE INDEX auth_log_mod_name_index USING HASH ON auth_log(module_name);
CREATE INDEX auth_log_action_index USING HASH ON auth_log(action);
CREATE INDEX auth_status_index USING BTREE ON auth_log(operation_status);
-- status - znaczenia:
-- 1 - powodzenie, wszystko ok
-- 2 - brak dostepu
-- 3 - nie odnaleziono acl
CREATE VIEW auth_log_403 AS SELECT * FROM auth_log WHERE operation_status=2 ORDER BY time_stamp DESC;
CREATE VIEW auth_log_404 AS SELECT * FROM auth_log WHERE operation_status=3 ORDER BY time_stamp DESC;

-- session security
CREATE TABLE session_sec (
	id INT PRIMARY KEY AUTO_INCREMENT,
	session_id VARCHAR(50) UNIQUE,
	session_checksum CHAR(40),-- sha1
	session_last_activity INT DEFAULT 0,
	ip_addr VARCHAR(31),
	browser_checksum CHAR(40)
);
-- file uploads
CREATE TABLE file_published (
	id INT PRIMARY KEY AUTO_INCREMENT,
	user_id INT,
	filename VARCHAR(255),
	file_size INT,
	description VARCHAR(500),
	width INT, -- for pics
	height INT, -- for pics
	mime_type VARCHAR (30)
);

-- categories/tags system
DROP TABLE IF EXISTS category;
CREATE TABLE category (
	id INT AUTO_INCREMENT PRIMARY KEY,
	parent_id INT
);
DROP TABLE IF EXISTS category_label;
CREATE TABLE category_label (
	id INT AUTO_INCREMENT PRIMARY KEY,
	category_id INT,	-- FK to category
	ln CHAR(2) DEFAULT 'PL',
	category_name VARCHAR(100)
);
DROP TABLE IF EXISTS categories_assigment;
CREATE TABLE categories_assignment (
	id INT AUTO_INCREMENT PRIMARY KEY,
	schema_name VARCHAR(50),
	res_id INT,
	cat_id INT
);
-- wygenerowac w locie dlugiego uniona/joina