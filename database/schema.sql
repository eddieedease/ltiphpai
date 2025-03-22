-- LTI 1.1 Consumer credentials
CREATE TABLE lti_consumers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_key VARCHAR(255) NOT NULL,
    secret VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- LTI 1.3 Platform configurations
CREATE TABLE lti13_deployments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issuer VARCHAR(255) NOT NULL,
    client_id VARCHAR(255) NOT NULL,
    deployment_id VARCHAR(255) NOT NULL,
    platform_key_set_url VARCHAR(255) NOT NULL,
    access_token_url VARCHAR(255) NOT NULL,
    auth_token_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Security table to prevent replay attacks
-- Stores single-use tokens (nonces) from LTI launches
-- Required for LTI 1.1 security
CREATE TABLE lti_nonces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nonce VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    consumer_key VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nonce_consumer (nonce, consumer_key),
    INDEX idx_timestamp (timestamp)
);

-- Store LTI assignments/activities
CREATE TABLE lti_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_link_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    consumer_key VARCHAR(255) NOT NULL,
    context_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resource_link (resource_link_id, consumer_key)
);

-- Store student results
CREATE TABLE lti_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    score DECIMAL(5,2),
    result_data JSON,
    is_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (activity_id) REFERENCES lti_activities(id),
    INDEX idx_user_activity (user_id, activity_id)
);
