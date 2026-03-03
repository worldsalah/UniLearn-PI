# Tutoring Platform - System Architecture Documentation

## 1. SYSTEM ARCHITECTURE

### 1.1 Folder Structure

```
src/
├── Controller/
│   ├── Api/
│   │   ├── AuthController.php          # Authentication endpoints
│   │   ├── TeacherController.php       # Teacher listing & profiles
│   │   ├── AvailabilityController.php  # Availability management
│   │   ├── BookingController.php       # Booking CRUD & lifecycle
│   │   ├── BundleController.php        # Session packages
│   │   ├── ReviewController.php        # Reviews & ratings
│   │   └── DashboardController.php     # Analytics & stats
│   └── Admin/
│       └── AdminController.php         # Admin panel
│
├── Entity/
│   ├── User.php                        # User with roles
│   ├── TeacherProfile.php              # Teacher-specific data
│   ├── Availability.php                # Weekly recurring schedule
│   ├── TimeSlot.php                    # Generated available slots
│   ├── Booking.php                     # Booking with lifecycle
│   ├── Session.php                     # Actual session instance
│   ├── Bundle.php                      # Session package
│   ├── BundleUsage.php                 # Bundle usage tracking
│   └── Review.php                      # Review & rating
│
├── Repository/
│   ├── UserRepository.php
│   ├── BookingRepository.php
│   ├── AvailabilityRepository.php
│   └── ...
│
├── Service/
│   ├── Booking/
│   │   ├── BookingService.php          # Main booking logic
│   │   ├── BookingValidator.php        # Business rules validation
│   │   └── BookingTransition.php       # State machine
│   ├── Availability/
│   │   ├── AvailabilityService.php     # Schedule management
│   │   └── SlotGenerator.php           # Time slot generation
│   ├── Bundle/
│   │   └── BundleService.php           # Package management
│   ├── Review/
│   │   └── ReviewService.php           # Review logic
│   └── Security/
│       └── RateLimiter.php             # Rate limiting
│
├── DTO/
│   ├── BookingRequest.php
│   ├── AvailabilityRequest.php
│   ├── ReviewRequest.php
│   └── DashboardStats.php
│
├── Event/
│   ├── BookingConfirmedEvent.php
│   ├── BookingCancelledEvent.php
│   ├── SessionCompletedEvent.php
│   └── ReviewSubmittedEvent.php
│
├── EventListener/
│   ├── BookingEventListener.php
│   └── ReviewEventListener.php
│
├── Security/
│   ├── Voter/
│   │   ├── BookingVoter.php            # Authorization for bookings
│   │   └── AvailabilityVoter.php      # Authorization for availability
│   └── Middleware/
│       └── JwtMiddleware.php
│
├── Enum/
│   ├── BookingStatus.php               # Booking states enum
│   ├── BundleType.php                  # Bundle types enum
│   └── UserRole.php                    # User roles enum
│
└── Exception/
    ├── BookingException.php
    ├── AvailabilityException.php
    ├── BundleException.php
    └── BusinessRuleViolationException.php
```

### 1.2 Layer Responsibilities

| Layer | Responsibility | Example |
|-------|---------------|---------|
| **Controller** | HTTP handling, request validation, response formatting | `BookingController::create()` |
| **Service** | Business logic, orchestration, transactions | `BookingService::createBooking()` |
| **Repository** | Data access, queries, persistence | `BookingRepository::findConflicting()` |
| **Entity** | Domain model, data structure, relationships | `Booking` entity |
| **DTO** | Data transfer, input/output shaping | `BookingRequest` |
| **Event** | Decoupled communication between components | `BookingConfirmedEvent` |
| **Security** | Authorization, access control | `BookingVoter` |
| **Exception** | Domain-specific error handling | `BusinessRuleViolationException` |

### 1.3 Best Practices

1. **Single Responsibility**: Each class has one reason to change
2. **Dependency Injection**: Services injected via constructor
3. **Repository Pattern**: Data access abstraction
4. **DTO Pattern**: Separate input/output from domain
5. **Event-Driven**: Decouple with events for side effects
6. **State Machine**: For complex lifecycle management
7. **Soft Delete**: Preserve data integrity
8. **Audit Trail**: Track creation/modification timestamps

---

## 2. DATABASE DESIGN

### 2.1 Entity Relationship Diagram

```
┌─────────────┐       ┌──────────────────┐       ┌─────────────┐
│    User     │───1:1─│  TeacherProfile  │       │   Bundle    │
├─────────────┤       ├──────────────────┤       ├─────────────┤
│ id          │       │ id               │       │ id          │
│ email       │       │ user_id (FK)     │──────│ student_id  │
│ password    │       │ subjects[]       │       │ type        │
│ roles[]     │       │ hourly_rate      │       │ sessions_total│
│ first_name  │       │ bio              │       │ sessions_used│
│ last_name   │       │ education        │       │ expires_at  │
│ timezone    │       │ rating_avg       │       │ price       │
│ created_at  │       │ review_count     │       │ status      │
│ updated_at  │       │ is_verified      │       │ created_at  │
│ deleted_at  │       └──────────────────┘       └─────────────┘
└─────────────┘                                       │ 1:N
      │                                               │
      │ 1:N                                     ┌─────┴──────┐
      ▼                                         │BundleUsage │
┌─────────────┐                                 ├────────────┤
│ Availability│                                 │ id         │
├─────────────┤                                 │ bundle_id  │
│ id          │                                 │ booking_id │
│ teacher_id  │◄──────────────────────┐         │ used_at    │
│ day_of_week │                       │         └────────────┘
│ start_time  │                       │
│ end_time    │                       │
│ is_active   │                       │
└─────────────┘                       │
      │                               │
      │ 1:N                           │
      ▼                               │
┌─────────────┐     N:1               │
│  TimeSlot   │───────────────────────┘
├─────────────┤
│ id          │
│ availability_id│
│ date        │
│ start_time  │
│ end_time    │
│ status      │
└─────────────┘
      │
      │ 1:1
      ▼
┌─────────────┐     N:1       ┌─────────────┐
│   Booking   │───────────────│   Session   │
├─────────────┤               ├─────────────┤
│ id          │               │ id          │
│ student_id  │               │ booking_id  │
│ teacher_id  │               │ started_at  │
│ time_slot_id│               │ ended_at    │
│ bundle_id   │               │ status      │
│ status      │               │ notes       │
│ price       │               │ recording_url│
│ notes       │               └─────────────┘
│ cancelled_at│                     │
│ cancel_reason│                    │ 1:1
│ created_at  │                     ▼
│ updated_at  │               ┌─────────────┐
└─────────────┘               │   Review    │
                              ├─────────────┤
                              │ id          │
                              │ session_id  │
                              │ student_id  │
                              │ teacher_id  │
                              │ rating      │
                              │ comment     │
                              │ created_at  │
                              └─────────────┘
```

### 2.2 Entity Definitions

#### User Entity
```sql
CREATE TABLE user (
    id              UUID PRIMARY KEY,
    email           VARCHAR(180) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,
    roles           JSON NOT NULL DEFAULT '["ROLE_STUDENT"]',
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    timezone        VARCHAR(50) DEFAULT 'UTC',
    is_verified     BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,
    
    INDEX idx_user_email (email),
    INDEX idx_user_roles (roles),
    INDEX idx_user_deleted (deleted_at)
);
```

#### TeacherProfile Entity
```sql
CREATE TABLE teacher_profile (
    id              UUID PRIMARY KEY,
    user_id         UUID UNIQUE NOT NULL REFERENCES user(id) ON DELETE CASCADE,
    subjects        JSON NOT NULL,
    hourly_rate     DECIMAL(10,2) NOT NULL,
    bio             TEXT,
    education       VARCHAR(255),
    experience_years INT DEFAULT 0,
    rating_avg      DECIMAL(3,2) DEFAULT 0.00,
    review_count    INT DEFAULT 0,
    is_verified     BOOLEAN DEFAULT FALSE,
    
    INDEX idx_teacher_subjects (subjects),
    INDEX idx_teacher_rating (rating_avg DESC),
    INDEX idx_teacher_rate (hourly_rate)
);
```

#### Availability Entity (Weekly Recurring)
```sql
CREATE TABLE availability (
    id              UUID PRIMARY KEY,
    teacher_id      UUID NOT NULL REFERENCES user(id) ON DELETE CASCADE,
    day_of_week     TINYINT NOT NULL, -- 0=Monday, 6=Sunday
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    
    UNIQUE KEY unique_availability (teacher_id, day_of_week, start_time),
    INDEX idx_availability_teacher (teacher_id),
    INDEX idx_availability_day (day_of_week)
);
```

#### TimeSlot Entity (Generated)
```sql
CREATE TABLE time_slot (
    id              UUID PRIMARY KEY,
    availability_id UUID NOT NULL REFERENCES availability(id) ON DELETE CASCADE,
    date            DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    status          ENUM('available', 'booked', 'blocked') DEFAULT 'available',
    
    UNIQUE KEY unique_slot (availability_id, date, start_time),
    INDEX idx_slot_date (date),
    INDEX idx_slot_status (status)
);
```

#### Bundle Entity
```sql
CREATE TABLE bundle (
    id              UUID PRIMARY KEY,
    student_id      UUID NOT NULL REFERENCES user(id) ON DELETE CASCADE,
    type            ENUM('single', 'pack_5', 'pack_10') NOT NULL,
    sessions_total  INT NOT NULL,
    sessions_used    INT DEFAULT 0,
    price           DECIMAL(10,2) NOT NULL,
    expires_at      TIMESTAMP NULL,
    status          ENUM('active', 'exhausted', 'expired') DEFAULT 'active',
    purchased_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_bundle_student (student_id),
    INDEX idx_bundle_status (status),
    INDEX idx_bundle_expires (expires_at)
);
```

#### Booking Entity
```sql
CREATE TABLE booking (
    id              UUID PRIMARY KEY,
    student_id      UUID NOT NULL REFERENCES user(id),
    teacher_id      UUID NOT NULL REFERENCES user(id),
    time_slot_id    UUID UNIQUE REFERENCES time_slot(id),
    bundle_id       UUID NULL REFERENCES bundle(id),
    status          ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    price           DECIMAL(10,2) NOT NULL,
    notes           TEXT,
    cancelled_at    TIMESTAMP NULL,
    cancelled_by    UUID NULL REFERENCES user(id),
    cancel_reason   VARCHAR(255),
    confirmed_at    TIMESTAMP NULL,
    completed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_booking_student (student_id),
    INDEX idx_booking_teacher (teacher_id),
    INDEX idx_booking_status (status),
    INDEX idx_booking_date (created_at)
);
```

#### Session Entity
```sql
CREATE TABLE session (
    id              UUID PRIMARY KEY,
    booking_id      UUID UNIQUE NOT NULL REFERENCES booking(id),
    started_at      TIMESTAMP NULL,
    ended_at        TIMESTAMP NULL,
    actual_duration INT NULL, -- in minutes
    status          ENUM('scheduled', 'in_progress', 'completed', 'no_show') DEFAULT 'scheduled',
    notes           TEXT,
    recording_url   VARCHAR(500),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_status (status),
    INDEX idx_session_booking (booking_id)
);
```

#### Review Entity
```sql
CREATE TABLE review (
    id              UUID PRIMARY KEY,
    session_id      UUID UNIQUE NOT NULL REFERENCES session(id),
    student_id      UUID NOT NULL REFERENCES user(id),
    teacher_id      UUID NOT NULL REFERENCES user(id),
    rating          TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment         TEXT,
    is_public       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_review_teacher (teacher_id),
    INDEX idx_review_rating (rating)
);
```

### 2.3 Constraints Summary

| Constraint | Table | Description |
|------------|-------|-------------|
| FK | booking.student_id | References user.id |
| FK | booking.teacher_id | References user.id |
| FK | booking.time_slot_id | References time_slot.id (UNIQUE) |
| FK | booking.bundle_id | References bundle.id (nullable) |
| FK | session.booking_id | References booking.id (UNIQUE) |
| FK | review.session_id | References session.id (UNIQUE) |
| CHECK | review.rating | Rating must be 1-5 |
| UNIQUE | time_slot | (availability_id, date, start_time) |
| UNIQUE | review | One review per session |

---

## 3. ADVANCED BUSINESS LOGIC

### 3.1 Booking Lifecycle State Machine

```
                    ┌─────────────┐
                    │   PENDING   │
                    └──────┬──────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
           ▼               ▼               ▼
    ┌────────────┐  ┌─────────────┐  ┌─────────────┐
    │ CONFIRMED  │  │  CANCELLED  │  │   NO_SHOW   │
    └─────┬──────┘  └─────────────┘  └─────────────┘
          │
          │ (after session end time)
          ▼
    ┌─────────────┐
    │  COMPLETED  │
    └─────────────┘
```

### 3.2 Business Rules Implementation

#### Rule 1: Only Teacher Can Confirm
```php
// In BookingVoter.php
public function canConfirm(User $user, Booking $booking): bool
{
    return $user->getId() === $booking->getTeacher()->getId()
        && $booking->getStatus() === BookingStatus::PENDING;
}
```

#### Rule 2: Student Can Cancel Only 24h Before
```php
// In BookingValidator.php
public function canStudentCancel(Booking $booking): bool
{
    $sessionStartTime = $booking->getTimeSlot()->getDateTime();
    $now = new \DateTime();
    $hoursUntilSession = ($sessionStartTime->getTimestamp() - $now->getTimestamp()) / 3600;
    
    return $hoursUntilSession >= 24 
        && in_array($booking->getStatus(), [BookingStatus::PENDING, BookingStatus::CONFIRMED]);
}
```

#### Rule 3: Auto-Complete After End Time
```php
// In SessionCompletionCommand.php (Cron job)
public function autoCompleteSessions(): int
{
    $sessions = $this->sessionRepository->findEndedButNotCompleted();
    
    foreach ($sessions as $session) {
        $session->setStatus(SessionStatus::COMPLETED);
        $session->getBooking()->setStatus(BookingStatus::COMPLETED);
    }
    
    $this->entityManager->flush();
    return count($sessions);
}
```

#### Rule 4: Prevent Double Booking
```php
// In BookingRepository.php
public function hasConflict(string $teacherId, \DateTime $start, \DateTime $end): bool
{
    return $this->createQueryBuilder('b')
        ->join('b.timeSlot', 'ts')
        ->where('b.teacher = :teacher')
        ->andWhere('b.status NOT IN (:cancelled)')
        ->andWhere('ts.date = :date')
        ->andWhere('ts.startTime < :end AND ts.endTime > :start')
        ->setParameter('teacher', $teacherId)
        ->setParameter('cancelled', [BookingStatus::CANCELLED])
        ->setParameter('date', $start->format('Y-m-d'))
        ->setParameter('start', $start->format('H:i:s'))
        ->setParameter('end', $end->format('H:i:s'))
        ->getQuery()->getOneOrNullResult() !== null;
}
```

#### Rule 5: Deduct Session From Bundle
```php
// In BundleService.php
public function deductSession(Booking $booking): void
{
    $bundle = $booking->getBundle();
    if (!$bundle) {
        return;
    }
    
    if ($bundle->getSessionsRemaining() <= 0) {
        throw new BundleException('No sessions remaining in bundle');
    }
    
    $bundle->incrementUsed();
    
    if ($bundle->getSessionsRemaining() === 0) {
        $bundle->setStatus(BundleStatus::EXHAUSTED);
    }
    
    $usage = new BundleUsage();
    $usage->setBundle($bundle);
    $usage->setBooking($booking);
    $usage->setUsedAt(new \DateTime());
    
    $this->entityManager->persist($usage);
}
```

### 3.3 Bundle Logic

```php
// Bundle types and pricing
class BundleType
{
    public const SINGLE = 'single';    // 1 session - full price
    public const PACK_5 = 'pack_5';    // 5 sessions - 10% discount
    public const PACK_10 = 'pack_10';  // 10 sessions - 20% discount
    
    public const DISCOUNTS = [
        self::SINGLE => 0,
        self::PACK_5 => 0.10,
        self::PACK_10 => 0.20,
    ];
    
    public const SESSIONS = [
        self::SINGLE => 1,
        self::PACK_5 => 5,
        self::PACK_10 => 10,
    ];
}

// Bundle validation
public function canUseBundle(Bundle $bundle): bool
{
    if ($bundle->getStatus() !== BundleStatus::ACTIVE) {
        return false;
    }
    
    if ($bundle->getSessionsRemaining() <= 0) {
        return false;
    }
    
    if ($bundle->getExpiresAt() && $bundle->getExpiresAt() < new \DateTime()) {
        $bundle->setStatus(BundleStatus::EXPIRED);
        $this->entityManager->flush();
        return false;
    }
    
    return true;
}
```

### 3.4 Availability Engine

```php
// SlotGenerator.php
public function generateSlotsForWeek(string $teacherId, \DateTime $weekStart): array
{
    $slots = [];
    $availabilities = $this->availabilityRepository->findActiveByTeacher($teacherId);
    
    foreach ($availabilities as $availability) {
        $slotDate = clone $weekStart;
        $slotDate->modify('+' . $availability->getDayOfWeek() . ' days');
        
        $currentTime = \DateTime::createFromFormat('H:i', $availability->getStartTime()->format('H:i'));
        $endTime = \DateTime::createFromFormat('H:i', $availability->getEndTime()->format('H:i'));
        
        while ($currentTime < $endTime) {
            $slotEnd = clone $currentTime;
            $slotEnd->modify('+1 hour');
            
            $slot = new TimeSlot();
            $slot->setAvailability($availability);
            $slot->setDate($slotDate);
            $slot->setStartTime($currentTime);
            $slot->setEndTime(min($slotEnd, $endTime));
            $slot->setStatus(SlotStatus::AVAILABLE);
            
            $slots[] = $slot;
            $currentTime->modify('+1 hour');
        }
    }
    
    return $slots;
}

// Timezone handling
public function convertToUserTimezone(\DateTime $dateTime, string $userTimezone): \DateTime
{
    $utc = new \DateTimeZone('UTC');
    $userTz = new \DateTimeZone($userTimezone);
    
    $dateTime->setTimezone($utc);
    $userTime = clone $dateTime;
    $userTime->setTimezone($userTz);
    
    return $userTime;
}
```

### 3.5 Review System

```php
// ReviewService.php
public function createReview(ReviewRequest $request): Review
{
    $session = $this->sessionRepository->find($request->sessionId);
    
    // Rule 1: Only after COMPLETED session
    if ($session->getStatus() !== SessionStatus::COMPLETED) {
        throw new BusinessRuleViolationException('Can only review completed sessions');
    }
    
    // Rule 2: Only one review per session
    if ($this->reviewRepository->findBySession($session->getId())) {
        throw new BusinessRuleViolationException('Session already reviewed');
    }
    
    // Rule 3: Only the student who booked can review
    if ($session->getBooking()->getStudent()->getId() !== $request->studentId) {
        throw new AccessDeniedException('Only the booking student can review');
    }
    
    $review = new Review();
    $review->setSession($session);
    $review->setStudent($session->getBooking()->getStudent());
    $review->setTeacher($session->getBooking()->getTeacher());
    $review->setRating($request->rating);
    $review->setComment($request->comment);
    
    $this->entityManager->persist($review);
    $this->entityManager->flush();
    
    // Update teacher average rating
    $this->updateTeacherRating($session->getBooking()->getTeacher());
    
    return $review;
}

private function updateTeacherRating(User $teacher): void
{
    $stats = $this->reviewRepository->getTeacherRatingStats($teacher->getId());
    
    $profile = $teacher->getTeacherProfile();
    $profile->setRatingAvg($stats['average']);
    $profile->setReviewCount($stats['count']);
    
    $this->entityManager->flush();
}
```

---

## 4. API DESIGN

### 4.1 Authentication Endpoints

#### POST /api/auth/register
```json
// Request
{
    "email": "student@example.com",
    "password": "SecurePass123!",
    "firstName": "John",
    "lastName": "Doe",
    "role": "student",  // or "teacher"
    "timezone": "Europe/Paris"
}

// Response 201
{
    "success": true,
    "data": {
        "id": "uuid-here",
        "email": "student@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "role": "student"
    }
}

// Error 400
{
    "success": false,
    "error": "Email already exists"
}
```

#### POST /api/auth/login
```json
// Request
{
    "email": "student@example.com",
    "password": "SecurePass123!"
}

// Response 200
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "refreshToken": "refresh-token-here",
        "expiresIn": 3600,
        "user": {
            "id": "uuid",
            "email": "student@example.com",
            "roles": ["ROLE_STUDENT"]
        }
    }
}

// Error 401
{
    "success": false,
    "error": "Invalid credentials"
}
```

### 4.2 Teacher Endpoints

#### GET /api/teachers
```
Query Parameters:
- subject: string (filter by subject)
- minRating: number (minimum rating)
- maxPrice: number (maximum hourly rate)
- page: int (default: 1)
- limit: int (default: 10, max: 50)

// Response 200
{
    "success": true,
    "data": {
        "teachers": [
            {
                "id": "uuid",
                "firstName": "Jane",
                "lastName": "Smith",
                "subjects": ["Mathematics", "Physics"],
                "hourlyRate": 45.00,
                "rating": 4.8,
                "reviewCount": 127,
                "bio": "Experienced math tutor...",
                "isVerified": true
            }
        ],
        "pagination": {
            "page": 1,
            "limit": 10,
            "total": 45,
            "pages": 5
        }
    }
}
```

#### GET /api/teachers/{id}
```json
// Response 200
{
    "success": true,
    "data": {
        "id": "uuid",
        "firstName": "Jane",
        "lastName": "Smith",
        "subjects": ["Mathematics", "Physics"],
        "hourlyRate": 45.00,
        "rating": 4.8,
        "reviewCount": 127,
        "bio": "Experienced math tutor...",
        "education": "PhD Mathematics, MIT",
        "experienceYears": 8,
        "isVerified": true,
        "recentReviews": [
            {
                "rating": 5,
                "comment": "Excellent teacher!",
                "createdAt": "2024-01-15T10:30:00Z"
            }
        ]
    }
}

// Error 404
{
    "success": false,
    "error": "Teacher not found"
}
```

### 4.3 Availability Endpoints

#### POST /api/availability
```json
// Request (Teacher only)
{
    "availabilities": [
        {
            "dayOfWeek": 0,  // Monday
            "startTime": "09:00",
            "endTime": "17:00"
        },
        {
            "dayOfWeek": 2,  // Wednesday
            "startTime": "14:00",
            "endTime": "20:00"
        }
    ]
}

// Response 201
{
    "success": true,
    "data": {
        "created": 2,
        "availabilities": [...]
    }
}

// Error 400
{
    "success": false,
    "error": "Invalid time range: end time must be after start time"
}
```

#### GET /api/availability/{teacherId}
```
Query Parameters:
- startDate: date (Y-m-d)
- endDate: date (Y-m-d)

// Response 200
{
    "success": true,
    "data": {
        "teacherId": "uuid",
        "slots": [
            {
                "id": "uuid",
                "date": "2024-02-05",
                "startTime": "09:00",
                "endTime": "10:00",
                "status": "available"
            },
            {
                "id": "uuid",
                "date": "2024-02-05",
                "startTime": "10:00",
                "endTime": "11:00",
                "status": "booked"
            }
        ]
    }
}
```

### 4.4 Booking Endpoints

#### POST /api/bookings
```json
// Request (Student only)
{
    "teacherId": "uuid",
    "timeSlotId": "uuid",
    "bundleId": "uuid",  // optional - if using bundle
    "notes": "Need help with calculus"
}

// Response 201
{
    "success": true,
    "data": {
        "id": "uuid",
        "status": "pending",
        "teacher": {
            "id": "uuid",
            "name": "Jane Smith"
        },
        "timeSlot": {
            "date": "2024-02-05",
            "startTime": "09:00",
            "endTime": "10:00"
        },
        "price": 45.00,
        "bundleUsed": true,
        "sessionsRemaining": 4,
        "createdAt": "2024-01-20T14:30:00Z"
    }
}

// Error 400
{
    "success": false,
    "error": "Time slot is no longer available"
}

// Error 400
{
    "success": false,
    "error": "Bundle has no remaining sessions"
}
```

#### PATCH /api/bookings/{id}/confirm
```json
// Request (Teacher only)
{
    "notes": "Confirmed, see you then!"
}

// Response 200
{
    "success": true,
    "data": {
        "id": "uuid",
        "status": "confirmed",
        "confirmedAt": "2024-01-20T15:00:00Z"
    }
}

// Error 403
{
    "success": false,
    "error": "Only the assigned teacher can confirm this booking"
}
```

#### PATCH /api/bookings/{id}/cancel
```json
// Request
{
    "reason": "Emergency, need to reschedule"
}

// Response 200
{
    "success": true,
    "data": {
        "id": "uuid",
        "status": "cancelled",
        "cancelledAt": "2024-01-21T10:00:00Z",
        "bundleSessionRestored": true
    }
}

// Error 400
{
    "success": false,
    "error": "Cannot cancel: session is within 24 hours"
}
```

#### GET /api/bookings/my
```
Query Parameters:
- status: string (pending, confirmed, completed, cancelled)
- role: string (student, teacher) - which perspective
- page: int
- limit: int

// Response 200
{
    "success": true,
    "data": {
        "bookings": [
            {
                "id": "uuid",
                "status": "confirmed",
                "teacher": {...},
                "student": {...},
                "timeSlot": {...},
                "price": 45.00,
                "createdAt": "..."
            }
        ],
        "pagination": {...}
    }
}
```

### 4.5 Bundle Endpoints

#### POST /api/bundles/purchase
```json
// Request (Student only)
{
    "type": "pack_5",  // single, pack_5, pack_10
    "teacherId": "uuid"  // optional - for specific teacher
}

// Response 201
{
    "success": true,
    "data": {
        "id": "uuid",
        "type": "pack_5",
        "sessionsTotal": 5,
        "sessionsUsed": 0,
        "price": 202.50,  // 5 * 45 * 0.90 (10% discount)
        "expiresAt": "2024-08-20T00:00:00Z",  // 6 months
        "status": "active"
    }
}
```

#### GET /api/bundles/my
```json
// Response 200
{
    "success": true,
    "data": {
        "bundles": [
            {
                "id": "uuid",
                "type": "pack_5",
                "sessionsRemaining": 3,
                "expiresAt": "2024-08-20T00:00:00Z",
                "status": "active"
            }
        ]
    }
}
```

### 4.6 Review Endpoints

#### POST /api/reviews
```json
// Request (Student only, after completed session)
{
    "sessionId": "uuid",
    "rating": 5,
    "comment": "Excellent session! Very helpful."
}

// Response 201
{
    "success": true,
    "data": {
        "id": "uuid",
        "rating": 5,
        "comment": "Excellent session! Very helpful.",
        "createdAt": "2024-02-06T10:00:00Z"
    }
}

// Error 400
{
    "success": false,
    "error": "Session must be completed before reviewing"
}

// Error 400
{
    "success": false,
    "error": "This session has already been reviewed"
}
```

### 4.7 Dashboard Endpoints

#### GET /api/dashboard/teacher
```json
// Response 200 (Teacher only)
{
    "success": true,
    "data": {
        "stats": {
            "totalSessions": 156,
            "completedSessions": 142,
            "cancelledSessions": 8,
            "noShowSessions": 6,
            "completionRate": 91.0,
            "totalEarnings": 6390.00,
            "averageRating": 4.8
        },
        "upcomingBookings": [
            {
                "id": "uuid",
                "studentName": "John Doe",
                "date": "2024-02-05",
                "time": "09:00-10:00",
                "status": "confirmed"
            }
        ],
        "recentReviews": [...]
    }
}
```

#### GET /api/dashboard/admin
```json
// Response 200 (Admin only)
{
    "success": true,
    "data": {
        "stats": {
            "totalUsers": 1250,
            "totalStudents": 1100,
            "totalTeachers": 150,
            "totalSessions": 5420,
            "totalRevenue": 243900.00,
            "averageSessionPrice": 45.00
        },
        "recentSignups": [...],
        "topTeachers": [...]
    }
}
```

### 4.8 HTTP Status Codes

| Code | Usage |
|------|-------|
| 200 | Success (GET, PATCH) |
| 201 | Created (POST) |
| 204 | No content (DELETE) |
| 400 | Bad request / validation error |
| 401 | Unauthorized |
| 403 | Forbidden (wrong role) |
| 404 | Not found |
| 409 | Conflict (double booking) |
| 422 | Unprocessable entity |
| 429 | Too many requests (rate limit) |
| 500 | Server error |

### 4.9 Error Handling Strategy

```php
// Exception handler format
{
    "success": false,
    "error": "Human readable message",
    "code": "BOOKING_CONFLICT",
    "details": {
        "field": "timeSlotId",
        "reason": "Already booked by another student"
    }
}
```

---

## 5. SECURITY

### 5.1 JWT Authentication

```php
// jwt.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
    refresh_token_ttl: 604800
```

### 5.2 Role-Based Access Control (RBAC)

```php
// security.yaml
access_control:
    - { path: ^/api/auth, roles: PUBLIC_ACCESS }
    - { path: ^/api/teachers, roles: PUBLIC_ACCESS, methods: [GET] }
    - { path: ^/api/availability, roles: ROLE_TEACHER, methods: [POST, PUT, DELETE] }
    - { path: ^/api/bookings, roles: ROLE_STUDENT, methods: [POST] }
    - { path: ^/api/bookings/.*/confirm, roles: ROLE_TEACHER, methods: [PATCH] }
    - { path: ^/api/bundles, roles: ROLE_STUDENT }
    - { path: ^/api/reviews, roles: ROLE_STUDENT, methods: [POST] }
    - { path: ^/api/dashboard/teacher, roles: ROLE_TEACHER }
    - { path: ^/api/dashboard/admin, roles: ROLE_ADMIN }
```

### 5.3 Input Validation

```php
// BookingRequest.php (DTO)
use Symfony\Component\Validator\Constraints as Assert;

class BookingRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $teacherId;

    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $timeSlotId;

    #[Assert\Uuid]
    public ?string $bundleId = null;

    #[Assert\Length(max: 1000)]
    public ?string $notes = null;
}
```

### 5.4 Rate Limiting

```php
// RateLimiter.php
class RateLimiter
{
    private const LIMITS = [
        'booking_create' => ['limit' => 10, 'window' => 3600],    // 10 bookings/hour
        'review_create' => ['limit' => 20, 'window' => 3600],     // 20 reviews/hour
        'auth_login' => ['limit' => 5, 'window' => 300],          // 5 login attempts/5min
    ];

    public function checkLimit(string $key, string $identifier): bool
    {
        $limit = self::LIMITS[$key];
        $cacheKey = "rate_limit:{$key}:{$identifier}";
        
        $current = $this->cache->get($cacheKey, 0);
        
        if ($current >= $limit['limit']) {
            throw new RateLimitException("Rate limit exceeded for {$key}");
        }
        
        $this->cache->increment($cacheKey, 1, $limit['window']);
        return true;
    }
}
```

### 5.5 Prevent Overbooking Attacks

```php
// In BookingService.php
public function createBooking(BookingRequest $request): Booking
{
    // Use database-level lock to prevent race conditions
    $this->entityManager->beginTransaction();
    
    try {
        // Lock the time slot row
        $timeSlot = $this->timeSlotRepository->findWithLock($request->timeSlotId);
        
        if ($timeSlot->getStatus() !== SlotStatus::AVAILABLE) {
            throw new BookingException('Time slot is no longer available');
        }
        
        // Create booking...
        $timeSlot->setStatus(SlotStatus::BOOKED);
        
        $this->entityManager->flush();
        $this->entityManager->commit();
        
        return $booking;
    } catch (\Exception $e) {
        $this->entityManager->rollback();
        throw $e;
    }
}
```

---

## 6. BONUS FEATURES

### 6.1 Analytics

```php
// DashboardStatsService.php
public function getTeacherStats(string $teacherId): array
{
    return [
        'totalSessions' => $this->bookingRepository->countByTeacher($teacherId),
        'completedSessions' => $this->bookingRepository->countByStatus($teacherId, BookingStatus::COMPLETED),
        'completionRate' => $this->calculateCompletionRate($teacherId),
        'totalEarnings' => $this->paymentRepository->sumByTeacher($teacherId),
        'averageRating' => $this->reviewRepository->getAverageRating($teacherId),
        'monthlyTrend' => $this->getMonthlyTrend($teacherId),
    ];
}
```

### 6.2 Soft Delete

```php
// SoftDeleteTrait.php
trait SoftDeleteTrait
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt = null;

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTime();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }
}

// SoftDeleteRepository.php
public function findActive(): array
{
    return $this->createQueryBuilder('e')
        ->where('e.deletedAt IS NULL')
        ->getQuery()
        ->getResult();
}
```

### 6.3 Audit Fields

```php
// AuditTrait.php
trait AuditTrait
{
    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
```

### 6.4 Pagination

```php
// PaginatedResult.php
class PaginatedResult
{
    public array $items;
    public int $page;
    public int $limit;
    public int $total;
    public int $pages;

    public static function create(array $items, int $page, int $limit, int $total): self
    {
        return new self([
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
        ]);
    }
}
```

### 6.5 Filtering

```php
// TeacherFilter.php
class TeacherFilter
{
    public function apply(QueryBuilder $qb, array $filters): QueryBuilder
    {
        if (isset($filters['subject'])) {
            $qb->andWhere('JSON_CONTAINS(tp.subjects, :subject) = 1')
               ->setParameter('subject', json_encode($filters['subject']));
        }

        if (isset($filters['minRating'])) {
            $qb->andWhere('tp.ratingAvg >= :minRating')
               ->setParameter('minRating', $filters['minRating']);
        }

        if (isset($filters['maxPrice'])) {
            $qb->andWhere('tp.hourlyRate <= :maxPrice')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }

        return $qb;
    }
}
```

---

## 7. PROJECT DOCUMENTATION STRUCTURE

### 7.1 ER Diagram Explanation

```
The Entity-Relationship diagram shows:

1. USER is the central entity with two specializations:
   - Students: Can book sessions, purchase bundles, leave reviews
   - Teachers: Have profiles, set availability, receive bookings

2. AVAILABILITY defines weekly recurring schedules
   - One-to-many with TIME_SLOT (generated instances)

3. BOOKING connects students, teachers, and time slots
   - Optional relation to BUNDLE (if using session package)
   - One-to-one with SESSION (actual meeting instance)

4. BUNDLE tracks session packages
   - Tracks usage via BUNDLE_USAGE (audit trail)

5. REVIEW is linked to SESSION (not booking)
   - Ensures reviews only after actual sessions
```

### 7.2 Sequence Diagram - Booking Flow

```
Student          Frontend         API            BookingService      Database
   │                │              │                  │                │
   │──Select slot──►│              │                  │                │
   │                │──POST /bookings───────────────►│                │
   │                │              │──Validate───────►│                │
   │                │              │                  │──Lock slot────►│
   │                │              │                  │◄──Locked───────│
   │                │              │                  │──Create booking►│
   │                │              │                  │◄──Created──────│
   │                │              │◄──Return booking─│                │
   │                │◄──201 Created│                  │                │
   │◄──Show confirmation│          │                  │                │
   │                │              │                  │                │
   │                │              │  [Teacher confirms]               │
   │                │              │                  │                │
   │                │──PATCH /confirm───────────────►│                │
   │                │              │──Check teacher──►│                │
   │                │              │──Update status──►│                │
   │                │              │──Send notification│               │
   │                │◄──200 OK────│                  │                │
   │◄──Notification─│              │                  │                │
```

### 7.3 README Structure

```markdown
# Tutoring Platform

## Overview
Brief description of the platform and its features.

## Requirements
- PHP 8.2+
- Symfony 6.4+
- MySQL 8.0+
- Composer

## Installation
```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Architecture
- MVC pattern with Service layer
- Repository pattern for data access
- DTO for API input/output
- Events for decoupled logic

## API Documentation
Link to OpenAPI/Swagger documentation.

## Business Rules
Key business rules implemented.

## Testing
```bash
php bin/phpunit
```

## Contributing
Guidelines for contributors.

## License
MIT License
```

### 7.4 Professor Presentation Guide

**Key Points to Emphasize:**

1. **Separation of Concerns**
   - Controllers handle HTTP only
   - Services contain business logic
   - Repositories handle data access

2. **Business Logic Complexity**
   - State machine for booking lifecycle
   - Validation rules with proper error messages
   - Transaction handling for data integrity

3. **Security Implementation**
   - JWT for stateless authentication
   - RBAC for role-based permissions
   - Voters for fine-grained authorization

4. **Database Design**
   - Proper normalization
   - Foreign key constraints
   - Indexes for performance

5. **Real-World Features**
   - Timezone handling
   - Rate limiting
   - Soft delete
   - Audit trail

**Questions to Anticipate:**

- "Why use events?" → For decoupling and extensibility
- "Why soft delete?" → Data recovery and audit trail
- "How do you handle concurrent bookings?" → Database-level locking
- "Why DTOs?" → Separate API contract from domain model
