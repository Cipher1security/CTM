CREATE TABLE adn2 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  admin_level INT NOT NULL,
  status ENUM('active', 'hidden', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL,
  password_changed_at TIMESTAMP NULL
);

INSERT INTO adn2 (username, password, name, admin_level, status, created_at, last_login, password_changed_at)
VALUES ('admin', '$2b$12$HCkWRccZ6NAo69/V00b.NORVimUYF7PZdAVkdIwCKlGqTK/vCQv1O', 'admin', 1, 'active', NOW(), NULL, NULL);


#password = admin