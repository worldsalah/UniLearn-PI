-- Insert user role first
INSERT INTO roles (name) VALUES ('user');

-- Insert test user
INSERT INTO user (full_name, email, password, role_id, agree_terms, status, created_at, bio, location)
VALUES (
    'Test User',
    'test@unilearn.com',
    '$2y$10$wlA/iO7WGWRE8k2jpZSnPeE15pqU8VRmPKqJVWIw0Zw8eUHVodbKy',
    1,
    1,
    'active',
    datetime('now'),
    'Test user for marketplace development',
    'Tunisia'
);
