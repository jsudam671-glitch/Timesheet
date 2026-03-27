-- Timesheet Database Schema (Production Version)
-- Updated with proper constraints, indexing, and documentation

CREATE DATABASE IF NOT EXISTS timesheet_pro;
USE timesheet_pro;

-- Drop existing table to recreate with improvements
DROP TABLE IF EXISTS timesheets;

-- Create timesheets table with proper structure
CREATE TABLE timesheets (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique identifier',
    person_name VARCHAR(100) NOT NULL COMMENT 'Name of the person submitting the timesheet',
    work_date DATE NOT NULL COMMENT 'Date of work',
    week_ending DATE NOT NULL COMMENT 'End date of the week',
    project VARCHAR(100) NOT NULL COMMENT 'Project name',
    task VARCHAR(500) NOT NULL COMMENT 'Description of work performed',
    hours DECIMAL(4,2) NOT NULL COMMENT 'Hours worked (max 8 per day)',
    remarks VARCHAR(500) COMMENT 'Additional remarks or notes',
    type VARCHAR(50) NOT NULL COMMENT 'Type of work (Development, Support, Testing)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of entry creation',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of last update',
    
    -- Constraints
    CONSTRAINT chk_hours CHECK (hours >= 0 AND hours <= 8),
    CONSTRAINT chk_dates CHECK (work_date <= week_ending),
    
    -- Indexes for performance
    INDEX idx_person_name (person_name),
    INDEX idx_work_date (work_date),
    INDEX idx_week_ending (week_ending),
    INDEX idx_project (project),
    INDEX idx_created_at (created_at),
    INDEX idx_person_week (person_name, week_ending)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores timesheet entries';
