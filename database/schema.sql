-- IoT Sensor Management System - PostgreSQL with TimescaleDB
-- Database Schema

-- Enable TimescaleDB extension
CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE;

-- ============================================================================
-- PERMISSIONS & ROLES
-- ============================================================================

CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    resource VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_permission_resource_action UNIQUE (resource, action)
);

CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    hierarchy_level INTEGER DEFAULT 100,
    is_system_role BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_company_role_name UNIQUE (company_id, name)
);

CREATE TABLE role_permissions (
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id)
);

-- ============================================================================
-- COMPANIES
-- ============================================================================

CREATE TABLE companies (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    client_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    billing_address TEXT,
    shipping_address TEXT,
    gst_number VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    deleted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_companies_email ON companies(email);
CREATE INDEX idx_companies_is_active ON companies(is_active);
CREATE INDEX idx_companies_deleted_at ON companies(deleted_at);

-- Add foreign key to roles table
ALTER TABLE roles ADD CONSTRAINT fk_roles_company
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE;

-- ============================================================================
-- USERS
-- ============================================================================

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT REFERENCES companies(id) ON DELETE CASCADE,
    role_id BIGINT NOT NULL REFERENCES roles(id),
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    deleted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP
);

CREATE INDEX idx_users_company_id ON users(company_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_users_is_active ON users(is_active);
CREATE INDEX idx_users_deleted_at ON users(deleted_at);

CREATE TABLE user_permissions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by BIGINT REFERENCES users(id),
    CONSTRAINT uq_user_permission UNIQUE (user_id, permission_id)
);

CREATE INDEX idx_user_permissions_user_id ON user_permissions(user_id);

-- ============================================================================
-- AUDIT LOGS
-- ============================================================================

CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    company_id BIGINT REFERENCES companies(id) ON DELETE CASCADE,
    event VARCHAR(255) NOT NULL,
    auditable_type VARCHAR(255) NOT NULL,
    auditable_id BIGINT,
    description TEXT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_company_id ON audit_logs(company_id);
CREATE INDEX idx_audit_logs_event ON audit_logs(event);
CREATE INDEX idx_audit_logs_auditable ON audit_logs(auditable_type, auditable_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- ============================================================================
-- LOCATION HIERARCHY
-- ============================================================================

CREATE TABLE locations (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'India',
    timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
    is_active BOOLEAN DEFAULT TRUE,
    deleted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_company_location UNIQUE (company_id, name)
);

CREATE INDEX idx_locations_company_id ON locations(company_id);
CREATE INDEX idx_locations_deleted_at ON locations(deleted_at);

CREATE TABLE hubs (
    id BIGSERIAL PRIMARY KEY,
    location_id BIGINT NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    deleted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_location_hub UNIQUE (location_id, name)
);

CREATE INDEX idx_hubs_location_id ON hubs(location_id);
CREATE INDEX idx_hubs_deleted_at ON hubs(deleted_at);

CREATE TABLE areas (
    id BIGSERIAL PRIMARY KEY,
    hub_id BIGINT NOT NULL REFERENCES hubs(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    deleted_at TIMESTAMP,

    -- Alert channel configuration
    alert_email_enabled BOOLEAN DEFAULT TRUE,
    alert_sms_enabled BOOLEAN DEFAULT FALSE,
    alert_voice_enabled BOOLEAN DEFAULT FALSE,
    alert_push_enabled BOOLEAN DEFAULT FALSE,

    -- Notification types enabled
    alert_warning_enabled BOOLEAN DEFAULT TRUE,
    alert_critical_enabled BOOLEAN DEFAULT TRUE,
    alert_back_in_range_enabled BOOLEAN DEFAULT TRUE,
    alert_device_status_enabled BOOLEAN DEFAULT TRUE,

    -- Notification interval for acknowledged alerts (in hours)
    acknowledged_alert_notification_interval INTEGER DEFAULT 24,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_hub_area UNIQUE (hub_id, name)
);

CREATE INDEX idx_areas_hub_id ON areas(hub_id);
CREATE INDEX idx_areas_deleted_at ON areas(deleted_at);

-- ============================================================================
-- USER AREA ACCESS
-- ============================================================================

CREATE TABLE user_area_access (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    area_id BIGINT NOT NULL REFERENCES areas(id) ON DELETE CASCADE,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by BIGINT REFERENCES users(id),
    CONSTRAINT uq_user_area UNIQUE (user_id, area_id)
);

CREATE INDEX idx_user_area_access_user_id ON user_area_access(user_id);
CREATE INDEX idx_user_area_access_area_id ON user_area_access(area_id);

-- ============================================================================
-- DEVICES
-- ============================================================================

CREATE TABLE devices (
    id BIGSERIAL PRIMARY KEY,

    -- Identification
    device_uid VARCHAR(50) UNIQUE NOT NULL,
    device_code VARCHAR(20) UNIQUE NOT NULL,
    make VARCHAR(50) DEFAULT 'VEGA',
    model VARCHAR(50) DEFAULT 'Alpha',
    type VARCHAR(50) NOT NULL, -- 'temp_humidity', 'dual_temp_humidity', 'temp_probe'
    firmware_version VARCHAR(50),

    -- API Authentication
    api_key VARCHAR(64) UNIQUE NOT NULL,

    -- Resolution and accuracy specs
    temp_resolution DECIMAL(4, 2) DEFAULT 0.1,
    temp_accuracy DECIMAL(4, 2) DEFAULT 0.5,
    humidity_resolution DECIMAL(4, 2) DEFAULT 1.0,
    humidity_accuracy DECIMAL(4, 2) DEFAULT 3.0,
    temp_probe_resolution DECIMAL(4, 2),
    temp_probe_accuracy DECIMAL(4, 2),

    -- Assignment (two-step: company first, then area)
    company_id BIGINT REFERENCES companies(id) ON DELETE SET NULL,
    area_id BIGINT REFERENCES areas(id) ON DELETE SET NULL,
    device_name VARCHAR(255),

    -- Status tracking
    status VARCHAR(50) DEFAULT 'offline', -- 'online', 'offline', 'maintenance'
    is_active BOOLEAN DEFAULT TRUE,
    last_reading_at TIMESTAMP,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_devices_device_uid ON devices(device_uid);
CREATE INDEX idx_devices_device_code ON devices(device_code);
CREATE INDEX idx_devices_api_key ON devices(api_key);
CREATE INDEX idx_devices_company_id ON devices(company_id);
CREATE INDEX idx_devices_area_id ON devices(area_id);
CREATE INDEX idx_devices_status ON devices(status);

-- ============================================================================
-- DEVICE CONFIGURATIONS
-- ============================================================================

CREATE TABLE device_configurations (
    id BIGSERIAL PRIMARY KEY,
    device_id BIGINT NOT NULL REFERENCES devices(id) ON DELETE CASCADE,

    -- Temperature thresholds (internal sensor)
    temp_min_critical DECIMAL(6, 2) DEFAULT 20.0,
    temp_max_critical DECIMAL(6, 2) DEFAULT 50.0,
    temp_min_warning DECIMAL(6, 2) DEFAULT 25.0,
    temp_max_warning DECIMAL(6, 2) DEFAULT 45.0,

    -- Humidity thresholds
    humidity_min_critical DECIMAL(6, 2) DEFAULT 40.0,
    humidity_max_critical DECIMAL(6, 2) DEFAULT 90.0,
    humidity_min_warning DECIMAL(6, 2) DEFAULT 50.0,
    humidity_max_warning DECIMAL(6, 2) DEFAULT 80.0,

    -- Temperature probe thresholds
    temp_probe_min_critical DECIMAL(6, 2),
    temp_probe_max_critical DECIMAL(6, 2),
    temp_probe_min_warning DECIMAL(6, 2),
    temp_probe_max_warning DECIMAL(6, 2),

    -- Recording intervals (in minutes)
    record_interval INTEGER DEFAULT 5,
    send_interval INTEGER DEFAULT 15,

    -- WiFi configuration
    wifi_ssid VARCHAR(100),
    wifi_password VARCHAR(100),

    -- Active sensor selection
    active_temp_sensor VARCHAR(10) DEFAULT 'INT',

    -- Tracking
    is_current BOOLEAN DEFAULT TRUE,
    updated_by BIGINT REFERENCES users(id) ON DELETE SET NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_device_current_config UNIQUE (device_id, is_current) DEFERRABLE
);

CREATE INDEX idx_device_configurations_device_id ON device_configurations(device_id);
CREATE INDEX idx_device_configurations_is_current ON device_configurations(is_current) WHERE is_current = TRUE;

-- ============================================================================
-- DEVICE READINGS (TimescaleDB Hypertable)
-- ============================================================================

CREATE TABLE device_readings (
    device_id INTEGER NOT NULL,
    recorded_at TIMESTAMP NOT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Denormalized hierarchy IDs for fast queries
    company_id INTEGER NOT NULL,
    location_id INTEGER,
    hub_id INTEGER,
    area_id INTEGER,

    -- Sensor values
    temperature DECIMAL(6, 2),
    humidity DECIMAL(6, 2),
    temp_probe DECIMAL(6, 2),

    -- Device metadata at time of reading
    battery_voltage DECIMAL(4, 2),
    battery_percentage SMALLINT,
    wifi_signal_strength INTEGER,

    -- Operational metadata
    firmware_version VARCHAR(20),

    -- Raw data reference
    raw_payload JSONB,

    PRIMARY KEY (device_id, recorded_at),
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- Create indexes for common query patterns (time-first queries)
CREATE INDEX idx_readings_device_time ON device_readings(device_id, recorded_at DESC);
CREATE INDEX idx_readings_company_time ON device_readings(company_id, recorded_at DESC);
CREATE INDEX idx_readings_location_time ON device_readings(location_id, recorded_at DESC);
CREATE INDEX idx_readings_hub_time ON device_readings(hub_id, recorded_at DESC);
CREATE INDEX idx_readings_area_time ON device_readings(area_id, recorded_at DESC);

-- Convert to TimescaleDB hypertable
SELECT create_hypertable(
    'device_readings'::regclass,
    'recorded_at'::name,
    chunk_time_interval => INTERVAL '7 days',
    if_not_exists => TRUE
);

-- Enable compression
ALTER TABLE device_readings SET (
    timescaledb.compress,
    timescaledb.compress_segmentby = 'device_id',
    timescaledb.compress_orderby = 'recorded_at DESC'
);

-- Add compression policy (compress chunks older than 7 days)
SELECT add_compression_policy('device_readings', INTERVAL '7 days');

-- ============================================================================
-- ALERTS
-- ============================================================================

CREATE TABLE alerts (
    id BIGSERIAL PRIMARY KEY,
    device_id BIGINT NOT NULL REFERENCES devices(id) ON DELETE CASCADE,

    -- Alert classification
    type VARCHAR(50) NOT NULL, -- 'temperature', 'humidity', 'temp_probe', 'device_offline'
    severity VARCHAR(50) NOT NULL, -- 'warning', 'critical'
    status VARCHAR(50) DEFAULT 'active', -- 'active', 'acknowledged', 'resolved', 'auto_resolved'

    -- Alert details
    trigger_value DECIMAL(8, 2) NOT NULL,
    threshold_breached VARCHAR(50),
    reason TEXT,

    -- Timestamps
    started_at TIMESTAMPTZ NOT NULL,
    ended_at TIMESTAMPTZ,

    -- Acknowledgment
    acknowledged_at TIMESTAMPTZ,
    acknowledged_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    acknowledge_comment TEXT,

    -- Resolution
    resolved_at TIMESTAMPTZ,
    resolved_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    resolve_comment TEXT,

    -- Duration in seconds
    duration_seconds INTEGER,

    -- Back in range flag
    is_back_in_range BOOLEAN DEFAULT FALSE,

    -- Notification tracking
    last_notification_at TIMESTAMPTZ,
    notification_count INTEGER DEFAULT 0,

    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_alerts_device_id ON alerts(device_id);
CREATE INDEX idx_alerts_status ON alerts(status);
CREATE INDEX idx_alerts_started_at ON alerts(started_at);
CREATE INDEX idx_alerts_device_status ON alerts(device_id, status);
CREATE INDEX idx_alerts_device_type_severity ON alerts(device_id, type, severity);
CREATE INDEX idx_alerts_status_notification ON alerts(status, last_notification_at);

-- ============================================================================
-- ALERT NOTIFICATIONS
-- ============================================================================

CREATE TABLE alert_notifications (
    id BIGSERIAL PRIMARY KEY,
    alert_id BIGINT NOT NULL REFERENCES alerts(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- Channel type
    channel VARCHAR(20) NOT NULL, -- 'email', 'sms', 'voice', 'push'

    -- Sent timestamp
    sent_at TIMESTAMPTZ,

    -- Delivery status
    is_delivered BOOLEAN DEFAULT FALSE,
    delivered_at TIMESTAMPTZ,
    delivery_error TEXT,

    -- Message details
    message_content TEXT,
    external_reference VARCHAR(255)
);

CREATE INDEX idx_alert_notifications_alert_id ON alert_notifications(alert_id);
CREATE INDEX idx_alert_notifications_user_id ON alert_notifications(user_id);
CREATE INDEX idx_alert_notifications_sent_at ON alert_notifications(sent_at);
CREATE INDEX idx_alert_notifications_alert_channel ON alert_notifications(alert_id, channel);
CREATE INDEX idx_alert_notifications_delivery ON alert_notifications(is_delivered, sent_at);

-- ============================================================================
-- TICKETS
-- ============================================================================

CREATE TABLE tickets (
    id BIGSERIAL PRIMARY KEY,

    -- Foreign keys
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    assigned_to BIGINT REFERENCES users(id) ON DELETE SET NULL,

    -- Optional related entities
    device_id BIGINT REFERENCES devices(id) ON DELETE SET NULL,
    location_id BIGINT REFERENCES locations(id) ON DELETE SET NULL,
    hub_id BIGINT REFERENCES hubs(id) ON DELETE SET NULL,
    area_id BIGINT REFERENCES areas(id) ON DELETE SET NULL,

    -- Ticket details
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    reason VARCHAR(255),
    status VARCHAR(50) DEFAULT 'open', -- 'open', 'in_progress', 'waiting_on_customer', 'resolved', 'closed'
    priority VARCHAR(50) DEFAULT 'medium', -- 'low', 'medium', 'high', 'urgent'

    -- Timestamps
    resolved_at TIMESTAMP,
    closed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_tickets_user_id ON tickets(user_id);
CREATE INDEX idx_tickets_company_id ON tickets(company_id);
CREATE INDEX idx_tickets_assigned_to ON tickets(assigned_to);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_priority ON tickets(priority);
CREATE INDEX idx_tickets_created_at ON tickets(created_at);

-- ============================================================================
-- TICKET COMMENTS
-- ============================================================================

CREATE TABLE ticket_comments (
    id BIGSERIAL PRIMARY KEY,

    -- Foreign keys
    ticket_id BIGINT NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- Comment details
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ticket_comments_ticket_id ON ticket_comments(ticket_id);
CREATE INDEX idx_ticket_comments_user_id ON ticket_comments(user_id);
CREATE INDEX idx_ticket_comments_is_internal ON ticket_comments(is_internal);
CREATE INDEX idx_ticket_comments_created_at ON ticket_comments(created_at);

-- ============================================================================
-- TICKET ATTACHMENTS
-- ============================================================================

CREATE TABLE ticket_attachments (
    id BIGSERIAL PRIMARY KEY,

    -- Foreign keys
    ticket_id BIGINT NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    comment_id BIGINT REFERENCES ticket_comments(id) ON DELETE CASCADE,
    uploaded_by BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- File details
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(255),
    file_size BIGINT DEFAULT 0,

    -- Timestamps
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ticket_attachments_ticket_id ON ticket_attachments(ticket_id);
CREATE INDEX idx_ticket_attachments_comment_id ON ticket_attachments(comment_id);
CREATE INDEX idx_ticket_attachments_uploaded_by ON ticket_attachments(uploaded_by);
CREATE INDEX idx_ticket_attachments_uploaded_at ON ticket_attachments(uploaded_at);
