# Testing Guide for Advanced Course Lifecycle System

## Quick Start Testing

### 1. Run the Automated Test Suite

```bash
# Run the basic functionality test
php bin/test-course-lifecycle.php

# Run unit tests
php bin/phpunit tests/Unit/

# Run integration tests  
php bin/phpunit tests/Integration/

# Run all tests
php bin/phpunit
```

### 2. Database Setup

```bash
# Run the migration to update database schema
php bin/console doctrine:migrations:migrate

# Check migration status
php bin/console doctrine:migrations:status

# Create test database (if needed)
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test
```

## Manual Testing Steps

### Step 1: Test Basic Functionality

1. **Test the enum system:**
   ```bash
   php bin/test-course-lifecycle.php
   ```
   You should see all ✅ checkmarks if everything is working.

2. **Check database tables:**
   ```sql
   DESCRIBE course;
   DESCRIBE course_audit_log;
   DESCRIBE course_version;
   ```

### Step 2: API Testing with Postman/cURL

#### 1. Get Allowed Transitions
```bash
curl -X GET http://localhost:8000/api/courses/transitions
```

#### 2. Create Test Course (via existing form or directly in database)
```sql
INSERT INTO course (title, short_description, requirements, learning_outcomes, target_audience, thumbnail_url, duration, price, status, user_id, created_at) 
VALUES ('Test Course', 'This is a test course description that meets minimum requirements', 'Basic knowledge', 'Learn PHP', 'Developers', '/test.jpg', 2.0, 99.99, 'draft', 1, NOW());
```

#### 3. Test Course Submission
```bash
# First, get auth token for instructor
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"instructor@test.com","password":"password"}'

# Then submit course (replace COURSE_ID and TOKEN)
curl -X POST http://localhost:8000/api/courses/COURSE_ID/submit \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

#### 4. Test Course Validation
```bash
curl -X POST http://localhost:8000/api/courses/COURSE_ID/validate \
  -H "Authorization: Bearer TOKEN"
```

#### 5. Test Admin Operations
```bash
# Publish course (admin only)
curl -X POST http://localhost:8000/api/courses/COURSE_ID/publish \
  -H "Authorization: Bearer ADMIN_TOKEN"

# Reject course with reason
curl -X POST http://localhost:8000/api/courses/COURSE_ID/reject \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason":"Course needs more detailed content"}'
```

### Step 3: Web Interface Testing

1. **Start the development server:**
   ```bash
   php -S localhost:8000 -t public/
   ```

2. **Access admin dashboard:**
   - Navigate to `http://localhost:8000/admin/courses`
   - Login as admin user
   - Check course statistics
   - Review pending courses

3. **Test instructor interface:**
   - Create a new course
   - Try to submit it for review
   - Check validation messages

### Step 4: Database Verification

After testing operations, verify data integrity:

```sql
-- Check course status changes
SELECT id, title, status, submitted_at, reviewed_at, published_at FROM course;

-- Check audit log entries
SELECT * FROM course_audit_log ORDER BY created_at DESC;

-- Check version control
SELECT * FROM course_version ORDER BY version_number DESC;

-- Verify status transitions are valid
SELECT from_status, to_status, COUNT(*) as count 
FROM course_audit_log 
GROUP BY from_status, to_status;
```

## Expected Test Results

### 1. CourseStatus Enum Tests
- ✅ All enum values accessible
- ✅ Transitions work correctly
- ✅ Role permissions enforced
- ✅ Visibility rules applied

### 2. API Tests
- ✅ 200 OK for valid operations
- ✅ 403 Forbidden for unauthorized access
- ✅ 400 Bad Request for invalid data
- ✅ Proper JSON responses

### 3. Database Tests
- ✅ All tables created with correct structure
- ✅ Foreign key constraints working
- ✅ Indexes properly created
- ✅ Data integrity maintained

### 4. Security Tests
- ✅ Role-based access control enforced
- ✅ Audit logging captures all changes
- ✅ Input validation prevents bad data
- ✅ CSRF protection on forms

## Troubleshooting

### Common Issues

1. **Migration fails:**
   ```bash
   # Check if migration file exists
   ls migrations/Version20260211000001.php
   
   # Check current migration status
   php bin/console doctrine:migrations:status
   ```

2. **Tests fail with 404:**
   - Check if routes are registered
   - Verify cache is cleared: `php bin/console cache:clear`
   - Check routing: `php bin/console debug:router`

3. **Permission denied errors:**
   - Verify user roles in database
   - Check security configuration
   - Ensure user is properly authenticated

4. **Database connection issues:**
   - Check `.env` file for correct database settings
   - Verify database server is running
   - Check database credentials

### Debug Commands

```bash
# Check Symfony environment
php bin/console debug:container --env=dev

# Check routes
php bin/console debug:router

# Check entities
php bin/console doctrine:mapping:info

# Check database connection
php bin/console doctrine:database:create --env=test

# Validate schema
php bin/console doctrine:schema:validate
```

## Performance Testing

### Load Testing Script

```bash
# Create simple load test
for i in {1..100}; do
  curl -X GET http://localhost:8000/api/courses/transitions &
done
wait
```

### Database Performance

```sql
-- Check slow queries
SHOW FULL PROCESSLIST;

-- Analyze query performance
EXPLAIN SELECT * FROM course WHERE status = 'published';

-- Check indexes
SHOW INDEX FROM course;
SHOW INDEX FROM course_audit_log;
```

## Production Readiness Checklist

- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] Database migration runs successfully
- [ ] API endpoints return correct responses
- [ ] Security measures are enforced
- [ ] Audit logging works correctly
- [ ] Performance is acceptable
- [ ] Error handling is comprehensive
- [ ] Documentation is complete
- [ ] Monitoring is configured

## Continuous Integration

Add to your `.github/workflows/tests.yml`:

```yaml
name: Course Lifecycle Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: doctrine, symfony
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: |
        php bin/test-course-lifecycle.php
        php bin/phpunit
        
    - name: Check code quality
      run: |
        php bin/console doctrine:schema:validate
        php bin/phpunit --coverage-clover coverage.xml
```

This comprehensive testing guide will help you verify that the advanced course lifecycle system is working correctly and is ready for production use.
