-- Add composite indexes for Study Materials module performance optimization
-- Migration: 2026_03_06_add_study_materials_indexes.sql

-- Composite index for common filter combinations
-- Used in: materials list with status + deleted_at filters
ALTER TABLE study_materials 
ADD INDEX idx_sm_tenant_status_deleted (tenant_id, status, deleted_at);

-- Composite index for published_at scheduling queries
-- Used in: scheduled publishing, showing only published materials
ALTER TABLE study_materials 
ADD INDEX idx_sm_published_scheduling (tenant_id, status, published_at, expires_at);

-- Composite index for access control queries
-- Used in: student material list filtering by batch/student permissions
ALTER TABLE study_materials 
ADD INDEX idx_sm_access_control (tenant_id, access_type, batch_id, status, deleted_at);

-- Index for access logs - frequently queried by tenant and date
ALTER TABLE study_material_access_logs 
ADD INDEX idx_smal_tenant_created (tenant_id, created_at DESC);

-- Index for access logs - material access tracking
ALTER TABLE study_material_access_logs 
ADD INDEX idx_smal_material_user (material_id, user_id, action);

-- Index for favorites - student material lookups
ALTER TABLE study_material_favorites 
ADD INDEX idx_smf_student_tenant (student_id, tenant_id);

-- Index for feedback - material rating queries
ALTER TABLE study_material_feedback 
ADD INDEX idx_smfb_material_rating (material_id, rating);

-- Index for permissions - entity-based lookups
ALTER TABLE study_material_permissions 
ADD INDEX idx_smp_entity_lookup (tenant_id, entity_type, entity_id);

-- Fulltext index already exists, but let's add index for tags JSON search
-- Note: JSON indexes in MySQL require specific column indexing

-- Composite index for categories with tenant filter
ALTER TABLE study_material_categories 
ADD INDEX idx_smc_tenant_status (tenant_id, status, deleted_at);
