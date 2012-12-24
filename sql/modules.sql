DROP TABLE IF EXISTS `tags_system`;
CREATE TABLE `tags_system` (
	id INT PRIMARY KEY AUTO_INCREMENT,
	tag_value VARCHAR(25) NOT NULL UNIQUE,
	hits INT DEFAULT 0,
	blacklisted CHAR(1) DEFAULT '0',
	ln CHAR(2) DEFAULT 'PL'	
);
-- content
DROP TABLE IF EXISTS `content`;
CREATE TABLE `content` (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT, -- owner id
  title VARCHAR(100) DEFAULT '' character set utf8 collate utf8_unicode_ci,
  content TEXT character set utf8 collate utf8_unicode_ci,
  ln CHAR(2) DEFAULT 'PL',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  include_in_list CHAR(1) DEFAULT '1',
  active CHAR(1) DEFAULT '0'
) ENGINE=InnoDB;
CREATE INDEX `content_usrid_index` USING BTREE ON content(user_id);

DROP TABLE IF EXISTS `user_groups_membership`;
CREATE TABLE `user_groups_membership` (
	id INT PRIMARY KEY AUTO_INCREMENT,
	user_id INT,
	group_id INT
);