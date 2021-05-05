CREATE DATABASE weather;

CREATE TABLE weather
(
    id int(11) NOT NULL AUTO_INCREMENT,
    zipcode INT NOT NULL,
    curr_temp INT NOT NULL,
    temp_min INT NOT NULL,
    temp_max INT NOT NULL,
    weather VARCHAR(255) NOT NULL,
    date_added DATETIME NOT NULL,
    PRIMARY KEY (id)
);