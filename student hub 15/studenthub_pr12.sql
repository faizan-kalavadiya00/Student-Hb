-- Student Hub Practical 12 event-management table.
-- This uses pr12_events so it does not conflict with the existing events table used by registrations.
USE studenthub;

CREATE TABLE IF NOT EXISTS pr12_events (
  event_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(80) NOT NULL,
  venue VARCHAR(180) NOT NULL,
  event_date DATETIME NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  poster_filename VARCHAR(80) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pr12_events_date (event_date),
  INDEX idx_pr12_events_category (category),
  INDEX idx_pr12_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pr12_events (title, description, category, venue, event_date, status)
SELECT seed.title, seed.description, seed.category, seed.venue, seed.event_date, seed.status
FROM (
 SELECT 'Student Orientation 2026' title,
        'Welcome session with campus guidance and an introduction to Student Hub.' description,
        'Academic' category, 'Main Auditorium' venue, '2026-11-05 10:00:00' event_date, 'open' status
 UNION ALL SELECT 'Tech Innovation Meetup',
        'Student presentations, technology talks, and project networking.',
        'Technology', 'DEPSTAR Seminar Hall', '2026-11-12 14:00:00', 'open'
 UNION ALL SELECT 'Annual Cultural Evening',
        'Student performances, art, music, and campus culture.',
        'Cultural', 'University Ground', '2026-11-20 17:00:00', 'closed'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM pr12_events LIMIT 1);
