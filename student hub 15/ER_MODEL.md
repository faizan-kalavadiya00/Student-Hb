# Student Hub PR8 — ER Model

```mermaid
erDiagram
    STUDENTS ||--o{ REGISTRATIONS : makes
    EVENTS ||--o{ REGISTRATIONS : receives

    STUDENTS {
        INT student_id PK
        VARCHAR enrollment_no UK
        VARCHAR full_name
        VARCHAR email UK
        VARCHAR department
        TINYINT study_year
    }
    EVENTS {
        INT event_id PK
        VARCHAR title
        TEXT description
        VARCHAR venue
        DATE event_date
        INT capacity
    }
    REGISTRATIONS {
        INT registration_id PK
        INT student_id FK
        INT event_id FK
        TIMESTAMP registered_at
        ENUM status
    }
```

## Cardinality
- One student can have zero or many registrations.
- One event can have zero or many registrations.
- Each registration refers to exactly one student and one event.
- `registrations` resolves the many-to-many relationship between `students` and `events`.

## Normalization notes
- Student details are stored once in `students`; event details are stored once in `events`.
- `registrations` stores only the relationship and registration-specific attributes.
- Primary keys identify rows, foreign keys preserve referential integrity, and `UNIQUE(student_id, event_id)` prevents duplicate event registrations for the same student.
