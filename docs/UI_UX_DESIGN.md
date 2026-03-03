# Tutoring Platform - UI/UX Design

## User Flows

### 1. Student Journey

```
Register → Login → Browse Teachers → View Profile → Check Availability → Book Session → Confirm → Attend → Review
```

### 2. Teacher Journey

```
Register → Login → Setup Profile → Set Availability → Receive Bookings → Confirm/Decline → Conduct Session → Receive Reviews
```

---

## Page Designs

### 1. Homepage (`/`)

```
┌─────────────────────────────────────────────────────────────────┐
│  LOGO                    Home  Teachers  How it Works  Login    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│     ┌─────────────────────────────────────────────────────┐    │
│     │                                                     │    │
│     │         Find Your Perfect Tutor                     │    │
│     │                                                     │    │
│     │   ┌──────────────────┐  ┌──────────────────┐       │    │
│     │   │ Subject          │  │ 📅 Date          │       │    │
│     │   └──────────────────┘  └──────────────────┘       │    │
│     │                                                     │    │
│     │   ┌────────────────────────────────────┐           │    │
│     │   │         🔍 Search Tutors           │           │    │
│     │   └────────────────────────────────────┘           │    │
│     │                                                     │    │
│     └─────────────────────────────────────────────────────┘    │
│                                                                 │
│  ─────────────────── Featured Tutors ───────────────────       │
│                                                                 │
│   ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐          │
│   │  👨‍🏫    │  │  👩‍🏫    │  │  👨‍🏫    │  │  👩‍🏫    │          │
│   │ John D. │  │ Sarah M.│  │ Mike R. │  │ Emma K. │          │
│   │ ⭐ 4.9  │  │ ⭐ 4.8  │  │ ⭐ 4.7  │  │ ⭐ 4.9  │          │
│   │ Math    │  │ Physics │  │ Chem    │  │ Biology │          │
│   │ $45/hr  │  │ $50/hr  │  │ $40/hr  │  │ $55/hr  │          │
│   └─────────┘  └─────────┘  └─────────┘  └─────────┘          │
│                                                                 │
│  ───────────────── How It Works ─────────────────              │
│                                                                 │
│   1️⃣ Find Tutor    2️⃣ Book Session    3️⃣ Learn & Grow       │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│  Footer: About | Contact | FAQ | Terms | Privacy               │
└─────────────────────────────────────────────────────────────────┘
```

---

### 2. Teacher Listing Page (`/teachers`)

```
┌─────────────────────────────────────────────────────────────────┐
│  LOGO                    Home  Teachers  How it Works  Login    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Filters:                                                  │  │
│  │ Subject [All ▼]  Rating [4+ ⭐]  Price [$0-$100]          │  │
│  │                                            [Clear] [Apply]│  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  Showing 24 tutors for "Mathematics"                            │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  ┌──────┐                                                │  │
│  │  │      │  Dr. John Smith                    ⭐ 4.9 (127)│  │
│  │  │ 👨‍🏫   │  Mathematics • Calculus • Algebra             │  │
│  │  │      │  🎓 PhD from MIT • 8 years experience          │  │
│  │  └──────┘                                                │  │
│  │                                   $45/hr    [View Profile]│  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  ┌──────┐                                                │  │
│  │  │      │  Prof. Sarah Miller                 ⭐ 4.8 (89)│  │
│  │  │ 👩‍🏫   │  Physics • Quantum Mechanics • Optics        │  │
│  │  │      │  🎓 Stanford University • 12 years            │  │
│  │  └──────┘                                                │  │
│  │                                   $50/hr    [View Profile]│  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  ┌──────┐                                                │  │
│  │  │      │  Michael Brown                   ⭐ 4.7 (56)  │  │
│  │  │ 👨‍🏫   │  Chemistry • Organic • Biochemistry           │  │
│  │  │      │  🎓 Harvard Graduate • 5 years                  │  │
│  │  └──────┘                                                │  │
│  │                                   $40/hr    [View Profile]│  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│              [← Previous]  1  2  3  ...  5  [Next →]           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 3. Teacher Profile Page (`/teachers/{id}`)

```
┌─────────────────────────────────────────────────────────────────┐
│  LOGO                    Home  Teachers  How it Works  Login    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  ┌────────┐                                              │ │
│  │  │        │   Dr. John Smith                              │ │
│  │  │   👨‍🏫   │   ⭐ 4.9 (127 reviews) • ✓ Verified           │ │
│  │  │        │   Mathematics • Calculus • Algebra            │ │
│  │  └────────┘                                              │ │
│  │                                                          │ │
│  │   $45/hour                                               │ │
│  │   [Book Session]  [❤️ Save]  [💬 Message]                │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌─────────────────────┐  ┌─────────────────────────────────┐  │
│  │ About               │  │ Availability Calendar           │  │
│  │                     │  │                                 │  │
│  │ 🎓 PhD Mathematics  │  │     March 2026                  │  │
│  │    MIT, 2018        │  │  Su Mo Tu We Th Fr Sa           │  │
│  │                     │  │      1  2  3  4  5  6           │  │
│  │ 💼 8 years teaching │  │   7  8  9 10 11 12 13           │  │
│  │    experience       │  │  14 15 16 17 18 19 20           │  │
│  │                     │  │  21 22 23 24 25 26 27           │  │
│  │ 🌍 Speaks: English, │  │  28 29 30 31                    │  │
│  │    Spanish          │  │                                 │  │
│  │                     │  │  Select a date to see slots:   │  │
│  │ 📚 Subjects:        │  │                                 │  │
│  │    • Calculus       │  │  ┌─────────────────────────┐   │  │
│  │    • Algebra        │  │  │ March 15, 2026          │   │  │
│  │    • Statistics     │  │  │                         │   │  │
│  │                     │  │  │ ○ 09:00 - 10:00         │   │  │
│  │                     │  │  │ ○ 10:00 - 11:00         │   │  │
│  │                     │  │  │ ● 11:00 - 12:00 ✓       │   │  │
│  │                     │  │  │ ○ 14:00 - 15:00         │   │  │
│  │                     │  │  │ ○ 15:00 - 16:00         │   │  │
│  │                     │  │  └─────────────────────────┘   │  │
│  │                     │  │                                 │  │
│  │                     │  │  [Continue to Book]            │  │
│  └─────────────────────┘  └─────────────────────────────────┘  │
│                                                                 │
│  ─────────────────── Reviews (127) ─────────────────           │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ ⭐⭐⭐⭐⭐  "Excellent teacher! Very patient..."           │  │
│  │  - Jane D. • March 10, 2026                              │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ ⭐⭐⭐⭐⭐  "Helped me understand calculus finally!"       │  │
│  │  - Mike R. • March 8, 2026                               │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4. Booking Modal

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │                    Book Session                     ✕    │  │
│   ├─────────────────────────────────────────────────────────┤  │
│   │                                                         │  │
│   │   Tutor: Dr. John Smith                                 │  │
│   │   Subject: Mathematics                                  │  │
│   │                                                         │  │
│   │   ┌─────────────────────────────────────────────────┐   │  │
│   │   │ Select Date & Time                              │   │  │
│   │   │                                                  │   │  │
│   │   │ 📅 March 15, 2026                               │   │  │
│   │   │ 🕐 11:00 - 12:00                                │   │  │
│   │   └─────────────────────────────────────────────────┘   │  │
│   │                                                         │  │
│   │   ┌─────────────────────────────────────────────────┐   │  │
│   │   │ Payment Options                                 │   │  │
│   │   │                                                  │   │  │
│   │   │ ○ Single Session - $45.00                        │   │  │
│   │   │                                                  │   │  │
│   │   │ ● 5-Session Pack - $202.50 (Save $22.50)        │   │  │
│   │   │   4 sessions remaining                           │   │  │
│   │   │                                                  │   │  │
│   │   │ ○ 10-Session Pack - $360.00 (Save $90.00)       │   │  │
│   │   └─────────────────────────────────────────────────┘   │  │
│   │                                                         │  │
│   │   ┌─────────────────────────────────────────────────┐   │  │
│   │   │ Notes for tutor (optional)                       │   │  │
│   │   │ ┌─────────────────────────────────────────────┐   │  │  │
│   │   │ │ Need help with integration techniques...   │   │  │  │
│   │   │ └─────────────────────────────────────────────┘   │  │  │
│   │   └─────────────────────────────────────────────────┘   │  │
│   │                                                         │  │
│   │   Total: $45.00                                         │  │
│   │                                                         │  │
│   │   [Cancel]                        [Confirm Booking]      │  │
│   │                                                         │  │
│   └─────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 5. Student Dashboard (`/dashboard/student`)

```
┌─────────────────────────────────────────────────────────────────┐
│  LOGO          Dashboard  My Sessions  My Bundles  Messages  👤  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Welcome back, Jane!                                            │
│                                                                 │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐               │
│  │ Total      │  │ Upcoming   │  │ Bundle     │               │
│  │ Sessions   │  │ Sessions   │  │ Sessions   │               │
│  │            │  │            │  │            │               │
│  │    12      │  │     3      │  │     7      │               │
│  └────────────┘  └────────────┘  └────────────┘               │
│                                                                 │
│  ─────────────────── Upcoming Sessions ─────────────────       │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 📅 Mar 15, 2026 • 11:00 AM                               │  │
│  │ Dr. John Smith - Calculus                                │  │
│  │ Status: ✓ Confirmed                                      │  │
│  │                          [Join Session] [Reschedule] [✕] │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 📅 Mar 18, 2026 • 2:00 PM                                │  │
│  │ Prof. Sarah Miller - Physics                             │  │
│  │ Status: ⏳ Pending Confirmation                          │  │
│  │                                    [View Details] [✕]    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ─────────────────── My Bundles ─────────────────              │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 5-Session Pack with Dr. John Smith                       │  │
│  │ ████████░░ 3/5 sessions used                             │  │
│  │ Expires: April 15, 2026                                  │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ─────────────────── Recent Reviews ─────────────────          │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Your review for Dr. John Smith                           │  │
│  │ ⭐⭐⭐⭐⭐ "Great session!" • Mar 10, 2026                 │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 6. Teacher Dashboard (`/dashboard/teacher`)

```
┌─────────────────────────────────────────────────────────────────┐
│  LOGO          Dashboard  My Schedule  My Students  Earnings  👤│
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Welcome back, Dr. Smith!                                      │
│                                                                 │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐               │
│  │ Total      │  │ This Week  │  │ Earnings   │               │
│  │ Students   │  │ Sessions   │  │ This Month │               │
│  │            │  │            │  │            │               │
│  │    45      │  │     8      │  │  $1,240    │               │
│  └────────────┘  └────────────┘  └────────────┘               │
│                                                                 │
│  ─────────────────── Today's Schedule ─────────────────        │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 09:00 - 10:00  │ Jane D.        │ ⏳ Waiting              │  │
│  │                │ Calculus       │ [Start Session]         │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 11:00 - 12:00  │ Mike R.        │ ✓ Confirmed             │  │
│  │                │ Algebra        │ [View Details]           │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 14:00 - 15:00  │ New Request   │ ⚠️ Pending              │  │
│  │                │ Statistics    │ [Accept] [Decline]       │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ─────────────────── Pending Requests ─────────────────        │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Sarah K. wants to book:                                  │  │
│  │ Mar 20, 2026 • 10:00 AM - Statistics                     │  │
│  │ [Accept] [Propose Different Time] [Decline]              │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ─────────────────── Recent Reviews ─────────────────          │
│                                                                 │
│  ⭐⭐⭐⭐⭐ "Best math tutor ever!" - Jane D.                    │
│  ⭐⭐⭐⭐⭐ "Very patient and helpful" - Mike R.                 │
│                                                                 │
│  Average Rating: ⭐ 4.9 (127 reviews)                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 7. Set Availability Page (`/teacher/availability`)

```
┌─────────────────────────────────────────────────────────────────┐
│  LOGO          Dashboard  My Schedule  My Students  Earnings  👤│
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Set Your Weekly Availability                                   │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                                                          │  │
│  │   Monday        ┌─────────┐ to ┌─────────┐  [+ Add]      │  │
│  │   ☑️           │ 09:00   │    │ 12:00   │                │  │
│  │                 └─────────┘    └─────────┘                │  │
│  │                 ┌─────────┐ to ┌─────────┐  [✕ Remove]    │  │
│  │                 │ 14:00   │    │ 18:00   │                │  │
│  │                 └─────────┘    └─────────┘                │  │
│  │                                                          │  │
│  │   Tuesday       ┌─────────┐ to ┌─────────┐  [+ Add]      │  │
│  │   ☑️           │ 10:00   │    │ 16:00   │                │  │
│  │                 └─────────┘    └─────────┘                │  │
│  │                                                          │  │
│  │   Wednesday     ┌─────────┐ to ┌─────────┐  [+ Add]      │  │
│  │   ☐            │ 09:00   │    │ 17:00   │                │  │
│  │                 └─────────┘    └─────────┘                │  │
│  │                                                          │  │
│  │   Thursday      No availability set        [+ Add]       │  │
│  │   ☐                                                       │  │
│  │                                                          │  │
│  │   Friday        ┌─────────┐ to ┌─────────┐  [+ Add]      │  │
│  │   ☑️           │ 09:00   │    │ 15:00   │                │  │
│  │                 └─────────┘    └─────────┘                │  │
│  │                                                          │  │
│  │   Saturday      No availability set        [+ Add]       │  │
│  │   ☐                                                       │  │
│  │                                                          │  │
│  │   Sunday        No availability set        [+ Add]       │  │
│  │   ☐                                                       │  │
│  │                                                          │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  Session Duration: [30 min ▼] [45 min] [60 min ✓]              │
│                                                                 │
│  Buffer Time Between Sessions: [15 min ▼]                      │
│                                                                 │
│                              [Cancel]  [Save Schedule]          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 8. Session Review Modal

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │                  Rate Your Session                 ✕     │  │
│   ├─────────────────────────────────────────────────────────┤  │
│   │                                                         │  │
│   │   Session with: Dr. John Smith                          │  │
│   │   Date: March 15, 2026 • 11:00 AM                       │  │
│   │   Subject: Calculus                                     │  │
│   │                                                         │  │
│   │   How was your session?                                 │  │
│   │                                                         │  │
│   │         ⭐        ⭐        ⭐        ⭐        ⭐          │  │
│   │        Poor      Fair     Good    Very Good  Excellent  │  │
│   │                                                         │  │
│   │   ┌─────────────────────────────────────────────────┐   │  │
│   │   │ Write a review (optional)                        │   │  │
│   │   │ ┌─────────────────────────────────────────────┐   │  │  │
│   │   │ │ Dr. Smith was very helpful. He explained   │   │  │  │
│   │   │ │ integration in a way I finally understood! │   │  │  │
│   │   │ │ I highly recommend him.                    │   │  │  │
│   │   │ └─────────────────────────────────────────────┘   │  │  │
│   │   └─────────────────────────────────────────────────┘   │  │
│   │                                                         │  │
│   │   ☑️ Make this review public                            │  │
│   │                                                         │  │
│   │   [Skip]                         [Submit Review]         │  │
│   │                                                         │  │
│   └─────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Color Palette

| Color | Hex | Usage |
|-------|-----|-------|
| Primary Blue | `#3B82F6` | Buttons, links, highlights |
| Secondary Purple | `#8B5CF6` | Accents, badges |
| Success Green | `#10B981` | Confirmed status, success messages |
| Warning Yellow | `#F59E0B` | Pending status, warnings |
| Error Red | `#EF4444` | Errors, cancelled status |
| Dark Gray | `#1F2937` | Text, headings |
| Light Gray | `#F3F4F6` | Backgrounds, cards |
| White | `#FFFFFF` | Card backgrounds |

---

## Typography

| Element | Font | Size | Weight |
|---------|------|------|--------|
| H1 | Inter | 32px | Bold |
| H2 | Inter | 24px | Semi-bold |
| H3 | Inter | 18px | Semi-bold |
| Body | Inter | 16px | Regular |
| Small | Inter | 14px | Regular |
| Button | Inter | 14px | Medium |

---

## Responsive Breakpoints

| Device | Width | Layout |
|--------|-------|--------|
| Mobile | < 640px | Single column, stacked |
| Tablet | 640px - 1024px | Two columns |
| Desktop | > 1024px | Full layout |

---

## Key UI Components

### Buttons

```
Primary:    [Book Session]  → Blue background, white text
Secondary:  [View Profile]  → White background, blue border
Danger:     [Cancel]        → Red background, white text
Success:    [Confirm]       → Green background, white text
```

### Status Badges

```
✓ Confirmed    → Green badge
⏳ Pending     → Yellow badge
✕ Cancelled    → Red badge
★ Completed    → Blue badge
```

### Cards

```
┌─────────────────────────┐
│ Shadow: 0 1px 3px rgba   │
│ Border-radius: 8px       │
│ Padding: 16px            │
│ Background: white        │
└─────────────────────────┘
```

---

## Animation Guidelines

| Action | Animation | Duration |
|--------|-----------|----------|
| Button hover | Scale up 1.02 | 150ms |
| Modal open | Fade in + slide up | 200ms |
| Card hover | Shadow increase | 150ms |
| Loading | Spinner | Continuous |
| Success | Checkmark pop | 300ms |
