# Tutoring Platform Backend

A professional online tutoring platform backend with advanced business logic for managing students, teachers, bookings, bundles, and reviews.

## Features

- **User Management**: Students, Teachers, and Admin roles with JWT authentication
- **Booking System**: Complete lifecycle management (Pending → Confirmed → Completed/Cancelled/No-Show)
- **Availability Engine**: Weekly recurring schedules with automatic time slot generation
- **Bundle System**: Session packages (1, 5, 10 sessions) with discounts and expiration
- **Review System**: Post-session reviews with automatic teacher rating updates
- **Analytics Dashboard**: Statistics for teachers, students, and admins

## Architecture

```
src/
├── Controller/Api/          # REST API endpoints
├── Entity/                   # Domain models
├── Enum/                     # Status and type enums
├── Exception/                # Domain exceptions
├── Repository/               # Data access layer
├── Security/Voter/           # Authorization voters
└── Service/                  # Business logic layer
    ├── Availability/         # Schedule management
    ├── Booking/              # Booking lifecycle
    ├── Bundle/               # Package management
    └── Security/             # Rate limiting
```

## Requirements

- PHP 8.2+
- Symfony 6.4+
- MySQL 8.0+
- Composer

## Installation

```bash
# Clone repository
git clone <repository-url>
cd tutoring-platform

# Install dependencies
composer install

# Configure environment
cp .env .env.local
# Edit .env.local with your database credentials

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair
```

## API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login and get JWT token |

### Teachers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/teachers` | List teachers (filterable) |
| GET | `/api/teachers/{id}` | Get teacher details |
| GET | `/api/teachers/{id}/availability` | Get available slots |

### Availability (Teacher only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/availability` | Set weekly schedule |
| GET | `/api/availability/my` | Get my schedule |
| PUT | `/api/availability/{id}` | Update availability |
| DELETE | `/api/availability/{id}` | Delete availability |

### Bookings
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/bookings` | Create booking (Student) |
| PATCH | `/api/bookings/{id}/confirm` | Confirm booking (Teacher) |
| PATCH | `/api/bookings/{id}/cancel` | Cancel booking |
| GET | `/api/bookings/my` | Get my bookings |
| GET | `/api/bookings/{id}` | Get booking details |

### Bundles (Student only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/bundles/purchase` | Purchase session package |
| GET | `/api/bundles/my` | Get my bundles |
| GET | `/api/bundles/stats` | Get bundle statistics |
| POST | `/api/bundles/calculate` | Calculate price preview |

### Reviews
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/reviews` | Create review (Student) |
| GET | `/api/reviews/teacher/{id}` | Get teacher reviews |
| GET | `/api/reviews/my` | Get my reviews |
| PUT | `/api/reviews/{id}` | Update review (24h window) |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/dashboard/teacher` | Teacher statistics |
| GET | `/api/dashboard/student` | Student statistics |
| GET | `/api/dashboard/admin` | Admin statistics |

## Business Rules

### Booking Lifecycle

```
PENDING → CONFIRMED → COMPLETED
    ↓         ↓
CANCELLED  CANCELLED
    ↓         ↓
           NO_SHOW
```

### Cancellation Rules
- **Students**: Can cancel only if session is more than 24 hours away
- **Teachers**: Can cancel anytime
- **Admins**: Can cancel anytime

### Bundle Rules
- **Single**: 1 session, 0% discount, 2 months expiration
- **Pack 5**: 5 sessions, 10% discount, 4 months expiration
- **Pack 10**: 10 sessions, 20% discount, 6 months expiration

### Review Rules
- Only after completed session
- One review per session
- Can edit within 24 hours of creation
- Teacher rating auto-updates

## Security

### JWT Authentication
```json
// Login request
POST /api/auth/login
{
    "email": "user@example.com",
    "password": "password"
}

// Response
{
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "...",
    "expiresIn": 3600
}
```

### Role-Based Access Control
- `ROLE_STUDENT`: Can book sessions, purchase bundles, leave reviews
- `ROLE_TEACHER`: Can set availability, confirm bookings
- `ROLE_ADMIN`: Full access to all features

### Rate Limiting
| Action | Limit | Window |
|--------|-------|--------|
| Booking creation | 10 | 1 hour |
| Review creation | 20 | 1 hour |
| Login attempts | 5 | 5 minutes |
| API general | 100 | 1 minute |

## Database Schema

### Key Entities
- **User**: Students, Teachers, Admins with soft delete
- **TeacherProfile**: Teacher-specific data, rating, subjects
- **Availability**: Weekly recurring schedule
- **TimeSlot**: Generated available time slots
- **Booking**: Session booking with lifecycle
- **TutoringSession**: Actual session instance
- **Bundle**: Session packages
- **Review**: Post-session reviews

### Relationships
```
User (Student) ──1:N──► Booking ◄──1:N── User (Teacher)
                            │
                            1:1
                            ▼
                     TutoringSession
                            │
                            1:1
                            ▼
                         Review

User (Student) ──1:N──► Bundle ──1:N──► BundleUsage
                            │
                            1:N
                            ▼
                         Booking

TeacherProfile ──1:N──► Availability ──1:N──► TimeSlot
                                                    │
                                                    1:1
                                                    ▼
                                                 Booking
```

## Cron Jobs

Add these to your crontab:

```bash
# Auto-complete ended sessions (every 15 minutes)
*/15 * * * * php bin/console app:session:auto-complete

# Expire bundles (every hour)
0 * * * * php bin/console app:bundle:expire

# Clean up old time slots (daily)
0 0 * * * php bin/console app:slots:cleanup
```

## Testing

```bash
# Run all tests
php bin/phpunit

# Run specific test
php bin/phpunit tests/Service/BookingServiceTest.php
```

## Error Handling

All API errors follow this format:

```json
{
    "success": false,
    "error": "Human readable message",
    "code": "ERROR_CODE",
    "details": {}
}
```

### Common Error Codes
| Code | HTTP Status | Description |
|------|-------------|-------------|
| `slot_not_available` | 400 | Time slot already booked |
| `double_booking` | 400 | Student has conflicting booking |
| `cancellation_too_late` | 400 | Within 24h cancellation window |
| `bundle_expired` | 400 | Bundle has expired |
| `not_authorized` | 403 | Wrong role or ownership |

## Professor Presentation Guide

### Key Points to Emphasize

1. **Separation of Concerns**
   - Controllers: HTTP handling only
   - Services: Business logic
   - Repositories: Data access
   - Entities: Domain model

2. **Business Logic Complexity**
   - State machine for booking lifecycle
   - Transaction handling for race conditions
   - Validation rules with clear error messages

3. **Security Implementation**
   - JWT for stateless authentication
   - RBAC for role permissions
   - Voters for fine-grained authorization
   - Rate limiting for abuse prevention

4. **Database Design**
   - Proper normalization
   - Foreign key constraints
   - Indexes for performance
   - Soft delete for data recovery

### Questions to Anticipate

**Q: Why use events?**
A: For decoupling components and enabling future extensibility without modifying core logic.

**Q: Why soft delete?**
A: Data recovery capability and audit trail preservation.

**Q: How do you handle concurrent bookings?**
A: Database-level row locking prevents race conditions during booking creation.

**Q: Why DTOs?**
A: Separate API contract from domain model, allowing each to evolve independently.

## License

MIT License
