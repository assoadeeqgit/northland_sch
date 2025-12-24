CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'A@123456.Aaa';
GRANT ALL PRIVILEGES ON northland_schools_kano.* TO 'admin'@'localhost';
CREATE USER IF NOT EXISTS 'admin'@'127.0.0.1' IDENTIFIED BY 'A@123456.Aaa';
GRANT ALL PRIVILEGES ON northland_schools_kano.* TO 'admin'@'127.0.0.1';
FLUSH PRIVILEGES;
