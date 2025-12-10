-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               PostgreSQL 16.10, compiled by Visual C++ build 1944, 64-bit
-- Server OS:                    
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES  */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table public.alerts
CREATE TABLE IF NOT EXISTS "alerts" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''alerts_id_seq''::regclass)',
	"device_id" BIGINT NOT NULL,
	"type" VARCHAR(255) NOT NULL,
	"severity" VARCHAR(255) NOT NULL,
	"status" VARCHAR(255) NOT NULL DEFAULT 'active',
	"trigger_value" NUMERIC(8,2) NOT NULL,
	"threshold_breached" VARCHAR(50) NULL DEFAULT NULL,
	"reason" TEXT NULL DEFAULT NULL,
	"started_at" TIMESTAMPTZ NOT NULL,
	"ended_at" TIMESTAMPTZ NULL DEFAULT NULL,
	"acknowledged_at" TIMESTAMPTZ NULL DEFAULT NULL,
	"acknowledged_by" BIGINT NULL DEFAULT NULL,
	"acknowledge_comment" TEXT NULL DEFAULT NULL,
	"resolved_at" TIMESTAMPTZ NULL DEFAULT NULL,
	"resolved_by" BIGINT NULL DEFAULT NULL,
	"resolve_comment" TEXT NULL DEFAULT NULL,
	"duration_seconds" INTEGER NULL DEFAULT NULL,
	"is_back_in_range" BOOLEAN NOT NULL DEFAULT 'false',
	"last_notification_at" TIMESTAMPTZ NULL DEFAULT NULL,
	"notification_count" INTEGER NOT NULL DEFAULT '0',
	"created_at" TIMESTAMPTZ NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "alerts_device_id_index" ("device_id"),
	INDEX "alerts_status_index" ("status"),
	INDEX "alerts_started_at_index" ("started_at"),
	INDEX "alerts_device_id_status_index" ("device_id", "status"),
	INDEX "alerts_device_id_type_severity_index" ("device_id", "type", "severity"),
	INDEX "alerts_status_last_notification_at_index" ("status", "last_notification_at"),
	CONSTRAINT "alerts_acknowledged_by_foreign" FOREIGN KEY ("acknowledged_by") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "alerts_device_id_foreign" FOREIGN KEY ("device_id") REFERENCES "devices" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "alerts_resolved_by_foreign" FOREIGN KEY ("resolved_by") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "alerts_type_check" CHECK ((((type)::text = ANY ((ARRAY['temperature'::character varying, 'humidity'::character varying, 'temp_probe'::character varying])::text[])))),
	CONSTRAINT "alerts_severity_check" CHECK ((((severity)::text = ANY ((ARRAY['warning'::character varying, 'critical'::character varying])::text[])))),
	CONSTRAINT "alerts_status_check" CHECK ((((status)::text = ANY ((ARRAY['active'::character varying, 'acknowledged'::character varying, 'resolved'::character varying, 'auto_resolved'::character varying])::text[]))))
);

-- Data exporting was unselected.

-- Dumping structure for table public.alert_notifications
CREATE TABLE IF NOT EXISTS "alert_notifications" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''alert_notifications_id_seq''::regclass)',
	"alert_id" BIGINT NOT NULL,
	"user_id" BIGINT NOT NULL,
	"channel" VARCHAR(20) NOT NULL,
	"sent_at" TIMESTAMPTZ NULL DEFAULT NULL,
	"is_delivered" BOOLEAN NOT NULL DEFAULT 'false',
	"delivered_at" TIMESTAMPTZ NULL DEFAULT NULL,
	"delivery_error" TEXT NULL DEFAULT NULL,
	"message_content" TEXT NULL DEFAULT NULL,
	"external_reference" VARCHAR(255) NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "alert_notifications_alert_id_index" ("alert_id"),
	INDEX "alert_notifications_user_id_index" ("user_id"),
	INDEX "alert_notifications_sent_at_index" ("sent_at"),
	INDEX "alert_notifications_alert_id_channel_index" ("alert_id", "channel"),
	INDEX "alert_notifications_is_delivered_sent_at_index" ("is_delivered", "sent_at"),
	CONSTRAINT "alert_notifications_alert_id_foreign" FOREIGN KEY ("alert_id") REFERENCES "alerts" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "alert_notifications_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.areas
CREATE TABLE IF NOT EXISTS "areas" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''areas_id_seq''::regclass)',
	"hub_id" BIGINT NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"description" TEXT NULL DEFAULT NULL,
	"is_active" BOOLEAN NOT NULL DEFAULT 'true',
	"deleted_at" TIMESTAMP NULL DEFAULT NULL,
	"alert_email_enabled" BOOLEAN NOT NULL DEFAULT 'true',
	"alert_sms_enabled" BOOLEAN NOT NULL DEFAULT 'false',
	"alert_voice_enabled" BOOLEAN NOT NULL DEFAULT 'false',
	"alert_push_enabled" BOOLEAN NOT NULL DEFAULT 'false',
	"alert_warning_enabled" BOOLEAN NOT NULL DEFAULT 'true',
	"alert_critical_enabled" BOOLEAN NOT NULL DEFAULT 'true',
	"alert_back_in_range_enabled" BOOLEAN NOT NULL DEFAULT 'true',
	"alert_device_status_enabled" BOOLEAN NOT NULL DEFAULT 'true',
	"acknowledged_alert_notification_interval" INTEGER NOT NULL DEFAULT '24',
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_hub_area" ("hub_id", "name"),
	INDEX "idx_areas_hub_id" ("hub_id"),
	INDEX "idx_areas_deleted_at" ("deleted_at"),
	CONSTRAINT "areas_hub_id_foreign" FOREIGN KEY ("hub_id") REFERENCES "hubs" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.audit_logs
CREATE TABLE IF NOT EXISTS "audit_logs" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''audit_logs_id_seq''::regclass)',
	"user_id" BIGINT NULL DEFAULT NULL,
	"company_id" BIGINT NULL DEFAULT NULL,
	"event" VARCHAR(255) NOT NULL,
	"auditable_type" VARCHAR(255) NOT NULL,
	"auditable_id" BIGINT NULL DEFAULT NULL,
	"description" TEXT NULL DEFAULT NULL,
	"old_values" JSON NULL DEFAULT NULL,
	"new_values" JSON NULL DEFAULT NULL,
	"ip_address" VARCHAR(45) NULL DEFAULT NULL,
	"user_agent" TEXT NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "idx_audit_logs_user_id" ("user_id"),
	INDEX "idx_audit_logs_company_id" ("company_id"),
	INDEX "idx_audit_logs_event" ("event"),
	INDEX "idx_audit_logs_auditable" ("auditable_type", "auditable_id"),
	INDEX "idx_audit_logs_created_at" ("created_at"),
	CONSTRAINT "audit_logs_company_id_foreign" FOREIGN KEY ("company_id") REFERENCES "companies" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "audit_logs_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE SET NULL
);

-- Data exporting was unselected.

-- Dumping structure for table public.cache
CREATE TABLE IF NOT EXISTS "cache" (
	"key" VARCHAR(255) NOT NULL,
	"value" TEXT NOT NULL,
	"expiration" INTEGER NOT NULL,
	PRIMARY KEY ("key")
);

-- Data exporting was unselected.

-- Dumping structure for table public.cache_locks
CREATE TABLE IF NOT EXISTS "cache_locks" (
	"key" VARCHAR(255) NOT NULL,
	"owner" VARCHAR(255) NOT NULL,
	"expiration" INTEGER NOT NULL,
	PRIMARY KEY ("key")
);

-- Data exporting was unselected.

-- Dumping structure for table public.companies
CREATE TABLE IF NOT EXISTS "companies" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''companies_id_seq''::regclass)',
	"name" VARCHAR(150) NOT NULL,
	"client_name" VARCHAR(150) NOT NULL,
	"email" VARCHAR(255) NOT NULL,
	"phone" VARCHAR(20) NULL DEFAULT NULL,
	"billing_address" TEXT NULL DEFAULT NULL,
	"shipping_address" TEXT NULL DEFAULT NULL,
	"gst_number" VARCHAR(20) NULL DEFAULT NULL,
	"is_active" BOOLEAN NOT NULL DEFAULT 'true',
	"deleted_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "idx_companies_email" ("email"),
	INDEX "idx_companies_is_active" ("is_active"),
	INDEX "idx_companies_deleted_at" ("deleted_at"),
	UNIQUE INDEX "companies_email_unique" ("email")
);

-- Data exporting was unselected.

-- Dumping structure for table public.devices
CREATE TABLE IF NOT EXISTS "devices" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''devices_id_seq''::regclass)',
	"device_uid" VARCHAR(50) NOT NULL,
	"device_code" VARCHAR(20) NOT NULL,
	"make" VARCHAR(50) NOT NULL DEFAULT 'VEGA',
	"model" VARCHAR(50) NOT NULL DEFAULT 'Alpha',
	"type" VARCHAR(255) NOT NULL,
	"firmware_version" VARCHAR(50) NULL DEFAULT NULL,
	"api_key" VARCHAR(64) NOT NULL,
	"temp_resolution" NUMERIC(4,2) NOT NULL DEFAULT '0.1',
	"temp_accuracy" NUMERIC(4,2) NOT NULL DEFAULT '0.5',
	"humidity_resolution" NUMERIC(4,2) NOT NULL DEFAULT '1',
	"humidity_accuracy" NUMERIC(4,2) NOT NULL DEFAULT '3',
	"temp_probe_resolution" NUMERIC(4,2) NULL DEFAULT NULL,
	"temp_probe_accuracy" NUMERIC(4,2) NULL DEFAULT NULL,
	"company_id" BIGINT NULL DEFAULT NULL,
	"area_id" BIGINT NULL DEFAULT NULL,
	"device_name" VARCHAR(255) NULL DEFAULT NULL,
	"status" VARCHAR(255) NOT NULL DEFAULT 'offline',
	"is_active" BOOLEAN NOT NULL DEFAULT 'true',
	"last_reading_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "idx_devices_device_uid" ("device_uid"),
	INDEX "idx_devices_device_code" ("device_code"),
	INDEX "idx_devices_api_key" ("api_key"),
	INDEX "idx_devices_company_id" ("company_id"),
	INDEX "idx_devices_area_id" ("area_id"),
	INDEX "idx_devices_status" ("status"),
	UNIQUE INDEX "devices_device_uid_unique" ("device_uid"),
	UNIQUE INDEX "devices_device_code_unique" ("device_code"),
	UNIQUE INDEX "devices_api_key_unique" ("api_key"),
	CONSTRAINT "devices_area_id_foreign" FOREIGN KEY ("area_id") REFERENCES "areas" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "devices_company_id_foreign" FOREIGN KEY ("company_id") REFERENCES "companies" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "devices_type_check" CHECK ((((type)::text = ANY ((ARRAY['single_temp'::character varying, 'single_temp_humidity'::character varying, 'dual_temp'::character varying, 'dual_temp_humidity'::character varying])::text[])))),
	CONSTRAINT "devices_status_check" CHECK ((((status)::text = ANY ((ARRAY['online'::character varying, 'offline'::character varying, 'maintenance'::character varying, 'decommissioned'::character varying])::text[]))))
);

-- Data exporting was unselected.

-- Dumping structure for table public.device_configurations
CREATE TABLE IF NOT EXISTS "device_configurations" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''device_configurations_id_seq''::regclass)',
	"device_id" BIGINT NOT NULL,
	"temp_min_critical" NUMERIC(6,2) NOT NULL DEFAULT '20',
	"temp_max_critical" NUMERIC(6,2) NOT NULL DEFAULT '50',
	"temp_min_warning" NUMERIC(6,2) NOT NULL DEFAULT '25',
	"temp_max_warning" NUMERIC(6,2) NOT NULL DEFAULT '45',
	"humidity_min_critical" NUMERIC(6,2) NOT NULL DEFAULT '40',
	"humidity_max_critical" NUMERIC(6,2) NOT NULL DEFAULT '90',
	"humidity_min_warning" NUMERIC(6,2) NOT NULL DEFAULT '50',
	"humidity_max_warning" NUMERIC(6,2) NOT NULL DEFAULT '80',
	"temp_probe_min_critical" NUMERIC(6,2) NULL DEFAULT NULL,
	"temp_probe_max_critical" NUMERIC(6,2) NULL DEFAULT NULL,
	"temp_probe_min_warning" NUMERIC(6,2) NULL DEFAULT NULL,
	"temp_probe_max_warning" NUMERIC(6,2) NULL DEFAULT NULL,
	"record_interval" INTEGER NOT NULL DEFAULT '5',
	"send_interval" INTEGER NOT NULL DEFAULT '15',
	"wifi_ssid" VARCHAR(100) NULL DEFAULT NULL,
	"wifi_password" VARCHAR(100) NULL DEFAULT NULL,
	"active_temp_sensor" VARCHAR(10) NOT NULL DEFAULT 'INT',
	"is_current" BOOLEAN NOT NULL DEFAULT 'true',
	"updated_by" BIGINT NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_device_current_config" ("device_id", "is_current"),
	INDEX "idx_device_configurations_device_id" ("device_id"),
	INDEX "idx_device_configurations_is_current" ("is_current"),
	CONSTRAINT "device_configurations_device_id_foreign" FOREIGN KEY ("device_id") REFERENCES "devices" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "device_configurations_updated_by_foreign" FOREIGN KEY ("updated_by") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE SET NULL
);

-- Data exporting was unselected.

-- Dumping structure for table public.device_readings
CREATE TABLE IF NOT EXISTS "device_readings" (
	"device_id" INTEGER NOT NULL,
	"recorded_at" TIMESTAMP NOT NULL,
	"received_at" TIMESTAMP NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
	"company_id" INTEGER NOT NULL,
	"location_id" INTEGER NULL DEFAULT NULL,
	"hub_id" INTEGER NULL DEFAULT NULL,
	"area_id" INTEGER NULL DEFAULT NULL,
	"temperature" NUMERIC(6,2) NULL DEFAULT NULL,
	"humidity" NUMERIC(6,2) NULL DEFAULT NULL,
	"temp_probe" NUMERIC(6,2) NULL DEFAULT NULL,
	"battery_voltage" NUMERIC(4,2) NULL DEFAULT NULL,
	"battery_percentage" SMALLINT NULL DEFAULT NULL,
	"wifi_signal_strength" INTEGER NULL DEFAULT NULL,
	"firmware_version" VARCHAR(20) NULL DEFAULT NULL,
	"raw_payload" JSONB NULL DEFAULT NULL,
	PRIMARY KEY ("device_id", "recorded_at"),
	INDEX "idx_readings_device_time" ("device_id", "recorded_at"),
	INDEX "idx_readings_company_time" ("company_id", "recorded_at"),
	INDEX "idx_readings_location_time" ("location_id", "recorded_at"),
	INDEX "idx_readings_hub_time" ("hub_id", "recorded_at"),
	INDEX "idx_readings_area_time" ("area_id", "recorded_at"),
	INDEX "device_readings_recorded_at_idx" ("recorded_at"),
	CONSTRAINT "device_readings_device_id_foreign" FOREIGN KEY ("device_id") REFERENCES "devices" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.failed_jobs
CREATE TABLE IF NOT EXISTS "failed_jobs" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''failed_jobs_id_seq''::regclass)',
	"uuid" VARCHAR(255) NOT NULL,
	"connection" TEXT NOT NULL,
	"queue" TEXT NOT NULL,
	"payload" TEXT NOT NULL,
	"exception" TEXT NOT NULL,
	"failed_at" TIMESTAMP NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
	PRIMARY KEY ("id"),
	UNIQUE INDEX "failed_jobs_uuid_unique" ("uuid")
);

-- Data exporting was unselected.

-- Dumping structure for table public.hubs
CREATE TABLE IF NOT EXISTS "hubs" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''hubs_id_seq''::regclass)',
	"location_id" BIGINT NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"description" TEXT NULL DEFAULT NULL,
	"is_active" BOOLEAN NOT NULL DEFAULT 'true',
	"deleted_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_location_hub" ("location_id", "name"),
	INDEX "idx_hubs_location_id" ("location_id"),
	INDEX "idx_hubs_deleted_at" ("deleted_at"),
	CONSTRAINT "hubs_location_id_foreign" FOREIGN KEY ("location_id") REFERENCES "locations" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.jobs
CREATE TABLE IF NOT EXISTS "jobs" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''jobs_id_seq''::regclass)',
	"queue" VARCHAR(255) NOT NULL,
	"payload" TEXT NOT NULL,
	"attempts" SMALLINT NOT NULL,
	"reserved_at" INTEGER NULL DEFAULT NULL,
	"available_at" INTEGER NOT NULL,
	"created_at" INTEGER NOT NULL,
	PRIMARY KEY ("id"),
	INDEX "jobs_queue_index" ("queue")
);

-- Data exporting was unselected.

-- Dumping structure for table public.job_batches
CREATE TABLE IF NOT EXISTS "job_batches" (
	"id" VARCHAR(255) NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"total_jobs" INTEGER NOT NULL,
	"pending_jobs" INTEGER NOT NULL,
	"failed_jobs" INTEGER NOT NULL,
	"failed_job_ids" TEXT NOT NULL,
	"options" TEXT NULL DEFAULT NULL,
	"cancelled_at" INTEGER NULL DEFAULT NULL,
	"created_at" INTEGER NOT NULL,
	"finished_at" INTEGER NULL DEFAULT NULL,
	PRIMARY KEY ("id")
);

-- Data exporting was unselected.

-- Dumping structure for table public.locations
CREATE TABLE IF NOT EXISTS "locations" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''locations_id_seq''::regclass)',
	"company_id" BIGINT NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"address" TEXT NULL DEFAULT NULL,
	"city" VARCHAR(100) NULL DEFAULT NULL,
	"state" VARCHAR(100) NULL DEFAULT NULL,
	"country" VARCHAR(100) NOT NULL DEFAULT 'India',
	"timezone" VARCHAR(50) NOT NULL DEFAULT 'Asia/Kolkata',
	"is_active" BOOLEAN NOT NULL DEFAULT 'true',
	"deleted_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_company_location" ("company_id", "name"),
	INDEX "idx_locations_company_id" ("company_id"),
	INDEX "idx_locations_deleted_at" ("deleted_at"),
	CONSTRAINT "locations_company_id_foreign" FOREIGN KEY ("company_id") REFERENCES "companies" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.migrations
CREATE TABLE IF NOT EXISTS "migrations" (
	"id" INTEGER NOT NULL DEFAULT 'nextval(''migrations_id_seq''::regclass)',
	"migration" VARCHAR(255) NOT NULL,
	"batch" INTEGER NOT NULL,
	PRIMARY KEY ("id")
);

-- Data exporting was unselected.

-- Dumping structure for table public.password_reset_tokens
CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
	"email" VARCHAR(255) NOT NULL,
	"token" VARCHAR(255) NOT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("email")
);

-- Data exporting was unselected.

-- Dumping structure for table public.permissions
CREATE TABLE IF NOT EXISTS "permissions" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''permissions_id_seq''::regclass)',
	"name" VARCHAR(100) NOT NULL,
	"description" TEXT NULL DEFAULT NULL,
	"resource" VARCHAR(50) NOT NULL,
	"action" VARCHAR(50) NOT NULL,
	"created_at" TIMESTAMP NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_permission_resource_action" ("resource", "action"),
	UNIQUE INDEX "permissions_name_unique" ("name")
);

-- Data exporting was unselected.

-- Dumping structure for table public.personal_access_tokens
CREATE TABLE IF NOT EXISTS "personal_access_tokens" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''personal_access_tokens_id_seq''::regclass)',
	"tokenable_type" VARCHAR(255) NOT NULL,
	"tokenable_id" BIGINT NOT NULL,
	"name" TEXT NOT NULL,
	"token" VARCHAR(64) NOT NULL,
	"abilities" TEXT NULL DEFAULT NULL,
	"last_used_at" TIMESTAMP NULL DEFAULT NULL,
	"expires_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" ("tokenable_type", "tokenable_id"),
	UNIQUE INDEX "personal_access_tokens_token_unique" ("token"),
	INDEX "personal_access_tokens_expires_at_index" ("expires_at")
);

-- Data exporting was unselected.

-- Dumping structure for table public.roles
CREATE TABLE IF NOT EXISTS "roles" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''roles_id_seq''::regclass)',
	"company_id" BIGINT NULL DEFAULT NULL,
	"name" VARCHAR(50) NOT NULL,
	"description" TEXT NULL DEFAULT NULL,
	"hierarchy_level" INTEGER NOT NULL DEFAULT '100',
	"is_system_role" BOOLEAN NOT NULL DEFAULT 'false',
	"is_editable" BOOLEAN NOT NULL DEFAULT 'true',
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_company_role_name" ("company_id", "name"),
	CONSTRAINT "roles_company_id_foreign" FOREIGN KEY ("company_id") REFERENCES "companies" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.role_permissions
CREATE TABLE IF NOT EXISTS "role_permissions" (
	"role_id" BIGINT NOT NULL,
	"permission_id" BIGINT NOT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("role_id", "permission_id"),
	CONSTRAINT "role_permissions_permission_id_foreign" FOREIGN KEY ("permission_id") REFERENCES "permissions" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "role_permissions_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "roles" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.sessions
CREATE TABLE IF NOT EXISTS "sessions" (
	"id" VARCHAR(255) NOT NULL,
	"user_id" BIGINT NULL DEFAULT NULL,
	"ip_address" VARCHAR(45) NULL DEFAULT NULL,
	"user_agent" TEXT NULL DEFAULT NULL,
	"payload" TEXT NOT NULL,
	"last_activity" INTEGER NOT NULL,
	PRIMARY KEY ("id"),
	INDEX "sessions_user_id_index" ("user_id"),
	INDEX "sessions_last_activity_index" ("last_activity")
);

-- Data exporting was unselected.

-- Dumping structure for table public.tickets
CREATE TABLE IF NOT EXISTS "tickets" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''tickets_id_seq''::regclass)',
	"user_id" BIGINT NOT NULL,
	"company_id" BIGINT NOT NULL,
	"assigned_to" BIGINT NULL DEFAULT NULL,
	"device_id" BIGINT NULL DEFAULT NULL,
	"location_id" BIGINT NULL DEFAULT NULL,
	"hub_id" BIGINT NULL DEFAULT NULL,
	"area_id" BIGINT NULL DEFAULT NULL,
	"subject" VARCHAR(255) NOT NULL,
	"description" TEXT NOT NULL,
	"reason" VARCHAR(255) NULL DEFAULT NULL,
	"status" VARCHAR(255) NOT NULL DEFAULT 'open',
	"priority" VARCHAR(255) NOT NULL DEFAULT 'medium',
	"resolved_at" TIMESTAMP NULL DEFAULT NULL,
	"closed_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	"deleted_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "idx_tickets_user_id" ("user_id"),
	INDEX "idx_tickets_company_id" ("company_id"),
	INDEX "idx_tickets_assigned_to" ("assigned_to"),
	INDEX "idx_tickets_status" ("status"),
	INDEX "idx_tickets_priority" ("priority"),
	INDEX "idx_tickets_created_at" ("created_at"),
	CONSTRAINT "tickets_area_id_foreign" FOREIGN KEY ("area_id") REFERENCES "areas" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "tickets_assigned_to_foreign" FOREIGN KEY ("assigned_to") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "tickets_company_id_foreign" FOREIGN KEY ("company_id") REFERENCES "companies" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "tickets_device_id_foreign" FOREIGN KEY ("device_id") REFERENCES "devices" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "tickets_hub_id_foreign" FOREIGN KEY ("hub_id") REFERENCES "hubs" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "tickets_location_id_foreign" FOREIGN KEY ("location_id") REFERENCES "locations" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "tickets_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.ticket_attachments
CREATE TABLE IF NOT EXISTS "ticket_attachments" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''ticket_attachments_id_seq''::regclass)',
	"ticket_id" BIGINT NOT NULL,
	"comment_id" BIGINT NULL DEFAULT NULL,
	"uploaded_by" BIGINT NOT NULL,
	"file_name" VARCHAR(255) NOT NULL,
	"file_path" VARCHAR(255) NOT NULL,
	"file_type" VARCHAR(255) NULL DEFAULT NULL,
	"file_size" BIGINT NOT NULL DEFAULT '0',
	"uploaded_at" TIMESTAMP NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "idx_ticket_attachments_ticket_id" ("ticket_id"),
	INDEX "idx_ticket_attachments_comment_id" ("comment_id"),
	INDEX "idx_ticket_attachments_uploaded_by" ("uploaded_by"),
	INDEX "idx_ticket_attachments_uploaded_at" ("uploaded_at"),
	CONSTRAINT "ticket_attachments_comment_id_foreign" FOREIGN KEY ("comment_id") REFERENCES "ticket_comments" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "ticket_attachments_ticket_id_foreign" FOREIGN KEY ("ticket_id") REFERENCES "tickets" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "ticket_attachments_uploaded_by_foreign" FOREIGN KEY ("uploaded_by") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.ticket_comments
CREATE TABLE IF NOT EXISTS "ticket_comments" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''ticket_comments_id_seq''::regclass)',
	"ticket_id" BIGINT NOT NULL,
	"user_id" BIGINT NOT NULL,
	"comment" TEXT NOT NULL,
	"is_internal" BOOLEAN NOT NULL DEFAULT 'false',
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "idx_ticket_comments_ticket_id" ("ticket_id"),
	INDEX "idx_ticket_comments_user_id" ("user_id"),
	INDEX "idx_ticket_comments_is_internal" ("is_internal"),
	INDEX "idx_ticket_comments_created_at" ("created_at"),
	CONSTRAINT "ticket_comments_ticket_id_foreign" FOREIGN KEY ("ticket_id") REFERENCES "tickets" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "ticket_comments_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.users
CREATE TABLE IF NOT EXISTS "users" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''users_id_seq''::regclass)',
	"company_id" BIGINT NULL DEFAULT NULL,
	"role_id" BIGINT NOT NULL,
	"email" VARCHAR(255) NOT NULL,
	"password" VARCHAR(255) NOT NULL,
	"first_name" VARCHAR(100) NOT NULL,
	"last_name" VARCHAR(100) NULL DEFAULT NULL,
	"phone" VARCHAR(20) NULL DEFAULT NULL,
	"is_active" BOOLEAN NOT NULL DEFAULT 'true',
	"remember_token" VARCHAR(100) NULL DEFAULT NULL,
	"deleted_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	"last_login_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	INDEX "idx_users_company_id" ("company_id"),
	INDEX "idx_users_email" ("email"),
	INDEX "idx_users_role_id" ("role_id"),
	INDEX "idx_users_is_active" ("is_active"),
	INDEX "idx_users_deleted_at" ("deleted_at"),
	UNIQUE INDEX "users_email_unique" ("email"),
	CONSTRAINT "users_company_id_foreign" FOREIGN KEY ("company_id") REFERENCES "companies" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "users_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "roles" ("id") ON UPDATE NO ACTION ON DELETE NO ACTION
);

-- Data exporting was unselected.

-- Dumping structure for table public.user_area_access
CREATE TABLE IF NOT EXISTS "user_area_access" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''user_area_access_id_seq''::regclass)',
	"user_id" BIGINT NOT NULL,
	"area_id" BIGINT NOT NULL,
	"granted_at" TIMESTAMP NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
	"granted_by" BIGINT NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_user_area" ("user_id", "area_id"),
	INDEX "idx_user_area_access_user_id" ("user_id"),
	INDEX "idx_user_area_access_area_id" ("area_id"),
	CONSTRAINT "user_area_access_area_id_foreign" FOREIGN KEY ("area_id") REFERENCES "areas" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "user_area_access_granted_by_foreign" FOREIGN KEY ("granted_by") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE NO ACTION,
	CONSTRAINT "user_area_access_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

-- Dumping structure for table public.user_permissions
CREATE TABLE IF NOT EXISTS "user_permissions" (
	"id" BIGINT NOT NULL DEFAULT 'nextval(''user_permissions_id_seq''::regclass)',
	"user_id" BIGINT NOT NULL,
	"permission_id" BIGINT NOT NULL,
	"granted_at" TIMESTAMP NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
	"granted_by" BIGINT NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE INDEX "uq_user_permission" ("user_id", "permission_id"),
	INDEX "idx_user_permissions_user_id" ("user_id"),
	CONSTRAINT "user_permissions_granted_by_foreign" FOREIGN KEY ("granted_by") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE NO ACTION,
	CONSTRAINT "user_permissions_permission_id_foreign" FOREIGN KEY ("permission_id") REFERENCES "permissions" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "user_permissions_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
