CREATE DATABASE webservices;

USE webservices;

CREATE TABLE data_formats(
	format_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	format_name VARCHAR(32) NOT NULL,
	content_type VARCHAR(64) NULL
);

INSERT INTO data_formats (format_name, content_type)
VALUES ('json', 'application/json'), ('xml', 'application/xml'),
	('text', 'text/plain'), ('form', 'multipart/form-data'),
	('form-urlencoded', 'application/x-www-form-urlencoded'), ('soap', 'wsdl');

CREATE TABLE services(
	service_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	service_name VARCHAR(64) NOT NULL,
	request_format_id INT NOT NULL,
	response_format_id INT NOT NULL,
	FOREIGN KEY(request_format_id) REFERENCES data_formats(format_id),
	FOREIGN KEY(response_format_id) REFERENCES data_formats(format_id)
);

INSERT INTO services (service_name, request_format_id, response_format_id)
VALUES ('nosis', 5, 2), ('ws_sr_padron_a5', 6, 6), ('veraz', 5, 2);

CREATE TABLE requests(
	request_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	service_id INT NOT NULL,
	request_method VARCHAR(32) NULL,
	request_key_params JSON NULL,
	request_body TEXT NULL,
	request_headers TEXT NULL,
	request_date TIMESTAMP NOT NULL DEFAULT NOW(),
	FOREIGN KEY(service_id) REFERENCES services(service_id),
	INDEX (service_id, request_method, request_date)
);

CREATE TABLE responses(
	request_id INT NOT NULL,
	response_body TEXT NULL,
	response_headers TEXT NULL,
	response_status INT NOT NULL DEFAULT 0,
	response_status_detail VARCHAR(128) NULL,
	response_date TIMESTAMP NOT NULL DEFAULT NOW(),
	FOREIGN KEY(request_id) REFERENCES requests(request_id)
);