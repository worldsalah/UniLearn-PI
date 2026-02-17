# Advanced Course Lifecycle Management System

## Overview

This system implements a production-ready course lifecycle management with strict state transitions, audit logging, version control, and event-driven notifications.

## Architecture Features

### 1. **State Machine with Strict Transitions**

The system uses a proper enum-based state machine with the following states:

```
DRAFT → IN_REVIEW → PUBLISHED → ARCHIVED
         ↓           ↓
      REJECTED   SOFT_DELETED
         ↓
      SOFT_DELETED
```

**Key Features:**
- **No invalid transitions allowed** - Each state has explicitly defined allowed transitions
- **Role-based access control** - Different transitions require different user roles
- **Business rule validation** - Courses must meet quality standards before submission

### 2. **Comprehensive Audit Trail**

Every status change is logged with:
- **Who made the change** (user, IP, user agent)
- **When it happened** (timestamp)
- **Why it happened** (rejection reasons, metadata)
- **Before/after states** (from/to status)

### 3. **Version Control System**

Published courses are automatically versioned:
- **Snapshot of all course data** at publish time
- **Curriculum preservation** (chapters and lessons structure)
- **Restore capability** for administrators
- **Version history tracking**

### 4. **Event-Driven Notifications**

Real-time notifications for:
- **Instructors** when courses are submitted, published, or rejected
- **Administrators** when courses need review
- **Students** when courses become available

### 5. **Advanced Business Rules**

#### Course Submission Validation:
- Minimum 3 lessons required
- Minimum 30 minutes duration
- Complete course information (title, description, requirements, outcomes)
- Thumbnail image required
- Proper categorization

#### Role-Based Permissions:
- **Instructors**: Can submit, edit draft courses
- **Administrators**: Can publish, reject, archive, delete courses
- **Students**: Can only view published courses

## Technical Implementation

### Database Schema

```sql
-- Enhanced course table
ALTER TABLE course ADD COLUMN submitted_at DATETIME;
ALTER TABLE course ADD COLUMN reviewed_at DATETIME;
ALTER TABLE course ADD COLUMN published_at DATETIME;
ALTER TABLE course ADD COLUMN archived_at DATETIME;
ALTER TABLE course ADD COLUMN rejection_reason TEXT;
ALTER TABLE course ADD COLUMN version_number INT DEFAULT 1;
ALTER TABLE course ADD COLUMN is_locked BOOLEAN DEFAULT FALSE;
ALTER TABLE course ADD COLUMN last_modified_by INT;

-- Audit logging
CREATE TABLE course_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    changed_by INT NOT NULL,
    from_status VARCHAR(20) NOT NULL,
    to_status VARCHAR(20) NOT NULL,
    reason TEXT,
    metadata JSON,
    created_at DATETIME NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255)
);

-- Version control
CREATE TABLE course_version (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    version_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    short_description TEXT NOT NULL,
    curriculum_snapshot JSON,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY (course_id, version_number)
);
```

### API Endpoints

#### Course Lifecycle Management:
- `POST /api/courses/{id}/submit` - Submit course for review
- `POST /api/courses/{id}/publish` - Publish course (Admin only)
- `POST /api/courses/{id}/reject` - Reject course with reason (Admin only)
- `POST /api/courses/{id}/archive` - Archive course (Admin only)
- `DELETE /api/courses/{id}` - Soft delete course (Admin only)
- `POST /api/courses/{id}/restore` - Restore deleted course (Admin only)

#### Validation & Information:
- `POST /api/courses/{id}/validate` - Validate course for submission
- `GET /api/courses/{id}/history` - Get course audit history
- `GET /api/courses/{id}/versions` - Get course versions
- `POST /api/courses/{id}/restore-from-version/{versionId}` - Restore from version
- `GET /api/courses/transitions` - Get all allowed transitions

### Service Layer Architecture

#### CourseLifecycleService
```php
// Key methods:
- canTransitionTo(Course $course, CourseStatus $newStatus): bool
- validateCourseForSubmission(Course $course): array
- transitionCourseStatus(Course $course, CourseStatus $newStatus, ?string $reason): array
- getCourseHistory(Course $course): array
- getCourseVersions(Course $course): array
- restoreFromVersion(Course $course, CourseVersion $version, User $user): array
```

#### Event System
```php
// Events dispatched:
CourseStatusChangeEvent - Triggered on every status change

// Subscribers:
CourseNotificationSubscriber - Handles all notifications
```

## Security Features

### 1. **Access Control**
- Role-based permissions for each transition
- Ownership verification for instructors
- CSRF protection for web forms
- IP and user agent tracking in audit logs

### 2. **Concurrency Protection**
- Database-level locking with `is_locked` field
- Optimistic locking for status updates
- Atomic transactions for state changes

### 3. **Data Integrity**
- Foreign key constraints
- Not null constraints on critical fields
- Unique constraints on version numbers
- Cascade deletes for related data

## Performance Optimizations

### 1. **Database Indexing**
```sql
INDEX on course_audit_log (course_id, created_at)
INDEX on course_audit_log (changed_by)
INDEX on course_version (course_id, version_number)
INDEX on course (status, published_at)
```

### 2. **Query Optimization**
- Efficient status-based filtering
- Lazy loading for relationships
- Batch operations for bulk updates

### 3. **Caching Strategy**
- Course status caching
- Audit log pagination
- Version metadata caching

## Error Handling Strategy

### 1. **Validation Errors**
```json
{
    "status": "error",
    "message": "Course validation failed: Course must have at least 3 lessons"
}
```

### 2. **Transition Errors**
```json
{
    "status": "error", 
    "message": "Cannot transition from Published to Draft"
}
```

### 3. **Permission Errors**
```json
{
    "status": "error",
    "message": "User must have ROLE_ADMIN to perform this action"
}
```

## Best Practices Implemented

### 1. **Clean Architecture**
- Separation of concerns (Controller → Service → Repository)
- Dependency injection
- Single responsibility principle
- Interface segregation

### 2. **Domain-Driven Design**
- Rich domain models (CourseStatus enum)
- Business logic in service layer
- Aggregate roots (Course entity)

### 3. **SOLID Principles**
- Single responsibility: Each service has one purpose
- Open/closed: Easy to extend with new statuses
- Liskov substitution: Enums are interchangeable
- Interface segregation: Specific interfaces for specific needs
- Dependency inversion: Depend on abstractions

## Testing Strategy

### 1. **Unit Tests**
- CourseStatus enum transitions
- CourseLifecycleService validation
- Event dispatching

### 2. **Integration Tests**
- API endpoint functionality
- Database transactions
- Event subscriber behavior

### 3. **End-to-End Tests**
- Complete course lifecycle
- User role scenarios
- Error handling paths

## Deployment Considerations

### 1. **Migration Strategy**
- Run database migration first
- Update existing course statuses
- Handle data consistency

### 2. **Monitoring**
- Status transition success rates
- Audit log growth
- Performance metrics

### 3. **Backup Strategy**
- Regular database backups
- Version data preservation
- Audit log retention policies

## Project Defense Points

### 1. **Technical Excellence**
- **State Machine Pattern**: Proper implementation with enum-based states
- **Audit Trail**: Complete traceability for compliance
- **Version Control**: Git-like versioning for course content
- **Event-Driven Architecture**: Decoupled notification system

### 2. **Business Value**
- **Quality Assurance**: Validation ensures course standards
- **Risk Management**: Audit logs for compliance and disputes
- **User Experience**: Clear status feedback and notifications
- **Scalability**: Clean architecture supports growth

### 3. **Security & Compliance**
- **Access Control**: Role-based permissions
- **Data Integrity**: Constraints and validations
- **Auditability**: Complete change history
- **Privacy**: IP tracking and user consent

### 4. **Maintainability**
- **Clean Code**: Well-structured, documented code
- **Testability**: Comprehensive test coverage
- **Extensibility**: Easy to add new features
- **Performance**: Optimized queries and caching

This implementation demonstrates enterprise-level software development with proper architecture, security, and business logic suitable for a university project defense.
